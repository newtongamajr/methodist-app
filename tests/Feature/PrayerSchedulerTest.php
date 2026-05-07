<?php

use App\Enums\LocationMode;
use App\Models\Church;
use App\Models\PrayerCampaign;
use App\Models\PrayerSchedule;
use App\Models\PrayerSignup;
use App\Models\PrayerSlot;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('generates 1-hour slots from start to end', function () {
    $church = Church::factory()->create();
    $schedule = PrayerSchedule::factory()->create([
        'church_id' => $church->id,
        'date' => '2026-06-01',
        'start_time' => '06:00:00',
        'end_time' => '12:00:00',
        'slot_minutes' => 60,
        'capacity_per_slot' => 4,
        'mode' => LocationMode::Presential->value,
    ]);

    $schedule->regenerateSlots();

    expect($schedule->slots()->count())->toBe(6);
    expect($schedule->slots()->first()->starts_at->format('H:i'))->toBe('06:00');
    expect($schedule->slots()->first()->capacity)->toBe(4);
});

it('regenerates slots without dropping ones with signups', function () {
    $church = Church::factory()->create();
    $schedule = PrayerSchedule::factory()->create([
        'church_id' => $church->id,
        'date' => '2026-06-01',
        'start_time' => '06:00:00',
        'end_time' => '08:00:00',
        'slot_minutes' => 60,
    ]);
    $schedule->regenerateSlots();

    $firstSlot = $schedule->slots()->orderBy('starts_at')->first();
    PrayerSignup::create([
        'prayer_slot_id' => $firstSlot->id,
        'user_id' => User::factory()->create()->id,
        'status' => 'confirmed',
    ]);

    $schedule->update(['end_time' => '07:00:00']);
    $schedule->regenerateSlots();

    expect(PrayerSlot::where('id', $firstSlot->id)->exists())->toBeTrue();
    expect($schedule->slots()->count())->toBe(1);
});

it('lets a user join and leave a slot for their church', function () {
    $church = Church::factory()->create();
    $user = User::factory()->create(['church_id' => $church->id]);
    $schedule = PrayerSchedule::factory()->create([
        'church_id' => $church->id,
        'date' => now()->addDay()->toDateString(),
        'start_time' => '06:00:00',
        'end_time' => '07:00:00',
        'slot_minutes' => 60,
        'capacity_per_slot' => 5,
    ]);
    $schedule->regenerateSlots();
    $slot = $schedule->slots()->first();

    $this->actingAs($user);

    Livewire::test('prayer.index')->call('join', $slot->id);
    expect($slot->confirmedSignups()->count())->toBe(1);

    Livewire::test('prayer.index')->call('leave', $slot->id);
    expect($slot->confirmedSignups()->count())->toBe(0);
});

it('refuses to overflow capacity', function () {
    $church = Church::factory()->create();
    $schedule = PrayerSchedule::factory()->create([
        'church_id' => $church->id,
        'date' => now()->addDay()->toDateString(),
        'start_time' => '06:00:00',
        'end_time' => '07:00:00',
        'slot_minutes' => 60,
        'capacity_per_slot' => 1,
    ]);
    $schedule->regenerateSlots();
    $slot = $schedule->slots()->first();

    $first = User::factory()->create(['church_id' => $church->id]);
    $second = User::factory()->create(['church_id' => $church->id]);

    PrayerSignup::create([
        'prayer_slot_id' => $slot->id,
        'user_id' => $first->id,
        'status' => 'confirmed',
    ]);

    $this->actingAs($second);
    Livewire::test('prayer.index')->call('join', $slot->id)->assertHasErrors('slot');

    expect($slot->confirmedSignups()->count())->toBe(1);
});

it('blocks signing up to another church\'s slot', function () {
    $a = Church::factory()->create();
    $b = Church::factory()->create();
    $schedule = PrayerSchedule::factory()->create([
        'church_id' => $a->id,
        'date' => now()->addDay()->toDateString(),
        'start_time' => '06:00:00',
        'end_time' => '07:00:00',
        'slot_minutes' => 60,
    ]);
    $schedule->regenerateSlots();
    $slot = $schedule->slots()->first();

    $userInB = User::factory()->create(['church_id' => $b->id]);
    $this->actingAs($userInB);

    try {
        Livewire::test('prayer.index')->call('join', $slot->id);
    } catch (Throwable) {
        // Either Livewire surfaces 403 directly or wraps it; we just want to
        // confirm the signup never landed in the database.
    }

    expect(PrayerSignup::where('prayer_slot_id', $slot->id)->where('user_id', $userInB->id)->exists())
        ->toBeFalse();
});

it('navigates between days via previousDay / nextDay', function () {
    $church = Church::factory()->create();
    $campaign = PrayerCampaign::factory()->range(
        now()->subDay()->toDateString(),
        now()->addDays(10)->toDateString(),
    )->create();

    foreach (range(1, 3) as $offset) {
        $schedule = PrayerSchedule::factory()->create([
            'church_id' => $church->id,
            'prayer_campaign_id' => $campaign->id,
            'date' => now()->addDays($offset)->toDateString(),
            'start_time' => '06:00:00',
            'end_time' => '07:00:00',
            'slot_minutes' => 60,
        ]);
        $schedule->regenerateSlots();
    }

    $user = User::factory()->create(['church_id' => $church->id]);
    $user->churches()->syncWithoutDetaching([$church->id => ['is_primary' => true]]);
    $this->actingAs($user);

    $cmp = Livewire::test('prayer.index')->set('campaignId', $campaign->id);
    $first = $cmp->get('selectedDate');

    $cmp->call('nextDay');
    $second = $cmp->get('selectedDate');
    expect($second)->not->toBe($first);

    $cmp->call('previousDay');
    expect($cmp->get('selectedDate'))->toBe($first);
});

it('day calendar lists confirmed users for each slot', function () {
    $church = Church::factory()->create();
    $schedule = PrayerSchedule::factory()->create([
        'church_id' => $church->id,
        'date' => now()->addDay()->toDateString(),
        'start_time' => '06:00:00',
        'end_time' => '08:00:00',
        'slot_minutes' => 60,
        'capacity_per_slot' => 5,
    ]);
    $schedule->regenerateSlots();
    $slot = $schedule->slots()->orderBy('starts_at')->first();

    $maria = User::factory()->create(['name' => 'Maria Demo', 'church_id' => $church->id]);
    PrayerSignup::create([
        'prayer_slot_id' => $slot->id,
        'user_id' => $maria->id,
        'status' => 'confirmed',
    ]);

    $viewer = User::factory()->create(['church_id' => $church->id]);
    $this->actingAs($viewer);

    Livewire::test('prayer.index')
        ->set('selectedDate', $schedule->date->toDateString())
        ->assertSee('Maria Demo');
});

it('user filter lists only members who have at least one confirmed slot in this church', function () {
    $church = Church::factory()->create();

    $schedule = PrayerSchedule::factory()->create([
        'church_id' => $church->id,
        'date' => now()->addDay()->toDateString(),
        'start_time' => '06:00:00',
        'end_time' => '08:00:00',
        'slot_minutes' => 60,
    ]);
    $schedule->regenerateSlots();
    $slot = $schedule->slots()->first();

    // Has a confirmed signup → should appear.
    $alice = User::factory()->create(['name' => 'Alice Praying']);
    PrayerSignup::create([
        'prayer_slot_id' => $slot->id,
        'user_id' => $alice->id,
        'status' => 'confirmed',
    ]);

    // Attached to the church but no slot → should NOT appear.
    $bob = User::factory()->create(['name' => 'Bob Idle']);
    $bob->churches()->syncWithoutDetaching([$church->id => ['is_primary' => true]]);

    // No relation at all → should NOT appear.
    User::factory()->create(['name' => 'Carol Outsider']);

    $viewer = User::factory()->create(['church_id' => $church->id]);
    $this->actingAs($viewer);

    Livewire::test('prayer.index')
        ->set('coverageFilter', 'user')
        ->assertSee('Alice Praying')
        ->assertDontSee('Bob Idle')
        ->assertDontSee('Carol Outsider');
});

it('forbids non-managers from the schedule editor', function () {
    $user = User::factory()->create();
    $user->assignRole('user');
    $this->actingAs($user);

    $this->get(route('admin.prayer-schedules.index'))->assertForbidden();
});

<?php

use App\Models\Church;
use App\Models\PrayerSchedule;
use App\Models\PrayerSignup;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function makeChurchWithSlot(): array
{
    $church = Church::factory()->create();
    $schedule = PrayerSchedule::factory()->create([
        'church_id' => $church->id,
        'date' => now()->addDay()->toDateString(),
        'start_time' => '06:00:00',
        'end_time' => '07:00:00',
        'slot_minutes' => 60,
        'capacity_per_slot' => 3,
    ]);
    $schedule->regenerateSlots();

    return [$church, $schedule->slots()->first()];
}

it('lets a local manager add another church member to a slot', function () {
    [$church, $slot] = makeChurchWithSlot();

    $manager = User::factory()->create(['church_id' => $church->id]);
    $manager->assignRole('local_manager');
    $manager->churches()->syncWithoutDetaching([$church->id => ['is_primary' => true]]);

    $member = User::factory()->create(['church_id' => $church->id]);
    $member->churches()->syncWithoutDetaching([$church->id => ['is_primary' => true]]);

    $this->actingAs($manager);

    Livewire::test('prayer.index')
        ->set('assignChoice.'.$slot->id, $member->id)
        ->call('addAssigned', $slot->id)
        ->assertHasNoErrors();

    expect(PrayerSignup::where('prayer_slot_id', $slot->id)->where('user_id', $member->id)->exists())
        ->toBeTrue();
});

it('refuses to assign a non-member to a slot', function () {
    [$church, $slot] = makeChurchWithSlot();

    $manager = User::factory()->create(['church_id' => $church->id]);
    $manager->assignRole('local_manager');
    $manager->churches()->syncWithoutDetaching([$church->id => ['is_primary' => true]]);

    $stranger = User::factory()->create(); // not attached to church

    $this->actingAs($manager);

    Livewire::test('prayer.index')
        ->set('assignChoice.'.$slot->id, $stranger->id)
        ->call('addAssigned', $slot->id)
        ->assertHasErrors('slot');

    expect(PrayerSignup::where('prayer_slot_id', $slot->id)->where('user_id', $stranger->id)->exists())
        ->toBeFalse();
});

it('lets an admin remove someone else\'s signup', function () {
    [$church, $slot] = makeChurchWithSlot();

    $manager = User::factory()->create(['church_id' => $church->id]);
    $manager->assignRole('local_manager');
    $manager->churches()->syncWithoutDetaching([$church->id => ['is_primary' => true]]);

    $member = User::factory()->create(['church_id' => $church->id]);
    $member->churches()->syncWithoutDetaching([$church->id => ['is_primary' => true]]);

    $signup = PrayerSignup::create([
        'prayer_slot_id' => $slot->id,
        'user_id' => $member->id,
        'status' => 'confirmed',
    ]);

    $this->actingAs($manager);

    Livewire::test('prayer.index')
        ->call('removeSignup', $signup->id)
        ->assertHasNoErrors();

    expect(PrayerSignup::find($signup->id))->toBeNull();
});

it('blocks a regular user from assigning others', function () {
    [$church, $slot] = makeChurchWithSlot();

    $regular = User::factory()->create(['church_id' => $church->id]);
    $regular->assignRole('user');
    $regular->churches()->syncWithoutDetaching([$church->id => ['is_primary' => true]]);

    $other = User::factory()->create(['church_id' => $church->id]);
    $other->churches()->syncWithoutDetaching([$church->id => ['is_primary' => true]]);

    $this->actingAs($regular);

    try {
        Livewire::test('prayer.index')
            ->set('assignChoice.'.$slot->id, $other->id)
            ->call('addAssigned', $slot->id);
    } catch (Throwable) {
        // Livewire wraps the abort_unless in a view exception; either way we
        // confirm the signup never landed.
    }

    expect(PrayerSignup::where('prayer_slot_id', $slot->id)->where('user_id', $other->id)->exists())
        ->toBeFalse();
});

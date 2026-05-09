<?php

use App\Models\Church;
use App\Models\PrayerCampaign;
use App\Models\PrayerSchedule;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('lets a global manager create a prayer campaign', function () {
    $super = User::factory()->create();
    $super->assignRole('national_admin');
    $this->actingAs($super);

    Livewire::test('admin.prayer-campaigns.editor')
        ->set('form.name', 'Mai 2026 Oração')
        ->set('form.description', 'Three weeks of intercession')
        ->set('form.objectives', "Cover every hour with prayer.\nRevival across the regions.")
        ->set('form.start_date', '2026-05-04')
        ->set('form.end_date', '2026-05-24')
        ->set('form.is_active', true)
        ->call('save')
        ->assertHasNoErrors();

    $created = PrayerCampaign::firstWhere('name', 'Mai 2026 Oração');
    expect($created)->not->toBeNull();
    expect($created->slug)->toBe('mai-2026-oracao');
    expect($created->objectives)->toContain('Revival');
});

it('rejects a campaign with end before start', function () {
    $super = User::factory()->create();
    $super->assignRole('national_admin');
    $this->actingAs($super);

    Livewire::test('admin.prayer-campaigns.editor')
        ->set('form.name', 'Backwards')
        ->set('form.start_date', '2026-05-10')
        ->set('form.end_date', '2026-05-01')
        ->call('save')
        ->assertHasErrors('form.end_date');
});

it('blocks regular users from prayer campaign CRUD', function () {
    $regular = User::factory()->create();
    $regular->assignRole('user');
    $this->actingAs($regular)
        ->get(route('admin.prayer-campaigns.index'))
        ->assertForbidden();
});

it('refuses a schedule whose date is outside the campaign window', function () {
    $church = Church::factory()->create();
    $manager = User::factory()->create();
    $manager->assignRole('national_admin');
    $this->actingAs($manager);

    $campaign = PrayerCampaign::factory()->range('2026-05-04', '2026-05-24')->create();

    Livewire::test('admin.prayer-schedules.editor')
        ->set('form.church_id', $church->id)
        ->set('form.prayer_campaign_id', $campaign->id)
        ->set('form.date', '2026-04-15') // outside window
        ->set('form.start_time', '06:00')
        ->set('form.end_time', '07:00')
        ->set('form.slot_minutes', 60)
        ->set('form.capacity_per_slot', 5)
        ->set('form.mode', 'presential')
        ->call('save')
        ->assertHasErrors('form.date');

    expect(PrayerSchedule::query()->count())->toBe(0);
});

it('propagates campaign id to generated slots', function () {
    $church = Church::factory()->create();
    $campaign = PrayerCampaign::factory()->range(
        now()->subDay()->toDateString(),
        now()->addDays(10)->toDateString(),
    )->create();

    $schedule = PrayerSchedule::factory()->create([
        'church_id' => $church->id,
        'prayer_campaign_id' => $campaign->id,
        'date' => now()->addDay()->toDateString(),
        'start_time' => '06:00:00',
        'end_time' => '08:00:00',
        'slot_minutes' => 60,
    ]);
    $schedule->regenerateSlots();

    expect($schedule->slots()->count())->toBe(2);
    foreach ($schedule->slots as $slot) {
        expect($slot->prayer_campaign_id)->toBe($campaign->id);
    }
});

it('only lists campaigns whose window overlaps the current month on /prayer', function () {
    $monthStart = now()->startOfMonth();
    $monthEnd = now()->endOfMonth();

    PrayerCampaign::factory()->range(
        $monthStart->copy()->addDay()->toDateString(),
        $monthStart->copy()->addDays(10)->toDateString(),
    )->create(['name' => 'Visible-Now']);

    PrayerCampaign::factory()->range(
        $monthEnd->copy()->addMonths(2)->toDateString(),
        $monthEnd->copy()->addMonths(2)->addDays(10)->toDateString(),
    )->create(['name' => 'Future-Only']);

    $church = Church::factory()->create();
    $user = User::factory()->forChurch($church)->create();
    $user->churches()->syncWithoutDetaching([$church->id => ['is_primary' => true]]);
    $this->actingAs($user);

    Livewire::test('prayer.index')
        ->assertSee('Visible-Now')
        ->assertDontSee('Future-Only');
});

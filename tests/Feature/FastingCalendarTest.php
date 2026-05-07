<?php

use App\Enums\FastingRestriction;
use App\Enums\FastingType;
use App\Models\FastingCampaign;
use App\Models\FastingEntry;
use App\Models\User;
use Livewire\Livewire;

function makeCampaign(?string $start = null, ?string $end = null): FastingCampaign
{
    $start ??= now()->subDays(3)->toDateString();
    $end ??= now()->addDays(20)->toDateString();

    return FastingCampaign::factory()->range($start, $end)->create();
}

it('renders the fasting calendar for an active campaign', function () {
    makeCampaign();
    $user = User::factory()->create();
    $this->actingAs($user)
        ->get(route('fasting.index'))
        ->assertOk();
});

it('shows a friendly message when there is no active campaign', function () {
    FastingCampaign::factory()->inactive()->create();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['locale' => 'en'])
        ->get(route('fasting.index'))
        ->assertOk()
        ->assertSee('There is no active fasting campaign right now.');
});

it('upserts a fasting entry tied to the active campaign', function () {
    $campaign = makeCampaign();
    $user = User::factory()->create();
    $this->actingAs($user);

    $date = $campaign->start_date->copy()->addDays(2)->toDateString();

    Livewire::test('fasting.index')
        ->call('openDay', $date)
        ->set('type', FastingType::TwentyFourHours->value)
        ->set('restrictions', [FastingRestriction::Coffee->value, FastingRestriction::SocialMedia->value])
        ->set('notes', 'For the campaign')
        ->call('save')
        ->assertHasNoErrors();

    $entry = FastingEntry::where('user_id', $user->id)->where('date', $date)->first();

    expect($entry)->not->toBeNull();
    expect($entry->fasting_campaign_id)->toBe($campaign->id);
    expect($entry->type)->toBe(FastingType::TwentyFourHours);
    expect($entry->restrictions)->toEqualCanonicalizing(['coffee', 'social_media']);
    expect($entry->notes)->toBe('For the campaign');
});

it('refuses an entry on a date outside the campaign window', function () {
    $campaign = makeCampaign();
    $user = User::factory()->create();
    $this->actingAs($user);

    $outside = $campaign->end_date->copy()->addDays(5)->toDateString();

    Livewire::test('fasting.index')
        ->set('editingDate', $outside) // forced via property; the UI also disables it
        ->set('type', FastingType::Lunch->value)
        ->call('save');

    expect(FastingEntry::where('user_id', $user->id)->where('date', $outside)->exists())->toBeFalse();
});

it('refuses a fasting type that is not allowed in the campaign', function () {
    $campaign = FastingCampaign::factory()->create([
        'types' => [FastingType::TwelveHours->value], // only the 12-hour fast is allowed
        'restrictions' => [FastingRestriction::Coffee->value],
        'is_active' => true,
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => now()->addDays(10)->toDateString(),
    ]);
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('fasting.index')
        ->call('openDay', $campaign->start_date->copy()->addDay()->toDateString())
        ->set('type', FastingType::TwentyFourHours->value) // not in allowed list
        ->call('save')
        ->assertHasErrors('type');
});

it('only lists campaigns whose window overlaps the current month', function () {
    $monthStart = now()->startOfMonth();
    $monthEnd = now()->endOfMonth();

    $overlapping = FastingCampaign::factory()->range(
        $monthStart->copy()->addDays(2)->toDateString(),
        $monthStart->copy()->addDays(15)->toDateString(),
    )->create(['name' => 'Inside-Month']);

    $straddlingStart = FastingCampaign::factory()->range(
        $monthStart->copy()->subDays(5)->toDateString(),
        $monthStart->copy()->addDay()->toDateString(),
    )->create(['name' => 'Straddles-Start']);

    $straddlingEnd = FastingCampaign::factory()->range(
        $monthEnd->copy()->subDay()->toDateString(),
        $monthEnd->copy()->addDays(7)->toDateString(),
    )->create(['name' => 'Straddles-End']);

    FastingCampaign::factory()->range(
        $monthStart->copy()->subMonths(2)->toDateString(),
        $monthStart->copy()->subMonths(2)->addDays(10)->toDateString(),
    )->create(['name' => 'Past-Month']);

    FastingCampaign::factory()->range(
        $monthEnd->copy()->addMonths(2)->toDateString(),
        $monthEnd->copy()->addMonths(2)->addDays(10)->toDateString(),
    )->create(['name' => 'Future-Month']);

    FastingCampaign::factory()->inactive()->range(
        $monthStart->copy()->addDay()->toDateString(),
        $monthStart->copy()->addDays(5)->toDateString(),
    )->create(['name' => 'Inside-But-Inactive']);

    $user = User::factory()->create();
    $this->actingAs($user);

    $cmp = Livewire::test('fasting.index')
        ->assertSee('Inside-Month')
        ->assertSee('Straddles-Start')
        ->assertSee('Straddles-End')
        ->assertDontSee('Past-Month')
        ->assertDontSee('Future-Month')
        ->assertDontSee('Inside-But-Inactive');
});

it('removes a fasting entry from the active campaign only', function () {
    $campaign = makeCampaign();
    $user = User::factory()->create();
    $entry = FastingEntry::factory()->create([
        'user_id' => $user->id,
        'fasting_campaign_id' => $campaign->id,
        'date' => $campaign->start_date->copy()->addDay()->toDateString(),
    ]);

    $this->actingAs($user);

    Livewire::test('fasting.index')
        ->call('delete', $entry->date->toDateString());

    expect(FastingEntry::find($entry->id))->toBeNull();
});

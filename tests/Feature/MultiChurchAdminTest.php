<?php

use App\Models\Church;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\PrayerCampaign;
use App\Models\PrayerSchedule;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function attach(User $user, Church $church, bool $primary = false): void
{
    $user->churches()->syncWithoutDetaching([$church->id => ['is_primary' => $primary]]);
}

it('reports manageableChurches for a multi-church local manager', function () {
    $a = Church::factory()->create();
    $b = Church::factory()->create();
    $manager = User::factory()->create();
    $manager->assignRole('local_admin');
    attach($manager, $a, true);
    attach($manager, $b);

    expect($manager->manageableChurchIds())->toEqualCanonicalizing([$a->id, $b->id]);
    expect($manager->canManageChurch($a->id))->toBeTrue();
    expect($manager->canManageChurch($b->id))->toBeTrue();
});

it('listing of admins includes admins from any of the manager\'s churches', function () {
    $a = Church::factory()->create();
    $b = Church::factory()->create();
    $manager = User::factory()->create();
    $manager->assignRole('local_admin');
    attach($manager, $a, true);
    attach($manager, $b);

    $adminA = User::factory()->create(['name' => 'Admin A']);
    $adminA->assignRole('local_admin');
    attach($adminA, $a, true);

    $adminB = User::factory()->create(['name' => 'Admin B']);
    $adminB->assignRole('local_admin');
    attach($adminB, $b, true);

    $stranger = User::factory()->create(['name' => 'Stranger Else']);
    $stranger->assignRole('local_admin');
    attach($stranger, Church::factory()->create(), true);

    $this->actingAs($manager)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee('Admin A')
        ->assertSee('Admin B')
        ->assertDontSee('Stranger Else');
});

it('master attaching new admin can pick from their pool but not foreign churches', function () {
    // Church associations live on /admin/users/{id}/churches now. The editor
    // creates the user; the churches page handles the pivot. This test runs
    // the editor → then the churches page, attempting to attach a foreign
    // church (which the page strips back to the master's pool).
    $a = Church::factory()->create();
    $b = Church::factory()->create();
    $foreign = Church::factory()->create();
    $manager = User::factory()->create();
    $manager->assignRole('local_admin');
    attach($manager, $a, true);
    attach($manager, $b);

    $this->actingAs($manager);

    Livewire::test('admin.users.editor')
        ->set('form.name', 'New Helper')
        ->set('form.email', 'helper@m.test')
        ->set('form.password', 'secret-password')
        ->set('form.password_confirmation', 'secret-password')
        ->set('form.role', 'local_admin')
        ->set('form.locale', 'pt_BR')
        ->set('form.appearance', 'system')
        ->call('save')
        ->assertHasNoErrors();

    $helper = User::firstWhere('email', 'helper@m.test');

    // The new churches page attaches one at a time via a searchable
    // listbox; foreign churches don't appear in the selectable list.
    $component = Livewire::test('admin.users.churches', ['userId' => $helper->id]);

    // a + b are in the manager's pool → selectable; foreign isn't.
    $selectableIds = $component->instance()->selectableChurches->pluck('id')->all();
    expect($selectableIds)->toContain($a->id, $b->id);
    expect($selectableIds)->not->toContain($foreign->id);

    $component
        ->set('selectedChurchId', $a->id)
        ->call('attach')
        ->set('selectedChurchId', $b->id)
        ->call('attach');

    expect($helper->fresh()->churches->pluck('id')->all())
        ->toEqualCanonicalizing([$a->id, $b->id]);
    // First attached is the primary by default.
    expect($helper->fresh()->person->managing_church_id)->toBe($a->id);
});

it('post editor list of available churches respects manager scope', function () {
    $a = Church::factory()->create();
    $b = Church::factory()->create();
    $foreign = Church::factory()->create(['name' => 'Foreign Church']);
    $manager = User::factory()->create();
    $manager->assignRole('local_admin');
    attach($manager, $a, true);
    attach($manager, $b);

    $this->actingAs($manager);

    Livewire::test('admin.posts.editor')
        ->assertSet('form.scope', 'local')
        ->assertDontSee('Foreign Church');
});

it('local schedule editor scopes the church dropdown to manageable churches', function () {
    $a = Church::factory()->create();
    $b = Church::factory()->create();
    $manager = User::factory()->create();
    $manager->assignRole('local_admin');
    attach($manager, $a, true);
    attach($manager, $b);

    $campaign = PrayerCampaign::factory()->range(
        now()->subDay()->toDateString(),
        now()->addDays(10)->toDateString(),
    )->create();

    $this->actingAs($manager);

    // Create-mode now picks one or more dates via the multi-date pillbox
    // (`form.dates`); the editor fans out one PrayerSchedule per date.
    Livewire::test('admin.prayer-schedules.editor')
        ->set('form.church_id', $b->id)
        ->set('form.prayer_campaign_id', $campaign->id)
        ->set('form.dates', [now()->addDay()->toDateString()])
        ->set('form.start_time', '06:00')
        ->set('form.end_time', '07:00')
        ->set('form.slot_minutes', 60)
        ->set('form.capacity_per_slot', 5)
        ->set('form.mode', 'presential')
        ->call('save')
        ->assertHasNoErrors();

    expect(PrayerSchedule::where('church_id', $b->id)->exists())->toBeTrue();
});

it('comments queue shows comments from any of the manager\'s churches', function () {
    $a = Church::factory()->create();
    $b = Church::factory()->create();
    $foreign = Church::factory()->create();

    $postA = Post::factory()->local($a)->published()->create();
    $postB = Post::factory()->local($b)->published()->create();
    $postForeign = Post::factory()->local($foreign)->published()->create();

    PostComment::factory()->for($postA)->create(['body' => 'Comment-A']);
    PostComment::factory()->for($postB)->create(['body' => 'Comment-B']);
    PostComment::factory()->for($postForeign)->create(['body' => 'Comment-Foreign']);

    $manager = User::factory()->create();
    $manager->assignRole('local_admin');
    attach($manager, $a, true);
    attach($manager, $b);

    $this->actingAs($manager)
        ->get(route('admin.comments.index'))
        ->assertOk()
        ->assertSee('Comment-A')
        ->assertSee('Comment-B')
        ->assertDontSee('Comment-Foreign');
});

it('church context switcher persists the active church in session', function () {
    $a = Church::factory()->create();
    $b = Church::factory()->create();
    $manager = User::factory()->create();
    $manager->assignRole('local_admin');
    attach($manager, $a, true);
    attach($manager, $b);

    $this->actingAs($manager);

    Livewire::test('church-context-switcher')
        ->call('switchTo', $b->id);

    expect(session('admin_church_id'))->toBe($b->id);
});

it('shows the attached church to a regular member as a read-only badge', function () {
    $church = Church::factory()->create(['name' => 'Igreja Demo']);
    $member = User::factory()->create();
    $member->assignRole('user');
    attach($member, $church, true);

    $this->actingAs($member);

    Livewire::test('church-context-switcher')
        ->assertSee('Igreja Demo');
});

it('refuses a regular member trying to set a church they are not attached to', function () {
    $own = Church::factory()->create();
    $other = Church::factory()->create();
    $member = User::factory()->create();
    $member->assignRole('user');
    attach($member, $own, true);

    $this->actingAs($member);

    Livewire::test('church-context-switcher')
        ->call('switchTo', $other->id);

    expect(session('admin_church_id'))->not->toBe($other->id);
});

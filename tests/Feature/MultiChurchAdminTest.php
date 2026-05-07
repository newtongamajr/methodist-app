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
    $manager = User::factory()->create(['church_id' => $a->id]);
    $manager->assignRole('local_manager');
    attach($manager, $a, true);
    attach($manager, $b);

    expect($manager->manageableChurchIds())->toEqualCanonicalizing([$a->id, $b->id]);
    expect($manager->canManageChurch($a->id))->toBeTrue();
    expect($manager->canManageChurch($b->id))->toBeTrue();
});

it('listing of admins includes admins from any of the manager\'s churches', function () {
    $a = Church::factory()->create();
    $b = Church::factory()->create();
    $manager = User::factory()->create(['church_id' => $a->id]);
    $manager->assignRole('local_manager');
    attach($manager, $a, true);
    attach($manager, $b);

    $adminA = User::factory()->create(['name' => 'Admin A']);
    $adminA->assignRole('local_manager');
    attach($adminA, $a, true);

    $adminB = User::factory()->create(['name' => 'Admin B']);
    $adminB->assignRole('local_manager');
    attach($adminB, $b, true);

    $stranger = User::factory()->create(['name' => 'Stranger Else']);
    $stranger->assignRole('local_manager');
    attach($stranger, Church::factory()->create(), true);

    $this->actingAs($manager)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee('Admin A')
        ->assertSee('Admin B')
        ->assertDontSee('Stranger Else');
});

it('master attaching new admin can pick from their pool but not foreign churches', function () {
    $a = Church::factory()->create();
    $b = Church::factory()->create();
    $foreign = Church::factory()->create();
    $manager = User::factory()->create(['church_id' => $a->id]);
    $manager->assignRole('local_manager');
    attach($manager, $a, true);
    attach($manager, $b);

    $this->actingAs($manager);

    Livewire::test('admin.users.editor')
        ->set('name', 'New Helper')
        ->set('email', 'helper@m.test')
        ->set('password', 'secret-password')
        ->set('church_ids', [$a->id, $b->id, $foreign->id])
        ->set('primary_church_id', $a->id)
        ->set('role', 'local_manager')
        ->set('locale', 'pt_BR')
        ->call('save')
        ->assertHasNoErrors();

    $helper = User::firstWhere('email', 'helper@m.test');
    expect($helper->churches->pluck('id')->all())
        ->toEqualCanonicalizing([$a->id, $b->id]);
    expect($helper->church_id)->toBe($a->id);
});

it('post editor list of available churches respects manager scope', function () {
    $a = Church::factory()->create();
    $b = Church::factory()->create();
    $foreign = Church::factory()->create(['name' => 'Foreign Church']);
    $manager = User::factory()->create();
    $manager->assignRole('local_manager');
    attach($manager, $a, true);
    attach($manager, $b);

    $this->actingAs($manager);

    Livewire::test('admin.posts.editor')
        ->assertSet('scope', 'local')
        ->assertDontSee('Foreign Church');
});

it('local schedule editor scopes the church dropdown to manageable churches', function () {
    $a = Church::factory()->create();
    $b = Church::factory()->create();
    $manager = User::factory()->create();
    $manager->assignRole('local_manager');
    attach($manager, $a, true);
    attach($manager, $b);

    $campaign = PrayerCampaign::factory()->range(
        now()->subDay()->toDateString(),
        now()->addDays(10)->toDateString(),
    )->create();

    $this->actingAs($manager);

    Livewire::test('admin.prayer-schedules.editor')
        ->set('church_id', $b->id)
        ->set('prayer_campaign_id', $campaign->id)
        ->set('date', now()->addDay()->toDateString())
        ->set('start_time', '06:00')
        ->set('end_time', '07:00')
        ->set('slot_minutes', 60)
        ->set('capacity_per_slot', 5)
        ->set('mode', 'presential')
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
    $manager->assignRole('local_manager');
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
    $manager = User::factory()->create(['church_id' => $a->id]);
    $manager->assignRole('local_manager');
    attach($manager, $a, true);
    attach($manager, $b);

    $this->actingAs($manager);

    Livewire::test('church-context-switcher')
        ->call('switchTo', $b->id);

    expect(session('admin_church_id'))->toBe($b->id);
});

it('shows the attached church to a regular member as a read-only badge', function () {
    $church = Church::factory()->create(['name' => 'Igreja Demo']);
    $member = User::factory()->create(['church_id' => $church->id]);
    $member->assignRole('user');
    attach($member, $church, true);

    $this->actingAs($member);

    Livewire::test('church-context-switcher')
        ->assertSee('Igreja Demo');
});

it('refuses a regular member trying to set a church they are not attached to', function () {
    $own = Church::factory()->create();
    $other = Church::factory()->create();
    $member = User::factory()->create(['church_id' => $own->id]);
    $member->assignRole('user');
    attach($member, $own, true);

    $this->actingAs($member);

    Livewire::test('church-context-switcher')
        ->call('switchTo', $other->id);

    expect(session('admin_church_id'))->not->toBe($other->id);
});

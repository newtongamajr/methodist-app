<?php

namespace Tests\Feature\Auth;

use App\Models\Church;
use App\Models\EcclesiasticalRegion;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $this->get('/register')->assertOk();
    }

    public function test_new_users_can_register_with_required_fields(): void
    {
        Livewire::test('auth.register')
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->set('nature', 'member')
            ->set('locale', 'pt_BR')
            ->call('register')
            ->assertRedirect(route('posts.index', absolute: false));

        $this->assertAuthenticated();

        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('user'));
        $this->assertSame(['member'], $user->person->natures);
        $this->assertSame('pt_BR', $user->locale);
    }

    public function test_new_users_can_register_with_a_church(): void
    {
        $region = EcclesiasticalRegion::factory()->create();
        $church = Church::factory()->create(['ecclesiastical_region_id' => $region->id]);

        Livewire::test('auth.register')
            ->set('name', 'Maria')
            ->set('email', 'maria@example.com')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->set('nature', 'interested')
            ->set('region_id', $region->id)
            ->set('church_id', $church->id)
            ->set('locale', 'es')
            ->set('phone', '(11) 99999-0000')
            ->set('birthdate', '1990-01-15')
            ->call('register')
            ->assertRedirect(route('posts.index', absolute: false));

        $user = User::where('email', 'maria@example.com')->first();
        $this->assertSame($church->id, $user->person->managing_church_id);
        $this->assertTrue($user->churches->contains($church));
        $this->assertSame(['interested'], $user->person->natures);
        // Register creates a Mobile contact whose stored value is already
        // prefixed with the country calling code (default BR / +55).
        $this->assertSame('+55 (11) 99999-0000', $user->person->contacts()->where('type', 'mobile')->value('value'));
    }
}

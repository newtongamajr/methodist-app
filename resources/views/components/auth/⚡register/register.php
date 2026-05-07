<?php

use App\Enums\AppLocale;
use App\Enums\MemberType;
use App\Models\Church;
use App\Models\EcclesiasticalRegion;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('layouts.guest')]
class extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $member_type = '';
    public ?int $region_id = null;
    public ?int $church_id = null;
    public string $locale = '';
    public string $phone = '';
    public string $birthdate = '';

    public function mount(): void
    {
        $this->locale = App::getLocale();
        $this->member_type = MemberType::Member->value;
    }

    public function updatedRegionId(): void
    {
        $this->church_id = null;
    }

    public function getRegionsProperty()
    {
        return EcclesiasticalRegion::query()->orderBy('display_order')->get(['id', 'code', 'name']);
    }

    public function getChurchesProperty()
    {
        if (! $this->region_id) {
            return collect();
        }

        return Church::query()
            ->where('ecclesiastical_region_id', $this->region_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'city', 'state']);
    }

    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
            'member_type' => ['required', 'string', 'in:'.implode(',', array_map(fn ($c) => $c->value, MemberType::cases()))],
            'region_id' => ['nullable', 'integer', 'exists:ecclesiastical_regions,id'],
            'church_id' => ['nullable', 'integer', 'exists:churches,id'],
            'locale' => ['required', 'string', 'in:'.implode(',', AppLocale::values())],
            'phone' => ['nullable', 'string', 'max:32'],
            'birthdate' => ['nullable', 'date', 'before:today'],
        ]);

        $validated['password'] = Hash::make($validated['password']);
        unset($validated['region_id']);
        $validated['appearance'] = session('appearance', 'system');

        foreach (['phone', 'birthdate', 'church_id'] as $nullable) {
            if (($validated[$nullable] ?? null) === '') {
                $validated[$nullable] = null;
            }
        }

        $user = User::create($validated);
        $user->assignRole('user');

        if ($validated['church_id'] ?? null) {
            $user->churches()->syncWithoutDetaching([
                $validated['church_id'] => ['is_primary' => true],
            ]);
        }

        event(new Registered($user));

        Auth::login($user);

        $this->redirect(route('posts.index', absolute: false), navigate: true);
    }
};
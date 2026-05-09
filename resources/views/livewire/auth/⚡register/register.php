<?php

use App\Enums\AppLocale;
use App\Enums\PersonContactType;
use App\Enums\PersonNature;
use App\Enums\PersonType;
use App\Models\Church;
use App\Models\EcclesiasticalRegion;
use App\Models\Person;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Computed;
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

    public string $nature = '';

    public ?int $region_id = null;

    public ?int $church_id = null;

    public string $locale = '';

    public string $phone = '';

    public string $birthdate = '';

    public function mount(): void
    {
        $this->locale = App::getLocale();
        $this->nature = PersonNature::Member->value;
    }

    public function updatedRegionId(): void
    {
        $this->church_id = null;
    }

    #[Computed]
    public function regions(): Collection
    {
        return EcclesiasticalRegion::query()->orderBy('display_order')->get(['id', 'code', 'name']);
    }

    #[Computed]
    public function churches(): Collection
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
            'nature' => ['required', 'string', 'in:'.implode(',', array_map(fn ($c) => $c->value, PersonNature::cases()))],
            'region_id' => ['nullable', 'integer', 'exists:ecclesiastical_regions,id'],
            'church_id' => ['nullable', 'integer', 'exists:churches,id'],
            'locale' => ['required', 'string', 'in:'.implode(',', AppLocale::values())],
            'phone' => ['nullable', 'string', 'max:32'],
            'birthdate' => ['nullable', 'date', 'before:today'],
        ]);

        foreach (['phone', 'birthdate', 'church_id'] as $nullable) {
            if (($validated[$nullable] ?? null) === '') {
                $validated[$nullable] = null;
            }
        }

        $user = DB::transaction(function () use ($validated) {
            $person = Person::create([
                'person_type' => PersonType::Individual->value,
                'name' => $validated['name'],
                'birthdate' => $validated['birthdate'] ?? null,
                'natures' => [$validated['nature']],
                'managing_church_id' => $validated['church_id'] ?? null,
            ]);

            if (! empty($validated['phone'])) {
                $person->contacts()->create([
                    'type' => PersonContactType::Phone->value,
                    'value' => $validated['phone'],
                    'is_primary' => true,
                ]);
            }

            return User::create([
                'person_id' => $person->id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'locale' => $validated['locale'],
                'appearance' => session('appearance', 'system'),
            ]);
        });

        $user->assignRole('user');

        if (! empty($validated['church_id'])) {
            $user->churches()->syncWithoutDetaching([
                $validated['church_id'] => ['is_primary' => true],
            ]);
        }

        event(new Registered($user));

        Auth::login($user);

        $this->redirect(route('posts.index', absolute: false), navigate: true);
    }
};

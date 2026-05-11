<?php

use App\Enums\AppLocale;
use App\Enums\MaritalStatus;
use App\Enums\PersonContactType;
use App\Enums\PersonNature;
use App\Enums\PersonType;
use App\Models\Church;
use App\Models\District;
use App\Models\EcclesiasticalRegion;
use App\Models\Person;
use App\Models\User;
use App\Support\TaxIdValidator;
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

    public ?int $district_id = null;

    public ?int $church_id = null;

    public string $locale = '';

    public string $phone = '';

    public string $birthdate = '';

    public string $gender = '';

    public string $marital_status = '';

    /** Always treated as a CPF on this form — the type isn't shown to the user. */
    public string $tax_id = '';

    public function mount(): void
    {
        $this->locale = App::getLocale();
        $this->nature = PersonNature::Member->value;
    }

    public function updatedRegionId(): void
    {
        if ($this->district_id) {
            $district = District::find($this->district_id);
            if (! $district || $district->ecclesiastical_region_id !== $this->region_id) {
                $this->district_id = null;
            }
        }
        if ($this->church_id) {
            $church = Church::find($this->church_id);
            if (! $church || $church->ecclesiastical_region_id !== $this->region_id) {
                $this->church_id = null;
            }
        }
    }

    public function updatedDistrictId(): void
    {
        if ($this->church_id) {
            $church = Church::find($this->church_id);
            if (! $church || $church->district_id !== $this->district_id) {
                $this->church_id = null;
            }
        }
    }

    public function updatedChurchId(): void
    {
        if (! $this->church_id) {
            return;
        }
        $church = Church::find($this->church_id);
        if (! $church) {
            return;
        }
        $this->region_id = $church->ecclesiastical_region_id;
        $this->district_id = $church->district_id;
    }

    #[Computed]
    public function regions(): Collection
    {
        return EcclesiasticalRegion::query()->orderBy('display_order')->get(['id', 'code', 'name']);
    }

    #[Computed]
    public function districts(): Collection
    {
        if (! $this->region_id) {
            return collect();
        }

        return District::query()
            ->where('ecclesiastical_region_id', $this->region_id)
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    #[Computed]
    public function churches(): Collection
    {
        $q = Church::query()->where('is_active', true)->orderBy('name');

        if ($this->district_id) {
            $q->where('district_id', $this->district_id);
        } elseif ($this->region_id) {
            $q->where('ecclesiastical_region_id', $this->region_id);
        }

        return $q->get(['id', 'name', 'city', 'state', 'district_id', 'ecclesiastical_region_id']);
    }

    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
            'nature' => ['required', 'string', 'in:'.implode(',', array_map(fn ($c) => $c->value, PersonNature::cases()))],
            'region_id' => ['nullable', 'integer', 'exists:ecclesiastical_regions,id'],
            'district_id' => ['nullable', 'integer', 'exists:districts,id'],
            'church_id' => ['nullable', 'integer', 'exists:churches,id'],
            'locale' => ['required', 'string', 'in:'.implode(',', AppLocale::values())],
            'phone' => ['nullable', 'string', 'max:32'],
            'birthdate' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'string', 'in:female,male,other'],
            'marital_status' => ['nullable', 'string', 'in:'.implode(',', array_map(fn ($c) => $c->value, MaritalStatus::cases()))],
            'tax_id' => ['nullable', 'string', 'max:32'],
        ]);

        foreach (['phone', 'birthdate', 'church_id', 'gender', 'marital_status', 'tax_id'] as $nullable) {
            if (($validated[$nullable] ?? null) === '') {
                $validated[$nullable] = null;
            }
        }

        // Tax ID is always a CPF on the public register form. Strip the mask
        // (dots/hyphens) to the 11 raw digits and reject invalid checksums up
        // front so the user fixes the typo here, not after their account exists.
        if (! empty($validated['tax_id'])) {
            $digits = TaxIdValidator::normalize($validated['tax_id']);
            if (! TaxIdValidator::validateCpf($digits)) {
                $this->addError('tax_id', __('The :type number is invalid.', ['type' => 'CPF']));

                return;
            }
            $validated['tax_id'] = $digits;
        }

        $user = DB::transaction(function () use ($validated) {
            $person = Person::create([
                'person_type' => PersonType::Individual->value,
                'name' => $validated['name'],
                'birthdate' => $validated['birthdate'] ?? null,
                'gender' => $validated['gender'] ?? null,
                'marital_status' => $validated['marital_status'] ?? null,
                'tax_id' => $validated['tax_id'] ?? null,
                'tax_id_type' => ! empty($validated['tax_id']) ? 'cpf' : null,
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

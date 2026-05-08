<?php

use App\Enums\AppLocale;
use App\Enums\ChurchType;
use App\Enums\LocationMode;
use App\Enums\MemberType;
use App\Models\Church;
use App\Models\EcclesiasticalRegion;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('layouts.app')]
class extends Component
{
    public ?Church $church = null;

    public ?int $ecclesiastical_region_id = null;
    public string $type = 'church';
    public string $name = '';
    public string $slug = '';
    public string $address = '';
    public string $city = '';
    public string $state = '';
    public string $zip = '';
    public string $timezone = 'America/Sao_Paulo';
    public int $max_prayers_per_slot = 5;
    public string $default_mode = 'presential';
    public string $phone = '';
    public string $email = '';
    public bool $is_active = true;

    public string $master_name = '';
    public string $master_email = '';
    public string $master_phone = '';
    public string $master_password = '';

    public function mount(?int $churchId = null): void
    {
        abort_unless(auth()->user()?->can('church.manage'), 403);

        if ($churchId) {
            $this->church = Church::findOrFail($churchId);
            $this->ecclesiastical_region_id = $this->church->ecclesiastical_region_id;
            $this->type = $this->church->type?->value ?? ChurchType::Church->value;
            $this->name = $this->church->name;
            $this->slug = $this->church->slug;
            $this->address = $this->church->address ?? '';
            $this->city = $this->church->city ?? '';
            $this->state = $this->church->state ?? '';
            $this->zip = $this->church->zip ?? '';
            $this->timezone = $this->church->timezone;
            $this->max_prayers_per_slot = $this->church->max_prayers_per_slot;
            $this->default_mode = $this->church->default_mode->value;
            $this->phone = $this->church->phone ?? '';
            $this->email = $this->church->email ?? '';
            $this->is_active = $this->church->is_active;
        }
    }

    #[Computed]
    public function regions(): Collection
    {
        return EcclesiasticalRegion::orderBy('display_order')->get(['id', 'code', 'name']);
    }

    public function save(): void
    {
        $isCreating = $this->church === null;

        $rules = [
            'ecclesiastical_region_id' => ['required', 'integer', 'exists:ecclesiastical_regions,id'],
            'type' => ['required', 'in:'.implode(',', ChurchType::values())],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('churches', 'slug')->ignore($this->church?->id)],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:2'],
            'zip' => ['nullable', 'string', 'max:16'],
            'timezone' => ['required', 'string', 'max:64'],
            'max_prayers_per_slot' => ['required', 'integer', 'min:1', 'max:200'],
            'default_mode' => ['required', 'in:'.implode(',', array_map(fn ($c) => $c->value, LocationMode::cases()))],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:255'],
            'is_active' => ['boolean'],
        ];

        if ($isCreating) {
            $rules += [
                'master_name' => ['required', 'string', 'max:255'],
                'master_email' => ['required', 'email', 'lowercase', 'max:255', 'unique:users,email'],
                'master_phone' => ['nullable', 'string', 'max:32'],
                'master_password' => ['required', 'string', 'min:8'],
            ];
        }

        $data = $this->validate($rules);

        $churchData = collect($data)->only([
            'ecclesiastical_region_id', 'type', 'name', 'slug', 'address', 'city', 'state', 'zip',
            'timezone', 'max_prayers_per_slot', 'default_mode', 'phone', 'email', 'is_active',
        ])->all();

        if (empty($churchData['slug'])) {
            $churchData['slug'] = Church::query()->where('slug', Str::slug($churchData['name']))->exists()
                ? Str::slug($churchData['name']).'-'.Str::lower(Str::random(5))
                : Str::slug($churchData['name']);
        }

        if ($isCreating) {
            $this->church = Church::create($churchData);

            $master = User::create([
                'name' => $data['master_name'],
                'email' => $data['master_email'],
                'phone' => $data['master_phone'] ?: null,
                'password' => Hash::make($data['master_password']),
                'church_id' => $this->church->id,
                'member_type' => MemberType::Member->value,
                'locale' => AppLocale::PtBR->value,
                'appearance' => 'system',
                'email_verified_at' => now(),
            ]);
            $master->assignRole('local_manager');
            $master->churches()->syncWithoutDetaching([$this->church->id => ['is_primary' => true]]);
        } else {
            $this->church->update($churchData);
        }

        session()->flash('status', $isCreating ? __('Church and master user created.') : __('Church updated.'));

        $this->redirect(route('admin.churches.edit', $this->church), navigate: true);
    }
};

<?php

use App\Enums\AppLocale;
use App\Enums\PersonContactType;
use App\Enums\PersonNature;
use App\Enums\PersonType;
use App\Livewire\Forms\ChurchForm;
use App\Models\Church;
use App\Models\District;
use App\Models\EcclesiasticalRegion;
use App\Models\Person;
use App\Models\User;
use App\Support\GenerateUniqueSlug;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('layouts.app')]
class extends Component
{
    public ChurchForm $form;

    public function mount(?int $churchId = null): void
    {
        abort_unless(auth()->user()?->can('church.manage'), 403);

        if ($churchId) {
            $this->form->setChurch(Church::findOrFail($churchId));
        }
    }

    public function updatedFormEcclesiasticalRegionId(): void
    {
        // Cascade: clear the chosen district when it no longer belongs to the
        // selected region.
        if (! $this->form->district_id) {
            return;
        }
        $district = District::find($this->form->district_id);
        if (! $district || $district->ecclesiastical_region_id !== $this->form->ecclesiastical_region_id) {
            $this->form->district_id = null;
        }
    }

    #[Computed]
    public function regions(): Collection
    {
        return EcclesiasticalRegion::orderBy('display_order')->get(['id', 'code', 'name']);
    }

    #[Computed]
    public function districts(): Collection
    {
        if (! $this->form->ecclesiastical_region_id) {
            return collect();
        }

        return District::query()
            ->where('ecclesiastical_region_id', $this->form->ecclesiastical_region_id)
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function save(): void
    {
        $isCreating = $this->form->church === null;

        $data = $this->form->validate();

        $churchData = collect($data)->only([
            'ecclesiastical_region_id', 'district_id', 'type', 'name', 'slug', 'code',
            'address', 'city', 'state', 'zip',
            'timezone', 'max_prayers_per_slot', 'default_mode', 'phone', 'email', 'is_active',
        ])->all();

        foreach (['code', 'address', 'city', 'state', 'zip', 'phone', 'email'] as $nullable) {
            if (($churchData[$nullable] ?? null) === '') {
                $churchData[$nullable] = null;
            }
        }

        if (($churchData['district_id'] ?? null) === '') {
            $churchData['district_id'] = null;
        }

        if (empty($churchData['slug'])) {
            $churchData['slug'] = (new GenerateUniqueSlug)(
                $churchData['name'],
                Church::query()->whereKeyNot($this->form->church?->id ?? 0),
            );
        }

        if ($isCreating) {
            // Each Church row is backed by an Organization-type Person; create
            // it first, then the Church row pointing to it.
            $church = DB::transaction(function () use ($churchData) {
                $orgPerson = Person::create([
                    'person_type' => PersonType::Organization->value,
                    'name' => $churchData['name'],
                    'natures' => [PersonNature::Church->value],
                ]);

                return Church::create($churchData + ['person_id' => $orgPerson->id]);
            });

            $master = DB::transaction(function () use ($data, $church) {
                $person = Person::create([
                    'person_type' => PersonType::Individual->value,
                    'name' => $data['master_name'],
                    'natures' => [PersonNature::Member->value],
                    'managing_church_id' => $church->id,
                ]);

                if (! empty($data['master_phone'])) {
                    $person->contacts()->create([
                        'type' => PersonContactType::Phone->value,
                        'value' => $data['master_phone'],
                        'is_primary' => true,
                    ]);
                }

                return User::create([
                    'person_id' => $person->id,
                    'name' => $data['master_name'],
                    'email' => $data['master_email'],
                    'password' => Hash::make($data['master_password']),
                    'locale' => AppLocale::PtBR->value,
                    'appearance' => 'system',
                    'email_verified_at' => now(),
                ]);
            });
            $master->assignRole('local_admin');
            $master->churches()->syncWithoutDetaching([$church->id => ['is_primary' => true]]);

            $this->form->church = $church;
        } else {
            $this->form->church->update($churchData);
            // Keep the linked Org Person's name in sync with the cached
            // Church.name (Person is canonical; this avoids drift).
            $this->form->church->person?->update(['name' => $churchData['name']]);
        }

        session()->flash('status', $isCreating ? __('Church and master user created.') : __('Church updated.'));

        $this->redirect(route('admin.churches.edit', $this->form->church), navigate: true);
    }
};

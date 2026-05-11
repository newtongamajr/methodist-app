<?php

use App\Enums\AppLocale;
use App\Enums\MemberType;
use App\Livewire\Forms\ChurchForm;
use App\Models\Church;
use App\Models\EcclesiasticalRegion;
use App\Models\User;
use App\Support\GenerateUniqueSlug;
use Illuminate\Support\Collection;
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

    #[Computed]
    public function regions(): Collection
    {
        return EcclesiasticalRegion::orderBy('display_order')->get(['id', 'code', 'name']);
    }

    public function save(): void
    {
        $isCreating = $this->form->church === null;

        $data = $this->form->validate();

        $churchData = collect($data)->only([
            'ecclesiastical_region_id', 'type', 'name', 'slug', 'address', 'city', 'state', 'zip',
            'timezone', 'max_prayers_per_slot', 'default_mode', 'phone', 'email', 'is_active',
        ])->all();

        if (empty($churchData['slug'])) {
            $churchData['slug'] = (new GenerateUniqueSlug)(
                $churchData['name'],
                Church::query()->whereKeyNot($this->form->church?->id ?? 0),
            );
        }

        if ($isCreating) {
            $church = Church::create($churchData);

            $master = User::create([
                'name' => $data['master_name'],
                'email' => $data['master_email'],
                'phone' => $data['master_phone'] ?: null,
                'password' => Hash::make($data['master_password']),
                'church_id' => $church->id,
                'member_type' => MemberType::Member->value,
                'locale' => AppLocale::PtBR->value,
                'appearance' => 'system',
                'email_verified_at' => now(),
            ]);
            $master->assignRole('local_manager');
            $master->churches()->syncWithoutDetaching([$church->id => ['is_primary' => true]]);

            $this->form->church = $church;
        } else {
            $this->form->church->update($churchData);
        }

        session()->flash('status', $isCreating ? __('Church and master user created.') : __('Church updated.'));

        $this->redirect(route('admin.churches.edit', $this->form->church), navigate: true);
    }
};

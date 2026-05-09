<?php

use App\Enums\PersonContactType;
use App\Enums\PersonNature;
use App\Enums\PersonType;
use App\Livewire\Forms\UserForm;
use App\Models\Person;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('layouts.app')]
class extends Component
{
    public UserForm $form;

    public function mount(?int $userId = null): void
    {
        $actor = auth()->user();
        abort_unless($actor && ($actor->can('users.manage') || $actor->can('users.manage.local')), 403);

        if ($userId) {
            $user = User::with(['roles', 'churches', 'person'])->findOrFail($userId);

            if (! $this->isSuper) {
                $allowed = $actor->manageableChurchIds();
                $userChurchIds = $user->churches->pluck('id')->all();
                abort_unless(count(array_intersect($allowed, $userChurchIds)) > 0, 403);
            }

            $this->form->setUser($user);
        } else {
            $defaults = $actor->manageableChurchIds();
            if (! $this->isSuper && $defaults) {
                $this->form->church_ids = $defaults;
                $this->form->primary_church_id = $actor->currentChurchId();
            } elseif ($current = $actor->currentChurchId()) {
                $this->form->primary_church_id = $current;
            }
        }
    }

    #[Computed]
    public function isSuper(): bool
    {
        return auth()->user()->can('users.manage');
    }

    #[Computed]
    public function selectableChurches()
    {
        return auth()->user()->manageableChurches()->map->only(['id', 'name']);
    }

    #[Computed]
    public function availableRoles(): array
    {
        return $this->isSuper
            ? ['national_admin', 'regional_admin', 'district_admin', 'local_admin']
            : ['local_admin'];
    }

    public function save(): void
    {
        $actor = auth()->user();
        $isCreating = $this->form->user === null;
        $allowedIds = $actor->manageableChurchIds();

        $data = $this->form->validate();

        $this->validate(['form.role' => ['required', Rule::in($this->availableRoles)]]);

        $churchIds = collect($data['church_ids'] ?? [])
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->all();

        $role = $this->form->role;
        if (! $this->isSuper) {
            $churchIds = array_values(array_intersect($churchIds, $allowedIds));
            if (empty($churchIds)) {
                $churchIds = $allowedIds;
            }
            $role = 'local_admin';
        }

        $primaryId = $data['primary_church_id'] ?? null;
        if ($primaryId && ! in_array((int) $primaryId, $churchIds, true)) {
            $primaryId = null;
        }
        if (! $primaryId && $churchIds) {
            $primaryId = $churchIds[0];
        }

        $user = DB::transaction(function () use ($isCreating, $data, $primaryId) {
            $personPayload = [
                'name' => $data['name'],
                'natures' => [PersonNature::Member->value],
                'managing_church_id' => $primaryId,
            ];

            if ($isCreating) {
                $person = Person::create($personPayload + ['person_type' => PersonType::Individual->value]);
            } else {
                $person = $this->form->user->person ?? Person::create($personPayload + ['person_type' => PersonType::Individual->value]);
                $person->fill($personPayload)->save();
            }

            $userPayload = [
                'name' => $data['name'],
                'email' => $data['email'],
                'locale' => $data['locale'],
            ];

            if (! empty($data['password'])) {
                $userPayload['password'] = Hash::make($data['password']);
            }

            if ($isCreating) {
                $user = User::create($userPayload + [
                    'person_id' => $person->id,
                    'appearance' => 'system',
                    'email_verified_at' => now(),
                ]);
            } else {
                $this->form->user->update($userPayload + ['person_id' => $person->id]);
                $user = $this->form->user;
            }

            $phone = $data['phone'] ?: null;
            $existing = $person->contacts()->where('type', PersonContactType::Phone->value)->first();
            if ($phone) {
                if ($existing) {
                    $existing->update(['value' => $phone, 'is_primary' => true]);
                } else {
                    $person->contacts()->create([
                        'type' => PersonContactType::Phone->value,
                        'value' => $phone,
                        'is_primary' => true,
                    ]);
                }
            } elseif ($existing) {
                $existing->delete();
            }

            return $user;
        });

        $this->form->user = $user;
        $user->syncRoles([$role]);

        $existing = $user->churches()->pluck('churches.id')->all();
        if ($this->isSuper) {
            $finalIds = $churchIds;
        } else {
            $untouched = array_values(array_diff($existing, $allowedIds));
            $finalIds = array_values(array_unique(array_merge($untouched, $churchIds)));
        }

        $sync = [];
        foreach ($finalIds as $id) {
            $sync[$id] = ['is_primary' => $primaryId === $id];
        }
        $user->churches()->sync($sync);

        session()->flash('status', $isCreating ? __('Administrator created.') : __('Administrator updated.'));

        $this->redirect(route('admin.users.edit', $user), navigate: true);
    }
};

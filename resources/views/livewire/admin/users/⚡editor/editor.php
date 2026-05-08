<?php

use App\Enums\MemberType;
use App\Livewire\Forms\UserForm;
use App\Models\User;
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
            $user = User::with(['roles', 'churches'])->findOrFail($userId);

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
            } elseif ($actor->church_id) {
                $this->form->primary_church_id = $actor->church_id;
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
            ? ['global_manager', 'local_manager']
            : ['local_manager'];
    }

    public function save(): void
    {
        $actor = auth()->user();
        $isCreating = $this->form->user === null;
        $allowedIds = $actor->manageableChurchIds();

        $data = $this->form->validate();

        // Role is constrained by who's editing — validate it on the component
        // so the Form class doesn't need to reach back into the actor's perms.
        $this->validate(['form.role' => ['required', Rule::in($this->availableRoles)]]);

        $churchIds = collect($data['church_ids'] ?? [])
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->all();

        // Masters can only attach churches from their own pool, and the role
        // is forced to local_manager regardless of what they posted.
        $role = $this->form->role;
        if (! $this->isSuper) {
            $churchIds = array_values(array_intersect($churchIds, $allowedIds));
            if (empty($churchIds)) {
                $churchIds = $allowedIds; // pin to whatever they manage
            }
            $role = 'local_manager';
        }

        $primaryId = $data['primary_church_id'] ?? null;
        if ($primaryId && ! in_array((int) $primaryId, $churchIds, true)) {
            $primaryId = null;
        }
        if (! $primaryId && $churchIds) {
            $primaryId = $churchIds[0];
        }

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?: null,
            'church_id' => $primaryId,
            'locale' => $data['locale'],
        ];

        if (! empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        if ($isCreating) {
            $payload['member_type'] = MemberType::Member->value;
            $payload['appearance'] = 'system';
            $payload['email_verified_at'] = now();
            $user = User::create($payload);
            $this->form->user = $user;
        } else {
            $this->form->user->update($payload);
            $user = $this->form->user;
        }

        $user->syncRoles([$role]);

        // Build the new pivot state. Master users only manipulate the slice
        // they're allowed to touch — other church attachments stay intact.
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

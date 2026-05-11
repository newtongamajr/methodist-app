<?php

use App\Livewire\Forms\MemberForm;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('layouts.app')]
class extends Component
{
    public MemberForm $form;

    public function mount(?int $userId = null): void
    {
        $actor = auth()->user();
        abort_unless($actor && ($actor->can('users.manage') || $actor->can('users.manage.local')), 403);

        if ($userId) {
            $user = User::with(['roles', 'churches'])->findOrFail($userId);

            // Refuse to edit administrators from this CRUD; that's /admin/users.
            abort_if(
                $user->roles->whereIn('name', ['global_manager', 'local_manager'])->isNotEmpty(),
                404
            );

            if (! $this->isSuper) {
                $allowed = $actor->manageableChurchIds();
                $userChurchIds = $user->churches->pluck('id')->all();
                abort_unless(count(array_intersect($allowed, $userChurchIds)) > 0, 403);
            }

            $this->form->setUser($user);
        } else {
            // Default new member to the actor's current church context.
            $current = $actor->currentChurchId();
            if ($current) {
                $this->form->church_ids = [$current];
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

    public function save(): void
    {
        $actor = auth()->user();
        $isCreating = $this->form->user === null;
        $allowedIds = $actor->manageableChurchIds();

        $data = $this->form->validate();

        $churchIds = collect($data['church_ids'] ?? [])
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->all();

        if (! $this->isSuper) {
            $churchIds = array_values(array_intersect($churchIds, $allowedIds));
            if (empty($churchIds)) {
                $churchIds = [$actor->currentChurchId()];
            }
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
            'birthdate' => $data['birthdate'] ?: null,
            'member_type' => $data['member_type'],
            'church_id' => $primaryId,
            'locale' => $data['locale'],
        ];

        if (! empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        if ($isCreating) {
            $payload['appearance'] = 'system';
            $payload['email_verified_at'] = now();
            $user = User::create($payload);
            $this->form->user = $user;
        } else {
            $this->form->user->update($payload);
            $user = $this->form->user;
        }

        $user->syncRoles(['user']);

        // Preserve church attachments outside the actor's manageable scope.
        $existing = $user->churches()->pluck('churches.id')->all();
        $finalIds = $this->isSuper
            ? $churchIds
            : array_values(array_unique(array_merge(
                array_values(array_diff($existing, $allowedIds)),
                $churchIds
            )));

        $sync = [];
        foreach ($finalIds as $id) {
            $sync[$id] = ['is_primary' => $primaryId === $id];
        }
        $user->churches()->sync($sync);

        session()->flash('status', $isCreating ? __('Member created.') : __('Member updated.'));

        $this->redirect(route('admin.members.edit', $user), navigate: true);
    }
};

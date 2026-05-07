<?php

use App\Enums\AppLocale;
use App\Enums\MemberType;
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
    public ?User $user = null;

    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $birthdate = '';
    public string $member_type = 'member';
    public array $church_ids = [];
    public ?int $primary_church_id = null;
    public string $password = '';
    public string $locale = 'pt_BR';

    public function mount(?int $userId = null): void
    {
        $actor = auth()->user();
        abort_unless($actor && ($actor->can('users.manage') || $actor->can('users.manage.local')), 403);

        if ($userId) {
            $this->user = User::with(['roles', 'churches'])->findOrFail($userId);

            // Refuse to edit administrators from this CRUD; that's /admin/users.
            abort_if(
                $this->user->roles->whereIn('name', ['global_manager', 'local_manager'])->isNotEmpty(),
                404
            );

            if (! $this->isSuper) {
                $allowed = $actor->manageableChurchIds();
                $userChurchIds = $this->user->churches->pluck('id')->all();
                abort_unless(count(array_intersect($allowed, $userChurchIds)) > 0, 403);
            }

            $this->name = $this->user->name;
            $this->email = $this->user->email;
            $this->phone = $this->user->phone ?? '';
            $this->birthdate = $this->user->birthdate?->format('Y-m-d') ?? '';
            $this->member_type = $this->user->member_type?->value ?? MemberType::Member->value;
            $this->church_ids = $this->user->churches->pluck('id')->map(fn ($v) => (int) $v)->all();
            $this->primary_church_id = $this->user->church_id;
            $this->locale = $this->user->locale ?? AppLocale::PtBR->value;
        } else {
            // Default new member to the actor's current church context.
            $current = $actor->currentChurchId();
            if ($current) {
                $this->church_ids = [$current];
                $this->primary_church_id = $current;
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
        $isCreating = $this->user === null;
        $allowedIds = $actor->manageableChurchIds();

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user?->id)],
            'phone' => ['nullable', 'string', 'max:32'],
            'birthdate' => ['nullable', 'date', 'before:today'],
            'member_type' => ['required', 'string', 'in:'.implode(',', array_map(fn ($c) => $c->value, MemberType::cases()))],
            'church_ids' => ['array'],
            'church_ids.*' => ['integer', 'exists:churches,id'],
            'primary_church_id' => ['nullable', 'integer', 'exists:churches,id'],
            'locale' => ['required', 'string', 'in:'.implode(',', AppLocale::values())],
            'password' => [$isCreating ? 'required' : 'nullable', 'string', 'min:8'],
        ];

        $data = $this->validate($rules);

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
            $this->user = User::create($payload);
        } else {
            $this->user->update($payload);
        }

        $this->user->syncRoles(['user']);

        // Preserve church attachments outside the actor's manageable scope.
        $existing = $this->user->churches()->pluck('churches.id')->all();
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
        $this->user->churches()->sync($sync);

        session()->flash('status', $isCreating ? __('Member created.') : __('Member updated.'));

        $this->redirect(route('admin.members.edit', $this->user), navigate: true);
    }
};
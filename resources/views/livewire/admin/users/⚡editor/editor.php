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
    public array $church_ids = [];
    public ?int $primary_church_id = null;
    public string $role = 'local_manager';
    public string $password = '';
    public string $locale = 'pt_BR';

    public function mount(?int $userId = null): void
    {
        $actor = auth()->user();
        abort_unless($actor && ($actor->can('users.manage') || $actor->can('users.manage.local')), 403);

        if ($userId) {
            $this->user = User::with(['roles', 'churches'])->findOrFail($userId);

            if (! $this->isSuper) {
                $allowed = $actor->manageableChurchIds();
                $userChurchIds = $this->user->churches->pluck('id')->all();
                abort_unless(count(array_intersect($allowed, $userChurchIds)) > 0, 403);
            }

            $this->name = $this->user->name;
            $this->email = $this->user->email;
            $this->phone = $this->user->phone ?? '';
            $this->church_ids = $this->user->churches->pluck('id')->map(fn ($v) => (int) $v)->all();
            $this->primary_church_id = $this->user->church_id;
            $this->role = $this->user->roles->pluck('name')->first() ?? 'local_manager';
            $this->locale = $this->user->locale ?? 'pt_BR';
        } else {
            $defaults = $actor->manageableChurchIds();
            if (! $this->isSuper && $defaults) {
                $this->church_ids = $defaults;
                $this->primary_church_id = $actor->currentChurchId();
            } elseif ($actor->church_id) {
                $this->primary_church_id = $actor->church_id;
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
        $isCreating = $this->user === null;
        $allowedIds = $actor->manageableChurchIds();

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user?->id)],
            'phone' => ['nullable', 'string', 'max:32'],
            'church_ids' => ['array'],
            'church_ids.*' => ['integer', 'exists:churches,id'],
            'primary_church_id' => ['nullable', 'integer', 'exists:churches,id'],
            'role' => ['required', Rule::in($this->availableRoles)],
            'locale' => ['required', 'string', 'in:'.implode(',', AppLocale::values())],
            'password' => [$isCreating ? 'required' : 'nullable', 'string', 'min:8'],
        ];

        $data = $this->validate($rules);

        $churchIds = collect($data['church_ids'] ?? [])
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->all();

        // Masters can only attach churches from their own pool, and the role
        // is forced to local_manager regardless of what they posted.
        if (! $this->isSuper) {
            $churchIds = array_values(array_intersect($churchIds, $allowedIds));
            if (empty($churchIds)) {
                $churchIds = $allowedIds; // pin to whatever they manage
            }
            $data['role'] = 'local_manager';
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
            $this->user = User::create($payload);
        } else {
            $this->user->update($payload);
        }

        $this->user->syncRoles([$data['role']]);

        // Build the new pivot state. Master users only manipulate the slice
        // they're allowed to touch — other church attachments stay intact.
        $existing = $this->user->churches()->pluck('churches.id')->all();
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
        $this->user->churches()->sync($sync);

        session()->flash('status', $isCreating ? __('Administrator created.') : __('Administrator updated.'));

        $this->redirect(route('admin.users.edit', $this->user), navigate: true);
    }
};
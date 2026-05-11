<?php

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
        }
    }

    #[Computed]
    public function isSuper(): bool
    {
        return auth()->user()->can('users.manage');
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
        $isCreating = $this->form->user === null;

        $data = $this->form->validate();

        $this->validate(['form.role' => ['required', Rule::in($this->availableRoles)]]);

        $role = $this->isSuper ? $this->form->role : 'local_admin';

        $user = DB::transaction(function () use ($isCreating, $data) {
            $personPayload = [
                'name' => $data['name'],
                'natures' => [PersonNature::Member->value],
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
                'appearance' => $data['appearance'],
            ];

            if (! empty($data['password'])) {
                $userPayload['password'] = Hash::make($data['password']);
            }

            if ($isCreating) {
                $user = User::create($userPayload + [
                    'person_id' => $person->id,
                    'email_verified_at' => now(),
                ]);
            } else {
                $this->form->user->update($userPayload + ['person_id' => $person->id]);
                $user = $this->form->user;
            }

            return $user;
        });

        $this->form->user = $user;
        $user->syncRoles([$role]);

        session()->flash('status', $isCreating ? __('Administrator created.') : __('Administrator updated.'));

        $this->redirect(route('admin.users.edit', $user), navigate: true);
    }
};

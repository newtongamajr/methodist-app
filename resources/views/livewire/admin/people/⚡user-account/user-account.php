<?php

use App\Enums\AppLocale;
use App\Models\Person;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public Person $person;

    /** Form state for the create-user flow. */
    public string $email = '';

    public string $password = '';

    public string $locale = 'pt_BR';

    public function mount(int $personId): void
    {
        abort_unless(auth()->user()?->can('users.manage') || auth()->user()?->can('users.manage.local'), 403);
        $this->person = Person::findOrFail($personId);
    }

    /**
     * The User row linked to this Person, if any. The connection is 1:1
     * via users.person_id (NOT NULL + unique). Only individuals can have
     * a User — organizations get a 403 from the create action.
     */
    #[Computed]
    public function user(): ?User
    {
        return $this->person->user;
    }

    public function createUser(): void
    {
        abort_if($this->person->person_type?->value !== 'individual', 422, __('Only individuals can have a user account.'));
        abort_if($this->person->user !== null, 422, __('This person already has a user account.'));

        $data = $this->validate([
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8'],
            'locale' => ['required', 'string', 'in:'.implode(',', AppLocale::values())],
        ]);

        User::create([
            'person_id' => $this->person->id,
            'name' => $this->person->name,
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'locale' => $data['locale'],
            'appearance' => 'system',
            'email_verified_at' => now(),
        ])->assignRole('user');

        $this->reset(['email', 'password']);
        unset($this->user);
        $this->dispatch('user-account-changed');

        session()->flash('user-status', __('User account created.'));
    }

    public function disconnect(): void
    {
        abort_unless(auth()->user()?->can('users.manage'), 403);
        $user = $this->person->user;
        if (! $user) {
            return;
        }

        // Refuse if the user being disconnected is the last national_admin
        // — leave at least one super-user standing.
        if ($user->hasRole('national_admin') && User::role('national_admin')->count() <= 1) {
            $this->addError('user', __('Cannot disconnect the last national_admin.'));

            return;
        }

        $user->delete();
        unset($this->user);
        $this->dispatch('user-account-changed');

        session()->flash('user-status', __('User account disconnected.'));
    }
};

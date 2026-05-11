<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Enums\AppAppearance;
use App\Enums\AppLocale;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Form;

class UserForm extends Form
{
    public ?User $user = null;

    public string $name = '';

    public string $email = '';

    public string $role = 'local_admin';

    public string $password = '';

    public string $password_confirmation = '';

    public string $locale = 'pt_BR';

    public string $appearance = 'system';

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user?->id)],
            'locale' => ['required', 'string', 'in:'.implode(',', AppLocale::values())],
            'appearance' => ['required', 'string', 'in:'.implode(',', array_map(fn ($c) => $c->value, AppAppearance::cases()))],
            // Password is required on create, optional on edit. Always confirmed
            // when present (Laravel's `confirmed` rule pairs with the
            // `password_confirmation` property).
            'password' => [$this->user === null ? 'required' : 'nullable', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function setUser(User $user): void
    {
        $person = $user->person;

        $this->user = $user;
        $this->name = $person?->name ?? $user->name;
        $this->email = $user->email;
        $this->role = $user->roles->pluck('name')->first() ?? 'local_admin';
        $this->locale = $user->locale ?? 'pt_BR';
        $this->appearance = $user->appearance ?? 'system';
    }
}

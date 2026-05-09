<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Enums\AppLocale;
use App\Enums\PersonContactType;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Form;

class UserForm extends Form
{
    public ?User $user = null;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public array $church_ids = [];

    public ?int $primary_church_id = null;

    public string $role = 'local_admin';

    public string $password = '';

    public string $locale = 'pt_BR';

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user?->id)],
            'phone' => ['nullable', 'string', 'max:32'],
            'church_ids' => ['array'],
            'church_ids.*' => ['integer', 'exists:churches,id'],
            'primary_church_id' => ['nullable', 'integer', 'exists:churches,id'],
            'locale' => ['required', 'string', 'in:'.implode(',', AppLocale::values())],
            'password' => [$this->user === null ? 'required' : 'nullable', 'string', 'min:8'],
        ];
    }

    public function setUser(User $user): void
    {
        $person = $user->person;

        $this->user = $user;
        $this->name = $person?->name ?? $user->name;
        $this->email = $user->email;
        $this->phone = $person?->contacts()->where('type', PersonContactType::Phone->value)->orderByDesc('is_primary')->value('value') ?? '';
        $this->church_ids = $user->churches->pluck('id')->map(fn ($v) => (int) $v)->all();
        $this->primary_church_id = $person?->managing_church_id;
        $this->role = $user->roles->pluck('name')->first() ?? 'local_admin';
        $this->locale = $user->locale ?? 'pt_BR';
    }
}

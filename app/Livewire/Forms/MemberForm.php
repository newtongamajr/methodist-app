<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Enums\AppLocale;
use App\Enums\PersonNature;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Form;

class MemberForm extends Form
{
    public ?User $user = null;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $birthdate = '';

    public string $nature = 'member';

    public array $church_ids = [];

    public ?int $primary_church_id = null;

    public string $password = '';

    public string $locale = 'pt_BR';

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user?->id)],
            'phone' => ['nullable', 'string', 'max:32'],
            'birthdate' => ['nullable', 'date', 'before:today'],
            'nature' => ['required', 'string', 'in:'.implode(',', array_map(fn ($c) => $c->value, PersonNature::cases()))],
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
        $this->phone = $person?->contacts()->where('type', 'phone')->orderByDesc('is_primary')->value('value') ?? '';
        $this->birthdate = $person?->birthdate?->format('Y-m-d') ?? '';
        $this->nature = $person?->natures[0] ?? PersonNature::Member->value;
        $this->church_ids = $user->churches->pluck('id')->map(fn ($v) => (int) $v)->all();
        $this->primary_church_id = $person?->managing_church_id;
        $this->locale = $user->locale ?? AppLocale::PtBR->value;
    }
}

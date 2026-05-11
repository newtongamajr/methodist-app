<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Enums\AppLocale;
use App\Enums\MemberType;
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

    public string $member_type = 'member';

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
            'member_type' => ['required', 'string', 'in:'.implode(',', array_map(fn ($c) => $c->value, MemberType::cases()))],
            'church_ids' => ['array'],
            'church_ids.*' => ['integer', 'exists:churches,id'],
            'primary_church_id' => ['nullable', 'integer', 'exists:churches,id'],
            'locale' => ['required', 'string', 'in:'.implode(',', AppLocale::values())],
            'password' => [$this->user === null ? 'required' : 'nullable', 'string', 'min:8'],
        ];
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone ?? '';
        $this->birthdate = $user->birthdate?->format('Y-m-d') ?? '';
        $this->member_type = $user->member_type?->value ?? MemberType::Member->value;
        $this->church_ids = $user->churches->pluck('id')->map(fn ($v) => (int) $v)->all();
        $this->primary_church_id = $user->church_id;
        $this->locale = $user->locale ?? AppLocale::PtBR->value;
    }
}

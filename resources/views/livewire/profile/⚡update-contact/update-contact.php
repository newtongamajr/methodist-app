<?php

use App\Enums\PersonContactType;
use App\Enums\PersonType;
use App\Models\Person;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

new class extends Component
{
    public string $phone = '';

    public ?string $birthdate = null;

    public function mount(): void
    {
        $user = Auth::user();
        $person = $user->person;
        $this->phone = $person?->contacts()->where('type', PersonContactType::Phone->value)->orderByDesc('is_primary')->value('value') ?? '';
        $this->birthdate = $person?->birthdate?->format('Y-m-d');
    }

    public function updateContact(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'phone' => ['nullable', 'string', 'max:32'],
            'birthdate' => ['nullable', 'date', 'before:today'],
        ]);

        foreach (['phone', 'birthdate'] as $nullable) {
            if (($validated[$nullable] ?? null) === '') {
                $validated[$nullable] = null;
            }
        }

        DB::transaction(function () use ($user, $validated) {
            $person = $user->person ?? Person::create([
                'person_type' => PersonType::Individual->value,
                'name' => $user->name,
            ]);
            if (! $user->person) {
                $user->person_id = $person->id;
                $user->save();
            }

            $person->birthdate = $validated['birthdate'] ?? null;
            $person->save();

            $existing = $person->contacts()->where('type', PersonContactType::Phone->value)->first();
            if (! empty($validated['phone'])) {
                if ($existing) {
                    $existing->update(['value' => $validated['phone'], 'is_primary' => true]);
                } else {
                    $person->contacts()->create([
                        'type' => PersonContactType::Phone->value,
                        'value' => $validated['phone'],
                        'is_primary' => true,
                    ]);
                }
            } elseif ($existing) {
                $existing->delete();
            }
        });

        $this->dispatch('profile-updated');
    }
};

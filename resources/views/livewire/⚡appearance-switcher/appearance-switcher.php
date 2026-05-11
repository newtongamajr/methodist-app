<?php

use App\Enums\AppAppearance;
use Illuminate\Support\Facades\App;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public function switchTo(string $appearance): void
    {
        if (! in_array($appearance, AppAppearance::values(), true)) {
            return;
        }

        session(['appearance' => $appearance]);

        if ($user = auth()->user()) {
            $user->forceFill(['appearance' => $appearance])->save();
        }

        // Client already called Flux.applyAppearance() optimistically;
        // server just persists the choice for the next page load.
    }

    #[Computed]
    public function options(): array
    {
        return collect(AppAppearance::cases())
            ->map(fn (AppAppearance $c) => [
                'value' => $c->value,
                'label' => $c->label(),
                'icon' => $c->icon(),
            ])
            ->all();
    }

    #[Computed]
    public function current(): string
    {
        return session('appearance', AppAppearance::System->value);
    }
};
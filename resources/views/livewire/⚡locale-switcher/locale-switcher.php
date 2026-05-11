<?php

use App\Enums\AppLocale;
use App\Http\Middleware\SetLocale;
use Illuminate\Support\Facades\App;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public function switchTo(string $locale): void
    {
        if (! in_array($locale, SetLocale::SUPPORTED, true)) {
            return;
        }

        session(['locale' => $locale]);
        App::setLocale($locale);

        if ($user = auth()->user()) {
            $user->update(['locale' => $locale]);
        }

        $this->redirect(request()->header('Referer') ?: '/', navigate: false);
    }

    #[Computed]
    public function options(): array
    {
        return collect(AppLocale::cases())
            ->map(fn (AppLocale $c) => [
                'value' => $c->value,
                'label' => $c->label(),
                'short' => match ($c) {
                    AppLocale::PtBR => 'PT',
                    AppLocale::En => 'EN',
                    AppLocale::Es => 'ES',
                },
            ])
            ->all();
    }

    #[Computed]
    public function current(): string
    {
        return App::getLocale();
    }
};
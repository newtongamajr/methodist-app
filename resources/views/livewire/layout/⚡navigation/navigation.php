<?php

use App\Enums\AppAppearance;
use App\Enums\AppLocale;
use App\Http\Middleware\SetLocale;
use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\App;
use Livewire\Component;

new class extends Component
{
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }

    public function switchAppearance(string $appearance): void
    {
        if (! in_array($appearance, AppAppearance::values(), true)) {
            return;
        }

        session(['appearance' => $appearance]);

        if ($user = auth()->user()) {
            $user->forceFill(['appearance' => $appearance])->save();
        }
    }

    public function switchLocale(string $locale): void
    {
        if (! in_array($locale, SetLocale::SUPPORTED, true)) {
            return;
        }

        session(['locale' => $locale]);
        App::setLocale($locale);

        if ($user = auth()->user()) {
            $user->forceFill(['locale' => $locale])->save();
        }

        $this->redirect(request()->header('Referer') ?: '/', navigate: false);
    }

    public function getAppearanceProperty(): string
    {
        return session('appearance', AppAppearance::System->value);
    }

    public function getAppearanceOptionsProperty(): array
    {
        return collect(AppAppearance::cases())
            ->map(fn (AppAppearance $c) => [
                'value' => $c->value,
                'label' => $c->label(),
                'icon' => $c->icon(),
            ])
            ->all();
    }

    public function getLocaleProperty(): string
    {
        return App::getLocale();
    }

    public function getLocaleOptionsProperty(): array
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
};
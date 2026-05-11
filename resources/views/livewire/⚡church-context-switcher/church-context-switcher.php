<?php

use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public function switchTo(int $churchId): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }

        $allowed = $user->contextChurches()->pluck('id')->all();
        if (! in_array($churchId, $allowed, true)) {
            return;
        }

        session(['admin_church_id' => $churchId]);

        $this->redirect($this->safeReturnUrl(), navigate: false);
    }

    protected function safeReturnUrl(): string
    {
        $fallback = route('posts.index');
        $referer = request()->header('Referer');

        if (! $referer) {
            return $fallback;
        }

        $refererHost = parse_url($referer, PHP_URL_HOST);
        $appHost = parse_url(config('app.url'), PHP_URL_HOST);

        return $refererHost === $appHost ? $referer : $fallback;
    }

    #[Computed]
    public function churches()
    {
        return auth()->user()?->contextChurches() ?? collect();
    }

    #[Computed]
    public function currentId(): ?int
    {
        return auth()->user()?->currentChurchId();
    }

    #[Computed]
    public function currentName(): ?string
    {
        return $this->churches->firstWhere('id', $this->currentId)?->name;
    }
};
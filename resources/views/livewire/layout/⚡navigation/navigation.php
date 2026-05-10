<?php

use App\Enums\AppAppearance;
use App\Enums\AppLocale;
use App\Http\Middleware\SetLocale;
use App\Livewire\Actions\Logout;
use App\Models\Church;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\App;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public string $commandSearch = '';

    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }

    /**
     * Re-render the menubar so the avatar in the profile dropdown picks up
     * the freshly-uploaded image. The avatar URL is computed at render
     * time (not via wire:model), so this otherwise-empty handler is the
     * cheapest way to bust the cached fragment.
     */
    #[On('avatar-updated')]
    public function refreshAvatar(): void
    {
        // Body-less on purpose — Livewire re-renders after every action.
    }

    public function switchAppearance(string $appearance): void
    {
        if (! in_array($appearance, AppAppearance::values(), true)) {
            return;
        }

        session(['appearance' => $appearance]);

        if ($user = auth()->user()) {
            $user->update(['appearance' => $appearance]);
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
            $user->update(['locale' => $locale]);
        }

        $this->redirect(request()->header('Referer') ?: '/', navigate: false);
    }

    #[Computed]
    public function appearance(): string
    {
        return session('appearance', AppAppearance::System->value);
    }

    #[Computed]
    public function appearanceOptions(): array
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
    public function locale(): string
    {
        return App::getLocale();
    }

    #[Computed]
    public function localeOptions(): array
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

    /**
     * Server-side command palette search results, grouped by entity. Permission-
     * gated and scoped to the actor's manageable churches for non-supers. Each
     * row carries an `edit_url` so the Blade can navigate without further logic.
     *
     * @return array{posts: \Illuminate\Support\Collection, churches: \Illuminate\Support\Collection, people: \Illuminate\Support\Collection}
     */
    #[Computed]
    public function commandResults(): array
    {
        $empty = ['posts' => collect(), 'churches' => collect(), 'people' => collect()];
        $user = auth()->user();
        $needle = trim($this->commandSearch);

        if (! $user || mb_strlen($needle) < 2) {
            return $empty;
        }

        $term = '%'.addcslashes($needle, '%_\\').'%';

        $posts = $user->can('posts.create.local')
            ? Post::query()
                ->where('title', 'like', $term)
                ->orderByDesc('updated_at')
                ->limit(5)
                ->get(['id', 'title'])
                ->map(fn (Post $p) => [
                    'id' => $p->id,
                    'label' => $p->title,
                    'edit_url' => route('admin.posts.edit', $p),
                ])
            : collect();

        $churches = $user->can('church.manage')
            ? Church::query()
                ->where('name', 'like', $term)
                ->orderBy('name')
                ->limit(5)
                ->get(['id', 'name'])
                ->map(fn (Church $c) => [
                    'id' => $c->id,
                    'label' => $c->name,
                    'edit_url' => route('admin.churches.edit', $c),
                ])
            : collect();

        $people = collect();
        if ($user->can('users.manage') || $user->can('users.manage.local')) {
            $isSuper = $user->can('users.manage');
            $allowed = $user->manageableChurchIds();

            $query = User::query()
                ->with('roles:id,name')
                ->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('email', 'like', $term))
                ->orderBy('name')
                ->limit(8);

            if (! $isSuper) {
                $query->whereHas('churches', fn ($q) => $q->whereIn('churches.id', $allowed));
            }

            $people = $query->get(['id', 'name', 'email'])->map(function (User $u) {
                $isAdmin = $u->roles->whereIn('name', ['national_admin', 'local_admin'])->isNotEmpty();

                return [
                    'id' => $u->id,
                    'label' => $u->name,
                    'sublabel' => $u->email,
                    'is_admin' => $isAdmin,
                    'edit_url' => $isAdmin
                        ? route('admin.users.edit', $u)
                        : route('admin.members.edit', $u),
                ];
            });
        }

        return ['posts' => $posts, 'churches' => $churches, 'people' => $people];
    }
};
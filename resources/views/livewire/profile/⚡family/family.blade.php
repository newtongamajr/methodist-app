<section class="space-y-6">
    <header>
        <flux:heading size="lg">{{ __('My family') }}</flux:heading>
        <flux:text class="mt-1">{{ __('People connected to you. Act as a child or teenager to record actions on their behalf.') }}</flux:text>
    </header>

    @if (! $this->person)
        <flux:callout variant="warning" icon="information-circle" inline :heading="__('Your account does not have a Person record yet.')" />
    @else
        @if ($this->spouse)
            <div>
                <flux:heading size="md">{{ __('Spouse') }}</flux:heading>
                <div class="mt-2 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                    <span class="font-medium">{{ $this->spouse->name }}</span>
                </div>
            </div>
        @endif

        @if ($this->parents->isNotEmpty())
            <div>
                <flux:heading size="md">{{ __('Parents') }}</flux:heading>
                <ul class="mt-2 space-y-2">
                    @foreach ($this->parents as $p)
                        <li wire:key="parent-{{ $p->id }}" class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                            {{ $p->name }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($this->children->isNotEmpty())
            <div>
                <flux:heading size="md">{{ __('Children') }}</flux:heading>
                <ul class="mt-2 space-y-2">
                    @foreach ($this->children as $c)
                        <li wire:key="child-{{ $c->id }}" class="flex items-center justify-between gap-3 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                            <div>
                                <div class="font-medium">{{ $c->name }}</div>
                                @if ($c->birthdate)
                                    <div class="text-xs text-zinc-500">{{ $c->birthdate->isoFormat('LL') }} · {{ __(':age years old', ['age' => $c->birthdate->age]) }}</div>
                                @endif
                            </div>
                            @if (auth()->user()->canActAs($c))
                                <flux:button wire:click="actAs({{ $c->id }})" size="sm" variant="primary" icon="user">
                                    {{ __('Act as :name', ['name' => $c->name]) }}
                                </flux:button>
                            @else
                                <flux:badge color="zinc">{{ __('Adult') }}</flux:badge>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($this->wards->isNotEmpty())
            <div>
                <flux:heading size="md">{{ __('Wards (legal guardianship)') }}</flux:heading>
                <ul class="mt-2 space-y-2">
                    @foreach ($this->wards as $w)
                        <li wire:key="ward-{{ $w->id }}" class="flex items-center justify-between gap-3 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                            <div>
                                <div class="font-medium">{{ $w->name }}</div>
                                @if ($w->birthdate)
                                    <div class="text-xs text-zinc-500">{{ $w->birthdate->isoFormat('LL') }}</div>
                                @endif
                            </div>
                            @if (auth()->user()->canActAs($w))
                                <flux:button wire:click="actAs({{ $w->id }})" size="sm" variant="primary" icon="user">
                                    {{ __('Act as :name', ['name' => $w->name]) }}
                                </flux:button>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($this->godchildren->isNotEmpty())
            <div>
                <flux:heading size="md">{{ __('Godchildren') }}</flux:heading>
                <ul class="mt-2 space-y-2">
                    @foreach ($this->godchildren as $g)
                        <li wire:key="godchild-{{ $g->id }}" class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                            {{ $g->name }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($this->parents->isEmpty() && $this->children->isEmpty() && $this->spouse === null && $this->wards->isEmpty() && $this->godchildren->isEmpty())
            <div class="rounded-lg border border-zinc-200 bg-white p-8 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900">
                {{ __('No family relationships recorded yet. Ask an administrator to add them on your Person record.') }}
            </div>
        @endif
    @endif
</section>

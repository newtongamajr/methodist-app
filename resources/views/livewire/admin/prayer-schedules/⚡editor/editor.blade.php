<div class="space-y-6">
    <div class="flex items-center justify-between gap-4">
        <flux:heading size="xl">
            {{ $form->schedule ? __('Edit prayer schedule') : __('New prayer schedule') }}
        </flux:heading>
        <flux:button :href="route('admin.prayer-schedules.index')" variant="ghost" wire:navigate>{{ __('Back to list') }}</flux:button>
    </div>

    @if (session('status'))
        <div class="rounded-md bg-emerald-50 p-3 text-sm font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
            {{ session('status') }}
        </div>
    @endif

    <form wire:submit="save" class="space-y-5">
        <div class="grid gap-4 sm:grid-cols-2">
            <flux:select wire:model="form.church_id" :label="__('Church')" required>
                <option value="">{{ __('— Select a church —') }}</option>
                @foreach ($this->churches as $church)
                    <option value="{{ $church->id }}">{{ $church->name }}</option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="form.prayer_campaign_id" :label="__('Prayer campaign')" required>
                <option value="">{{ __('— Select a campaign —') }}</option>
                @foreach ($this->campaigns as $c)
                    <option value="{{ $c->id }}">
                        {{ $c->name }} ({{ $c->start_date->isoFormat('L') }} – {{ $c->end_date->isoFormat('L') }})
                    </option>
                @endforeach
            </flux:select>
        </div>

        @if ($this->campaign)
            <flux:text class="text-sm text-zinc-500">
                {{ __('Schedules for this campaign must fall between :start and :end.', [
                    'start' => $this->campaign->start_date->isoFormat('LL'),
                    'end' => $this->campaign->end_date->isoFormat('LL'),
                ]) }}
            </flux:text>
        @endif

        <div class="grid gap-4 sm:grid-cols-3">
            <flux:date-picker
                wire:model="form.date"
                :label="__('Date')"
                required
                :min="$this->campaign?->start_date?->format('Y-m-d')"
                :max="$this->campaign?->end_date?->format('Y-m-d')"
            />
            <flux:time-picker wire:model="form.start_time" :label="__('Start time')" required />
            <flux:time-picker wire:model="form.end_time" :label="__('End time')" required />
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <flux:select wire:model="form.slot_minutes" :label="__('Slot length')">
                <option value="30">30 {{ __('minutes') }}</option>
                <option value="60">60 {{ __('minutes') }}</option>
            </flux:select>
            <flux:input wire:model="form.capacity_per_slot" :label="__('Max prayers per slot')" type="number" min="1" max="200" required />
            <flux:select wire:model="form.mode" :label="__('Mode')">
                @foreach (\App\Enums\LocationMode::cases() as $m)
                    <option value="{{ $m->value }}">{{ $m->label() }}</option>
                @endforeach
            </flux:select>
        </div>

        <flux:textarea wire:model="form.notes" :label="__('Notes')" rows="2" />

        <div class="flex justify-end gap-2">
            <flux:button :href="route('admin.prayer-schedules.index')" variant="ghost" wire:navigate>{{ __('Cancel') }}</flux:button>
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">{{ __('Save') }}</flux:button>
        </div>
    </form>

    @if ($form->schedule && $form->schedule->slots()->count())
        <div class="space-y-3">
            <flux:heading size="lg">{{ __('Slots') }}</flux:heading>
            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($form->schedule->slots as $slot)
                    <div wire:key="schedule-slot-{{ $slot->id }}" class="rounded-md border border-zinc-200 bg-white p-3 text-sm dark:border-zinc-700 dark:bg-zinc-900">
                        <div class="font-medium">{{ $slot->starts_at->format('H:i') }} – {{ $slot->ends_at->format('H:i') }}</div>
                        <div class="text-xs text-zinc-500">{{ $slot->confirmedSignups()->count() }} / {{ $slot->capacity }} {{ __('confirmed') }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
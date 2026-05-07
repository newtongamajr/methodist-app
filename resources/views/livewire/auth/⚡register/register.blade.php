<div>
    <flux:heading size="xl" class="text-center">{{ __('Create your account') }}</flux:heading>
    <flux:text class="mt-2 text-center">{{ __('Join the campaign and pray with us.') }}</flux:text>

    <form wire:submit="register" class="mt-8 space-y-5">
        <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus autocomplete="name" />

        <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="username" />

        <div class="grid gap-4 sm:grid-cols-2">
            <flux:input wire:model="password" :label="__('Password')" type="password" required autocomplete="new-password" />
            <flux:input wire:model="password_confirmation" :label="__('Confirm Password')" type="password" required autocomplete="new-password" />
        </div>

        <flux:select wire:model="member_type" :label="__('I am a')">
            @foreach (\App\Enums\MemberType::cases() as $type)
                <option value="{{ $type->value }}">{{ $type->label() }}</option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="region_id" :label="__('Ecclesiastical region')">
            <option value="">{{ __('— None —') }}</option>
            @foreach ($this->regions as $region)
                <option value="{{ $region->id }}">{{ $region->code }} — {{ $region->name }}</option>
            @endforeach
        </flux:select>

        @if ($this->churches->isNotEmpty())
            <flux:select wire:model="church_id" :label="__('Church')">
                <option value="">{{ __('— None —') }}</option>
                @foreach ($this->churches as $church)
                    <option value="{{ $church->id }}">
                        {{ $church->name }}@if ($church->city) — {{ $church->city }}/{{ $church->state }}@endif
                    </option>
                @endforeach
            </flux:select>
        @endif

        <div class="grid gap-4 sm:grid-cols-2">
            <flux:input wire:model="phone" :label="__('Phone')" type="tel" autocomplete="tel" />
            <flux:input wire:model="birthdate" :label="__('Birthdate')" type="date" />
        </div>

        <flux:select wire:model="locale" :label="__('Language')">
            @foreach (\App\Enums\AppLocale::cases() as $loc)
                <option value="{{ $loc->value }}">{{ $loc->label() }}</option>
            @endforeach
        </flux:select>

        <div class="flex items-center justify-between gap-4 pt-2">
            <a href="{{ route('login') }}" class="text-sm font-medium text-[#c8202f] hover:underline dark:text-rose-300" wire:navigate>
                {{ __('Already registered?') }}
            </a>
            <flux:button type="submit" variant="primary">
                {{ __('Register') }}
            </flux:button>
        </div>
    </form>
</div>
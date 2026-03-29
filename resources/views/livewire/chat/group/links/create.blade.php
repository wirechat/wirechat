@php
    $expirySliderOptions = [
        '1_hour' => __('wirechat::chat.group.invite_link.create.options.expiry.1_hour'),
        '1_day' => __('wirechat::chat.group.invite_link.create.options.expiry.1_day'),
        '1_week' => __('wirechat::chat.group.invite_link.create.options.expiry.1_week'),
        'never' => __('wirechat::chat.group.invite_link.create.options.expiry.never'),
    ];
    $expirySliderKeys = array_keys($expirySliderOptions);
    $expirySliderIndex = array_search($expiryPreset, $expirySliderKeys, true);
    $expirySliderKeysJs = (string) \Illuminate\Support\Js::from(array_values($expirySliderKeys));
@endphp

<div class=" max-w-xl rounded-xl border border-[var(--wc-light-border)] dark:border-[var(--wc-dark-border)] bg-[var(--wc-light-primary)] dark:bg-[var(--wc-dark-primary)] p-6 text-gray-900 shadow-xl dark:text-white">
    <div class="flex items-center justify-between gap-4">
        <button type="button" wire:click="closeWirechatModal" class="rounded-full p-2 text-gray-500 transition hover:bg-[var(--wc-light-secondary)] dark:hover:bg-[var(--wc-dark-secondary)]">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-6 w-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
        <h3 class="text-lg font-semibold">{{ __('wirechat::chat.group.invite_link.create.heading.label') }}</h3>
        <span class="w-10"></span>
    </div>

    <div class="mt-6 space-y-6">
        <div>
            <input type="text" wire:model.live="name" maxlength="120" placeholder="{{ __('wirechat::chat.group.invite_link.create.inputs.name.placeholder') }}"
                class="wc-input w-full rounded-lg border border-[var(--wc-light-border)] bg-[var(--wc-light-primary)] px-4 py-3 text-base dark:border-[var(--wc-dark-border)] dark:bg-[var(--wc-dark-primary)]">
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('wirechat::chat.group.invite_link.create.inputs.name.helper_text') }}</p>
            @error('name')
                <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
            @enderror
        </div>

        <div class="w-full">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">
                {{ __('wirechat::chat.group.invite_link.create.sections.expiry.label') }}</p>

            <div class="mt-3 flex w-full flex-col items-center">
                <div class="w-full">
                    <input
                        type="range"
                        min="0"
                        max="{{ count($expirySliderKeys) - 1 }}"
                        step="1"
                        value="{{ $expirySliderIndex === false ? count($expirySliderKeys) - 1 : $expirySliderIndex }}"
                        x-data="{}"
                        x-on:input="$wire.set('expiryPreset', {{ $expirySliderKeysJs }}[$event.target.value] ?? 'never')"
                        class="mx-auto w-full"
                    />
                </div>

                <div class="mt-1 flex justify-between px-2 items-center w-full text-xs text-gray-400 dark:text-gray-500">
                    @foreach ($expirySliderKeys as $key)
                        <span class="justify-self-center">|</span>
                    @endforeach
                </div>

                <div class="mt-2 flex justify-between w-full  text-center text-xs">
                    @foreach ($expirySliderOptions as $key => $label)
                        <span @class([
                            'flex items-center justify-center transition',
                            'font-medium text-[var(--wc-brand-primary)]' => $expiryPreset === $key,
                            'text-gray-600 dark:text-gray-300' => $expiryPreset !== $key,
                        ])>
                            @if ($key === 'never')
                                <x-wirechat::icons.infinite class="size-4" />
                                <span class="sr-only">{{ $label }}</span>
                            @else
                                {{ $label }}
                            @endif
                        </span>
                    @endforeach
                </div>
            </div>
            @error('expiryPreset')
                <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
            @enderror
        </div>

        {{-- Usage Presets --}}
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">{{ __('wirechat::chat.group.invite_link.create.sections.usage.label') }}</p>
            <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-5">
                @foreach ([
                    '1' => '1',
                    '10' => '10',
                    '50' => '50',
                    '100' => '100',
                    'unlimited' => __('wirechat::chat.group.invite_link.create.options.usage.unlimited'),
                ] as $value => $label)
                    <button type="button" wire:click="$set('usagePreset', '{{ $value }}')"
                        @class([
                            'rounded-lg border px-2 py-2 text-sm font-medium transition',
                            'border-[var(--wc-brand-primary)] bg-[var(--wc-brand-primary)] text-white' => $usagePreset === $value,
                            'border-[var(--wc-light-border)] dark:border-[var(--wc-dark-border)]' => $usagePreset !== $value,
                        ])>
                        {{ $label }}
                    </button>
                @endforeach
            </div>
            @error('usagePreset')
                <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
            @enderror
        </div>

        <div class="rounded-xl bg-[var(--wc-light-secondary)] px-4 py-3 text-sm text-gray-600 dark:bg-[var(--wc-dark-secondary)] dark:text-gray-300">
            {{ __('wirechat::chat.group.invite_link.create.labels.approval_notice') }}
        </div>

        <button type="button" wire:click="createLink" wire:loading.attr="disabled"
            class="inline-flex w-full items-center justify-center rounded-xl bg-[var(--wc-brand-primary)] px-4 py-3 text-sm font-medium text-white disabled:cursor-not-allowed disabled:opacity-70">
            {{ __('wirechat::chat.group.invite_link.create.actions.create.label') }}
        </button>
    </div>
</div>

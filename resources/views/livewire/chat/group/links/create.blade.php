@php
    $expirySliderOptions = [
        '1_hour' => __('wirechat::chat.group.invite_link.create.options.expiry.1_hour'),
        '1_day' => __('wirechat::chat.group.invite_link.create.options.expiry.1_day'),
        '1_week' => __('wirechat::chat.group.invite_link.create.options.expiry.1_week'),
        'never' => __('wirechat::chat.group.invite_link.create.options.expiry.never'),
    ];
    $expirySliderKeys = array_keys($expirySliderOptions);
    $expirySliderIndex = array_search($expiryPreset, $expirySliderKeys, true);

    $usageSliderOptions = [
        '1' => '1',
        '10' => '10',
        '50' => '50',
        '100' => '100',
        'unlimited' => __('wirechat::chat.group.invite_link.create.options.usage.unlimited'),
    ];
    $usageSliderKeys = array_map('strval', array_keys($usageSliderOptions));
    $usageSliderIndex = array_search((string) $usagePreset, $usageSliderKeys, true);
@endphp

<div class=" max-w-xl rounded-xl border border-[var(--wc-light-border)] dark:border-[var(--wc-dark-border)] bg-[var(--wc-light-primary)] dark:bg-[var(--wc-dark-primary)] p-6 text-zinc-900 shadow-xl dark:text-white">
    <div class="flex items-center justify-between gap-4">
        <button type="button" wire:click="closeWirechatModal" class="rounded-full p-2 text-zinc-500 transition hover:bg-[var(--wc-light-secondary)] dark:hover:bg-[var(--wc-dark-secondary)]">
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
            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ __('wirechat::chat.group.invite_link.create.inputs.name.helper_text') }}</p>
            @error('name')
                <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
            @enderror
        </div>

        <div class="w-full">
            <p class="text-base font-normal  text-zinc-500 dark:text-zinc-400">
                {{ __('wirechat::chat.group.invite_link.create.sections.expiry.label') }}</p>

            <div class="mt-3 flex w-full flex-col items-center">
                <div class="w-full">
                    <input
                        wire:key="expiry-slider-{{ $expiryPreset }}"
                        type="range"
                        min="0"
                        max="{{ count($expirySliderKeys) - 1 }}"
                        step="1"
                        value="{{ $expirySliderIndex === false ? count($expirySliderKeys) - 1 : $expirySliderIndex }}"
                        x-data="{}"
                        x-on:input="$wire.set('expiryPreset', @js(array_values($expirySliderKeys))[$event.target.value] ?? 'never')"
                        class="wc-range-zinc mx-auto w-full"
                    />
                </div>

                <div class="mt-1 flex justify-between px-2  items-center w-full text-xs text-zinc-400 dark:text-zinc-500">
                    @foreach ($expirySliderKeys as $key)
                        <span class="justify-self-center">|</span>
                    @endforeach
                </div>

                <div class="mt-2 flex justify-between w-full   text-center text-xs">
                    @foreach ($expirySliderOptions as $key => $label)
                        <button type="button"
                            wire:click="$set('expiryPreset', @js($key))"
                            @class([
                                'flex items-center justify-center transition cursor-pointer',
                                'font-medium text-[var(--wc-brand-primary)]' => $expiryPreset === $key,
                                'text-zinc-600 dark:text-zinc-300' => $expiryPreset !== $key,
                            ])>
                            @if ($key === 'never')
                                <x-wirechat::icons.infinite class="size-3" />
                                <span class="sr-only">{{ $label }}</span>
                            @else
                                {{ $label }}
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>
            @error('expiryPreset')
                <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
            @enderror
        </div>

        {{-- Usage Presets --}}
        <div>
            <p class="text-base font-normal  text-zinc-500 dark:text-zinc-400">{{ __('wirechat::chat.group.invite_link.create.sections.usage.label') }}</p>
            <div class="mt-3 flex w-full flex-col items-center">
                <div class=" w-full">
                    <input
                        wire:key="usage-slider-{{ $usagePreset }}"
                        type="range"
                        min="0"
                        max="{{ count($usageSliderKeys) - 1 }}"
                        step="1"
                        value="{{ $usageSliderIndex === false ? count($usageSliderKeys) - 1 : $usageSliderIndex }}"
                        x-data="{}"
                        x-on:input="$wire.set('usagePreset', @js(array_values($usageSliderKeys))[$event.target.value] ?? 'unlimited')"
                        class="wc-range-zinc mx-auto w-full"
                    />
                </div>

                <div class="relative mt-2 px-4 h-9 w-[97.5%] text-xs">
                    @foreach ($usageSliderOptions as $key => $label)
                        @php
                            $usageTransform = match ($key) {
                                '10' => 'translateX(calc(-50% + 0.25rem))',
                                '100' => 'translateX(calc(-50% - 0.25rem))',
                                default => 'translateX(-50%)',
                            };
                        @endphp
                        <button type="button"
                            wire:click="$set('usagePreset', @js((string) $key))"
                            @class([
                                'absolute top-0 flex flex-col gap-1 transition cursor-pointer',
                                'left-0 items-start text-left' => $loop->first,
                                'right-0 items-end text-right' => $loop->last,
                                'items-center text-center' => ! $loop->first && ! $loop->last,
                                'font-medium text-[var(--wc-brand-primary)]' => (string) $usagePreset === (string) $key,
                                'text-zinc-600 dark:text-zinc-300' => (string) $usagePreset !== (string) $key,
                            ])
                            @if (! $loop->first && ! $loop->last)
                                style="left: {{ ($loop->index / (count($usageSliderOptions) - 1)) * 100 }}%; transform: {{ $usageTransform }};"
                            @endif
                        >
                            <span class="text-zinc-400 dark:text-zinc-500">|</span>

                            @if ($key === 'unlimited')
                                <x-wirechat::icons.infinite class="size-3" />
                                <span class="sr-only">{{ $label }}</span>
                            @else
                                {{ $label }}
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>
            @error('usagePreset')
                <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
            @enderror
        </div>

        <div class="rounded-xl bg-[var(--wc-light-secondary)] px-4 py-3 text-sm text-zinc-600 dark:bg-[var(--wc-dark-secondary)] dark:text-zinc-300">
            {{ __('wirechat::chat.group.invite_link.create.labels.approval_notice') }}
        </div>

        <x-wirechat::button
            variant="primary"
            class="w-full"
            wire:click="createLink"
            wire:loading.attr="disabled">
            {{ __('wirechat::chat.group.invite_link.create.actions.create.label') }}
        </x-wirechat::button>
      
    </div>
</div>

<x-wirechat::section
    :title="__('wirechat::chat.group.invite_link.labels.additional_links')"
    :description="__('wirechat::chat.group.invite_link.labels.additional_links_helper')"
    class="space-y-4"
>
    <x-slot:actions>
        <x-wirechat::actions.open-modal
            component="wirechat.chat.group.links.create"
            :conversation="$conversation->id"
            :panel="$this->panel"
        >
            <x-wirechat::button size="sm">
                {{ __('wirechat::chat.group.invite_link.actions.create_new_link.label') }}
            </x-wirechat::button>
        </x-wirechat::actions.open-modal>
    </x-slot:actions>

    <div class="space-y-3">
        @forelse ($additionalInvites as $invite)
            <x-wirechat::actions.open-modal
                component="wirechat.chat.group.links.show"
                :conversation="$conversation->id"
                :panel="$this->panel"
                :arguments="['invite' => $invite->id]"
                wire:key="additional-invite-{{ $invite->id }}"
            >
                <button
                    type="button"
                    class="flex w-full items-center gap-4 rounded-xl border dark:border-zinc-700 px-4 py-4 text-left transition hover:bg-[var(--wc-light-secondary)]/60 dark:hover:bg-[var(--wc-dark-secondary)]/60"
                >
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-[var(--wc-light-secondary)] text-[var(--wc-brand-primary)] dark:bg-[var(--wc-dark-secondary)]">
                        <x-wirechat::icons.link class="size-5" />
                    </div>

                    <div class="min-w-0 flex-1">
                        <p class="truncate font-medium">{{ $invite->name ?: $invite->token }}</p>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            @if ($invite->limit)
                                {{ __('wirechat::chat.group.invite_link.labels.additional_link_usage_limited', ['usages' => $invite->usages, 'limit' => $invite->limit]) }}
                            @else
                                {{ __('wirechat::chat.group.invite_link.labels.additional_link_usage_total', ['usages' => $invite->usages]) }}
                            @endif
                            @if ($invite->expires_at)
                                • {{ __('wirechat::chat.group.invite_link.labels.additional_link_expires', ['time' => $invite->expires_at->diffForHumans()]) }}
                            @else
                                • {{ __('wirechat::chat.group.invite_link.labels.additional_link_never_expires') }}
                            @endif
                        </p>
                    </div>

                    <span class="text-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7" />
                        </svg>
                    </span>
                </button>
            </x-wirechat::actions.open-modal>
        @empty
            <div class="rounded-xl border border-dashed border-[var(--wc-light-border)] px-5 py-8 text-center text-sm text-gray-500 dark:border-[var(--wc-dark-border)] dark:text-gray-400">
                {{ __('wirechat::chat.group.invite_link.labels.additional_links_empty') }}
            </div>
        @endforelse
    </div>

    @if ($canLoadMore)
        <x-wirechat::button
            variant="secondary"
            full-width
            wire:click="loadMore"
            wire:loading.attr="disabled"
        >
            {{ __('wirechat::chat.group.invite_link.actions.load_more.label') }}
        </x-wirechat::button>
    @endif
</x-wirechat::section>

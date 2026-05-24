<?php

namespace Wirechat\Wirechat\Livewire\Concerns;

use Livewire\Attributes\Locked;
use Wirechat\Wirechat\Livewire\Chat\Chats;
use Wirechat\Wirechat\Models\Conversation;

/**
 * Trait Actionable
 *
 * @property Conversation|null $conversation
 */
trait Widget
{
    #[Locked]
    public bool $widget = false;

    /**
     * ----------------------------------------
     * ----------------------------------------
     * Check if is Widget
     * --------------------------------------------
     */
    public function isWidget(): bool
    {
        return $this->widget;
    }

    /**
     * Handle the termination of the component.
     *
     * If the component is a widget, it dispatches events to refresh the chat list
     * and notify the listener to close the chat. Otherwise, it redirects to the chats page.
     */
    public function handleComponentTermination(?string $redirectRoute = null, ?array $events = null)
    {

        // set redirect route
        if ($redirectRoute == null) {

            $redirectRoute = route($this->panel()->chatsRoute());
        }

        // set events to dispatch on termination
        if ($events == null) {
            $events = [
                ['close-chat', ['conversation' => $this->resolveConversationId()]],
            ];
        }
        if ($this->isWidget()) {

            $this->dispatchWidgetEvents($events);
        } else {
            // Redirect to the main chats page
            return $this->redirect($redirectRoute);
        }
    }

    /**
     * Dispatch events to the widget components. upon terminatoin
     */
    private function dispatchWidgetEvents(array $events): void
    {
        foreach ($events as $component => $event) {
            if (is_array($event)) {
                [$event, $params] = $event;
            }

            if (is_numeric($component)) {
                $this->dispatch($event, ...$params ?? []);
            } else {
                $this->dispatch($event, ...$params ?? [])->to($component);
            }
        }
    }

    /**
     * A method to dispatch open chat widget
     */
    public function openChat(int|string $conversation): void
    {
        $this->dispatch('open-chat', conversation: $conversation);
    }

    /**
     * A method to dispatch close chat widget
     */
    public function closeChat(): void
    {
        $this->dispatch('close-chat');
    }

    /**
     * Resolve the current conversation id from either $conversation or $conversationId.
     */
    protected function resolveConversationId(): int|string|null
    {
        if (isset($this->conversation)) {
            return $this->conversation->id;
        }

        /** @phpstan-ignore-next-line */
        return $this->conversationId ?? null;
    }
}

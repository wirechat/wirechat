<?php

namespace Namu\WireChat\Livewire\Concerns;

use Livewire\Component;

abstract class ModalComponent extends Component
{
    public bool $forceClose = false;

    public int $skipModals = 0;

    public bool $destroySkipped = false;

    public function destroySkippedModals(): self
    {
        $this->destroySkipped = true;

        return $this;
    }

    public function skipPreviousModals($count = 1, $destroy = false): self
    {
        $this->skipPreviousModal($count, $destroy);

        return $this;
    }

    public function skipPreviousModal($count = 1, $destroy = false): self
    {
        $this->skipModals = $count;
        $this->destroySkipped = $destroy;

        return $this;
    }

    public function forceClose(): self
    {
        $this->forceClose = true;

        return $this;
    }

    public function closeWireChatModal(): void
    {
        $this->dispatch('closeWireChatModal', force: $this->forceClose, skipPreviousModals: $this->skipModals, destroySkipped: $this->destroySkipped);
    }

    public function closeChatDrawer(): void
    {
        $this->dispatch('closeChatDrawer', force: $this->forceClose, skipPreviousModals: $this->skipModals, destroySkipped: $this->destroySkipped);
    }

    public function closeModalWithEvents(array $events): void
    {
        $this->emitModalEvents($events);
        // $this->closeModal();
        $this->closeWireChatModal();
        $this->closeChatDrawer();
    }

    public static function modalAttributes(): array
    {
        return [
            'closeOnEscape' => true,
            'closeOnEscapeIsForceful' => false,
            'dispatchCloseEvent' => false,
            'destroyOnClose' => false,
            'closeOnClickAway' => true,
        ];

    }

    private function emitModalEvents(array $events): void
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
}

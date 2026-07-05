<?php

namespace Wirechat\Wirechat\Livewire\New;

use Illuminate\Database\Eloquent\Model;
use Wirechat\Wirechat\Http\Resources\WirechatUserResource;
use Wirechat\Wirechat\Livewire\Concerns\HasPanel;
use Wirechat\Wirechat\Livewire\Concerns\ModalComponent;
use Wirechat\Wirechat\Livewire\Concerns\ResolvesPanelSearchResults;
use Wirechat\Wirechat\Livewire\Concerns\Widget;

class Chat extends ModalComponent
{
    use HasPanel;
    use ResolvesPanelSearchResults;
    use Widget;

    public $users = [];

    public $search;

    public static function modalAttributes(): array
    {
        return [
            'closeOnEscape' => true,
            'closeOnEscapeIsForceful' => true,
            'destroyOnClose' => true,
            'closeOnClickAway' => true,
        ];

    }

    /**
     * Search For users to create conversations with
     */
    public function updatedSearch()
    {
        if (blank($this->search)) {
            $this->users = [];
        } else {
            $this->users = WirechatUserResource::collection($this->messageableSearchResultModels())->resolve();
        }
    }

    public function createConversation($id, string $class)
    {
        $model = $this->resolveMessageableSearchResult($id, $class);

        if ($model instanceof Model) {
            $createdConversation = $this->panel()->hasMessageRequests()
                ? auth()->user()->sendMessageRequestTo($model)
                : auth()->user()->createConversationWith($model);

            if ($createdConversation) {

                // close dialog
                $this->closeWirechatModal();

                return $this->navigateToChat($createdConversation->id);

            }
        }
    }

    protected function messageableSearchResultModels()
    {
        return collect($this->panel()->searchUsers($this->search)->collection)
            ->map(fn ($resource) => $resource->resource ?? null)
            ->filter(fn ($model) => $model instanceof Model)
            ->filter(fn (Model $model): bool => auth()->user()->canSendMessageTo($model))
            ->values();
    }

    protected function resolveMessageableSearchResult($id, string $class): ?Model
    {
        $model = $this->resolvePanelSearchResult($id, $class);

        if ($model instanceof Model) {
            abort_unless(auth()->user()->canSendMessageTo($model), 403, 'You are not allowed to send messages to this user.');
        }

        return $model;
    }

    public function mount()
    {

        abort_unless(auth()->check(), 401);
    }

    public function render()
    {

        return view('wirechat::livewire.new.chat');
    }
}

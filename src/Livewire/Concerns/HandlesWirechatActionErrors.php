<?php

namespace Wirechat\Wirechat\Livewire\Concerns;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

trait HandlesWirechatActionErrors
{
    protected ?string $wirechatActionMethod = null;

    public function callHandlesWirechatActionErrors(string $methodName): void
    {
        if ($this->isWirechatGroupComponent()) {
            $this->wirechatActionMethod = $methodName;
        }
    }

    public function exceptionHandlesWirechatActionErrors(Throwable $e, callable $stopPropagation): void
    {
        if (! $this->isWirechatGroupComponent() || $this->wirechatActionMethod === null) {
            return;
        }

        if (! $e instanceof HttpExceptionInterface && ! $e instanceof ModelNotFoundException) {
            return;
        }

        $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 404;
        $message = trim($e->getMessage());

        if ($message === '') {
            $message = match ($status) {
                401 => 'You must be signed in to continue.',
                403 => 'You do not have permission to perform this action.',
                404 => 'This group action is no longer available.',
                405 => 'This action is not available here.',
                410 => 'This invite is no longer active.',
                422 => 'This action cannot be completed.',
                default => 'Something went wrong. Please try again.',
            };
        }

        $this->dispatch('wirechat-toast', type: 'error', message: $message);
        $stopPropagation();
    }

    protected function isWirechatGroupComponent(): bool
    {
        return str_starts_with(static::class, 'Wirechat\\Wirechat\\Livewire\\Chat\\Group\\');
    }
}

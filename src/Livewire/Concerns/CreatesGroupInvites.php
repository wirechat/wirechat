<?php

namespace Wirechat\Wirechat\Livewire\Concerns;

use Illuminate\Database\QueryException;
use Wirechat\Wirechat\Facades\Wirechat;
use Wirechat\Wirechat\Models\Invite;

trait CreatesGroupInvites
{
    protected function createInviteWithUniqueToken($inviteLinks, array $attributes, int $attempts = 5): Invite
    {
        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                $inviteClass = Wirechat::inviteModelClass();

                return $inviteLinks->create(array_merge($attributes, [
                    'token' => $inviteClass::generateToken(),
                ]));
            } catch (QueryException $exception) {
                if ($attempt >= $attempts || ! $this->isDuplicateInviteTokenException($exception)) {
                    throw $exception;
                }
            }
        }

        throw new \RuntimeException('Unable to create a unique invite token.');
    }

    protected function isDuplicateInviteTokenException(QueryException $exception): bool
    {
        return str_contains($exception->getMessage(), 'token')
            && in_array((string) ($exception->errorInfo[0] ?? ''), ['23000', '23505'], true);
    }
}

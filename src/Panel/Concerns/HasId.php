<?php

namespace Wirechat\Wirechat\Panel\Concerns;

use Exception;

trait HasId
{
    protected string $id;

    /**
     * @throws Exception
     */
    public function id(string $id): static
    {
        if (isset($this->id)) {
            throw new Exception("The panel has already been registered with the ID [{$this->id}].");
        }

        $this->id = $id;

        return $this;
    }

    public function getId(): string
    {
        if (! isset($this->id)) {
            throw new Exception('A panel has been registered without an `id()`.');
        }

        return $this->id;
    }
}

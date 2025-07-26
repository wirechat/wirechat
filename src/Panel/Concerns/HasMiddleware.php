<?php

namespace Namu\WireChat\Panel\Concerns;

trait HasMiddleware
{
    /**
     * @var array<string>
     */
    protected array $middleware = [];

    /**
     * @var array<string>
     */
    protected array $authMiddleware = [];


    /**
     * @param  array<string>  $middleware
     */
    public function authMiddleware(array $middleware): static
    {
        $this->authMiddleware = [
            ...$this->authMiddleware,
            ...$middleware,
        ];

//        if ($isPersistent) {
//            $this->persistentMiddleware($middleware);
//        }

        return $this;
    }


    /**
     * @param  array<string>  $middleware
     */
    public function middleware(array $middleware): static
    {
        $this->middleware = [
            ...$this->middleware,
            ...$middleware,
        ];
//
//        if ($isPersistent) {
//            $this->persistentMiddleware($middleware);
//        }

        return $this;
    }

    //todo:find out more about livewire persistant middleware
    //    /**
    //     * @param  array<string>  $middleware
    //     */
    //    public function persistentMiddleware(array $middleware): static
    //    {
    //        $this->livewirePersistentMiddleware = [
    //            ...$this->livewirePersistentMiddleware,
    //            ...$middleware,
    //        ];
    //
    //        return $this;
    //    }

    /**
     * @return array<string>
     */
    public function getMiddleware(): array
    {
        return [
            "panel:{$this->getId()}",
            ...$this->middleware,
        ];
    }

    /**
     * @return array<string>
     */
    public function getAuthMiddleware(): array
    {
        return $this->authMiddleware;
    }

    /**
     * @return array<string>
     */
//    protected function registerLivewirePersistentMiddleware(): void
//    {
//        Livewire::addPersistentMiddleware($this->livewirePersistentMiddleware);
//
//        $this->livewirePersistentMiddleware = [];
//    }
}

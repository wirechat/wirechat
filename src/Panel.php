<?php

namespace Namu\WireChat;

use Closure;
use Illuminate\Support\Arr;

class Panel
{
    protected string $id;
    protected string $path = '';
    protected string $routePrefix = '';
    protected array $middleware = [];
    protected array $features = ['search' => true, 'notifications' => true];
    protected bool|Closure $isDefault = false;

    public static function make(string $id = ''): static
    {
        $panel = new static;
        $panel->id = $id;
        return $panel;
    }


    public function id(string $id): static
    {
        $this->id = $id;
        return $this;
    }
    public function path(string $path): static
    {
        $this->path = $path;
        return $this;
    }


    public function routePrefix(string $prefix): static
    {
        $this->routePrefix = $prefix;
        return $this;
    }

    public function middleware(array $middleware): static
    {
        $this->middleware = $middleware;
        return $this;
    }

    public function features(array $features): static
    {
        $this->features = array_merge($this->features, $features);
        return $this;
    }

    public function default(bool|Closure $condition = true): static
    {
        $this->isDefault = $condition;
        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getRoutePrefix(): string
    {
        return $this->routePrefix;
    }

    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    public function hasFeature(string $feature): bool
    {
        return Arr::get($this->features, $feature, false);
    }

    public function isDefault(): bool
    {
        return $this->evaluate($this->isDefault);
    }

    protected function evaluate($value)
    {
        return $value instanceof Closure ? call_user_func($value, $this) : $value;
    }
}

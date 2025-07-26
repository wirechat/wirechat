<?php

namespace Namu\WireChat\Panel\Concerns;

use Closure;
use Illuminate\Support\Arr;
use Laravel\SerializableClosure\Serializers\Native;

trait HasRoutes
{
    /**
     * @var array<Closure | Native>
     */
    protected array $routes = [];

    protected string | Closure | null $homeUrl = null;

    protected string $path = '';

    public function homeUrl(string | Closure | null $url): static
    {
        $this->homeUrl = $url;
        return $this;
    }

    public function path(string $path): static
    {
        $this->path = $path;
        return $this;
    }

    public function routes(?Closure $routes): static
    {
        if ($routes) {
            $this->routes[] = $routes;
        }
        return $this;
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }

    public function getHomeUrl(): ?string
    {
        return $this->evaluate($this->homeUrl);
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getRoutePrefix(): string
    {
        return $this->path ? trim($this->path, '/') : '';
    }

    public function generateRouteName(string $name): string
    {
        return "wirechat.{$this->getId()}.{$name}";
    }

    public function route(string $name, array $parameters = [], bool $absolute = true): string
    {
        return route($this->generateRouteName($name), $parameters, $absolute);
    }
}

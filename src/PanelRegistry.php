<?php

namespace Namu\WireChat;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use ReflectionClass;
use Namu\WireChat\Exceptions\NoPanelProvidedException;

class PanelRegistry
{
    protected array $panels = [];
    protected ?Panel $defaultPanel = null;

    /**
     * @throws \Exception
     */
    public function register(Panel $panel): void
    {
        $id = $panel->getId();

        if (isset($this->panels[$id])) {
            throw new \Exception("Panel with ID '{$id}' already exists.");
        }

        $this->panels[$id] = $panel;

        if ($panel->isDefault()) {
            if ($this->defaultPanel !== null) {
                throw new \Exception("Only one panel can be marked as default.");
            }
            $this->defaultPanel = $panel;
        }

        Log::info('Panel registered', ['id' => $id]);
    }

    public function getProvidersPath(): string
    {
        return app_path('Providers/WireChat');
    }

    public function autoDiscover(): void
    {
        $directory = $this->getProvidersPath();

        if (! File::isDirectory($directory)) {
            Log::warning('WireChat providers directory not found', ['directory' => $directory]);
            return;
        }

        $files = File::files($directory);

        foreach ($files as $file) {
            $className = str_replace('.php', '', $file->getFilename());
            $fullClass = 'App\\Providers\\WireChat\\' . $className;

            if (! class_exists($fullClass)) {
                continue;
            }

            $reflection = new ReflectionClass($fullClass);
            if ($reflection->isSubclassOf(PanelProvider::class) && $reflection->hasMethod('panel')) {
                $method = $reflection->getMethod('panel');
                if ($method->isPublic() && !$method->isStatic()) {
                    $provider = $reflection->newInstanceWithoutConstructor();
                    $panel = $method->invoke($provider, Panel::make());
                    $this->register($panel);
                }
            }
        }
    }

    public function getDefault(): ?Panel
    {
        if ($this->defaultPanel === null) {
            throw new NoPanelProvidedException('No default panel has been set.');
        }
        return $this->defaultPanel;
    }

    public function get(string $idOrClass): ?Panel
    {
        if (isset($this->panels[$idOrClass])) {
            return $this->panels[$idOrClass];
        }

        $panel = $this->resolvePanelFromProvider($idOrClass);
        if ($panel) {
            return $panel;
        }

        return $this->defaultPanel;
    }

    protected function resolvePanelFromProvider(string $providerClass): ?Panel
    {
        if (! class_exists($providerClass)) {
            return null;
        }

        $reflection = new ReflectionClass($providerClass);
        if ($reflection->isSubclassOf(PanelProvider::class) && $reflection->hasMethod('panel')) {
            $method = $reflection->getMethod('panel');
            if ($method->isPublic() && !$method->isStatic()) {
                $provider = $reflection->newInstanceWithoutConstructor();
                $panel = $method->invoke($provider, Panel::make());
                return $panel;
            }
        }

        return null;
    }

    public function all(): array
    {
        return $this->panels;
    }
}

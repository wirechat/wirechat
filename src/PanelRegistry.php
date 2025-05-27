<?php

namespace Namu\WireChat;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use ReflectionClass;

class PanelRegistry
{
    protected array $panels = [];
    protected ?Panel $defaultPanel = null;

    public function register(Panel $panel): void
    {
        $id = $panel->getId();

        if (isset($this->panels[$id])) {
            throw new \Exception("Panel with ID '{$id}' already exists.");
        }

        $this->panels[$id] = $panel;

        if ($panel->isDefault() && $this->defaultPanel === null) {
            $this->defaultPanel = $panel;
        }

        Log::info('Panel registered', ['id' => $id]);
    }

    public function autoDiscover(): void
    {
        $directory = app_path('Providers/WireChat');

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
                    // Create a temporary instance without calling constructor
                    $provider = $reflection->newInstanceWithoutConstructor();
                    $panel = $method->invoke($provider, Panel::make());
                    $this->register($panel);
                }
            }
        }
    }

    public function get(string $id): ?Panel
    {
        return $this->panels[$id] ?? $this->defaultPanel;
    }

    public function getDefault(): ?Panel
    {
        return $this->defaultPanel;
    }

    public function all(): array
    {
        return $this->panels;
    }
}

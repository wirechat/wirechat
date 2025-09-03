<?php

namespace Wirechat\Wirechat;

use Illuminate\Support\Facades\Log;
use ReflectionClass;
use Wirechat\Wirechat\Exceptions\NoPanelProvidedException;
use Wirechat\Wirechat\Facades\WirechatColor;

class PanelRegistry
{
    protected array $panels = [];

    protected ?Panel $defaultPanel = null;

    protected ?Panel $currentPanel = null;

    public function __construct()
    {
        Log::info('PanelRegistry instance created', ['instance_id' => spl_object_id($this)]);
    }

    /**
     * Registers a panel, skipping duplicates without throwing an error.
     */
    public function register(Panel $panel): void
    {
        $id = $panel->getId();

        // Ensure ID is not null or empty
        if (empty($id)) {
            Log::error('Attempted to register panel with null or empty ID', ['panel' => get_class($panel)]);
            throw new \Exception('Panel ID cannot be null or empty.');
        }

        // Skip if panel ID already exists
        if (isset($this->panels[$id])) {
            Log::warning('Panel with ID already registered, skipping', [
                'id' => $id,
                'instance_id' => spl_object_id($this),
                'panels_count' => count($this->panels),
            ]);

            return;
        }

        $this->panels[$id] = $panel;

        if ($panel->isDefault()) {
            if ($this->defaultPanel !== null) {
                throw new \Exception('Only one panel can be marked as default.');
            }
            $this->defaultPanel = $panel;
        }

        // Register panel-specific settings (e.g., colors)
        $panel->register();

        Log::info('Panel registered successfully', [
            'id' => $id,
            'instance_id' => spl_object_id($this),
            'panels_count' => count($this->panels),
            'panel_ids' => array_keys($this->panels),
        ]);
    }

    public function setCurrent(string $panelId): void
    {
        $this->currentPanel = $this->panels[$panelId] ?? $this->defaultPanel;

        if ($this->currentPanel) {
            WirechatColor::register($this->currentPanel->getColors());
            Log::info('Current panel set', [
                'panelId' => $panelId,
                'instance_id' => spl_object_id($this),
                'current_panel_id' => $this->currentPanel->getId(),
            ]);
        } else {
            Log::warning('No panel found for setCurrent', [
                'panelId' => $panelId,
                'instance_id' => spl_object_id($this),
            ]);
        }
    }

    public function getCurrent(): ?Panel
    {
        Log::debug('Getting current panel', [
            'instance_id' => spl_object_id($this),
            'current_panel_id' => $this->currentPanel?->getId(),
        ]);

        return $this->currentPanel ?? $this->defaultPanel;
    }

    public function getDefault(): ?Panel
    {
        if ($this->defaultPanel === null) {
            Log::error('No default panel set', ['instance_id' => spl_object_id($this)]);
            throw new NoPanelProvidedException('No default panel has been set.');
        }
        Log::info('Returning default panel', [
            'id' => $this->defaultPanel->getId(),
            'instance_id' => spl_object_id($this),
        ]);

        return $this->defaultPanel;
    }

    /**
     * Retrieves a panel by its ID or provider class.
     */
    public function get(string $idOrClass): ?Panel
    {
        Log::debug('Attempting to get panel', [
            'idOrClass' => $idOrClass,
            'instance_id' => spl_object_id($this),
            'panels_count' => count($this->panels),
            'panel_ids' => array_keys($this->panels),
        ]);

        if (isset($this->panels[$idOrClass])) {
            Log::info('Panel found by ID', ['id' => $idOrClass, 'instance_id' => spl_object_id($this)]);

            return $this->panels[$idOrClass];
        }

        Log::warning('Panel not found by ID, attempting to resolve by provider class', [
            'idOrClass' => $idOrClass,
            'instance_id' => spl_object_id($this),
        ]);
        $panel = $this->resolvePanelFromProvider($idOrClass);
        if ($panel) {
            $this->register($panel);

            return $panel;
        }

        if ($this->defaultPanel === null) {
            Log::error('No default panel set', ['idOrClass' => $idOrClass, 'instance_id' => spl_object_id($this)]);
            throw new NoPanelProvidedException('No default panel has been set.');
        }

        Log::info('Returning default panel', [
            'id' => $this->defaultPanel->getId(),
            'instance_id' => spl_object_id($this),
        ]);

        return $this->defaultPanel;
    }

    protected function resolvePanelFromProvider(string $providerClass): ?Panel
    {
        if (! class_exists($providerClass)) {
            Log::warning('Provider class does not exist', [
                'class' => $providerClass,
                'instance_id' => spl_object_id($this),
            ]);

            return null;
        }

        $reflection = new ReflectionClass($providerClass);
        if ($reflection->isSubclassOf(PanelProvider::class) && $reflection->hasMethod('panel')) {
            $method = $reflection->getMethod('panel');
            if ($method->isPublic() && ! $method->isStatic()) {
                $provider = $reflection->newInstanceWithoutConstructor();
                $panel = $method->invoke($provider, Panel::make());

                // Check if panel ID already exists to avoid duplicate registration
                if (isset($this->panels[$panel->getId()])) {
                    Log::info('Panel already exists in registry, returning existing panel', [
                        'id' => $panel->getId(),
                        'instance_id' => spl_object_id($this),
                    ]);

                    return $this->panels[$panel->getId()];
                }

                return $panel;
            }
        }

        Log::warning('Provider class is not a valid PanelProvider or lacks a valid panel method', [
            'class' => $providerClass,
            'instance_id' => spl_object_id($this),
        ]);

        return null;
    }

    public function all(): array
    {
        Log::debug('Returning all panels', [
            'panels_count' => count($this->panels),
            'panel_ids' => array_keys($this->panels),
            'instance_id' => spl_object_id($this),
        ]);

        return $this->panels;
    }
}

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
      //  Log::info('PanelRegistry instance created', ['instance_id' => spl_object_id($this)]);
    }

    /**
     * Registers a panel, skipping duplicates without throwing an error.
     */
    public function register(Panel $panel): void
    {
        $id = $panel->getId();

        // Ensure ID is not null or empty
        if (empty($id)) {
            throw new \Exception('Panel ID cannot be null or empty.');
        }

        // Skip if panel ID already exists
        if (isset($this->panels[$id])) {
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
    }

    public function setCurrent(string $panelId): void
    {
        $this->currentPanel = $this->panels[$panelId] ?? $this->defaultPanel;

        if ($this->currentPanel) {
            WirechatColor::register($this->currentPanel->getColors());
        }
    }

    public function getCurrent(): ?Panel
    {
//        Log::debug('Getting current panel', [
//            'instance_id' => spl_object_id($this),
//            'current_panel_id' => $this->currentPanel?->getId(),
//        ]);

        return $this->currentPanel ?? $this->defaultPanel;
    }

    public function getDefault(): ?Panel
    {
        if ($this->defaultPanel === null) {
            throw new NoPanelProvidedException(
                'No default panel has been set. Please call ->default() on at least one panel in your Wirechat PanelProvider.'
            );
        }
        return $this->defaultPanel;
    }

    /**
     * Retrieves a panel by its ID or provider class.
     */
    public function get(string $idOrClass): ?Panel
    {

        if (isset($this->panels[$idOrClass])) {
            return $this->panels[$idOrClass];
        }


        $panel = $this->resolvePanelFromProvider($idOrClass);
        if ($panel) {
            $this->register($panel);

            return $panel;
        }

        if ($this->defaultPanel === null) {
            throw new NoPanelProvidedException('No default panel has been set.');
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
            if ($method->isPublic() && ! $method->isStatic()) {
                $provider = $reflection->newInstanceWithoutConstructor();
                $panel = $method->invoke($provider, Panel::make());

                // Check if panel ID already exists to avoid duplicate registration
                if (isset($this->panels[$panel->getId()])) {
                    return $this->panels[$panel->getId()];
                }

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

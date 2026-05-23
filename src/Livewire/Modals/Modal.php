<?php

namespace Wirechat\Wirechat\Livewire\Modals;

use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Reflector;
use Livewire\Component;
use Livewire\Mechanisms\ComponentRegistry;
use Wirechat\Wirechat\Livewire\Concerns\ModalComponent;

class Modal extends Component
{
    public ?string $activeComponent;

    public array $components = [];

    public function getListeners(): array
    {
        return [
            'openWirechatModal',
            'destroyWirechatModal',
        ];
    }

    public function resetState(): void
    {
        $this->components = [];
        $this->activeComponent = null;
    }

    public function openWirechatModal($component, $arguments = [], $modalAttributes = []): void
    {
        $componentClass = $this->resolveComponentClass($component);
        $id = md5($component.serialize($arguments));

        $arguments = collect($arguments)
            ->merge($this->resolveComponentProps($arguments, new $componentClass))
            ->all();

        $this->components[$id] = [
            'name' => $this->getComponentName($componentClass),
            'arguments' => $arguments,
            'modalAttributes' => array_merge(
                $componentClass::modalAttributes(), // Fetch reusable modal attributes
                $modalAttributes // Allow custom overrides
            ),
        ];

        $this->activeComponent = $id;

        $this->dispatch('activeWirechatModalComponentChanged', id: $id);
    }

    public function resolveComponentProps(array $attributes, Component $component): Collection
    {
        return $this->getPublicPropertyTypes($component)
            ->intersectByKeys($attributes)
            ->map(function ($className, $propName) use ($attributes) {
                $resolved = $this->resolveParameter($attributes, $propName, $className);

                return $resolved;
            });
    }

    protected function resolveParameter($attributes, $parameterName, $parameterClassName)
    {
        $parameterValue = $attributes[$parameterName];

        if ($parameterValue instanceof UrlRoutable) {
            return $parameterValue;
        }

        if (enum_exists($parameterClassName)) {
            /* @phpstan-ignore staticMethod.notFound */
            $enum = $parameterClassName::tryFrom($parameterValue);

            if ($enum !== null) {
                return $enum;
            }
        }

        $instance = app()->make($parameterClassName);

        if (! $model = $instance->resolveRouteBinding($parameterValue)) {
            throw (new ModelNotFoundException)->setModel(get_class($instance), [$parameterValue]);
        }

        return $model;
    }

    public function getPublicPropertyTypes($component): Collection
    {
        return collect($component->all())
            ->map(function ($value, $name) use ($component) {
                /* @phpstan-ignore argument.type */
                return Reflector::getParameterClassName(new \ReflectionProperty($component, $name));
            })
            ->filter();
    }

    protected function resolveComponentClass(string $component): string
    {
        if (class_exists(ComponentRegistry::class)
            && app()->bound(ComponentRegistry::class)) {
            $componentClass = app(ComponentRegistry::class)->getClass($component);
        } else {
            $componentClass = app('livewire.finder')->resolveClassComponentClassName($component);
        }

        abort_unless(is_subclass_of($componentClass, ModalComponent::class), 403);

        return $componentClass;
    }

    protected function getComponentName(string $class): string
    {
        if (class_exists(ComponentRegistry::class)
            && app()->bound(ComponentRegistry::class)) {
            return app(ComponentRegistry::class)->getName($class);
        }

        return app('livewire.finder')->normalizeName($class);
    }

    public function destroyWirechatModal($id): void
    {
        unset($this->components[$id]);
    }

    public function render()
    {
        return view('wirechat::livewire.modals.modal');
    }
}

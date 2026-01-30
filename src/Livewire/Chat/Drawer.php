<?php

namespace Wirechat\Wirechat\Livewire\Chat;

use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Reflector;
use Livewire\Component;

class Drawer extends Component
{
    public ?string $activeDrawerComponent;

    public array $drawerComponents = [];

    public function resetState(): void
    {
        $this->drawerComponents = [];
        $this->activeDrawerComponent = null;
    }

    public static function modalAttributes(): array
    {
        return [
            'closeOnEscape' => true,
            'closeOnEscapeIsForceful' => false,
            'dispatchCloseEvent' => true,
            'destroyOnClose' => true,
        ];
    }

    public function openChatDrawer($component, $arguments = [], $modalAttributes = []): void
    {

        $componentClass = $this->resolveComponentClass($component);
        $id = md5($component.serialize($arguments));

        $arguments = collect($arguments)
            ->merge($this->resolveComponentProps($arguments, new $componentClass))
            ->all();

        $this->drawerComponents[$id] = [
            'name' => $this->getComponentName($componentClass),
            'attributes' => $arguments, // Deprecated
            'arguments' => $arguments,
            'modalAttributes' => array_merge(
                $componentClass::modalAttributes(), // Fetch reusable modal attributes
                $modalAttributes // Allow custom overrides
            ),
        ];

        $this->activeDrawerComponent = $id;

        /* ! Changed listener name to activeChatDrawerComponentChanged to not interfer with main modal */
        $this->dispatch('activeChatDrawerComponentChanged', id: $id);

    }

    protected function resolveComponentClass(string $component): string
    {
        if (class_exists(\Livewire\Mechanisms\ComponentRegistry::class)
            && app()->bound(\Livewire\Mechanisms\ComponentRegistry::class)) {
            return app(\Livewire\Mechanisms\ComponentRegistry::class)->getClass($component);
        }

        return app('livewire.finder')->resolveClassComponentClassName($component);
    }

    protected function getComponentName(string $class): string
    {
        if (class_exists(\Livewire\Mechanisms\ComponentRegistry::class)
            && app()->bound(\Livewire\Mechanisms\ComponentRegistry::class)) {
            return app(\Livewire\Mechanisms\ComponentRegistry::class)->getName($class);
        }

        return app('livewire.finder')->normalizeName($class);
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
        $types = collect($component->all())
            ->map(function ($value, $name) use ($component) {
                /* @phpstan-ignore argument.type */
                return Reflector::getParameterClassName(new \ReflectionProperty($component, $name));
            })
            ->filter();

        return $types;

    }

    public function destroyChatDrawer($id): void
    {
        unset($this->drawerComponents[$id]);
    }

    public function getListeners(): array
    {
        return [
            'openChatDrawer',
            'destroyChatDrawer',
        ];
    }

    public function render()
    {
        return view('wirechat::livewire.chat.drawer');
    }
}

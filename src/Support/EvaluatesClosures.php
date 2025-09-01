<?php

namespace Wirechat\Wirechat\Support;

use Closure;
use Illuminate\Contracts\Container\BindingResolutionException;
use ReflectionFunction;
use ReflectionNamedType;
use ReflectionParameter;

/**
 * Trait EvaluatesClosures
 *
 * Provides functionality to evaluate values that may be Closures, resolving dependencies
 * for Closure parameters using reflection and Laravel's container.
 */
trait EvaluatesClosures
{
    /**
     * Evaluates a value, executing it if it's a Closure, with support for dependency injection.
     *
     * @template T
     *
     * @param  T | callable(): T  $value  The value to evaluate, which may be a Closure or static value.
     * @param  array<string, mixed>  $namedInjections  Explicitly provided named parameters for the Closure.
     * @param  array<string, mixed>  $typedInjections  Explicitly provided parameters for specific types.
     * @return T The evaluated value, either the result of the Closure or the original value.
     *
     * @throws BindingResolutionException If a required Closure parameter cannot be resolved.
     */
    public function evaluate(mixed $value, array $namedInjections = [], array $typedInjections = []): mixed
    {
        if (! $value instanceof Closure) {
            return $value;
        }

        $dependencies = [];

        foreach ((new ReflectionFunction($value))->getParameters() as $parameter) {
            $dependencies[] = $this->resolveClosureParameter($parameter, $namedInjections, $typedInjections);
        }

        return $value(...$dependencies);
    }

    /**
     * Resolves a single Closure parameter using named injections, typed injections, or Laravel's container.
     *
     * @param  ReflectionParameter  $parameter  The parameter to resolve.
     * @param  array<string, mixed>  $namedInjections  Explicitly provided named parameters.
     * @param  array<string, mixed>  $typedInjections  Explicitly provided parameters for specific types.
     * @return mixed The resolved parameter value.
     *
     * @throws BindingResolutionException If the parameter cannot be resolved and is not optional.
     */
    protected function resolveClosureParameter(ReflectionParameter $parameter, array $namedInjections, array $typedInjections): mixed
    {
        $parameterName = $parameter->getName();

        // Check for explicitly provided named injections
        if (array_key_exists($parameterName, $namedInjections)) {
            return value($namedInjections[$parameterName]);
        }

        // Resolve typed parameters
        $typedParameterClassName = $this->getTypedParameterClassName($parameter);

        if (filled($typedParameterClassName) && array_key_exists($typedParameterClassName, $typedInjections)) {
            return value($typedInjections[$typedParameterClassName]);
        }

        // Inject the current instance for parameters named 'panel' or typed as the current class
        if (
            ($parameterName === 'panel') ||
            ($typedParameterClassName === static::class)
        ) {
            return $this;
        }

        // Resolve via Laravel's container for typed parameters
        if (filled($typedParameterClassName)) {
            try {
                return app()->make($typedParameterClassName);
            } catch (BindingResolutionException $e) {
                // Rethrow with a more specific message if needed
                throw new BindingResolutionException("Failed to resolve [{$typedParameterClassName}] for Closure parameter.", 0, $e);
            }
        }

        // Use default value if available
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        // Return null for optional parameters
        if ($parameter->isOptional()) {
            return null;
        }

        // Throw an exception for unresolvable required parameters
        $staticClass = static::class;
        throw new BindingResolutionException("Unresolvable Closure parameter [\${$parameterName}] for [{$staticClass}].");
    }

    /**
     * Gets the class name of a typed parameter, handling 'self' and 'parent' keywords.
     *
     * @param  ReflectionParameter  $parameter  The parameter to inspect.
     * @return string|null The class name of the parameter type, or null if not a class type.
     */
    protected function getTypedParameterClassName(ReflectionParameter $parameter): ?string
    {
        $type = $parameter->getType();

        if (! $type instanceof ReflectionNamedType) {
            return null;
        }

        if ($type->isBuiltin()) {
            return null;
        }

        $name = $type->getName();
        $class = $parameter->getDeclaringClass();

        if (blank($class)) {
            return $name;
        }

        if ($name === 'self') {
            return $class->getName();
        }

        if ($name === 'parent' && ($parent = $class->getParentClass())) {
            return $parent->getName();
        }

        return $name;
    }
}

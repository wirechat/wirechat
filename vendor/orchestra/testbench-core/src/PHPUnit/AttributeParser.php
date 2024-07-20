<?php

namespace Orchestra\Testbench\PHPUnit;

use Orchestra\Testbench\Contracts\Attributes\Resolvable as ResolvableContract;
use Orchestra\Testbench\Contracts\Attributes\TestingFeature;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;

/**
 * @internal
 *
 * @phpstan-type TTestingFeature \Orchestra\Testbench\Contracts\Attributes\TestingFeature
 * @phpstan-type TAttributes TTestingFeature|\Orchestra\Testbench\Contracts\Attributes\Resolvable
 */
class AttributeParser
{
    /**
     * Parse attribute for class.
     *
     * @param  class-string  $className
     * @return array<int, array{key: class-string, instance: object}>
     *
     * @phpstan-return array<int, array{key: class-string<TTestingFeature>, instance: TTestingFeature}>
     */
    public static function forClass(string $className): array
    {
        $attributes = [];

        foreach ((new ReflectionClass($className))->getAttributes() as $attribute) {
            if (! static::validAttribute($attribute->getName())) {
                continue;
            }

            [$name, $instance] = static::resolveAttribute($attribute);

            if (! \is_null($name) && ! \is_null($instance)) {
                array_push($attributes, ['key' => $name, 'instance' => $instance]);
            }
        }

        return $attributes;
    }

    /**
     * Parse attribute for method.
     *
     * @param  class-string  $className
     * @param  string  $methodName
     * @return array<int, array{key: class-string, instance: object}>
     *
     * @phpstan-return array<int, array{key: class-string<TTestingFeature>, instance: TTestingFeature}>
     */
    public static function forMethod(string $className, string $methodName): array
    {
        $attributes = [];

        foreach ((new ReflectionMethod($className, $methodName))->getAttributes() as $attribute) {
            if (! static::validAttribute($attribute->getName())) {
                continue;
            }

            [$name, $instance] = static::resolveAttribute($attribute);

            if (! \is_null($name) && ! \is_null($instance)) {
                array_push($attributes, ['key' => $name, 'instance' => $instance]);
            }
        }

        return $attributes;
    }

    /**
     * Validate given attribute.
     *
     * @param  class-string|object  $class
     * @return bool
     */
    public static function validAttribute($class): bool
    {
        if (\is_string($class) && ! class_exists($class)) {
            return false;
        }

        $implements = class_implements($class);

        return isset($implements[TestingFeature::class])
            || isset($implements[ResolvableContract::class]);
    }

    /**
     * Resolve given attribute.
     *
     * @param  \ReflectionAttribute  $attribute
     * @return array{0: class-string, 1: object|null}
     *
     * @phpstan-return array{0: class-string<TTestingFeature>|null, 1: TTestingFeature|null}
     */
    protected static function resolveAttribute(ReflectionAttribute $attribute): array
    {
        return rescue(function () use ($attribute) {
            /** @var TTestingFeature|null $instance */
            $instance = isset(class_implements($attribute->getName())[ResolvableContract::class])
                ? transform($attribute->newInstance(), static fn (ResolvableContract $instance) => $instance->resolve()) /** @phpstan-ignore argument.type */
                : $attribute->newInstance();

            if (\is_null($instance)) {
                return [null, null];
            }

            /** @var class-string<TTestingFeature> $name */
            $name = \get_class($instance);

            return [$name, $instance];
        }, [null, null], false);
    }
}

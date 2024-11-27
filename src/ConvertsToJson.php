<?php

namespace LlewellynKevin\JsonObjects;

use ArrayObject;
use Exception;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;
use stdClass;

// TOOD: Add support for enum values on classes and try to autoconvert
// TODO: Add some tests for all these use cases
trait ConvertsToJson
{
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    public static function fromJson(string $json): static
    {
        return static::fromArray(json_decode($json, true));
    }

    public function toArray(): array
    {
        $output = [];
        $reflection = new ReflectionClass($this);
        foreach ($reflection->getProperties() as $property) {
            $attributes = $property->getAttributes(JsonAttribute::class);
            if (empty($attributes)) {
                continue;
            }

            $propName = $property->getName();
            $value = $this->$propName;
            if (is_null($value)) {
                continue;
            }
            $serializedValue = $this->serializeData($propName, $value);

            /** @var JsonAttribute */
            $jsonAttribute = $attributes[0]->newInstance();
            array_dot_set($output, explode('|', $jsonAttribute->label)[0], $serializedValue);
        }
        return $output;
    }

    public static function fromArray(array $data): static
    {
        $obj = static::nilInstance();
        $map = static::jsonLabelToPropMap();

        foreach ($map as $jsonLabels => $propData) {
            foreach (explode('|', $jsonLabels) as $jsonLabel) {
                if (is_null(array_dot_get($data, $jsonLabel))) {
                    continue;
                }

                [$propertyName, $className, $arrayClass] = $propData;
                if (!is_null($arrayClass)) {
                    $propertyVal = array_dot_get($data, $jsonLabel);
                    if (!is_array($propertyVal)) {
                        throw new Exception("Element at key '$jsonLabel' expects array of $arrayClass. Did not get array. Got '$propertyVal'.");
                    }
                    $obj->$propertyName = [];
                    foreach ($propertyVal as $subObject) {
                        $obj->$propertyName[] = $arrayClass::fromArray($subObject);
                    }
                } else if (!$className || !is_array(array_dot_get($data, $jsonLabel))) {
                    $obj->$propertyName = array_dot_get($data, $jsonLabel);
                } else {
                    $obj->$propertyName = $className::fromArray(array_dot_get($data, $jsonLabel));
                }
            }
        }

        return $obj;
    }

    public static function fromArrayObject(ArrayObject $data): static
    {
        return static::fromArray((array) $data);
    }

    public static function nilInstance(): static
    {
        $args = [];
        $reflection = new ReflectionClass(static::class);
        $properties = $reflection->getProperties();

        foreach ($properties as $property) {
            $type = $property->getType();
            if (is_null($type)) {
                $args[] = null;
                continue;
            }

            if ($type instanceof ReflectionUnionType) {
                $good = false;
                foreach ($type->getTypes() as $type) {
                    [$nilVal, $isValid] = static::getNilForType($type);
                    if ($isValid && !$good) {
                        $args[] = $nilVal;
                        $good = true;
                    }
                }
                if (!$good) {
                    throw new Exception('Typed Jsonable attributes must only be builtins or Jsonable themselves');
                } else {
                    continue;
                }
            }

            [$nilVal, $isValid] = static::getNilForType($type);
            if (!$isValid) {
                throw new Exception('Typed Jsonable attributes must only be builtins or Jsonable themselves');
            }
            $args[] = $nilVal;
        }

        return new static(...$args);
    }

    /** @return array<mixed, bool> */
    protected static function getNilForType(ReflectionNamedType $type): array
    {
        if ($type->allowsNull()) {
            return [null, true];
        }

        if ($type->isBuiltin()) {
            return [match (strtolower($type->getName())) {
                'string' => '',
                'int' => 0,
                'float' => 0,
                'bool' => false,
                'array' => [],
                'object' => new stdClass,
                'null' => null,
            }, true];
        }

        $className = $type->getName();
        $interfaces = class_implements($className);
        if (empty($interfaces) || !$interfaces || !array_key_exists(Jsonable::class, $interfaces)) {
            return [null, false];
        }
        return [$className::nilInstance(), true];
    }

    protected function serializeData(string $propertyName, mixed $value)
    {
        return match (true) {
            is_scalar($value) => $value,
            is_array($value) => array_map(fn ($item) => $this->serializeData($propertyName, $item), $value),
            $value instanceof Jsonable => $value->toArray(),
            default => throw new Exception("Cannot serialize property '$propertyName' into array, must be primitive or Jsonable"),
        };
    }

    protected static function jsonLabelToPropMap(): array
    {
        $output = [];
        $reflection = new ReflectionClass(static::class);
        $properties = $reflection->getProperties();

        foreach ($properties as $property) {
            $attributes = $property->getAttributes(JsonAttribute::class);
            if (empty($attributes)) {
                continue;
            }
            $jsonable = static::typeIsJsonable($property->getType());
            $jsonAttribute = $attributes[0];
            /** @var JsonAttribute */
            $jsonAttributeInstance = $jsonAttribute->newInstance();
            $output[$jsonAttributeInstance->label] = [
                $property->getName(),
                $jsonable,
                $jsonAttributeInstance->arrayElements,
            ];
        }
        return $output;
    }

    protected static function typeIsJsonable(ReflectionType $type): string|bool
    {
        if ($type instanceof ReflectionUnionType) {
            foreach ($type->getTypes() as $simpleType) {
                if ($simpleType->isBuiltin()) {
                    continue;
                }

                if (
                    array_key_exists(
                        Jsonable::class,
                        class_implements($simpleType->getName()),
                    )
                ) {
                    return $simpleType->getName();
                }
            }
            return false;
        }

        /** @var ReflectionNamedType */
        $type = $type;
        if ($type->isBuiltin()) {
            return false;
        }

        return array_key_exists(
            Jsonable::class,
            class_implements($type->getName()),
        ) ? $type->getName() : false;
    }
}

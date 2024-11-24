<?php

namespace LlewellynKevin\JsonObjects;

use Attribute;
use Exception;

#[Attribute]
class JsonAttribute
{
    // TODO: Make this use the property name by default if no label is provided
    public function __construct(
        public string $label,
        public ?string $arrayElements = null,
    ) {
        $this->checkArrayClass($arrayElements);
    }

    private function checkArrayClass(?string $arrayElements)
    {
        if (is_null($arrayElements)) {
            return;
        }

        if (!class_exists($arrayElements)) {
            throw new Exception("JsonAttribute argument arrayElements be a valid fqn.");
        }

        if (!class_implements($arrayElements, Jsonable::class)) {
            throw new Exception("JsonAttribute argument arrayElements field must implement Jsonable.");
        }
    }
}

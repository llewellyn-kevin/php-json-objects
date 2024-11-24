<?php

namespace LlewellynKevin\JsonObjects;

interface Jsonable
{
    public function toJson(): string;

    public function fromJson(): self;

    public function toArray(): array;

    public static function fromArray(array $data): self;
}

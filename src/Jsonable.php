<?php

namespace LlewellynKevin\JsonObjects;

interface Jsonable
{
    public function toJson(): string;

    public static function fromJson(string $data): self;

    public function toArray(): array;

    public static function fromArray(array $data): self;
}

<?php

namespace LlewellynKevin\JsonObjects;

use InvalidArgumentException;
use LlewellynKevin\JsonObjecs\Exceptions\InvalidNestedClass;

it('works with basic configuration', function () {
    $test = new class
    {
        use ConvertsToJson;
        #[JsonAttribute('test')]
        public string $test;
    };
    $class = $test::class;
    $out = $class::fromArray(['test' => 'foo']);
    expect($out->test)->toBe('foo');
});

it('throws an exception on a fake class', function () {
    $test = new class
    {
        use ConvertsToJson;
        #[JsonAttribute('test', 'nonclass')]
        public string $test;
    };
    $test::fromArray(['test' => 'foo']);
})->throws(InvalidArgumentException::class);

it('throws an exception on nonjsonable class', function () {
    $test = new class
    {
        use ConvertsToJson;
        #[JsonAttribute('test', BadNested::class)]
        public array $test;
    };

    $test::fromArray(['test' => [
        ['number' => 1],
        ['number' => 2],
    ]]);
})->throws(InvalidNestedClass::class);

it('makes array of subobjects', function () {
    $test = new class implements Jsonable
    {
        use ConvertsToJson;
        #[JsonAttribute('objects', GoodNested::class)]
        public array $objects;
    };

    $output = $test::fromArray(['objects' => [
        ['number' => 1],
        ['number' => 2],
    ]]);

    expect($output->objects)->toBeArray();
    expect($output->objects[0])->toBeInstanceOf(GoodNested::class)
        ->toHaveProperty('number', 1);
    expect($output->objects[1])->toBeInstanceOf(GoodNested::class)
        ->toHaveProperty('number', 2);
});

class BadNested
{
    use ConvertsToJson;
    #[JsonAttribute('number')]
    public int $number;
};

class GoodNested implements Jsonable
{
    use ConvertsToJson;
    #[JsonAttribute('number')]
    public int $number;
};

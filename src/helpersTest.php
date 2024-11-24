<?php

it('gets values from arrays', function () {
    expect(array_dot_get(['foo' => 'bar'], 'foo'))->toBe("bar");
});

it('returns null by default', function () {
    expect(array_dot_get([], 'foo'))->toBeNull();
});

it('returns specified default', function () {
    expect(array_dot_get([], 'foo', 'bar'))->toBe('bar');
});

it('gets nested values', function () {
    expect(array_dot_get(['foo' => ['bar' => ['baz' => 'final']]], 'foo.bar.baz'))->toBe('final');
});

it('gets array index values', function () {
    expect(array_dot_get(['foo' => ['one', 2, 'three']], 'foo.1'))->toBe(2);
    expect(array_dot_get(['foo' => ['one', 2, 'three']], 'foo.2'))->toBe('three');
});

it('sets an array value', function () {
    $array = ['foo' => 'bar'];
    $expected = ['foo' => 'new'];

    array_dot_set($array, 'foo', 'new');

    expect($array)->toBe($expected);
});

it('sets a new value', function() {
    $array = [];
    $expected = ['foo' => 'bar'];

    array_dot_set($array, 'foo', 'bar');

    expect($array)->toBe($expected);
});

it('sets nested values', function() {
    $array = ['foo' => ['bar' => ['baz' => 'buzz']]];
    $expected = ['foo' => ['bar' => ['baz' => 3]]];

    array_dot_set($array, 'foo.bar.baz', 3);

    expect($array)->toBe($expected);
});

it('sets nested values that don\'t exist', function() {
    $array = ['foo' => ['bar' => ['baz' => 'buzz']]];
    $expected = ['foo' => ['bar' => ['baz' => 'buzz', 'test' => 3]]];

    array_dot_set($array, 'foo.bar.test', 3);

    expect($array)->toBe($expected);
});

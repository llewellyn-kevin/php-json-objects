<?php

it('gets values from arrays', function () {
    expect(data_get(['foo' => 'bar'], 'foo'))->toBe("bar");
});

it('returns null by default', function () {
    expect(data_get([], 'foo'))->toBeNull();
});

it('returns specified default', function () {
    expect(data_get([], 'foo', 'bar'))->toBe('bar');
});

it('gets nested values', function () {
    expect(data_get(['foo' => ['bar' => ['baz' => 'final']]], 'foo.bar.baz'))->toBe('final');
});

it('gets array index values', function () {
    expect(data_get(['foo' => ['one', 2, 'three']], 'foo.1'))->toBe(2);
    expect(data_get(['foo' => ['one', 2, 'three']], 'foo.2'))->toBe('three');
});

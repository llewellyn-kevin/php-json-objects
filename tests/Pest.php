<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

// pest()->extend(Tests\TestCase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toFulfill', function (string $interface) {
    $methodMap = function (ReflectionMethod $method): string {
        return methodToString($method);
    };

    $interfaceMethods = array_map($methodMap, (new ReflectionClass($interface))->getMethods());
    $concreteMethods = array_map($methodMap, (new ReflectionClass($this->value))->getMethods());

    $missingImplementations = [];
    foreach ($interfaceMethods as $interfaceMethod) {
        if (array_search($interfaceMethod, $concreteMethods) === false) {
            $missingImplementations[] = $interfaceMethod;
        }
    }

    expect($missingImplementations)
        ->toBeEmpty("Failed asserting that {$this->value} is a valid implementation of interface $interface. Missing: [". implode(', ', $missingImplementations) . "] from interface.");

    return $this;
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/*
|--------------------------------------------------------------------------
| Internal Testing Functions
|--------------------------------------------------------------------------
*/

function typesToString(ReflectionType $types): string
{
    if ($types instanceof ReflectionUnionType) {
        $types = array_map(function (ReflectionNamedType $type) {
            return $type->getName();
        }, $types->getTypes());
        sort($types);
        return implode('|', $types);
    } elseif ($types instanceof ReflectionNamedType) {
        return ($types->allowsNull()) ? "null|{$types->getName()}" : $types->getName();
    }
    return "";
}

function parameterToString(ReflectionParameter $param): string
{
    $variadic = "";
    if ($param->isVariadic()) {
        $variadic = "...";
    }

    $default = "";
    if ($param->isDefaultValueAvailable()) {
        $default = ": " . $param->getDefaultValue();
    }

    if ($param->hasType()) {
        return $variadic . typesToString($param->getType()) . $default;
    }
    return $variadic . "mixed$default";
}

function methodToString(ReflectionMethod $method): string
{
    $args = '';
    foreach ($method->getParameters() as $param) {
        $arg = parameterToString($param);
        $args .= ($args == '') ? $arg : ", $arg";
    }

    $visibility = match (true) {
        $method->isPublic() => 'public',
        $method->isPrivate() => 'private',
        $method->isProtected() => 'protected',
    };

    $return = '';
    if ($method->hasReturnType()) {
        $return = ': ' . typesToString($method->getReturnType());
    }

    $name = $method->getName();
    if ($method->isStatic()) {
        $name = "static $name";
    }

    return "$visibility $name($args)$return";
}

it('can convert reflection methods to strings', function () {
    $fixture = new ReflectionClass(ReflectionTestFixture::class);
    $methods = $fixture->getMethods();

    // Visibility
    expect(methodToString($methods[0]))->toBe("public basic()");
    expect(methodToString($methods[1]))->toBe("private private()");
    expect(methodToString($methods[2]))->toBe("protected protected()");
    expect(methodToString($methods[3]))->toBe("public static static()");
    // Return types
    expect(methodToString($methods[4]))->toBe("public returnType(): int");
    expect(methodToString($methods[5]))->toBe("public returnUnionType(): array|float|int|string");
    expect(methodToString($methods[6]))->toBe("public returnMixedType(): null|mixed");
    expect(methodToString($methods[7]))->toBe("public returnVoidType(): void");
    expect(methodToString($methods[8]))->toBe("public returnNullableType(): null|int");
    expect(methodToString($methods[9]))->toBe("public returnNullableUnionType(): float|int|null|string");
    // Arguments
    expect(methodToString($methods[10]))->toBe("public withArg(int)");
    expect(methodToString($methods[11]))->toBe("public withArgs(int, float, array)");
    expect(methodToString($methods[12]))->toBe("public withObject(ReflectionTestFixture, int)");
    expect(methodToString($methods[13]))->toBe("public withUnionType(array|int)");
    expect(methodToString($methods[14]))->toBe("public withUnionTypes(array|string, float|int)");
    expect(methodToString($methods[15]))->toBe("public withNullableType(null|array)");
    expect(methodToString($methods[16]))->toBe("public withNoType(mixed)");
    expect(methodToString($methods[17]))->toBe("public withNoTypeMulti(mixed, mixed, int)");
    // Default Values
    expect(methodToString($methods[18]))->toBe("public withDefaults(mixed: a)");
    expect(methodToString($methods[19]))->toBe("public withDefaultsTyped(int: 1)");
    expect(methodToString($methods[20]))->toBe("public variadic(...int)");
    // Everything together
    expect(methodToString($methods[21]))->toBe("private static integration(float|int, mixed, string: test, ...int): null|int");
});

class ReflectionTestFixture
{
    public function basic()
    {
        return $this->private();
    }

    private function private()
    {
    }

    protected function protected()
    {
    }

    public static function static()
    {
    }

    public function returnType(): int
    {
        return 0;
    }

    public function returnUnionType(): int|string|array|float
    {
        return 0;
    }

    public function returnMixedType(): mixed
    {
        return 0;
    }

    public function returnVoidType(): void
    {
    }

    public function returnNullableType(): ?int
    {
        return 5;
    }

    public function returnNullableUnionType(): null|int|float|string
    {
        return null;
    }

    public function withArg(int $arg)
    {
        return $arg;
    }

    public function withArgs(int $a, float $b, array $c)
    {
        return $a + $b + $c[0];
    }

    public function withObject(ReflectionTestFixture $a, int $b)
    {
        return $a->returnMixedType() + $b;
    }

    public function withUnionType(int|array $a)
    {
        return $a;
    }

    public function withUnionTypes(array|string $a, int|float $b)
    {
        return $a . $b;
    }

    public function withNullableType(null|array $a)
    {
        return $a;
    }

    public function withNoType($a)
    {
        return $a;
    }

    public function withNoTypeMulti($a, $b, int $c)
    {
        return $a + $b + $c;
    }

    public function withDefaults($a = "a")
    {
        return $a;
    }

    public function withDefaultsTyped(int $a = 1)
    {
        return $a;
    }

    public function variadic(int ...$a)
    {
        return $this->integration(1, $a[0]);
    }

    private static function integration(int|float $num, $any, string $words = 'test', int ...$end): ?int
    {
        return intval(($num + $any) . $words . array_sum($end));
    }
}

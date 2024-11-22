# php-json-objects
Transform JSON into Data Objects and vice-versa

This package doesn't try to do anything fancy. It allows the user to configure
classes, so users can instantiate objects of that class from JSON or turn 
objects into JSON. It deliberately tries to stay framework agnostic and simple.

## Usage
Inspired by Go struct tags, this package uses PHP Attributes to marshall and 
unmarshall json.

```php
<?php

namepsace Acme\DataObjects;

use LlewellynKevin\JsonObjects\ConvertsToJson;

class Person
{
    use ConvertsToJson;

    public function __construct(
        #[JsonAttribute("name")]
        public string $name,
        #[JsonAttribute("age")]
        public string $age,
    ) {
        //
    }
}
```

You can instantiate this class from the static method: `fromJson`

```php
<?php

$json = '{"name": "Johnny Appleseed", "Age": 21}';
$person = Person::fromJson($json);
echo "Name: {$person->name}";
echo "Age: {$person->age}";

// Prints:
// Name: Johnny Appleseed
// Age: 21
```

Or you can create JSON from objects using the instance method: `toJson`

```php
<?php

$person = new Person("Johnny Appleseed", 21);
$json = $person->toJson();

echo $json;

// Prints:
// {"name":"Johnny Appleseed","Age":21}
```

You can also convert to and from associative arrays:

```php
<?php

$person = Person::fromArray(['name' => 'Vin', 'age' => 16]);
return $person;
// Person {name: Vin, age: 16}

return $person->toArray();
// ['name' => 'Vin', 'age' => 16]
```

You can nest any object that inherits the `Jsonable` interface:

```php
<?php

use LlewellynKevin\JsonObjects\ConvertsToJson;
use LlewellynKevin\JsonObjects\Jsonable;
use LlewellynKevin\JsonObjects\JsonAttribute;

class Address implements Jsonable
{
    use ConvertsToJson;

    public function __construct(
        #[JsonAttribute('street_address')]
        public string $streetAddress,
        #[JsonAttribute('city')]
        public string $city,
        #[JsonAttribute('state')]
        public string $state,
        #[JsonAttribute('zip')]
        public string $zip,
    ) {
        //
    }
}

class Person implements Jsonable
{
    use ConvertsToJson;

    public function __construct(
        #[JsonAttribute('name')]
        public string $name,
        #[JsonAttribute('address')]
        public Address $address,
    ) {
        //
    }
}

$personJson = <<<json
{
    "name": "Vin",
    "address": {
        "street_address": "3 Skaa Ln",
        "city": "Luthadel",
        "state": "Central Dominance",
        "zip": "11016",
    },
}
json;

$person = Person::fromJson($json);

return $person;
// Peron {name: Vin, address: Address {street_address: 3 Skaa Ln, city: Luthadel, state: Central Dominance, zip: 11016}}
```

You can also use 'dot' notation to access nested attributes:

```php
<?php

class Nested
{
    #[JsonAttribute('foo.bar')]
    public string $prop;
}

$nested = Nested::fromJson('{"foo":{"bar":"test"}}');

return $nested;
// Nested {prop: test}
```

You can also specify multiple possible json keys that are possible using a '|':

```php
<?php
#[JsonAttribute('one|two')]
public string $number;
```

Missing values in the json will be filled with a 'zero value' based on the 
following:
- Number: 0
- String: ""
- Array: []
- Object: {}
- Nested Jsonable: Zero version of the object
- Nullable Field: null

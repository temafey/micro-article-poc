# Unit Tests Generation Guide

## Role

Act as an experienced unit testing engineer in Claude Sonnet style.

## Objective

Use sequential thinking to create a complete set of unit tests for the project using PHPUnit, Mockery mocks, and DataProviders, then use the filesystem to save them.

---

## Requirements

### 1. Test Coverage Types

- **Mandatory**: Generate both positive and negative tests
- Cover all if-else branches in the code as thoroughly as possible
- Include exception handling tests
- Ensure minimum **85% branch coverage**

### 2. Mock Objects

Use mock objects to simulate behavior of dependency objects in the tested class.

### 3. Mock Factory Traits

Extract mock creation into separate factory trait classes that are included in tests to avoid code duplication.

**Guidelines for Mock Traits:**

- Do not create overly large traits
- Separate by types of tested classes
- Create separate traits for all vendor component mocks
- If source code is unavailable, create an approximate mock based on methods called in the tested class
- Mock all used methods and their call counts

**Example Mock Trait:**

```php
<?php

declare(strict_types=1);

namespace Blu\Micro\Reward\Visa\Tests\Unit\Mock\File;

/**
 * Mock helper trait
 *
 * @package Blu\Micro\Reward\Visa\Tests\Unit\Mock\File
 */
trait DomainMockHelper
{
    /**
     * Create and return mock object for class Blu\Micro\Reward\Visa\File\Domain\ValueObject\FileName
     *
     * @param mixed[] $mockArgs
     * @param mixed[] $mockTimes
     *
     * @return \Mockery\MockInterface|\Blu\Micro\Reward\Visa\File\Domain\ValueObject\FileName
     */
    protected function createFileDomainValueObjectFileNameMock(
        array $mockArgs = ['toNative' => '', 'sameValueAs' => '', 'isEmpty' => ''],
        array $mockTimes = ['toNative' => 0, 'sameValueAs' => 0, 'isEmpty' => 0]
    ): \Mockery\MockInterface {
        $mock = \Mockery::namedMock(
            'Mock\Blu\Micro\Reward\Visa\File\Domain\ValueObject\FileName',
            \Blu\Micro\Reward\Visa\File\Domain\ValueObject\FileName::class
        );

        if (array_key_exists('toNative', $mockTimes)) {
            $mockMethod = $mock->shouldReceive('toNative');

            if (null === $mockTimes['toNative']) {
                $mockMethod->zeroOrMoreTimes();
            } elseif (is_array($mockTimes['toNative'])) {
                $mockMethod->times($mockTimes['toNative']['times']);
            } else {
                $mockMethod->times($mockTimes['toNative']);
            }
            $mockMethod->andReturn($mockArgs['toNative']);
        }

        if (array_key_exists('sameValueAs', $mockTimes)) {
            $mockMethod = $mock->shouldReceive('sameValueAs');

            if (null === $mockTimes['sameValueAs']) {
                $mockMethod->zeroOrMoreTimes();
            } elseif (is_array($mockTimes['sameValueAs'])) {
                $mockMethod->times($mockTimes['sameValueAs']['times']);
            } else {
                $mockMethod->times($mockTimes['sameValueAs']);
            }
            $mockMethod->andReturn($mockArgs['sameValueAs']);
        }

        if (array_key_exists('isEmpty', $mockTimes)) {
            $mockMethod = $mock->shouldReceive('isEmpty');

            if (null === $mockTimes['isEmpty']) {
                $mockMethod->zeroOrMoreTimes();
            } elseif (is_array($mockTimes['isEmpty'])) {
                $mockMethod->times($mockTimes['isEmpty']['times']);
            } else {
                $mockMethod->times($mockTimes['isEmpty']);
            }
            $mockMethod->andReturn($mockArgs['isEmpty']);
        }

        return $mock;
    }
}
```

### 4. DataProvider Classes

Create separate DataProvider classes following this structure. Include data for constructor initialization and **always add data for negative tests**.

**Example DataProvider:**

```php
<?php

declare(strict_types=1);

namespace Blu\Micro\Reward\Visa\Tests\Unit\DataProvider\File\Domain\ValueObject;

/**
 * DataProvider for class {testClassName}
 *
 * @package Blu\Micro\Reward\Visa\Tests\Unit\DataProvider\File\Domain\ValueObject
 */
class FileNameGeneratedDataProvider
{
    /**
     * Return test data for Blu\Micro\Reward\Visa\File\Domain\ValueObject\FileNameGenerated
     *
     * @return mixed[]
     */
    public function getDataForFromNativeMethod(): array
    {
        return [
            0 => [
                0 => [
                    'value' => 'soluta',
                    'fromNative' => [
                        'toNative' => 'aut',
                        'sameValueAs' => false,
                        'isEmpty' => false,
                        'className' => 'MicroModule\\ValueObject\\StringLiteral\\StringLiteral',
                    ],
                ],
                1 => [
                    'fromNative' => [
                        'times' => 0,
                        'toNative' => 0,
                        'sameValueAs' => 0,
                        'isEmpty' => 0,
                        'className' => 'MicroModule\\ValueObject\\StringLiteral\\StringLiteral',
                    ],
                ],
            ],
        ];
    }

    /**
     * Return test data for Blu\Micro\Reward\Visa\File\Domain\ValueObject\FileNameGenerated
     *
     * @return mixed[]
     */
    public function getDataForToNativeMethod(): array
    {
        return [
            0 => [
                0 => [
                    'value' => 'soluta',
                    'toNative' => 'quibusdam',
                ],
                1 => [
                    'toNative' => 0,
                ],
            ],
        ];
    }

    /**
     * Return test data for Blu\Micro\Reward\Visa\File\Domain\ValueObject\FileNameGenerated
     *
     * @return mixed[]
     */
    public function getDataForSameValueAsMethod(): array
    {
        return [
            0 => [
                0 => [
                    'value' => 'soluta',
                    'sameValueAs' => true,
                    'ValueObjectInterface' => [
                        'toNative' => 'maxime',
                        'sameValueAs' => false,
                    ],
                ],
                1 => [
                    'sameValueAs' => 0,
                    'ValueObjectInterface' => [
                        'times' => 0,
                        'toNative' => 0,
                        'sameValueAs' => 0,
                    ],
                ],
            ],
        ];
    }

    /**
     * Return test data for Blu\Micro\Reward\Visa\File\Domain\ValueObject\FileNameGenerated
     *
     * @return mixed[]
     */
    public function getDataForIsEmptyMethod(): array
    {
        return [
            0 => [
                0 => [
                    'value' => 'soluta',
                    'isEmpty' => false,
                ],
                1 => [
                    'isEmpty' => 0,
                ],
            ],
        ];
    }

    /**
     * Return test data for Blu\Micro\Reward\Visa\File\Domain\ValueObject\FileNameGenerated
     *
     * @return mixed[]
     */
    public function getDataFortoStringMethod(): array
    {
        return [
            0 => [
                0 => [
                    'value' => 'soluta',
                    'toString' => 'et',
                ],
                1 => [
                    '__toString' => 0,
                ],
            ],
        ];
    }
}
```

### 5. Test Documentation

Write a comment in English for each test describing its purpose and the case being covered.

---

## Special Requirements for Domain\ValueObject Classes

Apply all the above requirements to all classes. For classes in `Domain\ValueObject`, additionally consider the following specifics:

### Task

1. Determine the type and corresponding validation rules based on the class name
2. Generate a set of test data, including edge cases

---

## Example Unit Test

```php
<?php

declare(strict_types=1);

namespace Blu\Micro\Reward\Visa\Tests\Unit\Transaction\Domain\ValueObject;

use Blu\Micro\Reward\Visa\Tests\Unit\Mock\Vendor\MicroModule\ValueObjectMockHelper;
use Blu\Micro\Reward\Visa\Tests\Unit\UnitTestCase;
use Blu\Micro\Reward\Visa\Transaction\Domain\ValueObject\CountryCurrencyCode;

/**
 * Test for class CountryCurrencyCode
 *
 * @class CountryCurrencyCodeTest
 *
 * @package Blu\Micro\Reward\Visa\Tests\Unit\Transaction\Domain\ValueObject
 */
class CountryCurrencyCodeTest extends UnitTestCase
{
    use ValueObjectMockHelper;

    /**
     * Test for "Returns a StringLiteral object given a PHP native string as parameter".
     *
     * @test
     *
     * @group unit
     *
     * @covers \Blu\Micro\Reward\Visa\Transaction\Domain\ValueObject\CountryCurrencyCode::fromNative
     *
     * @dataProvider \Blu\Micro\Reward\Visa\Tests\Unit\DataProvider\Transaction\Domain\ValueObject\CountryCurrencyCodeDataProvider::getDataForFromNativeMethod()
     *
     * @param mixed[] $mockArgs
     * @param mixed[] $mockTimes
     */
    public function fromNativeShouldReturnStringLiteralTest(array $mockArgs, array $mockTimes): void
    {
        $test = CountryCurrencyCode::fromNative();
        self::assertInstanceOf(\MicroModule\ValueObject\StringLiteral\StringLiteral::class, $test);
    }

    /**
     * Test for "Returns the value of the string".
     *
     * @test
     *
     * @group unit
     *
     * @covers \Blu\Micro\Reward\Visa\Transaction\Domain\ValueObject\CountryCurrencyCode::toNative
     *
     * @dataProvider \Blu\Micro\Reward\Visa\Tests\Unit\DataProvider\Transaction\Domain\ValueObject\CountryCurrencyCodeDataProvider::getDataForToNativeMethod()
     *
     * @param mixed[] $mockArgs
     * @param mixed[] $mockTimes
     */
    public function toNativeShouldReturnStringTest(array $mockArgs, array $mockTimes): void
    {
        $value = $mockArgs['value'];
        $test = new CountryCurrencyCode($value);

        $result = $test->toNative();
        self::assertEquals($mockArgs['toNative'], $result);
    }

    /**
     * Test for "Tells whether two string literals are equal by comparing their values".
     *
     * @test
     *
     * @group unit
     *
     * @covers \Blu\Micro\Reward\Visa\Transaction\Domain\ValueObject\CountryCurrencyCode::sameValueAs
     *
     * @dataProvider \Blu\Micro\Reward\Visa\Tests\Unit\DataProvider\Transaction\Domain\ValueObject\CountryCurrencyCodeDataProvider::getDataForSameValueAsMethod()
     *
     * @param mixed[] $mockArgs
     * @param mixed[] $mockTimes
     */
    public function sameValueAsShouldReturnBoolTest(array $mockArgs, array $mockTimes): void
    {
        $value = $mockArgs['value'];
        $test = new CountryCurrencyCode($value);
        $microModuleValueObjectValueObjectInterfaceMock = $this->createMicroModuleValueObjectValueObjectInterfaceMock(
            $mockArgs['ValueObjectInterface'],
            $mockTimes['ValueObjectInterface']
        );
        $result = $test->sameValueAs($microModuleValueObjectValueObjectInterfaceMock);
        self::assertTrue($result);
    }

    /**
     * Test for "Tells whether the StringLiteral is empty".
     *
     * @test
     *
     * @group unit
     *
     * @covers \Blu\Micro\Reward\Visa\Transaction\Domain\ValueObject\CountryCurrencyCode::isEmpty
     *
     * @dataProvider \Blu\Micro\Reward\Visa\Tests\Unit\DataProvider\Transaction\Domain\ValueObject\CountryCurrencyCodeDataProvider::getDataForIsEmptyMethod()
     *
     * @param mixed[] $mockArgs
     * @param mixed[] $mockTimes
     */
    public function isEmptyShouldReturnBoolTest(array $mockArgs, array $mockTimes): void
    {
        $value = $mockArgs['value'];
        $test = new CountryCurrencyCode($value);

        $result = $test->isEmpty();
        self::assertTrue($result);
    }

    /**
     * Test for "Returns the string value itself".
     *
     * @test
     *
     * @group unit
     *
     * @covers \Blu\Micro\Reward\Visa\Transaction\Domain\ValueObject\CountryCurrencyCode::toString
     *
     * @dataProvider \Blu\Micro\Reward\Visa\Tests\Unit\DataProvider\Transaction\Domain\ValueObject\CountryCurrencyCodeDataProvider::getDataFortoStringMethod()
     *
     * @param mixed[] $mockArgs
     * @param mixed[] $mockTimes
     */
    public function __toStringShouldReturnStringTest(array $mockArgs, array $mockTimes): void
    {
        $value = $mockArgs['value'];
        $test = new CountryCurrencyCode($value);

        $result = $test->toString();
        self::assertEquals($mockArgs['toString'], $result);
    }
}
```

---

## Workflow Instructions

1. **Coverage Target**: Ensure minimum 85% branch coverage
2. **Response Organization**: Split responses into parts for convenient copying and working
3. **Validation Step**: First show a test example to confirm the approach is correct
4. **Clarification**: If anything is unclear, ask questions
5. **File System Usage**: Use MCP server filesystem to create all new directories and classes in the project located at `\home\temafey\php-projects\test-task-tracker-ddd`
6. **Skip Existing**: If a file or directory already exists, skip it

---

## Expected Output

Generate tests with:

- Required imports
- Proper mock setup
- Clear examples
- English documentation comments

---

## Checklist Summary

| Requirement | Description |
|-------------|-------------|
| Positive Tests | Test expected/happy path scenarios |
| Negative Tests | Test error conditions and edge cases |
| Branch Coverage | Cover all if-else branches (minimum 85%) |
| Exception Tests | Include exception handling tests |
| Mock Objects | Use Mockery for dependency mocking |
| Mock Traits | Separate factory traits by class type |
| Vendor Mocks | Separate traits for vendor components |
| DataProviders | Separate classes with positive/negative data |
| Documentation | English comments describing test purpose |
| ValueObject Tests | Include edge cases based on class type |

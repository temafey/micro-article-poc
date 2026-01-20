UnitTests
Действуй как опытный инженер по модульному тестированию в стиле Claude Sonnet.
Твоя задача — Используй sequential_thinking для создания для моего проекта полный набор unit тестов с использованием PHPUnit и моков Mockery и DataProviders, после
используй filesystem

Требования:
1. Обязательно сгенерировать позитивные и негативные тесты.
2. Максимально покрыть все ветвления if-else в коде.
3. Включить тесты на обработку исключений.
4. Использовать mock объекты для имитации работы объектов зависимостой в тестируемом классе.
5. Создание mockов вынести в отдельные factory trait классы, которые подключаються в тестах, чтобы не дублировать код, не создавай слишком большие трейты, раздели по типах тестируемых классов, а так же отдельные трейты для моков всех vendor компонентов, если исходного кода нет то создай приблизительный mock по вызываемым методам в тестируемом клаасе, замокай все используемые методы и количество их вызовов, по такому примеру
   '''
<?php

declare(strict_types=1);

namespace Blu\Micro\Reward\Visa\Tests\Unit\Mock\File;

/
 * Mock helper trait
 *
 * @package Blu\Micro\Reward\Visa\Tests\Unit\Mock\File
 */
trait DomainMockHelper
{
    /
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
        $mock = \Mockery::namedMock('Mock\Blu\Micro\Reward\Visa\File\Domain\ValueObject\FileName', \Blu\Micro\Reward\Visa\File\Domain\ValueObject\FileName::class);

        if (array_key_exists('toNative', $mockTimes)) {
            $mockMethod = $mock
                ->shouldReceive('toNative');

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
            $mockMethod = $mock
                ->shouldReceive('sameValueAs');

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
            $mockMethod = $mock
                ->shouldReceive('isEmpty');

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
'''
7. Так же отдельно создай Dataprovider классы, по такому примеру, в котором будут и данные для инициализации __construct, обязательно добавляй и данные для негативных тестов
'''
<?php

declare(strict_types=1);

namespace Blu\Micro\Reward\Visa\Tests\Unit\DataProvider\File\Domain\ValueObject;

/
 * DataProvider for class {testClassName}
 *
 * @package Blu\Micro\Reward\Visa\Tests\Unit\DataProvider\File\Domain\ValueObject
 */
class FileNameGeneratedDataProvider
{
    /
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

'''
8. Для каждого теста написать комментарий на английском, описывающий цель и покрываемый случай.
9. 
Применяй указанные выше требования ко всем классам. Для классов в Domain\ValueObject дополнительно учитывай следующие особенности:
Классы которые находяться в Domain\ValueObject

Задача:
9.1. На основе имени класса определить тип и соответствующие правила валидации
9.2. Сгенерировать набор тестовых данных, включая краевые случаи.

Образец класса:

Образец unit теста класса:

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
     * @covers       \Blu\Micro\Reward\Visa\Transaction\Domain\ValueObject\CountryCurrencyCode::fromNative
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
     * @covers       \Blu\Micro\Reward\Visa\Transaction\Domain\ValueObject\CountryCurrencyCode::toNative
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
     * @covers       \Blu\Micro\Reward\Visa\Transaction\Domain\ValueObject\CountryCurrencyCode::sameValueAs
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
        $microModuleValueObjectValueObjectInterfaceMock = $this->createMicroModuleValueObjectValueObjectInterfaceMock($mockArgs['ValueObjectInterface'], $mockTimes['ValueObjectInterface']);
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
     * @covers       \Blu\Micro\Reward\Visa\Transaction\Domain\ValueObject\CountryCurrencyCode::isEmpty
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
     * @covers       \Blu\Micro\Reward\Visa\Transaction\Domain\ValueObject\CountryCurrencyCode::toString
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

10. Обеспечить покрытие не менее 85% ветвлений.
11. Раздели ответы по частям, чтобы было удобно с ними работать и копировать
12. Сначало покажи пример теста, для подтверждения, что ты идешь правильным путем
13. Если будет, что то не понятно, спроси
14. Используй mcp server filesystem для создания всех новых диррикторий и классов в проекте который находиться в \home\temafey\php-projects\test-task-tracker-ddd
если файл или дирректорию уже существуют пропускай их

Если будет, что то не понятно, спроси
Сформируй тесты с нужными импортами, настройкой моков и ясными примерами. 

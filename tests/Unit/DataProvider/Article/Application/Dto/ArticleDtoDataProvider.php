<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Application\Dto;

/**
 * DataProvider for ArticleDto tests.
 *
 * @see Tests\Unit\Article\Application\Dto\ArticleDtoTest
 */
final class ArticleDtoDataProvider
{
    /**
     * @return iterable<string, array{uuid: string, title: string, description: string, shortDescription: string, status: string}>
     */
    public static function provideValidConstructionData(): iterable
    {
        yield 'standard article dto' => [
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            'title' => 'Article DTO Title',
            'description' => 'This is a comprehensive article DTO description that contains sufficient content to meet the minimum validation requirements.',
            'shortDescription' => 'DTO summary',
            'status' => 'draft',
        ];

        yield 'published article dto' => [
            'uuid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
            'title' => 'Published Article DTO',
            'description' => 'This is a published article DTO with comprehensive description meeting all validation requirements for DTO construction.',
            'shortDescription' => 'Published DTO',
            'status' => 'published',
        ];

        yield 'archived article dto' => [
            'uuid' => 'b1c2d3e4-f5a6-4b7c-8d9e-0f1a2b3c4d5e',
            'title' => 'Archived Article DTO',
            'description' => 'This is an archived article DTO with comprehensive description that meets all necessary validation requirements.',
            'shortDescription' => 'Archived DTO',
            'status' => 'archived',
        ];

        yield 'dto with unicode content' => [
            'uuid' => 'c3d4e5f6-a7b8-9c0d-1e2f-3a4b5c6d7e8f',
            'title' => 'Новости дня в DTO',
            'description' => 'Это описание DTO на русском языке с необходимым количеством символов для успешной валидации системы управления.',
            'shortDescription' => 'DTO на русском',
            'status' => 'draft',
        ];

        yield 'dto with maximum length fields' => [
            'uuid' => 'd4e5f6a7-b8c9-0d1e-2f3a-4b5c6d7e8f9a',
            'title' => str_repeat('D', 255),
            'description' => str_repeat(
                'This is a DTO description segment that will be repeated to meet minimum length. ',
                50
            ),
            'shortDescription' => str_repeat('Short', 100),
            'status' => 'draft',
        ];
    }

    /**
     * @return iterable<string, array{uuid: string, title: string, description: string, shortDescription: string, status: string, expectedException: class-string<\Throwable>}>
     */
    public static function provideInvalidConstructionData(): iterable
    {
        yield 'empty title' => [
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            'title' => '',
            'description' => 'This is a valid description that meets all validation requirements for article DTO construction.',
            'shortDescription' => 'Valid summary',
            'status' => 'draft',
            'expectedException' => \InvalidArgumentException::class,
        ];

        yield 'description too short' => [
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            'title' => 'Valid Title',
            'description' => 'Short',
            'shortDescription' => 'Valid summary',
            'status' => 'draft',
            'expectedException' => \InvalidArgumentException::class,
        ];

        yield 'invalid status value' => [
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            'title' => 'Valid Title',
            'description' => 'This is a valid description that meets all validation requirements for article DTO construction.',
            'shortDescription' => 'Valid summary',
            'status' => 'invalid_status',
            'expectedException' => \InvalidArgumentException::class,
        ];

        yield 'malformed uuid' => [
            'uuid' => 'not-a-uuid',
            'title' => 'Valid Title',
            'description' => 'This is a valid description that meets all validation requirements for article DTO construction.',
            'shortDescription' => 'Valid summary',
            'status' => 'draft',
            'expectedException' => \InvalidArgumentException::class,
        ];

        yield 'title exceeds maximum length' => [
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            'title' => str_repeat('A', 256),
            'description' => 'This is a valid description that meets all validation requirements for article DTO construction.',
            'shortDescription' => 'Valid summary',
            'status' => 'draft',
            'expectedException' => \InvalidArgumentException::class,
        ];
    }

    /**
     * @return iterable<string, array{dtoData: array, expectedJson: array}>
     */
    public static function provideSerializationData(): iterable
    {
        yield 'serialize standard dto' => [
            'dtoData' => [
                'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                'title' => 'Serializable Article',
                'description' => 'This is a description for serialization testing with comprehensive content meeting validation requirements.',
                'shortDescription' => 'Serializable',
                'status' => 'draft',
            ],
            'expectedJson' => [
                'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                'title' => 'Serializable Article',
                'description' => 'This is a description for serialization testing with comprehensive content meeting validation requirements.',
                'shortDescription' => 'Serializable',
                'status' => 'draft',
            ],
        ];

        yield 'serialize dto with special characters' => [
            'dtoData' => [
                'uuid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
                'title' => 'Article & "Special" Characters',
                'description' => 'This description contains special characters like & < > " \' for testing serialization with comprehensive validation.',
                'shortDescription' => 'Special chars',
                'status' => 'published',
            ],
            'expectedJson' => [
                'uuid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
                'title' => 'Article & "Special" Characters',
                'description' => 'This description contains special characters like & < > " \' for testing serialization with comprehensive validation.',
                'shortDescription' => 'Special chars',
                'status' => 'published',
            ],
        ];
    }

    /**
     * @return iterable<string, array{fromData: array, toData: array, expectedEquality: bool}>
     */
    public static function provideEqualityComparisons(): iterable
    {
        yield 'identical dtos are equal' => [
            'fromData' => [
                'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                'title' => 'Same Article',
                'description' => 'Identical description with comprehensive content for equality testing and validation purposes.',
                'shortDescription' => 'Same',
                'status' => 'draft',
            ],
            'toData' => [
                'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                'title' => 'Same Article',
                'description' => 'Identical description with comprehensive content for equality testing and validation purposes.',
                'shortDescription' => 'Same',
                'status' => 'draft',
            ],
            'expectedEquality' => true,
        ];

        yield 'different uuids are not equal' => [
            'fromData' => [
                'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                'title' => 'Article One',
                'description' => 'First description with comprehensive content for equality testing and validation purposes.',
                'shortDescription' => 'One',
                'status' => 'draft',
            ],
            'toData' => [
                'uuid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
                'title' => 'Article One',
                'description' => 'First description with comprehensive content for equality testing and validation purposes.',
                'shortDescription' => 'One',
                'status' => 'draft',
            ],
            'expectedEquality' => false,
        ];

        yield 'different titles are not equal' => [
            'fromData' => [
                'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                'title' => 'Title One',
                'description' => 'Shared description with comprehensive content for equality testing and validation purposes.',
                'shortDescription' => 'Shared',
                'status' => 'draft',
            ],
            'toData' => [
                'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                'title' => 'Title Two',
                'description' => 'Shared description with comprehensive content for equality testing and validation purposes.',
                'shortDescription' => 'Shared',
                'status' => 'draft',
            ],
            'expectedEquality' => false,
        ];
    }
}

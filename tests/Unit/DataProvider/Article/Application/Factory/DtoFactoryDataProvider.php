<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Application\Factory;

/**
 * DataProvider for DtoFactory tests.
 *
 * @see Tests\Unit\Article\Application\Factory\DtoFactoryTest
 */
final class DtoFactoryDataProvider
{
    /**
     * @return iterable<string, array{entityData: array, expectedDtoData: array}>
     */
    public static function provideValidDtoCreationFromEntity(): iterable
    {
        yield 'create dto from draft article entity' => [
            'entityData' => [
                'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                'title' => 'Draft Article Entity',
                'description' => 'This is a comprehensive draft article entity description that contains sufficient content to meet validation requirements.',
                'shortDescription' => 'Draft entity summary',
                'status' => 'draft',
            ],
            'expectedDtoData' => [
                'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                'title' => 'Draft Article Entity',
                'description' => 'This is a comprehensive draft article entity description that contains sufficient content to meet validation requirements.',
                'shortDescription' => 'Draft entity summary',
                'status' => 'draft',
            ],
        ];

        yield 'create dto from published article entity' => [
            'entityData' => [
                'uuid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
                'title' => 'Published Article Entity',
                'description' => 'This is a comprehensive published article entity description with all necessary content for validation requirements.',
                'shortDescription' => 'Published entity summary',
                'status' => 'published',
            ],
            'expectedDtoData' => [
                'uuid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
                'title' => 'Published Article Entity',
                'description' => 'This is a comprehensive published article entity description with all necessary content for validation requirements.',
                'shortDescription' => 'Published entity summary',
                'status' => 'published',
            ],
        ];

        yield 'create dto from archived article entity' => [
            'entityData' => [
                'uuid' => 'b1c2d3e4-f5a6-4b7c-8d9e-0f1a2b3c4d5e',
                'title' => 'Archived Article Entity',
                'description' => 'This is a comprehensive archived article entity description that meets all validation requirements for DTO creation.',
                'shortDescription' => 'Archived entity summary',
                'status' => 'archived',
            ],
            'expectedDtoData' => [
                'uuid' => 'b1c2d3e4-f5a6-4b7c-8d9e-0f1a2b3c4d5e',
                'title' => 'Archived Article Entity',
                'description' => 'This is a comprehensive archived article entity description that meets all validation requirements for DTO creation.',
                'shortDescription' => 'Archived entity summary',
                'status' => 'archived',
            ],
        ];

        yield 'create dto from entity with unicode content' => [
            'entityData' => [
                'uuid' => 'c3d4e5f6-a7b8-9c0d-1e2f-3a4b5c6d7e8f',
                'title' => 'Новости дня из сущности',
                'description' => 'Это подробное описание сущности новостей на русском языке с необходимым количеством символов для валидации.',
                'shortDescription' => 'Краткое описание сущности',
                'status' => 'draft',
            ],
            'expectedDtoData' => [
                'uuid' => 'c3d4e5f6-a7b8-9c0d-1e2f-3a4b5c6d7e8f',
                'title' => 'Новости дня из сущности',
                'description' => 'Это подробное описание сущности новостей на русском языке с необходимым количеством символов для валидации.',
                'shortDescription' => 'Краткое описание сущности',
                'status' => 'draft',
            ],
        ];
    }

    /**
     * @return iterable<string, array{arrayData: array, expectedDtoData: array}>
     */
    public static function provideValidDtoCreationFromArray(): iterable
    {
        yield 'create dto from array data' => [
            'arrayData' => [
                'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                'title' => 'Array Article Title',
                'description' => 'This is a comprehensive array article description that contains sufficient content to meet validation requirements.',
                'short_description' => 'Array summary',
                'status' => 'draft',
            ],
            'expectedDtoData' => [
                'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                'title' => 'Array Article Title',
                'description' => 'This is a comprehensive array article description that contains sufficient content to meet validation requirements.',
                'shortDescription' => 'Array summary',
                'status' => 'draft',
            ],
        ];

        yield 'create dto from snake_case array keys' => [
            'arrayData' => [
                'uuid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
                'title' => 'Snake Case Article',
                'description' => 'This is a comprehensive snake case article description with all necessary content for validation requirements.',
                'short_description' => 'Snake case summary',
                'status' => 'published',
            ],
            'expectedDtoData' => [
                'uuid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
                'title' => 'Snake Case Article',
                'description' => 'This is a comprehensive snake case article description with all necessary content for validation requirements.',
                'shortDescription' => 'Snake case summary',
                'status' => 'published',
            ],
        ];

        yield 'create dto with data transformation' => [
            'arrayData' => [
                'uuid' => 'b1c2d3e4-f5a6-4b7c-8d9e-0f1a2b3c4d5e',
                'title' => '  Trimmed Title  ',
                'description' => 'This is a comprehensive description with whitespace that will be trimmed during DTO creation for validation.',
                'short_description' => '  Trimmed Summary  ',
                'status' => 'draft',
            ],
            'expectedDtoData' => [
                'uuid' => 'b1c2d3e4-f5a6-4b7c-8d9e-0f1a2b3c4d5e',
                'title' => 'Trimmed Title',
                'description' => 'This is a comprehensive description with whitespace that will be trimmed during DTO creation for validation.',
                'shortDescription' => 'Trimmed Summary',
                'status' => 'draft',
            ],
        ];
    }

    /**
     * @return iterable<string, array{invalidData: array, expectedException: class-string<\Throwable>, expectedMessage?: string}>
     */
    public static function provideInvalidDtoCreation(): iterable
    {
        yield 'missing uuid throws exception' => [
            'invalidData' => [
                'title' => 'Valid Title',
                'description' => 'This is a valid description with comprehensive content meeting all validation requirements.',
                'shortDescription' => 'Valid summary',
                'status' => 'draft',
            ],
            'expectedException' => \InvalidArgumentException::class,
            'expectedMessage' => 'Missing required field: uuid',
        ];

        yield 'invalid uuid format throws exception' => [
            'invalidData' => [
                'uuid' => 'invalid-uuid',
                'title' => 'Valid Title',
                'description' => 'This is a valid description with comprehensive content meeting all validation requirements.',
                'shortDescription' => 'Valid summary',
                'status' => 'draft',
            ],
            'expectedException' => \InvalidArgumentException::class,
            'expectedMessage' => 'Invalid UUID format',
        ];

        yield 'empty title throws exception' => [
            'invalidData' => [
                'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                'title' => '',
                'description' => 'This is a valid description with comprehensive content meeting all validation requirements.',
                'shortDescription' => 'Valid summary',
                'status' => 'draft',
            ],
            'expectedException' => \InvalidArgumentException::class,
            'expectedMessage' => 'Title cannot be empty',
        ];

        yield 'description too short throws exception' => [
            'invalidData' => [
                'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                'title' => 'Valid Title',
                'description' => 'Short',
                'shortDescription' => 'Valid summary',
                'status' => 'draft',
            ],
            'expectedException' => \InvalidArgumentException::class,
            'expectedMessage' => 'Description too short',
        ];

        yield 'invalid status throws exception' => [
            'invalidData' => [
                'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                'title' => 'Valid Title',
                'description' => 'This is a valid description with comprehensive content meeting all validation requirements.',
                'shortDescription' => 'Valid summary',
                'status' => 'invalid_status',
            ],
            'expectedException' => \InvalidArgumentException::class,
            'expectedMessage' => 'Invalid status value',
        ];
    }

    /**
     * @return iterable<string, array{entities: array, expectedDtoCount: int}>
     */
    public static function provideBatchDtoCreation(): iterable
    {
        yield 'create batch of dtos from entities' => [
            'entities' => [
                [
                    'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                    'title' => 'First Article',
                    'description' => 'First comprehensive description with content meeting validation requirements.',
                    'shortDescription' => 'First',
                    'status' => 'draft',
                ],
                [
                    'uuid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
                    'title' => 'Second Article',
                    'description' => 'Second comprehensive description with content meeting validation requirements.',
                    'shortDescription' => 'Second',
                    'status' => 'published',
                ],
                [
                    'uuid' => 'b1c2d3e4-f5a6-4b7c-8d9e-0f1a2b3c4d5e',
                    'title' => 'Third Article',
                    'description' => 'Third comprehensive description with content meeting validation requirements.',
                    'shortDescription' => 'Third',
                    'status' => 'archived',
                ],
            ],
            'expectedDtoCount' => 3,
        ];

        yield 'create batch skipping invalid entities' => [
            'entities' => [
                [
                    'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                    'title' => 'Valid Article',
                    'description' => 'Valid comprehensive description with content meeting validation requirements.',
                    'shortDescription' => 'Valid',
                    'status' => 'draft',
                ],
                [
                    'uuid' => 'invalid-uuid',
                    'title' => 'Invalid Article',
                    'description' => 'This entity has invalid uuid.',
                    'shortDescription' => 'Invalid',
                    'status' => 'draft',
                ],
                [
                    'uuid' => 'b1c2d3e4-f5a6-4b7c-8d9e-0f1a2b3c4d5e',
                    'title' => 'Another Valid',
                    'description' => 'Another valid comprehensive description with content meeting validation requirements.',
                    'shortDescription' => 'Another',
                    'status' => 'published',
                ],
            ],
            'expectedDtoCount' => 2,
        ];
    }

    /**
     * @return iterable<string, array{readModelData: array, expectedDtoData: array}>
     */
    public static function provideDtoCreationFromReadModel(): iterable
    {
        yield 'create dto from read model' => [
            'readModelData' => [
                'id' => 1,
                'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                'title' => 'Read Model Article',
                'description' => 'This is a comprehensive read model article description with all necessary content for validation.',
                'short_description' => 'Read model summary',
                'status' => 'published',
                'created_at' => '2024-01-01 10:00:00',
                'updated_at' => '2024-01-02 15:30:00',
            ],
            'expectedDtoData' => [
                'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                'title' => 'Read Model Article',
                'description' => 'This is a comprehensive read model article description with all necessary content for validation.',
                'shortDescription' => 'Read model summary',
                'status' => 'published',
            ],
        ];

        yield 'create dto excluding internal fields' => [
            'readModelData' => [
                'id' => 2,
                'uuid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
                'title' => 'Clean DTO Article',
                'description' => 'This is a comprehensive clean DTO article description with all necessary content for proper validation.',
                'short_description' => 'Clean DTO summary',
                'status' => 'draft',
                'created_at' => '2024-01-03 12:00:00',
                'updated_at' => '2024-01-03 12:00:00',
                'internal_flag' => true,
                'version' => 5,
            ],
            'expectedDtoData' => [
                'uuid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
                'title' => 'Clean DTO Article',
                'description' => 'This is a comprehensive clean DTO article description with all necessary content for proper validation.',
                'shortDescription' => 'Clean DTO summary',
                'status' => 'draft',
            ],
        ];
    }
}

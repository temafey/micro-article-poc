# Symfony 8.0 Technical Knowledge Map for Claude Code

## Quick Reference

**Version**: Symfony 8.0.x (Current Stable)
**PHP Requirement**: 8.4.0 or higher
**Release Date**: November 2025
**Maintenance Until**: July 2026 (bug fixes), then upgrade to 8.1+
**Status**: Active Development

## Key Differences: Symfony 8.0 vs 7.x

| Aspect | Symfony 7.x | Symfony 8.0 |
|--------|-------------|-------------|
| **Deprecations** | Contains all deprecation layers | No deprecations (clean codebase) |
| **PHP Version** | 8.2+ | 8.4+ required |
| **Codebase Size** | Includes deprecated code | 13,202 lines trimmed |
| **Lazy Objects** | Via proxies | Native PHP 8.4 support |
| **Performance** | Baseline | Up to 70% cache improvement |

## Symfony 8.0 Major Features & Improvements

### 1. PHP 8.4 Native Lazy Objects

Revolutionary memory optimization using PHP 8.4's native lazy object support.

```php
use Symfony\Component\DependencyInjection\Attribute\Lazy;

#[Lazy]
class ExpensiveService
{
    public function __construct(
        private DatabaseConnection $connection,
        private ComplexCalculator $calculator
    ) {
        // Only initialized when service is actually used
        // 50% memory reduction in large applications
    }
}
```

**Benefits:**
- 50% memory reduction in large applications
- Significantly faster application startup
- No code generation required (unlike Doctrine proxies)
- Reduced CPU overhead for unused services

### 2. New Components

#### JsonPath Component
RFC 9535 compliant JSONPath query implementation.

```php
use Symfony\Component\JsonPath\JsonPath;

// Basic path queries
$data = ['users' => [['name' => 'John', 'age' => 30], ['name' => 'Jane', 'age' => 25]]];
$names = JsonPath::query($data, '$.users[*].name');
// Result: ['John', 'Jane']

// Complex filtering
$adults = JsonPath::query($data, '$.users[?@.age >= 18]');

// Builder pattern
$path = JsonPath::builder()
    ->root()
    ->property('users')
    ->filter('@.age > 25')
    ->property('name');
```

#### ObjectMapper Component
Powerful object transformation and mapping system.

```php
use Symfony\Component\ObjectMapper\ObjectMapper;
use Symfony\Component\ObjectMapper\Attribute\Map;

class UserDto
{
    public function __construct(
        #[Map('user_name')]
        public string $name,
        #[Map('email_address')]
        public string $email
    ) {}
}

$mapper = new ObjectMapper();
$user = $mapper->map([
    'user_name' => 'John',
    'email_address' => 'john@example.com'
], UserDto::class);
```

#### JsonStreamer Component
High-performance, low-memory JSON encoder/decoder.

```php
use Symfony\Component\JsonStreamer\JsonStreamEncoder;
use Symfony\Component\JsonStreamer\JsonStreamDecoder;

// Streaming encode large datasets
$encoder = new JsonStreamEncoder();
foreach ($encoder->encode($largeDataset) as $chunk) {
    echo $chunk;
}

// Stream decode without loading entire JSON into memory
$decoder = new JsonStreamDecoder();
foreach ($decoder->decode($jsonStream) as $item) {
    processItem($item);
}
```

#### TypeInfo Component
Extract PHP type information from properties/methods/functions.

```php
use Symfony\Component\TypeInfo\TypeResolver;
use Symfony\Component\TypeInfo\Type;

$resolver = new TypeResolver();
$type = $resolver->resolve(new \ReflectionProperty(User::class, 'email'));

if ($type->isNullable()) {
    // Handle nullable type
}
```

### 3. Invokable Commands with Attributes

Write commands as invokable classes without boilerplate.

```php
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:process-article',
    description: 'Process article articles'
)]
final class ProcessArticleCommand
{
    public function __construct(
        private ArticleProcessor $processor
    ) {}

    public function __invoke(
        #[Argument(description: 'Article ID to process')]
        string $articleId,

        #[Option(description: 'Output format')]
        string $format = 'json',

        #[Option(name: 'dry-run', description: 'Simulate without changes')]
        bool $dryRun = false,

        SymfonyStyle $io
    ): int {
        if ($dryRun) {
            $io->note('Running in dry-run mode');
        }

        $this->processor->process($articleId, $format);

        $io->success('Article processed successfully');
        return 0;
    }
}
```

### 4. Multi-Step Forms

Break complex forms into guided steps with per-step validation.

```php
use Symfony\Component\Form\Extension\Core\Type\MultiStepType;
use Symfony\Component\Form\FormBuilderInterface;

class ArticleCreationForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('step1', ArticleBasicInfoType::class, [
                'label' => 'Basic Information',
            ])
            ->add('step2', ArticleContentType::class, [
                'label' => 'Content',
                'dependencies' => ['step1'], // Conditional on step1
            ])
            ->add('step3', ArticlePublishingType::class, [
                'label' => 'Publishing Options',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'multi_step' => true,
            'validation_groups' => function (FormInterface $form) {
                return ['step' . $form->getCurrentStep()];
            },
        ]);
    }
}
```

### 5. PHP Array Configuration

New compact and expressive configuration format.

```php
// config/packages/framework.php
return [
    'framework' => [
        'secret' => '%env(APP_SECRET)%',
        'http_method_override' => false,
        'handle_all_throwables' => true,

        'cache' => [
            'app' => 'cache.adapter.redis',
            'default_redis_provider' => 'redis://localhost',
            'pools' => [
                'article.cache' => [
                    'adapter' => 'cache.adapter.redis',
                    'default_lifetime' => 3600,
                ],
            ],
        ],

        'session' => [
            'handler_id' => null,
            'cookie_secure' => 'auto',
            'cookie_samesite' => 'lax',
        ],
    ],
];
```

### 6. Twig Extension Attributes

Define Twig functions and filters with attributes, lazy-loaded by default.

```php
use Symfony\Bridge\Twig\Attribute\AsTwigFilter;
use Symfony\Bridge\Twig\Attribute\AsTwigFunction;

class ArticleExtension
{
    #[AsTwigFilter('article_status_label')]
    public function statusLabel(string $status): string
    {
        return match ($status) {
            'draft' => 'Draft',
            'published' => 'Published',
            'archived' => 'Archived',
            default => 'Unknown',
        };
    }

    #[AsTwigFunction('latest_article')]
    public function latestArticle(int $limit = 5): array
    {
        // Return latest article
    }
}
```

### 7. Enhanced Cache System (70% Performance Boost)

```php
use Symfony\Component\Cache\Adapter\RedisTagAwareAdapter;
use Symfony\Component\Cache\Marshaller\MsgPackMarshaller;

// New optimized cache with MsgPack serialization
$cache = new RedisTagAwareAdapter(
    $redisConnection,
    namespace: 'article_v8',
    marshaller: new MsgPackMarshaller() // 40% less serialization overhead
);

// Efficient tag-based invalidation
$cache->invalidateTags(['article:123', 'category:tech']);

// Batch operations
$cache->getItems(['article:1', 'article:2', 'article:3']);
```

**YAML Configuration:**
```yaml
framework:
    cache:
        app: cache.adapter.redis
        default_redis_provider: '%env(REDIS_URL)%'
        pools:
            article.cache:
                adapter: cache.adapter.redis
                default_lifetime: 3600
                tags: true
            events.cache:
                adapter: cache.adapter.redis
                default_lifetime: 300
```

### 8. Asset Pre-Compression

Compress assets at build time for zero runtime CPU cost.

```yaml
framework:
    assets:
        packages:
            app:
                base_urls:
                    - 'https://static1.example.com'
                    - 'https://static2.example.com'
                # Pre-compress with multiple algorithms
                precompress:
                    - zstd  # Best compression ratio
                    - br    # Brotli
                    - gzip  # Fallback
```

### 9. Stateless CSRF Protection

CSRF protection without sessions - perfect for APIs and cached pages.

```php
use Symfony\Component\Security\Csrf\StatelessCsrfTokenManager;

// Controller with stateless CSRF
#[Route('/api/article', methods: ['POST'])]
public function create(
    #[MapRequestPayload] ArticleDto $dto,
    #[IsCsrfTokenValid('article_create', tokenKey: 'X-CSRF-Token')]
): JsonResponse {
    // Token validated via header, no session required
}
```

```yaml
# config/packages/framework.yaml
framework:
    csrf_protection:
        stateless_token_ids:
            - article_create
            - article_update
            - article_delete
```

### 10. Security Voter Explanations

Debug authorization issues with voter decision explanations.

```php
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ArticleVoter extends Voter
{
    protected function voteOnAttribute(
        string $attribute,
        mixed $subject,
        TokenInterface $token
    ): bool {
        $user = $token->getUser();

        if ($attribute === 'EDIT' && $subject instanceof Article) {
            if ($subject->getAuthor() !== $user) {
                $this->explain('User is not the author of this article article');
                return false;
            }

            if ($subject->isPublished()) {
                $this->explain('Published article cannot be edited');
                return false;
            }

            $this->explain('User is the author and article is not published');
            return true;
        }

        return false;
    }
}
```

### 11. Signed Messenger Messages

Cryptographically secure message signatures for tamper-proof queues.

```yaml
# config/packages/messenger.yaml
framework:
    messenger:
        transports:
            async:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
                options:
                    signing:
                        enabled: true
                        key: '%env(MESSENGER_SIGNING_KEY)%'
```

```php
use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage(signed: true)]
class ArticleCreatedMessage
{
    public function __construct(
        public readonly string $articleId,
        public readonly \DateTimeImmutable $createdAt
    ) {}
}
```

### 12. FrankenPHP Integration

Native support for FrankenPHP worker mode with 4x faster response times.

```dockerfile
# Dockerfile.frankenphp
FROM dunglas/frankenphp:latest-php8.4

COPY . /app
WORKDIR /app

# Enable worker mode for persistent PHP processes
ENV FRANKENPHP_CONFIG="worker ./public/index.php"
ENV APP_RUNTIME="Runtime\\FrankenPhpSymfony\\Runtime"

EXPOSE 80 443
CMD ["frankenphp", "run"]
```

```php
// public/index.php for FrankenPHP
use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
```

## Architecture Integration for DDD Projects

### Domain Layer with ObjectMapper

```php
use Symfony\Component\ObjectMapper\ObjectMapper;
use Symfony\Component\ObjectMapper\Attribute\Map;

// Value Object with ObjectMapper integration
final readonly class ArticleId
{
    public function __construct(
        private string $value
    ) {
        if (!Uuid::isValid($value)) {
            throw new InvalidArgumentException('Invalid Article ID');
        }
    }

    public static function fromArray(array $data): self
    {
        $mapper = new ObjectMapper();
        return $mapper->map($data, self::class);
    }

    public function getValue(): string
    {
        return $this->value;
    }
}

// Entity with lazy loading
#[Lazy]
final class ArticleEntity extends EventSourcedAggregateRoot
{
    // Entity initialized only when accessed
}
```

### Application Layer with Enhanced Commands

```php
#[AsCommand(name: 'article:create')]
final class CreateArticleConsoleCommand
{
    public function __construct(
        private CommandBus $commandBus,
        private ObjectMapper $mapper
    ) {}

    public function __invoke(
        #[Argument(description: 'Article title')]
        string $title,

        #[Argument(description: 'Article description')]
        string $description,

        #[Option(description: 'Publish immediately')]
        bool $publish = false,

        SymfonyStyle $io
    ): int {
        $command = $this->mapper->map([
            'title' => $title,
            'description' => $description,
            'publish' => $publish,
        ], CreateArticleCommand::class);

        $this->commandBus->handle($command);

        $io->success('Article created successfully');
        return 0;
    }
}
```

### Infrastructure Layer with Enhanced Caching

```php
use Symfony\Component\Cache\Attribute\Cache;

final class ArticleRepository implements ArticleRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CacheInterface $articleCache
    ) {}

    #[Cache(pool: 'article.cache', expiresAfter: 3600)]
    public function findById(ArticleId $id): ?Article
    {
        return $this->entityManager->find(Article::class, $id->getValue());
    }

    public function save(Article $article): void
    {
        $this->entityManager->persist($article);
        $this->entityManager->flush();

        // Invalidate cache with tags
        $this->articleCache->invalidateTags([
            "article:{$article->getId()->getValue()}",
            'article:list'
        ]);
    }
}
```

### Presentation Layer with JsonPath Testing

```php
use Symfony\Component\JsonPath\JsonPath;

final class ArticleApiTest extends WebTestCase
{
    public function testGetArticleReturnsCorrectStructure(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/v2/article/123');

        $response = $client->getResponse();
        $this->assertResponseIsSuccessful();

        $content = $response->getContent();

        // JsonPath assertions
        $this->assertNotEmpty(JsonPath::query(json_decode($content, true), '$.data.id'));
        $this->assertEquals('published', JsonPath::query(json_decode($content, true), '$.data.status')[0]);
        $this->assertCount(1, JsonPath::query(json_decode($content, true), '$.data.author'));
    }
}
```

## Project-Specific Implementation Status

### Current Stack (This Project)

| Component | Version | Status |
|-----------|---------|--------|
| PHP | 8.4 | Configured |
| Symfony Framework | 8.0.* | Active |
| Doctrine ORM | 3.5 | Configured |
| Broadway (Event Sourcing) | 2.6 | Active |
| Tactician (Command Bus) | 1.6 | Active |
| RabbitMQ (Messaging) | via enqueue | Active |

### Symfony 8 Features Usage

| Feature | Status | Location |
|---------|--------|----------|
| AutoconfigureTag | Active | All command/query handlers |
| Lazy Objects | Not yet | Consider for heavy services |
| JsonPath | Not yet | Potential for API testing |
| ObjectMapper | Not yet | Potential for DTOs |
| Invokable Commands | Partial | CLI commands use traditional pattern |
| Twig Attributes | Not yet | No Twig usage in microservice |
| Asset Compression | N/A | API-only service |
| Stateless CSRF | Not yet | Consider for API endpoints |

### Recommended Upgrades

1. **Convert CLI Commands to Invokable Pattern**
```php
// Before (current)
final class EmulateHttpRequestCommand extends Command
{
    protected function configure(): void { ... }
    protected function execute(): int { ... }
}

// After (Symfony 8 pattern)
#[AsCommand(name: 'app:emulate-http')]
final class EmulateHttpRequestCommand
{
    public function __invoke(
        #[Argument] string $method,
        #[Argument] string $uri,
        #[Option] ?string $body = null
    ): int { ... }
}
```

2. **Add Lazy Loading to Heavy Services**
```php
#[Lazy]
final class ArticleSlugGeneratorService implements ArticleSlugGeneratorServiceInterface
{
    // Only initialized when slug generation is needed
}
```

3. **Implement JsonPath for API Testing**
```php
use Symfony\Component\JsonPath\JsonPath;

// In functional tests
$article = JsonPath::query($response, '$.data.article[?@.status == "published"]');
```

## Performance Optimization

### Cache Configuration for Symfony 8

```yaml
# config/packages/cache.yaml
framework:
    cache:
        app: cache.adapter.redis
        default_redis_provider: '%env(REDIS_URL)%'

        pools:
            # Event store cache
            event_store.cache:
                adapter: cache.adapter.redis
                default_lifetime: 86400
                tags: true

            # Read model cache
            read_model.cache:
                adapter: cache.adapter.redis
                default_lifetime: 3600
                tags: true

            # Query result cache
            query.cache:
                adapter: cache.adapter.redis
                default_lifetime: 300
```

### Container Optimization

```yaml
# config/services.yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true
        public: false
        # Symfony 8 optimization
        bind:
            $projectDir: '%kernel.project_dir%'
            $environment: '%kernel.environment%'

    # Lazy load expensive services
    App\Infrastructure\Service\ExpensiveService:
        lazy: true
```

## Migration from Symfony 7.3

### Upgrade Checklist

```bash
# 1. Verify PHP version
php -v  # Must be >= 8.4.0

# 2. Update composer.json
composer require "symfony/*:8.0.*" --update-with-dependencies

# 3. Clear caches
php bin/console cache:clear
rm -rf var/cache/*

# 4. Check for deprecations (should be none in 8.0)
php bin/console debug:container --deprecations

# 5. Run tests
./vendor/bin/phpunit

# 6. Verify application
php bin/console about
```

### Breaking Changes to Address

1. **Removed Deprecations**: All 7.x deprecated features removed
2. **PHP 8.4 Required**: Update Docker images, CI/CD pipelines
3. **Native Lazy Objects**: Update proxy configurations
4. **Configuration Format**: Consider migrating to PHP arrays

## Development Workflow

### Code Generation with Symfony 8 Patterns

```bash
# Generate invokable command
php bin/console make:command --invokable app:my-command

# Generate controller with attributes
php bin/console make:controller Api/ArticleController

# Debug container services
php bin/console debug:container --tag=tactician.handler

# Profile console commands
php bin/console app:process-article --profile
```

### Testing with New Components

```php
use Symfony\Component\JsonPath\JsonPath;
use Symfony\Component\ObjectMapper\ObjectMapper;

class ArticleApiTest extends ApiTestCase
{
    public function testCreateArticle(): void
    {
        $response = $this->post('/api/v2/article', [
            'title' => 'Test Article',
            'description' => 'Test description'
        ]);

        $data = json_decode($response->getContent(), true);

        // JsonPath assertions
        $this->assertNotEmpty(JsonPath::query($data, '$.data.id'));
        $this->assertEquals('draft', JsonPath::query($data, '$.data.status')[0]);

        // ObjectMapper for response validation
        $mapper = new ObjectMapper();
        $dto = $mapper->map($data['data'], ArticleDto::class);
        $this->assertInstanceOf(ArticleDto::class, $dto);
    }
}
```

## Quick Action Commands

### Common Development Tasks

```bash
# Create new Symfony 8 project
composer create-project symfony/skeleton:^8.0 my-project
cd my-project

# Add common packages
composer require symfony/console:^8.0
composer require symfony/cache:^8.0
composer require symfony/json-path:^8.0
composer require symfony/object-mapper:^8.0

# Generate static error pages
APP_ENV=prod php bin/console error:dump var/cache/prod/error_pages/

# Debug cache configuration
php bin/console debug:cache

# Check Symfony 8 requirements
php bin/console about
```

### Performance Optimization Commands

```bash
# Optimize for production
composer install --no-dev --optimize-autoloader
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod

# Asset optimization with pre-compression
php bin/console assets:install --symlink --relative

# Container analysis
php bin/console debug:container --show-arguments
```

## Additional Resources

- **Official Documentation**: [symfony.com/doc/8.0](https://symfony.com/doc/8.0)
- **Release Notes**: [symfony.com/releases/8.0](https://symfony.com/releases/8.0)
- **Upgrade Guide**: [symfony.com/doc/current/setup/upgrade_major.html](https://symfony.com/doc/current/setup/upgrade_major.html)
- **New Features Blog**: [symfony.com/blog/category/living-on-the-edge/8.0-7.4](https://symfony.com/blog/category/living-on-the-edge/8.0-7.4)
- **PHP 8.4 Lazy Objects RFC**: [wiki.php.net/rfc/lazy_objects](https://wiki.php.net/rfc/lazy_objects)
- **FrankenPHP**: [frankenphp.dev](https://frankenphp.dev)

---

*This technical knowledge map is optimized for Claude Code integration and DDD-based microservice development with Symfony 8.0. Last updated: December 2025*

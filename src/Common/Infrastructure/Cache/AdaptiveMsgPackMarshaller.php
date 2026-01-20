<?php

declare(strict_types=1);

namespace Micro\Component\Common\Infrastructure\Cache;

use Symfony\Component\Cache\Marshaller\DefaultMarshaller;
use Symfony\Component\Cache\Marshaller\MarshallerInterface;
use Symfony\Component\DependencyInjection\Attribute\Lazy;

/**
 * Adaptive Marshaller with MsgPack support and automatic fallback.
 *
 * Provides:
 * - MsgPack binary serialization when extension is available (~40% faster)
 * - Automatic fallback to PHP native serialization otherwise
 * - Seamless switching without configuration changes
 *
 * This ensures the application works regardless of msgpack extension availability.
 */
#[Lazy]
final class AdaptiveMsgPackMarshaller implements MarshallerInterface
{
    private readonly MarshallerInterface $delegate;

    private readonly bool $useMsgPack;

    public function __construct()
    {
        $this->useMsgPack = \extension_loaded('msgpack');
        $this->delegate = $this->useMsgPack
            ? new MsgPackMarshaller()
            : new DefaultMarshaller();
    }

    /**
     * Check if MsgPack is being used.
     */
    public function isMsgPackEnabled(): bool
    {
        return $this->useMsgPack;
    }

    public function marshall(array $values, ?array &$failed): array
    {
        return $this->delegate->marshall($values, $failed);
    }

    public function unmarshall(string $value): mixed
    {
        return $this->delegate->unmarshall($value);
    }
}

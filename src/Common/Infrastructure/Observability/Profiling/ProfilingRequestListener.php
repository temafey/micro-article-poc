<?php

declare(strict_types=1);

namespace Micro\Component\Common\Infrastructure\Observability\Profiling;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Automatically profiles HTTP requests using Pyroscope.
 *
 * Starts profiling on request start and uploads the profile
 * data to Pyroscope when the request terminates.
 *
 * ADR-014 Phase 4.2: Continuous Profiling Setup
 */
#[AsEventListener(event: KernelEvents::REQUEST, method: 'onRequest', priority: 1000)]
#[AsEventListener(event: KernelEvents::TERMINATE, method: 'onTerminate', priority: -1000)]
final class ProfilingRequestListener
{
    public function __construct(
        private readonly PyroscopeProfiler $profiler,
    ) {
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        $this->profiler->addLabel('http_method', $request->getMethod());
        $this->profiler->addLabel('http_route', $request->attributes->get('_route', 'unknown'));
        $this->profiler->addLabel('http_controller', $request->attributes->get('_controller', 'unknown'));

        $this->profiler->start();
    }

    public function onTerminate(TerminateEvent $event): void
    {
        $response = $event->getResponse();

        $this->profiler->addLabel('http_status', (string) $response->getStatusCode());

        $this->profiler->stop();
    }
}

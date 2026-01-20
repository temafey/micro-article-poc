<?php

declare(strict_types=1);

namespace Micro\Component\Common\Presentation\Rest;

use Micro\Component\Common\Infrastructure\Api\ApiVersionResolver;
use Nelmio\ApiDocBundle\Controller\DocumentationController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * API Documentation Controller.
 *
 * Provides versioned API documentation
 */
readonly class ApiDocController
{
    /**
     * @param DocumentationController $docController   Nelmio documentation controller
     * @param ApiVersionResolver      $versionResolver Version resolver service
     */
    public function __construct(
        private DocumentationController $docController,
        private ApiVersionResolver $versionResolver,
    ) {
    }

    /**
     * API V1 Documentation (Swagger UI).
     */
    #[Route('/api/v1/docs', name: 'api_v1_docs', methods: ['GET'])]
    public function docsV1(Request $request): Response
    {
        return $this->docController->__invoke($request, 'v1');
    }

    /**
     * API V2 Documentation (Swagger UI).
     */
    #[Route('/api/v2/docs', name: 'api_v2_docs', methods: ['GET'])]
    public function docsV2(Request $request): Response
    {
        return $this->docController->__invoke($request, 'v2');
    }

    /**
     * Latest API Documentation (JSON).
     */
    #[Route('/api/doc', name: 'api_latest_doc', methods: ['GET'])]
    public function docLatest(Request $request): Response
    {
        $latestVersion = $this->versionResolver->getLatestVersion();
        // Force JSON response by setting Accept header
        $request->headers->set('Accept', 'application/json');

        return $this->docController->__invoke($request, $latestVersion);
    }
}

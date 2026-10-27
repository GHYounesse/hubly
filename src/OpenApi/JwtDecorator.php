<?php

namespace App\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model;
use ApiPlatform\OpenApi\OpenApi;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;

/**
 * Documents POST /api/login (handled by the security firewall, so API Platform does not know it).
 */
#[AsDecorator('api_platform.openapi.factory')]
final class JwtDecorator implements OpenApiFactoryInterface
{
    public function __construct(private readonly OpenApiFactoryInterface $decorated) {}

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);
        $schemas = $openApi->getComponents()->getSchemas();

        $schemas['Token'] = new \ArrayObject([
            'type' => 'object',
            'properties' => [
                'token' => ['type' => 'string', 'readOnly' => true],
            ],
        ]);
        $schemas['Credentials'] = new \ArrayObject([
            'type' => 'object',
            'properties' => [
                'email' => ['type' => 'string', 'example' => 'admin@hubly.test'],
                'password' => ['type' => 'string', 'example' => 'Demo1234!'],
            ],
        ]);

        $login = new Model\PathItem(
            ref: 'JWT Token',
            post: new Model\Operation(
                operationId: 'postCredentialsItem',
                tags: ['Authentication'],
                responses: [
                    '200' => [
                        'description' => 'A JWT, valid for one hour.',
                        'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/Token']]],
                    ],
                    '401' => ['description' => 'Invalid credentials.'],
                    '429' => ['description' => 'Too many login attempts, retry later.'],
                ],
                summary: 'Get a JWT to call the API.',
                requestBody: new Model\RequestBody(
                    description: 'Your Hubly e-mail and password.',
                    content: new \ArrayObject([
                        'application/json' => ['schema' => ['$ref' => '#/components/schemas/Credentials']],
                    ]),
                ),
                security: [],
            ),
        );
        $openApi->getPaths()->addPath('/api/login', $login);

        return $openApi;
    }
}

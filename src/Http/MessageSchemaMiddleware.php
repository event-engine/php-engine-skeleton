<?php

declare(strict_types = 1);

namespace MyService\Http;

use EventEngine\EventEngine;
use EventEngine\Messaging\Message;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\JsonResponse;

final class MessageSchemaMiddleware implements RequestHandlerInterface
{
    /**
     * @var EventEngine
     */
    private $eventEngine;

    public function __construct(EventEngine $eventEngine)
    {
        $this->eventEngine = $eventEngine;
    }


    /**
     * Handle the request and return a response.
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var UriInterface $uri */
        $uri = $request->getAttribute('original_uri', $request->getUri());

        $serverUrl = $uri->withPath(str_replace('-schema', '', $uri->getPath()));

        $eventEngineSchema = $this->eventEngine->messageBoxSchema();

        $paths = [];

        foreach ($eventEngineSchema['properties']['commands'] as $messageName => $schema) {
            [$path, $pathDef] = $this->messageSchemaToPath($messageName, Message::TYPE_COMMAND, $schema);
            $paths[$path] = $pathDef;
        }

        foreach ($eventEngineSchema['properties']['events'] as $messageName => $schema) {
            [$path, $pathDef] = $this->messageSchemaToPath($messageName, Message::TYPE_EVENT, $schema);
            $paths[$path] = $pathDef;
        }

        foreach ($eventEngineSchema['properties']['queries'] as $messageName => $schema) {
            [$path, $pathDef] = $this->messageSchemaToPath($messageName, Message::TYPE_QUERY, $schema);
            $paths[$path] = $pathDef;
        }

        $componentSchemas = [];

        foreach ($eventEngineSchema['definitions'] ?? [] as $componentName => $componentSchema) {
            $componentSchemas[$componentName] = $this->jsonSchemaToOpenApiSchema($componentSchema);
        }

        $schema = [
            'openapi' => '3.0.0',
            'servers' => [
                [
                    'description' => 'MyService Server',
                    'url' => (string)$serverUrl
                ]
            ],
            'info' => [
                'description' => 'An endpoint for sending messages to MyService.',
                'version' => 'v0.1.0',
                'title' => 'MyService Message Box'
            ],
            'tags' => [
                [
                    'name' => 'queries',
                    'description' => 'Requests to read data from the system'
                ],
                [
                    'name' => 'commands',
                    'description' => 'Requests to write data to the system or execute an action',
                ],
                [
                    'name' => 'events',
                    'description' => 'Requests to notify the system about an event happened elsewhere'
                ]
            ],
            'paths' => $paths,
            'components' =>  ['schemas' => $componentSchemas],
        ];

        return new JsonResponse($schema);
    }

    private function messageSchemaToPath(string $messageName, string $messageType, array $messageSchema = null): array
    {
        $responses = [];

        if($messageType === Message::TYPE_QUERY) {
            $responses['200'] = [
                'description' => $messageSchema['response']['description'] ?? $messageName,
                'content' => [
                    'application/json' => [
                        'schema' => $this->jsonSchemaToOpenApiSchema($messageSchema['response'])
                    ]
                ]
            ];

            unset($messageSchema['response']);
        } else {
            $responses['202'] = [
                'description' => "$messageType accepted"
            ];
        }

        switch ($messageType) {
            case Message::TYPE_COMMAND:
                $tag = 'commands';
                break;
            case Message::TYPE_QUERY:
                $tag = 'queries';
                break;
            case Message::TYPE_EVENT:
                $tag = 'events';
                break;
            default:
                throw new \RuntimeException("Unknown message type given. Got $messageType");

        }

        return [
            "/{$messageName}",
            [
                'post' => [
                    'tags' => [$tag],
                    'summary' => $messageName,
                    'operationId' => "$messageType.$messageName",
                    'description' => $messageSchema['description'] ?? "Send a $messageName $messageType",
                    'requestBody' => [
                        'content' => [
                            'application/json' => [
                                'schema' => $this->jsonSchemaToOpenApiSchema($messageSchema)
                            ]
                        ]
                    ],
                    'responses' => $responses
                ]
            ]
        ];
    }

    private function jsonSchemaToOpenApiSchema(array $jsonSchema): array
    {
        if(isset($jsonSchema['type']) && is_array($jsonSchema['type'])) {
            $type = null;
            $containsNull = false;
            foreach ($jsonSchema['type'] as $possibleType) {
                if(\mb_strtolower($possibleType) !== 'null') {
                    if($type) {
                        throw new \RuntimeException("Got JSON Schema type defined as an array with more than one type + NULL set. " . \json_encode($jsonSchema));
                    }
                    $type = $possibleType;
                } else {
                    $containsNull = true;
                }
            }
            $jsonSchema['type'] = $type;
            if($containsNull) {
                $jsonSchema['nullable'] = true;
            }
        }

        if(isset($jsonSchema['properties']) && is_array($jsonSchema['properties'])) {
            foreach ($jsonSchema['properties'] as $propName => $propSchema) {
                $jsonSchema['properties'][$propName] = $this->jsonSchemaToOpenApiSchema($propSchema);
            }
        }

        if(isset($jsonSchema['items']) && is_array($jsonSchema['items'])) {
            $jsonSchema['items'] = $this->jsonSchemaToOpenApiSchema($jsonSchema['items']);
        }

        if(isset($jsonSchema['$ref'])) {
            $jsonSchema['$ref'] = str_replace('definitions', 'components/schemas', $jsonSchema['$ref']);
        }

        return $jsonSchema;
    }
}

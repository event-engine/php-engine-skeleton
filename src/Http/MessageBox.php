<?php

declare(strict_types=1);

namespace MyService\Http;

use EventEngine\Messaging\CommandDispatchResult;
use EventEngine\Messaging\MessageDispatcher;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\JsonResponse;

/**
 * One middleware for all commands and events
 */
final class MessageBox implements RequestHandlerInterface
{
    /**
     * @var MessageDispatcher
     */
    private $messageDispatcher;

    public function __construct(MessageDispatcher $messageDispatcher)
    {
        $this->messageDispatcher = $messageDispatcher;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $payload = null;
        $messageName = 'UNKNOWN';
        $metadata = [];

        try {
            $payload = $request->getParsedBody();

            $messageName = $request->getAttribute('message_name', $messageName);

            if (\is_array($payload) && isset($payload['message_name'])) {
                $messageName = $payload['message_name'];
                $metadata = $payload['metadata'] ?? [];
                $payload = $payload['payload'] ?? [];
            }

            $result = $this->messageDispatcher->dispatch($messageName, $payload, $metadata);

            if ($result === null || $result instanceof CommandDispatchResult) {
                return new EmptyResponse(StatusCodeInterface::STATUS_ACCEPTED);
            }

            if(is_object($result) && method_exists($result, 'toArray')) {
                $result = $result->toArray();
            }

            return new JsonResponse($result);
        } catch (\InvalidArgumentException $e) {
            throw new \RuntimeException(
                $e->getMessage(),
                StatusCodeInterface::STATUS_BAD_REQUEST,
                $e
            );
        }
    }
}

<?php
declare(strict_types=1);

namespace MyService\Http;

use Codeliner\ArrayReader\ArrayReader;
use EventEngine\Discolight\ServiceRegistry;
use EventEngine\EventEngine;
use MyService\System\PsrErrorLogger;
use Psr\Log\LoggerInterface;
use Zend\Diactoros\Response;
use Zend\ProblemDetails\ProblemDetailsMiddleware;
use Zend\ProblemDetails\ProblemDetailsResponseFactory;

trait HttpServices
{
    abstract public function logger(): LoggerInterface;
    abstract public function eventEngine(): EventEngine;

    //HTTP endpoints
    public function httpMessageBox(): MessageBox
    {
        return $this->makeSingleton(MessageBox::class, function () {
            return new MessageBox($this->eventEngine());
        });
    }

    public function eventEngineHttpMessageSchema(): MessageSchemaMiddleware
    {
        return $this->makeSingleton(MessageSchemaMiddleware::class, function () {
            return new MessageSchemaMiddleware($this->eventEngine());
        });
    }

    public function problemDetailsMiddleware(): ProblemDetailsMiddleware
    {
        return $this->makeSingleton(ProblemDetailsMiddleware::class, function() {
            $isDevEnvironment = $this->config()->stringValue('environment', 'prod') === 'dev';

            $errorHandler = new ProblemDetailsMiddleware(new ProblemDetailsResponseFactory(
                function() {
                    return new Response();
                },
                $isDevEnvironment
            ));
            $errorHandler->attachListener(new PsrErrorLogger($this->logger()));

            return $errorHandler;
        });
    }

    abstract protected function makeSingleton(string $serviceId, callable $factory);
    abstract protected function config(): ArrayReader;
    abstract protected function assertMandatoryConfigExists(string $path): void;
}

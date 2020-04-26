<?php
declare(strict_types = 1);

use EventEngine\DocumentStore\DocumentStore;
use EventEngine\EeCockpit\EeCockpitHandler;
use EventEngine\EventEngine;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\TextResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Laminas\HttpHandlerRunner\RequestHandlerRunner;
use Laminas\Stratigility\Middleware\ErrorResponseGenerator;
use Laminas\Stratigility\MiddlewarePipe;
use Mezzio\Helper\BodyParams\BodyParamsMiddleware;
use Mezzio\ProblemDetails\ProblemDetailsMiddleware;
use MyService\Http\CorsMiddleware;
use MyService\Http\OriginalUriMiddleware;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use function Laminas\Stratigility\middleware;
use function Laminas\Stratigility\path;

chdir(dirname(__DIR__));

require 'vendor/autoload.php';

/** @var \Psr\Container\ContainerInterface $container */
$container = include 'config/container.php';

//Note: this is important and needs to happen before further dependencies are pulled
$env = getenv('PROOPH_ENV')?: 'prod';
$devMode = $env === EventEngine::ENV_DEV;

$app = new MiddlewarePipe();

$app->pipe($container->get(ProblemDetailsMiddleware::class));

// CORS middleware ensures that cockpit can access the Event Engine backend, since it runs on another port
$app->pipe(new CorsMiddleware());

$app->pipe(new BodyParamsMiddleware());

$app->pipe(new OriginalUriMiddleware());

// Register Cockpit admin backend handler
$app->pipe(path(
    '/cockpit',
    middleware(function (Request $req, RequestHandler $handler) use($container, $env, $devMode): Response {
        /** @var EventEngine $eventEngine */
        $eventEngine = $container->get(EventEngine::class);
        $eventEngine->bootstrap($env, $devMode);

        $cockpitHandler = new EeCockpitHandler(
            $eventEngine,
            $container->get(DocumentStore::class)
        );

        return $cockpitHandler->handle($req);
    })
));

// Register Event Engine backend
$app->pipe(path(
    '/api',
    middleware(function (Request $req, RequestHandler $handler) use($container, $env, $devMode): Response {
        /** @var FastRoute\Dispatcher $router */
        $router = require 'config/api_router.php';

        $route = $router->dispatch($req->getMethod(), $req->getUri()->getPath());

        if ($route[0] === FastRoute\Dispatcher::NOT_FOUND) {
            return new EmptyResponse(404);
        }

        if ($route[0] === FastRoute\Dispatcher::METHOD_NOT_ALLOWED) {
            return new EmptyResponse(405);
        }

        foreach ($route[2] as $name => $value) {
            $req = $req->withAttribute($name, $value);
        }

        if(!$container->has($route[1])) {
            throw new \RuntimeException("Http handler not found. Got " . $route[1]);
        }

        $container->get(EventEngine::class)->bootstrap($env, $devMode);

        /** @var RequestHandler $httpHandler */
        $httpHandler = $container->get($route[1]);

        return $httpHandler->handle($req);
    })
));

$app->pipe(path('/', middleware(function (Request $request, $handler): Response {
    //@TODO add homepage with infos about event-engine and the skeleton
    return new TextResponse("It works");
})));

$server = new RequestHandlerRunner(
    $app,
    new SapiEmitter(),
    [ServerRequestFactory::class, 'fromGlobals'],
    function (Throwable $e) {
        $generator = new ErrorResponseGenerator();
        return $generator($e, new ServerRequest(), new \Laminas\Diactoros\Response());
    }
);

$server->run();


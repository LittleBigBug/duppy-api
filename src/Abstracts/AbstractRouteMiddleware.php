<?php
namespace Duppy\Abstracts;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

abstract class AbstractRouteMiddleware {
    /**
     * Request obj
     *
     * @var Request
     */
    protected static Request $request;

    /**
     * Request Handler obj
     *
     * @var RequestHandler
     */
    protected static RequestHandler $handler;

    /**
     * Response obj
     *
     * @var Response
     */
    protected static Response $response;

    /**
     * Calls the middleware
     *
     * @param Request $request
     * @param RequestHandler $handler
     * @return Response
     */
    final public function __invoke(Request $request, RequestHandler $handler): Response {
        static::$request = $request;
        static::$handler = $handler;
        static::$response = $handler->handle($request);

        $this->handle();

        return static::$response;
    }

    /**
     * Handles the middleware
     */
    abstract public function handle();

    /**
     * Get request obj
     *
     * @return Request
     */
    final protected static function getRequest(): Request {
        return static::$request;
    }

    /**
     * Get handler obj
     *
     * @return RequestHandler
     */
    final protected static function getHandler(): RequestHandler {
        return static::$handler;
    }

    /**
     * Get response obj
     *
     * @return Response
     */
    final protected static function getResponse(): Response {
        return static::$response;
    }
}

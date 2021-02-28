<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

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

        static::$response = new \Slim\Psr7\Response;

        $didNext = false;

        $next = function() use ($handler, $request, &$didNext) {
            if ($didNext) {
                return;
            }

            $response = $handler->handle($request);
            static::setResponse($response);

            $didNext = true;
        };

        $continue = $this->handle($next) ?? true;

        if ($continue && !$didNext) {
            static::$response = $handler->handle($request);
        }

        return static::$response;
    }

    /**
     * Handles the middleware
     *
     * @param callable $next Call Next middleware. Doesn't need to be called. It will be called if it hasn't and this function returns true.
     * @return ?bool
     */
    abstract public function handle(callable $next): ?bool;

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

    /**
     * Set the immutable response
     *
     * @param Response $response
     */
    final protected static function setResponse(Response $response) {
        static::$response = $response;
    }

}

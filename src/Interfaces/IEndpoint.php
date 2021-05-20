<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Interfaces;

/**
 * Endpoint Interface
 * @package Duppy\Interfaces
 */
interface IEndpoint {

    /**
     * Sets uri(s)
     *
     * @param array|string|null
     */
    public static function setUri(array|string|null $newUris);

    /**
     * Returns type(s) of requests accepted
     *
     * @return array
     */
    public static function getTypes(): array;

    /**
     * Returns uri(s)
     *
     * @return array|null
     */
    public static function getUri(): ?array;

    /**
     * Returns the uri function name map
     *
     * @return array|null
     */
    public static function getUriFuncMap(): ?array;

    /**
     * Returns the uri redirect map
     *
     * @return array|null
     */
    public static function getUriRedirectMap(): ?array;

    /**
     * Returns the uri map types
     *
     * @return array|boolean|null
     */
    public static function getUriMapTypes(): array|bool|null;

    /**
     * Returns the middleware for the endpoint
     *
     * @return array
     */
    public static function getMiddleware(): array;

    /**
     * Returns endpoint mapped middleware
     *
     * @return array
     */
    public static function getMappedMiddleware(): array;

    /**
     * Returns the endpoint parent group class name (AbstractEndpointGroup)
     *
     * @return string|null
     */
    public static function getParentGroupEndpoint(): ?string;

}
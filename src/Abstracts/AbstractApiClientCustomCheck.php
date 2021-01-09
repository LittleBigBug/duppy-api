<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Abstracts;

/**
 * Simple class that will have a function called to verify validity of ApiClient Tokens
 * @package Duppy\Abstracts
 */
abstract class AbstractApiClientCustomCheck {

    /**
     * Function that will validate tokens and return the success
     *
     * @param string $tokenCheck
     * @return bool
     */
    public function __invoke(string $tokenCheck): bool {
        return false;
    }

}
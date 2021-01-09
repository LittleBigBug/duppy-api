<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy;

use Duppy\Enum\DuppyError;
use Exception;
use Throwable;

/**
 * Generic Exception for use in Duppy for the DuppyError enum
 * @package Duppy
 */
class DuppyException extends Exception {

    protected DuppyError $errorCode;

    /**
     * DuppyException constructor.
     *
     * @param string $message
     * @param DuppyError|null $errorCode
     * @param Throwable|null $previous
     */
    public function __construct(string $message = "", ?DuppyError $errorCode = null, Throwable $previous = null) {
        $codeUse = DuppyError::unknown();

        if ($errorCode != null) {
            $codeUse = $errorCode;
        }

        parent::__construct($message, 0, $previous);
        $this->errorCode = $codeUse;
    }

    /**
     * @return DuppyError
     */
    public function getErrorCode(): DuppyError {
        return $this->errorCode;
    }

}
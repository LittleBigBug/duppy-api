<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy;

use Duppy\Enum\DuppyError;
use Exception;
use JetBrains\PhpStorm\Pure;
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
    #[Pure]
    public function __construct(?DuppyError $errorCode = null, string $message = "", Throwable $previous = null) {
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
    #[Pure]
    public function getErrorCode(): DuppyError {
        return $this->errorCode;
    }

    /**
     * @return DuppyError
     */
    #[Pure]
    public function err(): DuppyError {
        return $this->getErrorCode();
    }

}
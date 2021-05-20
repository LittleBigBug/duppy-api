<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Abstracts;

use JetBrains\PhpStorm\Pure;

abstract class AbstractEntity {

    /**
     * Each entity class needs their own definition of this function so that doctrine knows to use it for lazy-loading
     * Returns a property
     *
     * @param string $property
     * @return mixed
     */
    #[Pure] abstract public function get(string $property): mixed;

}
<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Bootstrapper;

class DCache {

    /**
     * @var mixed
     */
    protected mixed $object;

    /**
     * Optional creator callable to automatically create when the get function is called
     * 
     * @var DCallable
     */
    protected DCallable $creator;

    /**
     * DCache constructor
     * 
     * @param ?DCallable $creator = null
     */
    public function __construct(?DCallable $creator = null) {
        $this->creator = $creator ?? new DCallable;
    }

    /**
     * Set the cached object
     * 
     * @param mixed $object
     * @return mixed $object
     */
    public function setObject(mixed $object) {
        $this->object = $object;
        return $object;
    }

    /**
     * Clears this objects cache
     */
    public function clear() {
        $this->setObject(null);
    }

    /**
     * Get and try to generate the cached object
     * 
     * @return mixed
     */
    public function get(): mixed {
        if ($this->object == null) {
            return $this->object = $this->creator->invoke();
        }

        return $this->object;
    }

}
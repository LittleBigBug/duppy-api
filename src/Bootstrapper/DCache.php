<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Bootstrapper;

use Serializable;

class DCache {

    /**
     * @var mixed
     */
    protected mixed $object = null;

    /**
     * Optional creator callable to automatically create when the get function is called
     * 
     * @var DCallable
     */
    protected DCallable $creator;

    /**
     * Array of references to objects (that are Serializable) that we should watch
     *
     * @var ?Serializable[]
     */
    protected ?array $onChangeRefs;

    /**
     * @var ?Serializable[]
     */
    protected ?array $onChangeRefHash;

    /**
     * DCache constructor
     * 
     * @param ?DCallable $creator = null
     */
    public function __construct(?DCallable $creator = null) {
        $this->creator = $creator ?? new DCallable;
    }

    /**
     * Set the creator to recreate a cache object as needed
     *
     * @param DCallable $creator
     */
    public function setCreator(DCallable $creator) {
        $this->creator = $creator;
    }

    /**
     * Set the cached object
     * 
     * @param mixed $object
     * @return mixed $object
     */
    public function setObject(mixed $object): mixed {
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
     * Checks hashes for any objects changed within onChangeRefs
     * 
     * @return mixed
     */
    public function get(): mixed {
        $objectChanged = false;

        if ($this->onChangeRefs != null) {
            foreach ($this->onChangeRefs as $id => $ref) {
                $storedHash = $this->onChangeRefHash[$id];

                $srl = serialize($ref);
                $genHash = md5($srl);

                if ($storedHash == null || $storedHash !== $genHash) {
                    $objectChanged = true;
                    $this->onChangeRefHash[$id] = $genHash;
                    continue;
                }
            }
        }

        if ($objectChanged || $this->object == null) {
            return $this->object = $this->creator->invoke();
        }

        return $this->object;
    }

    /**
     * Set a createOnChange hook (to the specified ID) to check the hash of the referenced object
     * every time its retrieved, and if the hash mismatches then serve a new object from the creator DCallable
     *
     * @param Serializable &$ref
     * @param int $id = 0
     */
    public function createOnChange(Serializable &$ref, int $id = 0) {
        if ($this->onChangeRefs == null) {
            $this->onChangeRefs = [];
        }

        $srl = serialize($ref);
        $hash = md5($srl);

        $this->onChangeRefs[$id] = &$ref;
        $this->onChangeRefHash[$id] = $hash;
    }

    /**
     * Removes a createOnChange hook of the specified ID (default 0)
     *
     * @param int $id = 0
     */
    public function removeOnChange(int $id = 0) {
        unset($this->onChangeRefs[$id]);
        unset($this->onChangeRefHash[$id]);

        if (sizeof($this->onChangeRefs) < 1) {
            $this->onChangeRefs = null;
            $this->onChangeRefHash = null;
        }
    }

}
<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Entities;

use Duppy\Abstracts\AbstractApiClientCustomCheck;

/**
 * ApiClient Entity
 *
 * Abstract entity that allows authoritative logins to the API directly without a JWT Authorization
 *
 * @ORM\Entity
 * @ORM\Table(name="apiclients")
 */
class ApiClients {

    /**
     * @ORM\Id
     * @ORM\Column(type="string")
     */
    protected string $clientId;

    /**
     * Authentication Method
     * Default is below but you can use "custom"
     * @ORM\Column(type="string")
     */
    protected string $method;

    /**
     * Authentication Token (hashed as a password)
     * Only used when the above method is null or 'token'
     *
     * This is a cleartext string as a function if the above method is "custom".
     * The method should accept one argument (the token passed) and return a bool if it is successful
     * @ORM\Column(type="string")
     */
    protected string $token;

    // Each entity class needs their own version of this function so that doctrine knows to use it for lazy-loading
    /**
     * Return a property
     *
     * @param string $property
     * @return mixed
     */
    public function get(string $property) {
        return $this->$property;
    }

    /**
     * @param string $clientId
     */
    public function setClientId(string $clientId) {
        $this->clientId = $clientId;
    }

    /**
     * @param string $method
     */
    public function setMethod(string $method) {
        $this->method = $method;
    }

    /**
     * Default mode 'token':
     * Hashes a raw token and sets it
     *
     * Mode 'custom':
     * Uses a class name string Duppy\Class\Name and invokes it (__invoke)
     *
     * Returns its success
     * @param string $token
     * @return bool
     */
    public function setToken(string $token): bool {
        $set = $token;

        if ($this->method == null || $this->method == "token") {
            $hash = password_hash($token, PASSWORD_DEFAULT);

            if ($hash == false) {
                return false;
            }

            $set = $hash;
        }

        $this->token = $set;
        return true;
    }

    /**
     * Verifies a raw token against the stored hash
     * Returns its success
     * @param string $oToken
     * @return bool
     */
    public function checkToken(string $oToken): bool {
        if ($this->method != null) {
            switch ($this->method) {
                case "custom":
                    $className = $this->token;

                    if (!is_subclass_of($className, AbstractApiClientCustomCheck::class)) {
                        return false;
                    }

                    $classInst = new $className;
                    return $classInst($oToken);
            }
        }

        // Default to Token style (hashed)
        return password_verify($oToken, $this->token);
    }

}
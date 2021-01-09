<?php
/*
 *                  This file is part of Duppy Suite
 *                         https://dup.drm.gg
 *                               -= * =-
 */

namespace Duppy\Attributes;

use Attribute;
use Duppy\Enum\DuppyError;
use Duppy\Util;
use ReflectionException;
use ReflectionFunction;

#[Attribute]
class DupHook {

    protected string $eventName;

    protected int $priority;

    protected static array $hookCache = [];

    /**
     * @return string
     */
    public function getEventName(): string {
        return $this->eventName;
    }

    /**
     * @return int
     */
    public function getPriority(): int {
        return $this->priority;
    }

    /**
     * DupHook constructor.
     * @param string $eventName
     * @param int $priority
     */
    public function __construct(string $eventName, int $priority = 5) {
        $this->eventName = $eventName;
        $this->priority = $priority;
    }

    /**
     * @param string $eventName
     * @return array
     * @throws ReflectionException
     */
    private static function GetHooks(string $eventName): array {
        $cache = Util::indArrayNull(static::$hookCache, $eventName);

        if ($cache == null) {
            $funcs = get_defined_functions();
            $userFuncs = $funcs["user"];

            foreach ($userFuncs as $function) {
                $func = new ReflectionFunction($function);
                $attributes = $func->getAttributes();

                if (count($attributes) < 1) {
                    continue;
                }

                foreach ($attributes as $attribute) {
                    if (!is_subclass_of($attribute, DupHook::class)) {
                        continue;
                    }

                    $evName = $attribute->getEventName();

                    if ($evName != $eventName) {
                        continue;
                    }

                    $cache[] = [
                      "function" => $func,
                      "priority" => $attribute->getPriority(),
                    ];
                }
            }

            // Sort newly found hooks by priority
            if (count($cache) > 0) {
                $cmp = function($hookA, $hookB) {
                    $a = $hookA["priority"];
                    $b = $hookB["priority"];

                    // https://i.imgur.com/sXCMKVR.mp4
                    return $b <=> $a;
                };

                usort($cache, $cmp);
                static::$hookCache[$eventName] = $cache;
            }
        }

        return $cache;
    }

    /**
     * Calls all hook functions by the event name with the arguments.
     * Hooks will stop being called in the order of their priority (descending) until a function returns something.
     * That value is then immediately returned.
     *
     * @param string $eventName
     * @param mixed $args
     * @return mixed
     * @throws ReflectionException
     */
    public static function CallHook(string $eventName, ...$args): mixed {
        $hooks = static::GetHooks($eventName);

        if (count($hooks) < 1) {
            return DuppyError::noneFound();
        }

        $ret = null;

        foreach ($hooks as $hook) {
            $func = $hook["function"];

            if (!is_subclass_of($func, ReflectionFunction::class)) {
                continue;
            }

            $return = $func->invoke(...$args);

            if ($return != null) {
                $ret = $return;
                break;
            }
        }

        return $ret;
    }

}
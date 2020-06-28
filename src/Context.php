<?php
declare(strict_types=1);


namespace EasySwoole\Hyperf\Database;

use Hyperf\Utils\Arr;
use Hyperf\Utils\Coroutine;
use Swoole\Coroutine as SwCoroutine;

class Context
{
    protected static $nonCoContext = [];

    public static function inCoroutine(): bool
    {
        return Coroutine::id() > 0;
    }

    public static function set(string $id, $value)
    {
        if (self::inCoroutine()) {
            SwCoroutine::getContext()[$id] = $value;
        } else {
            static::$nonCoContext[$id] = $value;
        }
        return $value;
    }

    public static function get(string $id, $default = null, $coroutineId = null)
    {
        if (self::inCoroutine()) {
            if ($coroutineId !== null) {
                return SwCoroutine::getContext($coroutineId)[$id] ?? $default;
            }
            return SwCoroutine::getContext()[$id] ?? $default;
        }

        return static::$nonCoContext[$id] ?? $default;
    }

    public static function has(string $id, $coroutineId = null)
    {
        if (self::inCoroutine()) {
            if ($coroutineId !== null) {
                return isset(SwCoroutine::getContext($coroutineId)[$id]);
            }
            return isset(SwCoroutine::getContext()[$id]);
        }

        return isset(static::$nonCoContext[$id]);
    }

    /**
     * Release the context when you are not in coroutine environment.
     * @param string $id
     */
    public static function destroy(string $id)
    {
        unset(static::$nonCoContext[$id]);
    }

    /**
     * Copy the context from a coroutine to current coroutine.
     * @param int $fromCoroutineId
     * @param array $keys
     */
    public static function copy(int $fromCoroutineId, array $keys = []): void
    {
        /**
         * @var \ArrayObject
         * @var \ArrayObject $current
         */
        $from = SwCoroutine::getContext($fromCoroutineId);
        $current = SwCoroutine::getContext();
        $current->exchangeArray($keys ? Arr::only($from->getArrayCopy(), $keys) : $from->getArrayCopy());
    }

    /**
     * Retrieve the value and override it by closure.
     * @param string $id
     * @param \Closure $closure
     * @return mixed
     */
    public static function override(string $id, \Closure $closure)
    {
        $value = null;
        if (self::has($id)) {
            $value = self::get($id);
        }
        $value = $closure($value);
        self::set($id, $value);
        return $value;
    }

    /**
     * Retrieve the value and store it if not exists.
     * @param string $id
     * @param mixed $value
     * @return mixed|null
     */
    public static function getOrSet(string $id, $value)
    {
        if (! self::has($id)) {
            return self::set($id, value($value));
        }
        return self::get($id);
    }

    public static function getContainer()
    {
        if (Coroutine::inCoroutine()) {
            return SwCoroutine::getContext();
        }

        return static::$nonCoContext;
    }
}

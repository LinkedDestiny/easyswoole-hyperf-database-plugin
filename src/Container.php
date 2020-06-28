<?php
declare(strict_types=1);


namespace EasySwoole\Hyperf\Database;


use EasySwoole\Component\Di;
use EasySwoole\Component\Singleton;
use Psr\Container\ContainerInterface;
use Throwable;

class Container implements ContainerInterface
{
    use Singleton;

    /**
     * @param string $id
     * @return callable|mixed|string|null
     * @throws Throwable
     */
    public function get($id)
    {
        return Di::getInstance()->get($id);
    }

    /**
     * @param string $id
     * @return callable|mixed|string|null
     * @throws Throwable
     */
    public function has($id)
    {
        return Di::getInstance()->get($id) != null;
    }
}
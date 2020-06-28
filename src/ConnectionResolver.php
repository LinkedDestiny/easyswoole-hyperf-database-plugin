<?php
declare(strict_types=1);

namespace EasySwoole\Hyperf\Database;

use EasySwoole\Component\Singleton;
use EasySwoole\Pool\Manager;
use Hyperf\Database\ConnectionInterface;
use Hyperf\Database\ConnectionResolverInterface;

class ConnectionResolver implements ConnectionResolverInterface
{
    use Singleton;

    /**
     * The default connection name.
     *
     * @var string
     */
    protected $default = 'default';

    public function __construct()
    {

    }

    /**
     * Get a database connection instance.
     *
     * @param string $name
     * @return ConnectionInterface
     */
    public function connection($name = null)
    {
        if (is_null($name)) {
            $name = $this->getDefaultConnection();
        }

        $connection = null;
        $id = $this->getContextKey($name);
        if (Context::has($id)) {
            $connection = Context::get($id);
        }

        if (! $connection instanceof ConnectionInterface) {
            $pool = Manager::getInstance()->get($name);
            /** @var ConnectionInterface $connection */
            $connection = $pool->getObj();
            try {
                Context::set($id, $connection);
            } finally {
                if (Context::inCoroutine()) {
                    defer(function () use ($pool, $connection) {
                        $pool->recycleObj($connection);
                    });
                }
            }
        }

        return $connection;
    }

    /**
     * Get the default connection name.
     *
     * @return string
     */
    public function getDefaultConnection()
    {
        return $this->default;
    }

    /**
     * Set the default connection name.
     *
     * @param string $name
     */
    public function setDefaultConnection($name)
    {
        $this->default = $name;
    }

    /**
     * The key to identify the connection object in coroutine context.
     * @param mixed $name
     * @return string
     */
    private function getContextKey($name): string
    {
        return sprintf('database.connection.%s', $name);
    }
}

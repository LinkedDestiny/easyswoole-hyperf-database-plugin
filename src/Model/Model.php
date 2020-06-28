<?php
declare(strict_types=1);

namespace EasySwoole\Hyperf\Database\Model;

use EasySwoole\Hyperf\Database\ConnectionResolver;
use EasySwoole\Hyperf\Database\Container;
use Hyperf\Database\ConnectionInterface;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Throwable;

class Model extends \Hyperf\Database\Model\Model
{
    /**
     * @var string the full namespace of repository class
     */
    protected $repository;

    /**
     * Get the database connection for the model.
     * @throws Throwable
     */
    public function getConnection(): ConnectionInterface
    {
        $connectionName = $this->getConnectionName();
        return ConnectionResolver::getInstance()->connection($connectionName);
    }

    /**
     * @throws RuntimeException when the model does not define the repository class
     */
    public function getRepository()
    {
        if (! $this->repository || ! class_exists($this->repository) && ! interface_exists($this->repository)) {
            throw new RuntimeException(sprintf('Cannot detect the repository of %s', static::class));
        }
        return $this->getContainer()->get($this->repository);
    }

    protected function getContainer(): ContainerInterface
    {
        return Container::getInstance();
    }
}
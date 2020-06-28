<?php
namespace EasySwoole\Hyperf\Database\Pool;

use EasySwoole\Hyperf\Database\Container;
use EasySwoole\Pool\Config as PoolConfig;
use EasySwoole\Pool\AbstractPool;
use Hyperf\Database\Connectors\ConnectionFactory;
use Throwable;

class MysqlPool extends AbstractPool
{
    /**
     * @var array
     */
    protected $dbConfig;

    /**
     * @var ConnectionFactory
     */
    private $factory;

    /**
     * MysqlPool constructor.
     * @param array $dbConfig
     * @throws Throwable
     */
    public function __construct(array $dbConfig)
    {
        $poolConfig = new PoolConfig($dbConfig['pool']);
        parent::__construct($poolConfig);

        $this->dbConfig = $dbConfig;
        $this->factory = new ConnectionFactory(Container::getInstance());
    }

    protected function createObject()
    {
        return $this->factory->make($this->dbConfig);
    }
}
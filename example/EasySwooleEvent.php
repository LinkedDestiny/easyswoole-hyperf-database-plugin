<?php

namespace EasySwoole\EasySwoole;

use EasySwoole\EasySwoole\Swoole\EventRegister;
use EasySwoole\EasySwoole\AbstractInterface\Event;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;
use EasySwoole\Hyperf\Database\Pool\MysqlPool;
use EasySwoole\Pool\Manager;
use Throwable;

class EasySwooleEvent implements Event
{
    /**
     * @throws Throwable
     */
    public static function initialize()
    {
        // TODO: Implement initialize() method.
        date_default_timezone_set('Asia/Shanghai');

        // db
        $config = Config::getInstance()->getConf("MYSQL");
        foreach ($config as $name => $conf) {
            Manager::getInstance()->register(new MysqlPool($conf),$name);
        }
    }

    public static function mainServerCreate(EventRegister $register)
    {

    }


    public static function afterRequest(Request $request, Response $response): void
    {
        // TODO: Implement afterAction() method.
    }

    public static function onRequest(Request $request, Response $response): bool
    {
        // TODO: Implement onRequest() method.
    }
}

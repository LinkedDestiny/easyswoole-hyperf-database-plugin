<?php
return [
    'SERVER_NAME' => "EasySwoole",
    'MAIN_SERVER' => [
        'LISTEN_ADDRESS' => '0.0.0.0',
        'PORT'           => 9501,
        'SERVER_TYPE'    => EASYSWOOLE_WEB_SERVER, //可选为 EASYSWOOLE_SERVER  EASYSWOOLE_WEB_SERVER EASYSWOOLE_WEB_SOCKET_SERVER,EASYSWOOLE_REDIS_SERVER
        'SOCK_TYPE'      => SWOOLE_TCP,
        'RUN_MODEL'      => SWOOLE_PROCESS,
        'SETTING'        => [
            'worker_num'    => 2,
            'reload_async'  => true,
            'max_wait_time' => 3,
        ]
    ],

    'TEMP_DIR' => '/tmp/easyswoole',
    'LOG_DIR'  => null,

    "MYSQL" => [
        'default' => [
            'driver'    => 'mysql',
            'host'      => '127.0.0.1',
            'port'      => 3306,
            'database'  => 'test',
            'username'  => 'root',
            'password'  => '12345678',
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => 't_',
            'pool'      => [
                'intervalCheckTime' => 10*1000,
                'maxIdleTime' => 60,
                'maxObjectNum' => 10,
                'minObjectNum' => 1,
                'getObjectTimeout' => 3.0,
            ],
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_STRINGIFY_FETCHES => false,
            ],
        ],
    ],
];

<?php

namespace modules\orm;

use framework\Project;
use framework\modules\AbstractModule;

class Module extends AbstractModule {

    /**
     * @var \PDO
     */
    static $client;

    public function getName(){
        return 'ORM';
    }

    private $init = false;

    public function initConnection(){
        if ( $this->init ) return;

        $this->init = true;
        $project = Project::current();
        $config  = $project->config;

        $dbHost    = $config->get('orm.host');
        $dbName    = $config->get('orm.dbname', 'regenix');
        $dbUser    = $config->get('orm.user', 'root');
        $dbPass    = $config->get('orm.password');
        $dbTimeout = $config->getNumber('orm.timeout', 0);

        self::$client = new \PDO($dbHost . ';dbname=' .$dbName, $dbUser, $dbPass);
        self::$client->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
    }

    public function __construct(){


    }
}
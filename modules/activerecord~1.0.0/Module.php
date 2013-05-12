<?php
namespace modules\activerecord;

use framework\Project;
use framework\exceptions\CoreException;
use framework\lang\String;
use framework\modules\AbstractModule;

class Module extends AbstractModule {

    public function getName(){
        return 'Active Record';
    }

    private $init = false;

    public function initConnection(){
        if ( $this->init ) return;

        $this->init = true;
        $project = Project::current();
        $config  = $project->config;

        require __DIR__ . '/idiorm.php';

        $url = $config->getString('db.url', 'sqlite::memory:');
        \ORM::configure(array(
            'connection_string' => $config->getString('db.url', $url),
            'username' => $config->getString('db.username'),
            'password' => $config->getString('db.password')
        ));

        $driver = substr($url, 0, strpos($url, ':'));
        if (!in_array($driver, \PDO::getAvailableDrivers(), true)){
            throw new \PDOException(String::format('Unable load `php_pdo_%s` pdo driver extension, please install it!', $driver));
        }

        \ORM::configure('error_mode', APP_MODE_STRICT === true ? \PDO::ERRMODE_WARNING : \PDO::ERRMODE_EXCEPTION);
    }

    public function __construct(){
        if ( !extension_loaded( 'pdo' ) ){
            throw CoreException::formated('Unable to load `php_pdo` extension, please install it!');
        }
    }
}
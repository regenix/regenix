<?php
namespace regenix\mvc;

use regenix\core\Regenix;
use regenix\lang\IClassInitialization;

/**
 * Model Class
 */
class Model extends \RedBean_SimpleModel implements IClassInitialization {

    /** @var string|null */
    public $id;

    function __construct() {
        foreach ((array)$this as $prop => $value) {
            if ($prop[0] !== "\0")
                unset($this->{$prop});
        }
    }

    public function __get($prop) {
        $propTypes = $this->getPropertyTypes();
        if ($type = $propTypes[$prop]) {
            if (!(($value = parent::__get($prop)) instanceof Model))
                $value = $this->fetchAs($type)->{$prop};
            return $value;
        } else {
            return parent::__get($prop);
        }
    }

    /**
     * @return array
     */
    protected function getPropertyTypes() {
        return [];
    }

    /**
     * @return null|string
     */
    public function getId() {
        return $this->bean->getID();
    }

    /**
     * @return bool
     */
    public function isNew() {
        return !!$this->getId();
    }

    /**
     * @param string $type
     * @return \RedBeanPHP\OODBBean
     */
    public function fetchAs($type) {
        return $this->unbox()->fetchAs($type);
    }

    public static function initialize() {
        if (!defined('REDBEAN_MODEL_PREFIX')) {
            $app = Regenix::app();
            $prefix = '\\models\\';

            if ($app) {
                $prefix = $app->config->getString('db.model_prefix', $prefix);

                $dsn = $app->config->get('db.address', 'localhost');
                $password = $app->config->get('db.password');
                $user = $app->config->get('db.user', 'root');
                $frozen = $app->config->getBoolean('db.frozen', false);

                \R::setup($dsn, $user, $password, $frozen);
                \R::selectDatabase( 'default' );
            }
            define('REDBEAN_MODEL_PREFIX', $prefix);
        }
    }
}
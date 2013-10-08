<?php
namespace regenix {
    use regenix\core\Regenix;

    define('REGENIX_ROOT', str_replace(DIRECTORY_SEPARATOR, '/', __DIR__) . '/');
    require REGENIX_ROOT . 'core/Regenix.php';
}

namespace {

    define('PHP_TRAITS', function_exists('trait_exists'));

    function dump($var){
        echo '<pre class="_dump">';
        print_r($var);
        echo '</pre>';
    }

    /**
     * get absolute all traits
     * @param $class
     * @param bool $autoload
     * @return array
     */
    function class_uses_all($class, $autoload = true) {
        $traits = array();
        if (!PHP_TRAITS)
            return $traits;

        do {
            $traits = array_merge(class_uses($class, $autoload), $traits);
        } while($class = get_parent_class($class));
        foreach ($traits as $trait => $same) {
            $traits = array_merge(class_uses($trait, $autoload), $traits);
        }
        return array_unique($traits);
    }

    /**
     * check usage trait in object
     * @param $object
     * @param $traitName
     * @param bool $autoload
     * @return bool
     */
    function trait_is_use($object, $traitName, $autoload = true){
        if (!PHP_TRAITS)
            return false;

        $traits = class_uses_all($object, $autoload);
        return isset($traits[$traitName]);
    }
}



<?php
namespace controllers\api;

use ide\Plugin;

class CoreApi extends Api {

    protected static function jsonPlugin(Plugin $plugin){
        if ($plugin == null)
            return null;

        return array(
            'class' => get_class($plugin),
            'name'  => $plugin->getName(),
            'assets' => $plugin->getRealAssets(),
            'all_assets' => $plugin->getAllRealAssets()
        );
    }

    public function plugins(){
        $plugins = Plugin::getAll();
        $result  = array();
        foreach($plugins as $plugin){
            $result[] = self::jsonPlugin($plugin);
        }
        return $result;
    }

    public function plugin($class = null){
        foreach(Plugin::getAll() as $plugin){
            if ($class === get_class($plugin))
                return $plugin;
        }
        $this->notFound();
    }
}
<?php
namespace ide;


abstract class EditorType {

    /**
     * @return Plugin
     */
    public function getPlugin(){
        return Plugin::getByInstance($this);
    }

    /**
     * @return array of files js/css etc.
     */
    protected function getAssets(){
        return array();
    }

    /**
     * @return array
     */
    final public function getRealAssets(){
        $result = array();
        foreach($this->getAssets() as $asset){
            $plugin = $this->getPlugin();
            $result[] = $plugin ? $plugin->getAssetRealPath($asset) : $asset;
        }
        return $result;
    }

    /**
     * init javascript code
     * @return string
     */
    public function getJavaScript(){
        return '';
    }

    /**
     * @return array
     */
    public function toJson(){
        return array('assets' => $this->getRealAssets());
    }
}
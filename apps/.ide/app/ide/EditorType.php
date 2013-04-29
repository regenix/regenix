<?php
namespace ide;


abstract class EditorType {

    /**
     * @return array of files js/css etc.
     */
    public function getAssets(){
        return array();
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
        return array('assets' => $this->getAssets());
    }
}
<?php

namespace framework\config;

class PropertiesConfiguration extends Configuration {

    public function loadData(){

        $handle = fopen($this->file->getAbsolutePath(), "r+");
        while (($buffer = fgets($handle, 4096)) !== false) {

            $line = explode('=', $buffer, 2);
            if ( sizeof($line) < 2 ) continue;
            if ( strpos(ltrim($line[0]), '#') === 0) continue;

            $this->addProperty( trim($line[0]), trim($line[1]) );
        }
    }
    
    
    public function addProperty($key, $value){
        $this->data[ $key ] = $value;
    }

    public function clearProperty($key){
        if ( isset($this->data[$key]) )
            $this->data[$key] = null;
    }

    public function containsKey($key){
        return isset($this->data[$key]);
    }

    public function get($key, $default = null){

        if ( $this->containsKey($key) )
            return $this->data[ $key ];

        return $default;
    }

    public function getNumber($key, $default = 0){
        if ( $this->containsKey($key) )
            return (int)$this->data[ $key ];

        return (int)$default;
    }

    public function getDouble($key, $default = 0.0){
        if ( $this->containsKey($key) )
            return (double)$this->data[ $key ];

        return (double)$default;
    }

    public function getBoolean($key, $default = false){
        if ( $this->containsKey($key) )
            return $this->data[ $key ] !== '' && $this->data[ $key ] != 0;

        return $default !== false && $default !== '' && $default != 0;
    }

    public function getString($key, $default = ""){
        if ( $this->containsKey($key) )
            return (string)$this->data[ $key ];

        return (string)$default;
    }

    public function getArray($key, $default = array()){

        if ( !$this->containsKey($key) )
            $result = $default;
        else
            $result = $this->data[ $key ];

        if ( is_string($result) || is_object($result) ){
            $result = explode(',', (string)$result, 100);
            $result = array_map('trim', $result);
        } else if ( is_array($result) ){
            $result = array_map('trim', $result);
        } else
            throw new ConfigurationReadException($this,
                StringUtils::format('Can\'t transform default to array type for "%s"', $key));

        return $result;
    }

    public function getKeys(){
        return array_keys($this->data);
    }

    /**
     * @param $prefix
     * @return Configuration
     */
    public function subset($prefix){

        $result = new Configuration();
        foreach($this->data as $key => $value){
            if ( strpos($key, $prefix) === 0 ){
                $newKey = substr($key, strlen($prefix));
                $result->addProperty( $newKey, $value );
            }
        }

        return $result;
    }
}

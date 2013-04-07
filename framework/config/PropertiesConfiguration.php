<?php

namespace framework\config;

class PropertiesConfiguration extends Configuration {

    const type = __CLASS__;

    private $env = false;

    public function loadData(){
        $files = $this->files;
        if ( !$files )
            $files = array($this->file);
        
        foreach ($files as $file){
            if (!$file->exists()) continue;
            
            $handle = fopen($file->getAbsolutePath(), "r+");
            while (($buffer = fgets($handle, 4096)) !== false) {

                $line = explode('=', $buffer, 2);
                if ( sizeof($line) < 2 ) continue;
                if ( strpos(ltrim($line[0]), '#') === 0) continue;

                $this->addProperty( trim($line[0]), trim($line[1]) );
            }
            fclose($handle);
        }
    }
    
    public function setEnv($env){
        $this->env = $env;
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

    /**
     * @param $key
     * @param null $default
     * @return null|mixed
     */
    public function get($key, $default = null){
        if ($this->env && isset($this->data[ $tmp = $this->env . '.' . $key ]) )
            $value = $this->data[ $tmp ];
        else if ( $this->containsKey($key) )
            $value = $this->data[ $key ];

        return $value && $value != '!default' ? $value : $default;
    }

    /**
     * @param $key
     * @param int $default
     * @return int
     */
    public function getNumber($key, $default = 0){
        return (int)$this->get($key, $default);
    }

    /**
     * @param $key
     * @param float $default
     * @return float
     */
    public function getDouble($key, $default = 0.0){
        return (double)$this->get($key, $default);
    }

    /**
     * @param $key
     * @param bool $default
     * @return bool
     */
    public function getBoolean($key, $default = false){
        $value = $this->get($key, $default);
        return $value !== false && $value !== '' && $value !== '0' && $value !== 'off' && $value != 0;
    }

    /**
     * @param $key
     * @param string $default
     * @return string
     */
    public function getString($key, $default = ""){
        return (string)$this->get($key, $default);
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
     * @deprecated
     * @param $prefix
     * @return Configuration
     */
    public function subset($prefix){
        $result = new Configuration(null);
        foreach($this->data as $key => $value){
            if ( strpos($key, $prefix) === 0 ){
                $newKey = substr($key, strlen($prefix));
                //$result->addProperty( $newKey, $value );
            }
        }

        return $result;
    }
}

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
}

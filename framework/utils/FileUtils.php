<?php

namespace framework\utils;

class FileUtils {
    
    
    public static function getExtension($path){

        return pathinfo($path, PATHINFO_EXTENSION); 
    }
}
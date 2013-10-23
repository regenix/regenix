<?php
namespace regenix\exceptions;

use regenix\lang\CoreException;

class JsonParseException extends CoreException {

    public function __construct(){
        $error = json_last_error();
        $msg = 'json parse error';
        switch($error){
            case JSON_ERROR_DEPTH: $msg = 'maximum stack depth has been exceeded'; break;
            case JSON_ERROR_STATE_MISMATCH: $msg = 'invalid or malformed JSON'; break;
            case JSON_ERROR_CTRL_CHAR: $msg = 'control character error, possibly incorrectly encoded'; break;
            case JSON_ERROR_SYNTAX: $msg = 'syntax error'; break;
            /*JSON_ERROR_UTF8*/ case 5: $msg = 'malformed UTF-8 characters, possibly incorrectly encoded'; break;
        }
        parent::__construct('JSON Parse: ' . $msg);
    }
}
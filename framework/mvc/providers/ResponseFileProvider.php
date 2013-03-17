<?php

namespace framework\mvc\providers;

use framework\io\File;

class ResponseFileProvider extends ResponseProvider {

    const type = __CLASS__;

    const CLASS_TYPE = File::type;
    
    public function __construct() {
       
        $file = $this->response->getEntity();
        $this->response->setContentType( $file->getMimeType() );
        $this->response->applyHeaders(array(
               
            'Content-Disposition' => 'attachment; filename=' 
                                        . urlencode( $file->getName() ),
            
            'Content-Length' => $file->length()           
        ));   
    }
    
    public function render(){
        flush();
        readfile($this->response->getEntity()->getPath());
    }
}

<?php

namespace framework\mvc\providers;

class ResponseFileProvider extends ResponseProvider {
    
    const CLASS_TYPE = '\framework\io\File';
    
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

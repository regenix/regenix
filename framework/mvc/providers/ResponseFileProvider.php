<?php

namespace framework\mvc\providers;

use framework\io\File;
use framework\mvc\MIMETypes;
use framework\mvc\Response;

class ResponseFileProvider extends ResponseProvider {

    const type = __CLASS__;

    const CLASS_TYPE = FileResponse::type;
    
    public function __construct(Response $response) {
        parent::__construct($response);

        /** @var $file FileResponse */
        $file = $response->getEntity();
        $response->setContentType( MIMETypes::getByExt($file->file->getExtension()) );
        $response->applyHeaders(array(
            'Content-Description' => 'File Transfer',
            'Content-Transfer-Encoding' => 'binary',
            'Expires' => '0',
            'Cache-Control' => 'must-revalidate',
            'Pragma' => 'public',
            'Content-Length' => $file->file->length()
        ));

        if ($file->attach)
            $response->setHeader('Content-Disposition', 'attachment; filename=' . urlencode( $file->file->getName() ));
    }

    public function onBeforeRender(){}
    
    public function render(){
        flush();
        readfile($this->response->getEntity()->file->getPath());
    }
}

class FileResponse {

    const type = __CLASS__;

    public $attach;

    /**
     * @var File
     */
    public $file;

    public function __construct($file, $attach = true){
        if(!($file instanceof File))
            $file = new File($file);

        $this->file   = $file;
        $this->attach = $attach;
    }
}

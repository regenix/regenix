<?php

namespace regenix\mvc\providers;

use regenix\lang\File;
use regenix\mvc\MIMETypes;
use regenix\mvc\Request;
use regenix\mvc\Response;

class ResponseFileProvider extends ResponseProvider {

    const type = __CLASS__;

    const CLASS_TYPE = FileResponse::type;

    public $cached = false;
    
    public function __construct(Response $response) {
        parent::__construct($response);

        $request = Request::current();

        /** @var $file FileResponse */
        $file = $response->getEntity();
        $response->setContentType( MIMETypes::getByExt($file->file->getExtension()) );

        $etag = md5($file->file->lastModified());
        $response->cacheETag($etag);
        if (!$file->attach && $request->isCachedEtag($etag)){
            $response->setStatus(304);
            $this->cached = true;
        } else {
            $response->applyHeaders(array(
                'Content-Description' => 'File Transfer',
                'Content-Transfer-Encoding' => 'binary',
                'Pragma' => 'public',
                'Content-Length' => $file->file->length()
            ));

            if ($file->attach){
                $response->setHeader('Expires', '0');
                $response->setHeader('Cache-Control', 'must-revalidate');
                $response->setHeader('Content-Disposition', 'attachment; filename=' . urlencode( $file->file->getName() ));
            }
        }
    }

    public function onBeforeRender(){}
    
    public function render(){
        if (!$this->cached){
            flush();
            readfile($this->response->getEntity()->file->getPath());
        }
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

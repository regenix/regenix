<?php

namespace regenix\mvc\providers;

use regenix\lang\DI;
use regenix\mvc\http\MimeTypes;
use regenix\mvc\http\Request;
use regenix\mvc\http\Response;

class ResponseFileProvider extends ResponseProvider {

    const type = __CLASS__;

    const CLASS_TYPE = FileResponse::type;

    public $cached = false;
    
    public function __construct(Response $response) {
        parent::__construct($response);

        $request = DI::getInstance(Request::type);

        /** @var $file FileResponse */
        $file = $response->getEntity();
        $response->setContentType( MimeTypes::getByExt($file->file->getExtension()) );

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

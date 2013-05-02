<?php
namespace controllers\api;

use framework\mvc\Controller;
use framework\mvc\controllers\RESTController;
use ide\Project;

abstract class Api extends RESTController {

    /** @var Project */
    protected $project = null;

    protected function onBefore(){
        if ($this->request->isMethod('POST')){
            $data = $this->body->asJSON();

            if ($data['project']){
                $this->project = Project::findByName($data['project']);
            }
        }
    }
}
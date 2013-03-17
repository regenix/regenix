<?php
namespace modules\console;

use framework\SDK;
use framework\mvc\Controller;
use framework\modules\AbstractModule;

class Module extends AbstractModule {

    const type = __CLASS__;

    public function getName() {
        return 'Console';
    }
    
    public function __construct() {
        
        SDK::addBeforeRequest(array($this, 'onBeforeRequest'));
    }
    
    public function onBeforeRequest(Controller $controller){
        
        // TEST handler
        if ($controller->uri->get('redirect')){
            $controller->redirect('http://google.ru/');
        }
    }
}
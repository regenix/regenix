<?php
namespace regenix\core;

use regenix\lang\File;
use regenix\mvc\http\Request;

abstract class AbstractGlobalBootstrap {
    public function onBeforeDeploy(\ZipArchive $zip, $newAppDir){}
    public function onAfterDeploy(\ZipArchive $zip, $newAppDir){}

    public function onBeforeRegisterApps(File &$pathToApps){}
    public function onAfterRegisterApps(&$apps){}

    public function onBeforeRegisterCurrentApp(Application $app){}
    public function onAfterRegisterCurrentApp(Application $app){}

    public function onException(\Exception $e){}
    public function onError(array $error){}

    public function onBeforeRequest(Request $request){}
    public function onAfterRequest(Request $request){}
    public function onFinallyRequest(Request $request){}
}
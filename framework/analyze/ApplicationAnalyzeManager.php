<?php
namespace regenix\analyze;

use regenix\Application;
use regenix\config\PropertiesConfiguration;
use regenix\lang\File;

class ApplicationAnalyzeManager extends AnalyzeManager {

    private $application;

    public function __construct(Application $application) {
        parent::__construct($application->getSrcPath());
        $configurationFile = new File($application->getPath() . 'conf/analyzer.conf');

        if ($configurationFile->exists()){
            $configuration = new PropertiesConfiguration($configurationFile);
            $configuration->setEnv($application->getMode());
            $this->setConfiguration($configuration);
        }

        $this->application = $application;
    }

    public function getApplication() {
        return $this->application;
    }
}
<?php
namespace regenix\console\commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use regenix\core\Regenix;
use regenix\core\Application;
use regenix\analyze\AnalyzeManager;
use regenix\analyze\Analyzer;
use regenix\analyze\ApplicationAnalyzeManager;
use regenix\analyze\exceptions\AnalyzeException;
use regenix\config\PropertiesConfiguration;
use regenix\console\RegenixCommand;
use regenix\lang\ClassScanner;
use regenix\lang\File;
use regenix\lang\String;
use regenix\lang\SystemCache;
use regenix\modules\Module;

class AnalyzeCommand extends RegenixCommand {

    protected function configure() {
        $this
            ->setName('analyze')
            ->setDescription('Analyzes all PHP sources for finding hidden errors')
            ->addOption(
                'incremental',
                null,
                InputOption::VALUE_OPTIONAL
            )
            ->addOption(
                'framework',
                null,
                InputOption::VALUE_OPTIONAL
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output){

        if ($input->getOption('framework')){
            SystemCache::removeAll();
            ClassScanner::scan(false);
            $analyzer = new AnalyzeManager(Regenix::getFrameworkPath());
            $analyzer->addIgnorePath('vendor/');
            $analyzer->addIgnorePath('console/.resource');

            $configFile = new File(Regenix::getFrameworkPath() . 'analyzer.conf');
            if ($configFile->exists())
                $analyzer->setConfiguration(new PropertiesConfiguration($configFile));

            $this->writeln('Analyzing the framework ...');
        } else {
            $this->checkApplicationLoaded();

            $app = $this->getApplication()->app;
            $analyzer = new ApplicationAnalyzeManager($app);

            $this->writeln('Analyzing the "%s" application ...', $app->getName());
        }

        $this->writeln();

        $errors = array();
        $analyzer->analyze($input->getOption('incremental'), true,
            function(AnalyzeException $e) use ($output, &$errors) {
                $output->writeln(String::format(
                    "       [fail] %s (line %s) \n" .
                    "           %s",
                    get_class($e), $e->getSourceLine(), $e->getMessage()
                ));
                $output->writeln('');
                $errors[] = $e;

        }, function(File $file) use ($output) {
                $path = $file->getPath();
                $path = str_replace(ROOT, '', $path);
                $output->writeln('    -> ' . $path);
        });

        $this->writeln();
        if ($errors) {
            $this->writeln('[FAIL] Analysis has found a number of errors, %s.', sizeof($errors));
            return 1;
        } else {
            $this->writeln('[OK] Analysis has found no errors.');
            return 0;
        }
    }
}
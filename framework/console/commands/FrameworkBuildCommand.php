<?php
namespace regenix\console\commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use regenix\analyze\exceptions\ParseAnalyzeException;
use regenix\console\RegenixCommand;
use regenix\core\Regenix;
use regenix\exceptions\FileNotFoundException;
use regenix\lang\File;
use regenix\lang\String;

class FrameworkBuildCommand extends RegenixCommand {

    protected $packages = array();

    protected function configure() {
        $this
            ->setName('framework-build')
            ->setDescription('Join all framework files to one file')
            ->addOption(
                'outputFile',
                null,
                InputOption::VALUE_OPTIONAL,
                'A path for result output file'
            )
            ->addOption(
                'buildFile',
                null,
                InputOption::VALUE_OPTIONAL,
                'A build file'
            );
    }

    protected function buildFile(File $outputFile){
        if ($outputFile->exists())
            $outputFile->delete();

        $outputFile->open('w+');
        $outputFile->write('<?php ' . "\n");

        foreach ($this->packages as $namespace => $info){
            $outputFile->write("namespace " . ($namespace === '*' ? '' : $namespace) . " {\n");
            foreach($info['uses'] as $use => $alias){
                if (!String::endsWith($use, '\\' . $alias)){
                    $outputFile->write('use ' . $use . ' as ' . $alias . ';');
                } else {
                    $outputFile->write('use ' . $use . ';');
                }
                $outputFile->write("\n");
            }
            $outputFile->write("\n\n");
            foreach($info['files'] as $name => $body){
                $outputFile->write('//@@file: ' . $name . "\n");
                $outputFile->write(implode("\n", $body));
                $outputFile->write("\n");
            }

            $outputFile->write("\n} //!end\n\n");
        }

        $outputFile->close();
    }

    protected function processFile(File $sourceFile){
        $this->writeln('    -> %s', $fileName = str_replace(Regenix::getFrameworkPath(), '', $sourceFile->getPath()));

        $contents = $sourceFile->getContents();
        $parser = new \PHPParser_Parser(new \PHPParser_Lexer_Emulative());
        try {
            $statements = $parser->parse($contents);
        } catch (\PHPParser_Error $e) {
            throw new ParseAnalyzeException($sourceFile, $e->getRawLine(), $e->getMessage());
        }
        $contents = explode("\n", str_replace("\n\r", "\n", $contents));

        foreach($statements as $statement){
            if ($statement instanceof \PHPParser_Node_Stmt_Namespace){
                if ($statement->name)
                    $name = $statement->name->toString();
                else
                    $name = '*';

                $startLine = $statement->getAttribute('startLine');

                if (strpos($contents[$startLine - 1], '{') !== false){
                    $endLine = $statement->getAttribute('endLine');
                } else
                    $endLine = sizeof($contents) + 1;

                foreach($statement->stmts as $one){
                    if ($one instanceof \PHPParser_Node_Stmt_Use){
                        foreach($one->uses as $use){
                            $this->packages[$name]['uses'][ $use->name->toString() ] =
                                $use->alias ? $use->alias : $use->name->toString();
                        }
                        $startLine = $one->getAttribute('endLine') + 1;
                    } elseif ($one instanceof \PHPParser_Node_Stmt_Class){
                        foreach($one->implements as $implement){
                            if ($implement->toString() === 'IClassInitialization'){
                                $this->writeln('        ignoring ... due to IClassInitialization');
                                return;
                            }
                        }
                    }
                }

                $length = $endLine - $startLine;
                $this->packages[$name]['files'][$fileName] = array_slice($contents, $startLine, $length - 1);
            }
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output){
        $this->writeln('Framework build is starting ...');
        $this->writeln();
        $outputFile = $input->getOption('outputFile');
        if (!$outputFile)
            $outputFile = SYSTEM_CACHE_TMP_DIR . '/RegenixBuild.php';

        $buildFile  = $input->getOption('buildFile');
        if (!$buildFile)
            $buildFile = Regenix::getFrameworkPath() . 'framework.build';

        $file = new File($buildFile);
        if (!$file->exists())
            throw new FileNotFoundException($file);

        $outputFile = new File($outputFile);

        $file->readLines(function($line) use ($output, $outputFile) {
            $line = trim($line);
            if (!$line || $line[0] === '#')
                return;

            $path = new File(Regenix::getFrameworkPath() . $line);

            if ($path->isDirectory()){
                $files = $path->findFiles(true);
                foreach($files as $file){
                    $this->processFile($file);
                }
            } else if ( $path->exists() ) {
                $this->processFile($path);
            }
        });

        $this->writeln();
        $this->writeln('Building result file ... %s', $outputFile->getPath());
        $this->buildFile($outputFile);
        $this->writeln('Done. (!) Use `Regenix::requireBuild()` in <root>/index.php to include the build file');

        $outputFile->close();
    }
}
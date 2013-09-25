<?php
namespace regenix\console;

    use Symfony\Component\Config\Definition\Exception\Exception;
    use regenix\Regenix;

{
    $root = dirname(dirname(__DIR__));
    require $root . '/framework/include.php';
    Regenix::init($root, false);

    $console = new RegenixConsole();
    try {
        $console->run();
    } catch (Exception $e){
        echo "\n    Exception error: " . str_replace('\\', '.', get_class($e)) . "\n";
        echo "\n        message: " . $e->getMessage();
        echo "\n        file: " . $e->getFile();
        echo "\n        line: " . $e->getLine();
        echo "\n\n      Exit code: 1\n";
        exit(1);
    }
}
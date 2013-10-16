<?php
namespace regenix\frontend\dart;

use Symfony\Component\Process\Process;
use regenix\exceptions\WrappedException;
use regenix\frontend\Language;
use regenix\frontend\LanguageException;
use regenix\lang\File;
use regenix\lang\String;

class DartLanguage extends Language {

    public function getName() {
        return 'Dart';
    }

    public function getExtension() {
        return 'dart';
    }

    public function getHtmlInsert($path, array $arguments = array()){
        return '<script type="text/javascript" src="' . $path . '"></script>';
    }

    public function isMonolithic() {
        return true;
    }

    /**
     * @param File $file
     * @return File
     */
    public function getOutFile(File $file) {
        return new File($file->getPath() . '.js');
    }

    public function processing(File $file, File $outFile) {
        if (!$outFile->exists())
            throw new LanguageException(
                'Use Dart SDK to generate "%s" file via dart2js or install an IDE extension for Dart',
                $outFile->getRelativePath($this->manager->getAssetPath())
            );
    }
}


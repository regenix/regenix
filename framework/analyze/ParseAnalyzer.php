<?php
namespace regenix\analyze;

use regenix\SDK;

class ParseAnalyzer extends Analyzer {

    public function analyze(){
        $parser = new \PHPParser_Parser(new \PHPParser_Lexer());
        try {
            $parser->parse($this->file->getContents());
        } catch (\PHPParser_Error $e) {
            throw new ParseException($this->file, $e->getRawLine(), $e->getMessage());
        }
    }
}

class ParseException extends AnalyzeException {}
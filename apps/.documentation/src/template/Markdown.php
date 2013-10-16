<?php

namespace template;

use Michelf\MarkdownExtra;
use regenix\lang\File;

class Markdown extends MarkdownExtra {

    private function getAnchor($text){
        $text = File::sanitize(str_replace(' ', '-', strtolower($text)));
        return $text;
    }

    protected function _doHeaders_callback_setext($matches) {
        if ($matches[3] == '-' && preg_match('{^- }', $matches[1]))
            return $matches[0];
        $level = $matches[3]{0} == '=' ? 1 : 2;
        $attr  = $this->doExtraAttributes("h$level", $dummy =& $matches[2]);
        $text = $this->runSpanGamut($matches[1]);

        $block = "<a name='". $this->getAnchor($text) ."'></a>" . "<h$level$attr>".$text."</h$level>";
        return "\n" . $this->hashBlock($block) . "\n\n";
    }

    protected function _doHeaders_callback_atx($matches) {
        $level = strlen($matches[1]);
        $attr  = $this->doExtraAttributes("h$level", $dummy =& $matches[3]);

        $text = $this->runSpanGamut($matches[2]);
        $block = "<a name='". $this->getAnchor($text) ."'></a>" . "<h$level$attr>".$text."</h$level>";
        return "\n" . $this->hashBlock($block) . "\n\n";
    }
}
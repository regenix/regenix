<?php
namespace template\tags;

use regenix\core\Regenix;
use regenix\exceptions\TemplateNotFoundException;
use regenix\lang\File;
use regenix\mvc\template\TemplateLoader;
use regenix\template\RegenixTemplate;
use regenix\template\RegenixTemplateTag;
use template\Markdown;

class IncludeMarkdownTag implements RegenixTemplateTag {

    public function getName() {
        return 'include.markdown';
    }

    public function call($args, RegenixTemplate $ctx) {
        $file = $args['_arg'];
        $templateFile = TemplateLoader::findFile($file);

        if (!$templateFile)
            throw new TemplateNotFoundException($file);
        $templateFile = new File($templateFile);
        $lastMod = $templateFile->lastModified();

        $tempDir = Regenix::getTempPath() . 'mdtpl/';
        $hash = sha1($file);
        $saveFile = new File($tempDir . $hash . $lastMod . '.html');
        if (!$saveFile->exists()){
            $saveFile->getParentFile()->mkdirs();
            $saveFile->putContents(
                $content = Markdown::defaultTransform($templateFile->getContents())
            );
        }

        if ( REGENIX_IS_DEV ){
            foreach(glob($tempDir . $hash . '.*.html') as $file){
                $file = realpath($file);
                if ( $file == $saveFile->getPath() ) continue;
                @unlink($file);
            }
        }

        $template = new RegenixTemplate(false);
        $template->setTempDir($tempDir);
        $template->setFile($saveFile->getPath());

        $args = array_merge($ctx->getArgs(), $args);
        return $template->render($args, true, true);
    }
}
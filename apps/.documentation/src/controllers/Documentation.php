<?php
namespace controllers;

use regenix\exceptions\HttpException;
use regenix\exceptions\TemplateNotFoundException;
use regenix\i18n\I18n;
use regenix\lang\CoreException;
use regenix\lang\File;
use regenix\mvc\Controller;
use regenix\mvc\template\TemplateLoader;

class Documentation extends Controller {

    /** @var File */
    private $docRoot;

    public function __construct(){
        parent::__construct();
        $this->docRoot = new File(ROOT . '/documentation/');
    }

    protected function onBefore(){
        if (!$this->docRoot->isDirectory())
            throw new CoreException("Documentation directory is not exists: %s", $this->docRoot);

        $lang = I18n::getLang();
        if (!I18n::availLang($lang))
            throw new HttpException(404, 'Cannot find documentation for "%s" language', $lang);

        if ($lang === 'default')
            $lang = 'en';

        $this->put('currentAction', '.' . get_class($this) .'.'. $this->actionMethod);
        $this->put('languages', array('en' => 'English', 'ru' => 'Русский'));
        $this->put('LANG', $lang);
        $this->put('ONLINE', $this->request->getHost() === 'regenix.ru');
    }

    public function index(){
        $this->render('welcome');
    }

    public function detail($file){
        $originFile = $file;
        $lang = I18n::getLang();
        if ($lang == 'default')
            $lang = 'en';

        $template = $lang . '/' . $file;
        $page = new File($lang . '/' . $file, $this->docRoot);

        if ($page->isDirectory()){
            $page = new File('README.md', $this->docRoot);
            $template .= 'README.md';
        }

        $originTemplate = $template;
        if (!TemplateLoader::findFile($template))
            $template = 'Documentation/todo.md';

        $this->put('originFile', $originFile);
        $this->put('file', $template);
        $this->render();
    }
}
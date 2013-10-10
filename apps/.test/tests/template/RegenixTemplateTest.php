<?php
namespace tests\template;

use regenix\core\Regenix;
use regenix\lang\String;
use regenix\mvc\template\TemplateLoader;
use regenix\template\RegenixTemplate;
use regenix\template\RegenixTemplateFilter;
use regenix\template\RegenixTemplateTag;
use tests\RegenixTest;

class RegenixTemplateTest extends RegenixTest {

    /**
     * @param string $file
     * @return RegenixTemplate
     */
    protected function template($file = ''){
        TemplateLoader::registerPath(__DIR__ . '/views/');

        $template = new RegenixTemplate();
        $template->setTempDir(Regenix::getTempPath());
        $template->setTplDirs(array(__DIR__ . '/views/'));
        if ($file)
            $template->setFile(__DIR__ . '/views/' . $file);

        return $template;
    }

    public function testArgs(){
        $template = $this->template();
        $template->putArg('test_arg', true);
        $this->assertStrongEqual(true, $template->getArg('test_arg'));
    }

    public function testSimpleFilters(){
        $template = $this->template();

        $result = $template->callFilter('test_simple', 'foobar');
        $this->assertEqual('[foobar]', $result);
        $this->assertStrongEqual(true, $template->getArg('test_simple_filter'));
    }

    public function testSimpleTags(){
        $template = $this->template();

        $result = $template->renderTag('foobar', array(), true);
        $this->assertEqual('FOOBAR', $result);
        $this->assertStrongEqual(true, $template->getArg('foobar_tag'));
    }

    public function testDuplicate(){
        $template = $this->template();
        $template->putArg('test_arg', true);

        $duplicate = $template->duplicate();
        $this->assertNotEqual($template, $duplicate);
        $this->assertType(RegenixTemplate::type, $duplicate);
        if ($this->isLastOk()){
            $this->assertNot(true === $duplicate->getArg('test_arg'));
        }

        $this->assertNot($duplicate->registerFilter(new SimpleFilter()));
        $this->assertNot($duplicate->registerTag(new SimpleTag()));
    }

    public function testRenderVars(){
        $template = $this->template('simple.html');
        $result = $template->render(array('var' => 'foobar'), false, true);

        $this->assertEqual('[foobar]', $result);

        // test htmlspecialchars
        $result = $template->render(array('var' => '<foobar>'), false, true);
        $this->assertEqual('['.htmlspecialchars('<foobar>').']', $result);
    }

    public function testRenderBuiltinFilters(){
        $template = $this->template('filters.html');

        $args = array(
            'raw' => '<raw>',
            'format' => time(),
            'upperCase' => 'upper',
            'lowerCase' => 'lower',
            'nl2br' => "nl\nbr",
            'trim' => '      trim    ',
            'substring' => 'substring', 'substring_from' => 3, 'substring_to' => 5,
            'replace' => 'replace', 'replace_from' => 're', 'replace_to' => 'un'
        );

        $result = $template->render($args, false, true);

        $this->assert(strpos($result, '['.'<raw>'.']') !== false);
        $this->assert(strpos($result, '['.date('Y.m.d', $args['format']).']') !== false);
        $this->assert(strpos($result, '['.strtoupper($args['upperCase']).']') !== false);
        $this->assert(strpos($result, '['.strtolower($args['lowerCase']).']') !== false);
        $this->assert(strpos($result, '['.nl2br($args['nl2br']).']') !== false);
        $this->assert(strpos($result, '['.trim($args['trim']).']') !== false);
        $this->assert(strpos($result, '['.String::substring($args['substring'], 3, 5).']') !== false);
        $this->assert(strpos($result, '['.str_replace('re', 'un', $args['replace']).']') !== false);
    }

    public function testInheritance(){
        $template = $this->template('inheritance.html');
        $result = $template->render(array(), true, true);

        $this->assert(strpos($result, 'FOOBAR') !== false);
        $this->assert(strpos($result, '[LAZY]') !== false);
    }

    public function testHtmlTag(){
        $template = $this->template('html_tag.html');
        $result = $template->render(array('var' => 'HELLO', 'word' => 'WORLD'), true, true);

        $this->assertEqual('HELLO - WORLD', $result);
    }

    public function testIncludeTag(){
        $template = $this->template('include_tag.html');
        $result = $template->render(array('suffix' => 'WORLD'), true, true);

        $this->assertEqual('.tags/test_simple.html - WORLD', $result);
    }

    public function testRenderTag(){
        $template = $this->template('render_tag.html');
        $result = $template->render(array('suffix' => 'WORLD'), true, true);

        $this->assertEqual('.tags/test_simple.html - ', $result);
    }
}

class SimpleFilter implements RegenixTemplateFilter {

    public function getName() {
        return 'test_simple';
    }

    public function call($value, array $args, RegenixTemplate $ctx) {
        if ($ctx)
            $ctx->putArg($this->getName() . '_filter', true);

        return '[' . $value . ']';
    }
}

class SimpleTag implements RegenixTemplateTag {

    function getName() {
        return 'foobar';
    }

    public function call($args, RegenixTemplate $ctx) {
        if ($ctx)
            $ctx->putArg($this->getName() . '_tag', true);

        return 'FOOBAR';
    }
}
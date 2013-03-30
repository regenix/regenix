<?php
namespace controllers;

use framework\lang\IClassInitialization;
use framework\mvc\Controller;
use framework\mvc\Annotations;
use models\Post;
use modules\mongodb\AtomicInc;
use modules\mongodb\AtomicPush;
use modules\mongodb\AtomicUnset;
use modules\mongodb\Document;

class Application extends Controller {

    /**
     * @var string
     */
    protected static $arg;

    public function index(){

        /** @var $post Post */

        $postService = Post::getService();
        $post = $postService->findById('5143605fe5adcc2c0b000000');
        $post->groups = array(1,2,3);
        $postService->save($post);

        dump($post);
        $this->renderJSON('OK');
    }
}
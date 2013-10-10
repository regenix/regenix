<?php
namespace regenix\mvc;

use regenix\lang\StrictObject;

class Pagination extends StrictObject {

    public $currentPage;
    public $elementOnPage;
    public $allCount;

    public $pageCount;

    public function __construct($currentPage, $elementOnPage, $allCount){
        $this->currentPage = $currentPage;
        $this->elementOnPage = $elementOnPage;
        $this->allCount    = $allCount;

        $this->pageCount = ceil($this->allCount / $this->elementOnPage);
    }

    public function isLast(){
        return $this->currentPage >= $this->pageCount;
    }

    public function isFirst(){
        return $this->currentPage <= 1;
    }
}
<?php
namespace regenix\mvc\db;

use regenix\lang\CoreException;

class DBPagination {
    protected $page;
    protected $limit;
    protected $elementCount = -1;
    protected $pageCount = -1;

    private function __construct() { }

    public function toArray() {
        return ['page' => $this->page, 'limit' => $this->limit];
    }

    public function getLimit() {
        return $this->limit;
    }

    public function getPage() {
        return $this->page;
    }

    public function getElementCount() {
        if ($this->elementCount === -1)
            throw new CoreException("Unknown element count");

        return $this->elementCount;
    }

    public function setElementCount($elementCount) {
        $this->elementCount = $elementCount;
        $this->pageCount = (int)ceil($elementCount / $this->limit);
        if ($this->pageCount == 0)
            $this->pageCount = 1;
    }

    public function getPageCount() {
        if ($this->pageCount === -1)
            throw new CoreException("Unknown page count");

        return $this->pageCount;
    }

    public static function of($page = 1, $limit = 10) {
        $r = new DBPagination();
        $r->page = $page;
        $r->limit = $limit;
        return $r;
    }
}
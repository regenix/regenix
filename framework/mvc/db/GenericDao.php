<?php
namespace regenix\mvc\db;

use RedBeanPHP\OODBBean;
use dao\DBPagination;
use regenix\lang\CoreException;
use regenix\lang\IClassInitialization;
use regenix\mvc\Model;

abstract class GenericDao implements IClassInitialization {

    const DATA_TYPE = '';

    /** @var DBOrder */
    protected $order;

    /** @var DBPagination */
    protected $pagination;

    /**
     * @throws \regenix\lang\CoreException
     * @return string
     */
    final public function getType() {
        if (!static::DATA_TYPE)
            throw new CoreException("Please set a data type for the '" . get_called_class() . "' class");

        return static::DATA_TYPE;
    }

    /**
     * @return \dao\DBOrder
     */
    public function getOrder() {
        return $this->order;
    }

    /**
     * @return \dao\DBPagination
     */
    public function getPagination() {
        return $this->pagination;
    }

    /**
     * @return Model
     */
    public function create() {
        /** @var $model Model */
        $model = \R::dispense($this->getType())->box();
        return $model;
    }

    /**
     * @param $id
     * @return Model
     */
    public function get($id) {
        $r = \R::findOne($this->getType(), 'id = ?', [$id]);
        return $r ? $r->box() : null;
    }

    /**
     * @param array $ids
     * @return Model[]
     */
    public function getAll(array $ids) {
        $r = [];
        foreach(\R::loadAll($this->getType(), $ids) as $el) {
            $r[] = $el->box();
        }
        return $r;
    }

    /**
     * @param Model $entity
     * @return int|string
     */
    public function save(Model $entity) {
        return \R::store($entity->unbox());
    }

    /**
     * @param Model $entity
     */
    public function delete(Model $entity) {
        \R::trash($entity->unbox());
    }

    /**
     * @param string $addSql
     * @param array $bindings
     * @return int
     */
    public function count($addSql = NULL, array $bindings = []) {
        return \R::count($this->getType(), $addSql, $bindings);
    }

    /**
     * @param DBOrder $order
     * @return GenericDao
     */
    public function withOrder(DBOrder $order) {
        $r = clone $this;
        $r->order = $order;
        return $r;
    }

    /**
     * @param DBPagination $pagination
     * @return GenericDao
     */
    public function withPagination(DBPagination $pagination) {
        $r = clone $this;
        $r->pagination = $pagination;
        return $r;
    }

    /**
     * @param DBPagination $pagination
     * @param DBOrder $order
     * @return GenericDao
     */
    public function with(DBPagination $pagination = null, DBOrder $order = null) {
        $r = clone $this;
        $r->pagination = $pagination;
        $r->order = $order;
        return $r;
    }

    /**
     * @param int $limit
     * @return GenericDao
     */
    public function withLimit($limit) {
        return $this->withPagination(DBPagination::of(1, $limit));
    }

    /**
     * @param null|string $sql
     * @param array $bindings
     * @return Model[]
     */
    public function find($sql = NULL, array $bindings = []) {
        if (!$this->pagination && !$this->order)
            return $this->_find($sql, $bindings);

        if ($this->pagination) {
            $elementCount = $this->count($sql, $bindings);
            $this->pagination->setElementCount($elementCount);
        }

        if ($this->order) {
            $sql .= $this->order->toSql();
        }

        if ($this->pagination) {
            $sql .= ' LIMIT ?, ?';
            $bindings[] = ($this->pagination->getPage() - 1) * $this->pagination->getLimit();
            $bindings[] = $this->pagination->getLimit();
        }

        return $this->_find($sql, $bindings);
    }

    /**
     * @param null|string $sql
     * @param array $bindings
     * @return Model[]
     */
    protected function _find($sql = NULL, array $bindings = []) {
        $r = [];
        foreach(\R::find($this->getType(), $sql, $bindings) as $el) {
            $r[] = $el->box();
        }
        return $r;
    }

    /**
     * @param null|string $sql
     * @param array $bindings
     * @return Model
     */
    public function findOne($sql = NULL, array $bindings = []) {
        $r = \R::findOne($this->getType(), $sql, $bindings);
        return !$r ? null : $r->box();
    }

    /**
     * @param null|string $sql
     * @param array $bindings
     * @return Model
     */
    public function findLast($sql = NULL, array $bindings = []){
        $r = \R::findLast($this->getType(), $sql, $bindings);
        return !$r ? null : $r->box();
    }

    /**
     * @param null|string $sql
     * @param array $bindings
     * @return Model[]
     */
    public function findAll($sql = NULL, array $bindings = []) {
        $r = [];
        foreach(\R::findAll($this->getType(), $sql, $bindings) as $el) {
            $r[] = $r->box();
        }
        return $r;
    }

    /**
     * @return array
     */
    public function inspect() {
        return \R::inspect($this->getType());
    }

    public static function initialize() {
        Model::initialize();
    }
}
<?php
namespace regenix\mvc\db;

/**
 * Class DBQuery
 * @package regenix\mvc\db
 */
class DBCriteria {

    /** @var GenericDao */
    protected $dao;
    protected $orderSql = '';
    protected $sql = '';
    protected $arguments = [];

    function __construct(GenericDao $dao) {
        $this->dao = $dao;
    }

    public function getSql() {
        return $this->sql . ' ' . $this->orderSql;
    }

    public function getArguments() {
        return ($this->arguments);
    }

    public function addOrder(DBOrder $order) {
        if (!$this->orderSql)
            $this->orderSql .= 'ORDER BY';

        $this->orderSql .= $order->toSql(false);
        return $this;
    }

    /**
     * @param $sql
     * @return $this
     */
    public function sql($sql) {
        $this->sql .= " $sql ";
        return $this;
    }

    /**
     * @param $field
     * @param $value
     * @param $op
     * @return $this
     */
    protected function _op($field, $value, $op) {
        $this->sql .= " $field $op ? ";
        $this->arguments[] = $value;
        return $this;
    }

    /**
     * @param $field
     * @param $value
     * @return $this
     */
    public function equal($field, $value) {
        return $this->_op($field, $value, '=');
    }

    /**
     * @param $field
     * @param $value
     * @return $this
     */
    public function greater($field, $value) {
        return $this->_op($field, $value, '>');
    }

    /**
     * @param $field
     * @param $value
     * @return $this
     */
    public function greaterEq($field, $value) {
        return $this->_op($field, $value, '>=');
    }

    /**
     * @param $field
     * @param $value
     * @return $this
     */
    public function smaller($field, $value) {
        return $this->_op($field, $value, '<');
    }

    /**
     * @param $field
     * @param $value
     * @return $this
     */
    public function smallerEq($field, $value) {
        return $this->_op($field, $value, '<=');
    }

    /**
     * @param $field
     * @param $value
     * @return $this
     */
    public function notEqual($field, $value) {
        return $this->_op($field, $value, '!=');
    }

    /**
     * @param $field
     * @return $this
     */
    public function notNull($field) {
        return $this->sql("$field != NULL");
    }

    /**
     * @param $field
     * @return $this
     */
    public function isNull($field) {
        return $this->sql("$field = NULL");
    }

    /**
     * @param DBCriteria[] $queries
     */
    public function addOr(array $queries) {
        $i = 0;
        $sql = '';
        foreach($queries as $query) {
            if ($i != 0) {
                $sql .= ' or ';
            }

            $sql .= '(' . $query->getSql() . ')';
            $this->arguments = array_merge($this->arguments, $query->arguments);
            $i++;
        }
    }

    /**
     * @param $count
     * @return $this
     */
    public function setMaxResults($count) {
        $this->dao->withLimit($count);
        return $this;
    }

    /**
     * @param DBPagination $pagination
     * @return $this
     */
    public function setPagination(DBPagination $pagination) {
        $this->dao->withPagination($pagination);
        return $this;
    }

    /**
     * @return \regenix\mvc\Model[]
     */
    public function find() {
        return $this->dao->find($this->getSql(), $this->getArguments());
    }

    /**
     * @return \regenix\mvc\Model[]
     */
    public function findAll() {
        return $this->dao->findAll($this->getSql(), $this->getArguments());
    }

    /**
     * @return \regenix\mvc\Model
     */
    public function findOne() {
        return $this->dao->findOne($this->getSql(), $this->getArguments());
    }

    /**
     * @return \regenix\mvc\Model
     */
    public function findLast() {
        return $this->dao->findLast($this->getSql(), $this->getArguments());
    }

    /**
     * @return int
     */
    public function count() {
        return $this->dao->count($this->getSql(), $this->getArguments());
    }
}
<?php
namespace regenix\mvc\db;

class DBOrder {
    /** @var string */
    protected $field;

    /** @var string */
    protected $type;

    private function __construct() {}

    /**
     * @return string
     */
    public function getField() {
        return $this->field;
    }

    /**
     * @return string
     */
    public function getType() {
        return $this->type;
    }

    /**
     * @param bool $full
     * @return string
     */
    public function toSql($full = true) {
        return ($full ? ' ORDER BY' : '') . " $this->field $this->type ";
    }

    public static function asc($field) {
        return self::of($field);
    }

    public static function desc($field) {
        return self::of($field, 'DESC');
    }

    public static function of($field, $type = 'ASC') {
        $r = new DBOrder();
        $r->field = $field;
        $r->type = $type;
        return $r;
    }
}
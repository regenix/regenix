<?php
namespace models\mixins;


trait TDefaultInformation {

    /**
     * @id
     * @var oid
     */
    public $id;

    /**
     * @column act
     * @var boolean
     */
    public $active = true;
}
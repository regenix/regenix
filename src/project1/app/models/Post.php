<?php

namespace models;

use modules\mongodb\DAO;
use modules\mongodb\Document;
use modules\mongodb\Service;

/**
 * @collection posts
 * @indexed name=1, desc=-1, $background
 * @indexed name=-1, desc=1, $background
 */
class Post extends Document {

    const type = __CLASS__;

    /** 
     * @id
     * @var \MongoId
     */
    public $_id;
    
    /**
     * @indexed $background
     * @length 255
     * @column nm
     * @var string
     */
    public $name;
    
    /** 
     * @column ds
     * @length 10000
     * @var string 
     */
    public $desc;

    /**
     * @indexed $background
     * @column pr
     * @ref $lazy
     * @var Post
     */
    public $parent;


    /**
     * @var int
     */
    public $skip;

    /**
     * @var int[]
     */
    public $groups = array();
}


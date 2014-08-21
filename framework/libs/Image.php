<?php
namespace regenix\libs;

use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Imagine\Image\Color;
use Imagine\Image\ImageInterface;
use Imagine\Image\Point;
use regenix\lang\File;


class Image {
    /** @var ImageInterface */
    protected $img;

    private function __construct() { }

    /**
     * @return int
     */
    public function getWidth() {
        return $this->img->getSize()->getWidth();
    }

    /**
     * @return int
     */
    public function getHeight() {
        return $this->img->getSize()->getHeight();
    }

    /**
     * @return array
     */
    public function getSize() {
        $size = $this->img->getSize();
        return array($size->getWidth(), $size->getHeight());
    }

    /**
     * @param Image $image
     * @return $this
     */
    public function applyMask(Image $image) {
        $this->img->applyMask($image->img);
        return $this;
    }

    /**
     * @param int $x
     * @param int $y
     * @param int $w
     * @param int $h
     * @return $this
     */
    public function crop($x, $y, $w, $h) {
        $this->img->crop(new Point($x, $y), new Box($w, $h));
        return $this;
    }

    /**
     * @param int $w
     * @param int $h
     * @return $this
     */
    public function resize($w, $h) {
        $this->img->resize(new Box($w, $h));
        return $this;
    }

    /**
     * @param int $w
     * @param int $h
     * @param string $mode inset or outbound
     * @return $this
     */
    public function thumbnail($w, $h, $mode = 'inset') {
        $this->img->thumbnail(new Box($w, $h), $mode);
        return $this;
    }

    /**
     * @param Image $image
     * @param int $x
     * @param int $y
     * @return $this
     */
    public function paste(Image $image, $x, $y) {
        $this->img->paste($image->img, new Point($x, $y));
        return $this;
    }

    /**
     * @return $this
     */
    public function strip() {
        $this->img->strip();
        return $this;
    }

    /**
     * @param $newPath
     * @param array $options
     * @return $this
     */
    public function save($newPath, array $options = array()) {
        $this->img->save($newPath, $options);
        return $this;
    }

    /**
     * @return Image
     */
    public function copy() {
        $r = new Image();
        $r->img = $this->img->copy();
        return $r;
    }

    public function __clone() {
        $this->img = $this->copy();
    }

    /**
     * @param string|File $path
     * @return Image
     */
    public static function of($path) {
        $r = new Image();
        $imagine = new Imagine();
        $r->img = $imagine->open($path instanceof File ? $path->getPath() : $path);
        return $r;
    }

    /**
     * @param int $w
     * @param int $h
     * @param null|string|int|array $color
     */
    public static function create($w, $h, $color = null) {
        $r = new Image();
        $imagine = new Imagine();
        $r->img = $imagine->create(new Box($w, $h), $color ? new Color($color) : null);
    }
}
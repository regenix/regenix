<?php
namespace framework\libs;

use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use framework\Project;
use framework\lang\File;
use framework\lang\IClassInitialization;

class ImageUtils implements IClassInitialization {

    /**
     * @var File
     */
    private static $path;

    private static function _resize($filePath, $width, $height, $type){
        if (!file_exists($filePath))
            return null;

        $mtime = filemtime($filePath);
        $ext   = pathinfo($filePath, PATHINFO_EXTENSION);
        $sha   = sha1($filePath . '#' . $mtime . '#' . $width . '#' . $height . '#' . $type);

        $newPath = self::$path->getPath() . $sha . '.' . $ext;
        if (file_exists($newPath))
            return $newPath;

        $imagine = new Imagine();
        $imagine
            ->open($filePath)
            ->thumbnail(new Box($width, $height), $type)
            ->save($newPath);

        return $newPath;
    }

    public static function crop($filePath, $width, $height){
        return self::_resize($filePath, $width, $height, \Imagine\Image\ImageInterface::THUMBNAIL_OUTBOUND);
    }

    public static function resize($filePath, $width, $height){
        return self::_resize($filePath, $width, $height, \Imagine\Image\ImageInterface::THUMBNAIL_INSET);
    }

    public static function initialize(){
        $project    = Project::current();
        self::$path = new File($project->getPublicPath() . 'gen/images/');
        self::$path->mkdirs();
    }
}
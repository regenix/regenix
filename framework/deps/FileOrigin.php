<?php
/**
 * Created by JetBrains PhpStorm.
 * User: dim-s
 * Date: 23.04.13
 * Time: 18:09
 * To change this template use File | Settings | File Templates.
 */

namespace framework\deps;

use framework\io\File;

class FileOrigin extends Origin {

    const type = __CLASS__;
    const PREFIX = 'file';

    protected $dir;

    public function __construct($address){
        $this->dir = substr($address, strlen(self::PREFIX) + 1);
    }

    /**
     * @param $path
     * @throws ConnectException
     * @return mixed
     */
    protected function json($path){
        try {
            $data = file_get_contents($this->dir . $this->env . '/' . $path);
            return json_decode($data, true);
        } catch (\Exception $e){
            throw ConnectException::formated("Can`t download `%s` resource from repository", $this->dir . $path);
        }
    }

    /**
     * @return int time unix
     */
    public function lastUpdated(){
        $json = $this->json('status.json');
        return (int)$json['last_upd'];
    }

    /**
     * @param $group
     * @return array
     */
    public function getMetaInfo($group){
        $json = $this->json($group . '/index.json');
        return $json;
    }

    /**
     * @param string $group
     * @param string $version
     * @param string $name
     * @param string $toDir
     * @return mixed
     */
    public function downloadDependency($group, $version, $name, $toDir){
        try {
            $data = file_get_contents($this->dir . $this->env . '/' . $group . '/' . $version . '/' . $name);

            $fileName = $toDir . $name;
            $file = new File($fileName);
            $file->getParent()->mkdirs();

            file_put_contents($fileName, $data);
            return true;
        } catch (\Exception $e){
            return false;
        }
    }
}
<?php
namespace framework\deps;

use framework\exceptions\CoreException;
use framework\exceptions\HttpException;
use framework\io\File;
use framework\lang\String;
use framework\libs\WS;

abstract class Origin {

    const type = __CLASS__;
    const PREFIX = '???';

    protected $env;
    private $address;

    public function __construct($address){
        $this->address = $address;
    }

    /**
     * @param string $origin
     * @return bool
     */
    public static function isCurrent($origin){
        $is = String::startsWith($origin, static::PREFIX);
        return $is;
    }

    /**
     * @return int time unix
     */
    abstract public function lastUpdated();

    /**
     * @param $group
     * @return array
     */
    abstract public function getMetaInfo($group);

    /**
     * @return mixed
     */
    public function getAddress(){
        return $this->address;
    }

    /**
     * @param string $group
     * @param string $version
     * @param string $name
     * @param string $toDir
     * @return mixed
     */
    abstract public function downloadDependency($group, $version, $name, $toDir);

    /**
     * @param string $env
     */
    public function setEnv($env){
        $this->env = $env;
    }


    private static $originTypes = array();

    /**
     * @param $originClass
     * @throws
     */
    public static function register($originClass){
        $reflect = new \ReflectionClass($originClass);
        if (!$reflect->isSubclassOf(Origin::type)){
            throw CoreException::formated('Repository origin must be extends `%s` class', Origin::type);
        }

        self::$originTypes[] = $originClass;
    }

    /**
     * @param string $address
     * @return Origin|null
     */
    public static function createOriginByAddress($address){
        foreach(self::$originTypes as $type){
            if ($type::isCurrent($address)){
                return new $type($address);
            }
        }

        return null;
    }
}


class GithubOrigin extends Origin {

    const type = __CLASS__;
    const PREFIX = 'github';

    /** @var string */
    protected $address;

    /** @var string */
    protected $rawAddress;

    public function __construct($address){
        parent::__construct($address);
        $address = substr($address, 7); // "github:" strip

        if (!String::endsWith($address, '/'))
            $address .= '/';

        $this->address = $address;
        $this->rawAddress = 'https://raw.github.com/' . $address;
    }

    /**
     * @param $path
     * @return string
     */
    protected function getUrlRaw($path){
        return $this->rawAddress . $this->env . '/' . $path;
    }

    /**
     * @param $path
     * @return \framework\libs\WSRequest
     */
    protected function ws($path){
        return WS::url($this->getUrlRaw($path))->timeout(10);
    }

    /**
     * @param $path
     * @throws ConnectException
     * @return mixed
     */
    protected function json($path){
        $response = $this->ws($path)->get();
        if ($response->isSuccess()){
            return $response->asJson();
        } else {
            if ($response->status === 404){
                throw HttpException::formated(404, 'Not found `%s` resource in repository', $path);
            } else
                throw ConnectException::formated("Can`t download `%s` resource from repository", $response->url);
        }
    }

    /**
     * @return int time unix
     */
    public function lastUpdated() {
        $json = $this->json('status.json');
        return (int)$json['last_upd'];
    }

    /**
     * @param $group
     * @return array|mixed
     */
    public function getMetaInfo($group){
        $json = $this->json($group . '/index.json');
        return $json;
    }

    public function downloadDependency($group, $version, $name, $toDir){
        $url = $group . '/' . $version . '/' . $name;
        $fileName = $toDir . $name;
        $file = new File($fileName);
        $response = $this->ws($url)->get();

        if ($response->isSuccess()){
            $response->asFile( $file );
            return true;
        } else {
            return false;
        }
    }
}


class FileOrigin extends Origin {

    const type = __CLASS__;
    const PREFIX = 'file';

    protected $dir;

    public function __construct($address){
        parent::__construct($address);
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
            $file->getParentFile()->mkdirs();

            file_put_contents($fileName, $data);
            return true;
        } catch (\Exception $e){
            return false;
        }
    }
}
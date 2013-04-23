<?php
namespace framework\deps;


use framework\io\File;
use framework\lang\String;
use framework\libs\WS;

class GithubOrigin extends Origin {

    const type = __CLASS__;
    const PREFIX = 'github';

    /** @var string */
    protected $address;

    /** @var string */
    protected $rawAddress;

    public function __construct($address){
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
        } else
            throw ConnectException::formated("Can`t download `%s` resource from repository", $response->url);
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
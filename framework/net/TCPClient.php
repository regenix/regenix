<?php
namespace regenix\net;

use regenix\lang\CoreException;
use regenix\lang\File;
use regenix\lang\IOException;
use regenix\lang\StrictObject;

class TCPClient extends StrictObject {

    /** @var resource */
    public  $handle;

    /** @var string */
    protected $host;

    /** @var int */
    protected $port;

    /** @var bool */
    protected $persistence = true;

    public function __construct($host, $port){
        $this->host = $host;
        $this->port = $port;
    }

    /**
     * @throws SocketException
     */
    public function open(){
        $errno = null;
        $errmsg = null;

        $t = microtime(1);
        $this->handle = fsockopen('tcp://' . $this->host . ':' . $this->port, $errno, $errmsg);
        dump(stream_get_meta_data($this->handle));
        die((microtime(1) - $t) * 1000);

        /*
        stream_socket_client('tcp://' . $this->host . ':' . $this->port, $errno, $errmsg, 3,
            $this->persistence ? STREAM_CLIENT_CONNECT : STREAM_CLIENT_CONNECT);*/

        if ($errno){
            throw new SocketException($errno, $errmsg);
        }
    }

    public function close(){
        if ($this->handle)
            fclose($this->handle);
    }

    /**
     * @param $message
     * @param bool $retry
     * @throws \regenix\lang\IOException
     * @return int
     */
    public function write($message, $retry = true){
        if (!$this->handle)
            throw new IOException("Socket is not opened");

        $len = strlen($message);
        if (fwrite($this->handle, $message, $len) !== $len){
            if ($retry){
                $this->close();
                $this->open();
                return $this->write($message, false);
            }
            throw new IOException("Can't write to socket");
        }
        return $len;
    }

    /**
     * @throws \regenix\lang\IOException
     * @return string
     */
    public function read(){
        if (!$this->handle)
            throw new IOException("Socket is not opened");

        $contents = fread($this->handle, 4);
        $len = unpack('N', $contents);
        return fread($this->handle, $len[1]);
    }

    public function call($message){
        $this->write($message);
        return $this->read();
    }
}

class SocketException extends CoreException {

    private $errorCode;

    public function __construct($code, $message){
        $this->code = $code;
        parent::__construct($message);
    }

    /**
     * @return mixed
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }
}
<?php
namespace regenix\scheduler;


use regenix\Regenix;
use regenix\cache\Cache;
use regenix\lang\ClassScanner;
use regenix\lang\CoreException;
use regenix\lang\File;
use regenix\libs\Time;

class Scheduler {

    const CACHE_INTERVAL_UPDATE = 30; // sec

    /** @var string */
    protected $name;

    /** @var SchedulerTask[] */
    protected $tasks = array();

    /** @var array */
    protected $cache = array();

    /** @var int */
    protected $lastSaveTime = 0;

    public function __construct($name){
        $this->name = $name;
        $this->lastSaveTime = time();
        $classes = ClassScanner::find(SchedulerTask::type)->getChildrensAll();
        foreach($classes as $class){
            $this->tasks[] = $class->newInstance(array($this));
        }

        $this->loadCache();
    }

    public function update(){
        foreach($this->tasks as $task){
            $task->run();
        }
        $this->updateCache();
    }

    public function getTasks(){
        return $this->tasks;
    }

    protected function getCacheFile(){
        return new File(Regenix::getTempPath() . 'scheduler/' . $this->name . '.sch.tmp');
    }

    protected function loadCache(){
        $file = $this->getCacheFile();
        if ($file->exists()){
            try {
                $this->cache = unserialize($file->getContents());
            } catch (\Exception $e){
                $this->cache = array();
            }
        }
    }

    protected function updateCache($force = false){
        $file = $this->getCacheFile();
        $update = $force;

        if (!$file->exists()){
            $file->getParentFile()->mkdirs();
            $update = true;
        } elseif (!$force) {
            $lastUpd = $file->lastModified();
            $update  = $lastUpd + self::CACHE_INTERVAL_UPDATE < time();
        }

        if ($update){
            $file->open('w+');
            $file->write(serialize($this->cache));
            $file->close();
        }
    }

    public function __setCache($name, $value){
        $this->cache[$name] = $value;
    }

    public function __getCache($name){
        return $this->cache[$name];
    }

    public function __incCache($name, $value = 1){
        $this->cache[$name] += $value;
    }
}

abstract class SchedulerTask {
    const type = __CLASS__;
    const CACHE_TIMESTAMP_INTERVAL = 1000000;

    /** @var Scheduler */
    protected $scheduler;

    abstract protected function invoke();
    abstract protected function getInterval();

    public function __construct(Scheduler $scheduler){
        $this->scheduler = $scheduler;
    }

    function isOnStart(){
        return false;
    }

    function getRepeatCount(){
        return 0;
    }

    public function getTimestamp(){
        return (int)$this->scheduler->__getCache(get_class($this) . '.stamp');
    }

    private function setTimestamp(){
        $this->scheduler->__setCache(get_class($this) . '.stamp', time());
    }

    public function getRunCount(){
        return (int)$this->scheduler->__getCache(get_class($this) . '.runs');
    }

    private function incRunCount(){
        $this->scheduler->__incCache(get_class($this) . '.runs', 1);
    }

    protected function resetRunCount(){
        $this->scheduler->__setCache(get_class($this) . '.runs', 0);
    }

    protected function isTimeLeft(){
        if ($this->isOnStart() && !$this->getRunCount()){
            return true;
        }

        $stamp = $this->getTimestamp();
        $interval = Time::parseDuration($this->getInterval());

        return time() - $stamp >= $interval;
    }

    public function run(){
        if ($this->isTimeLeft()){
            $this->setTimestamp();
            if ($this->isOnStart() || ($repeatCount = $this->getRepeatCount())){
                if ($repeatCount && $this->getRunCount() >= $repeatCount){
                    return false;
                }

                $this->incRunCount();
            }
            $this->invoke();
            return true;
        }
        return false;
    }
}
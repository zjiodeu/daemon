<?php

define('PROJECT_PATH', realpath(__DIR__));

require PROJECT_PATH . DIRECTORY_SEPARATOR . PSocket;

class PDaemon {
    
    protected function daemonize() {
       
       $pid = pcntl_fork();
       if ($pid < 0) {
           throw new LogicException("pcntl_fork error");
       }
       else if ($pid) {
           file_put_contents(self::PID, $pid);
           exit();
       }
       else {
           posix_setsid();
       }
    }
    
    public function start() {
        
    }
    
    public function stop() {
        
    }
    
    public function restart() {
        if (!$this->stop()) {
            throw new Exception("Cannot correctly restart" . __CLASS__);
        }
        $this->start();
    }
}

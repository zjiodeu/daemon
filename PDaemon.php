<?php
interface phpd {
    
    public function start();

    public function restart();

    public function stop();
}


class PDaemon implements phpd{
    
    protected $_argv = array();
    
    protected $_pidfile;
    
    public function __construct($name) {
        $this->_pidfile = "/run/" . $name . ".pid";
        $this->_argv = $_SERVER['argv'];
        /*fclose(STDIN);
        fclose(STDOUT);
        fclose(STDERR);*/
    }
    
    public function daemonize() {
        $command = strtolower($this->_argv[1]);
       switch($command) {
           
           case 'start': {
               $this->start();
               break;
           }
           case 'stop' : {
               $this->stop();
               break;
           }
           
           case 'restart' : {
               $this->restart();
               break;
           }
           default : {
               throw new Exception('Use start, stop or restart');
           }
       }
    }
    
    public function start() {
       $pid = pcntl_fork();
       if ($pid < 0) {
           throw new LogicException("pcntl_fork error");
       }
       else if ($pid) {
           file_put_contents($this->_pidfile, $pid);
           exit();
       }
       else {
           posix_setsid();
       }
    }
    
    public function stop() {
        if (false !== ($pid = $this->getPid())) {
            $kill = posix_kill($pid, SIGTERM);
            if (!$kill) {
                throw new Exception("cannot kill process with pid : $pid");
            }
            echo "Daemon was stopped";
            return true;
        }
        else {
            echo "seems daemon not running";
        }
        return false;
    }
    
    public function restart() {
        if (!$this->stop()) {
            throw new Exception("Cannot correctly restart" . __CLASS__);
        }
        $this->start();
    }
    
    protected function getPid() {
        return file_exists($this->_pidfile) ?
            (int)file_get_contents($this->_pidfile) :
            false;
    }
}

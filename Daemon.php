#!/usr/bin/php -q
<?php

class LocalD {
    
    protected static $_instance;
    
    protected static $_server;
    
    const MAX_CONNECTIONS = 10;
    
    const PID = '/run/localD.pid';
    
    protected function __construct() {
        try {
            $socket = '/tmp/' . __CLASS__ . ".sock";
            if (file_exists($socket)) {
                unlink($socket);
            }
            self::$_server = stream_socket_server("unix:///$socket", $errno, $errorMessage);
            if (!self::$_server) {
                throw new Exception($errorMessage);
            }
            chmod($socket, 0666);
                    
        } catch (Exception $ex) {
            $this->log($ex);
            exit();
        }
    }
    
    public static function instance() {
        if (file_exists(self::PID)) {
            $pid = file_get_contents(self::PID);
            throw new Exception(__CLASS__ . " has already been running. Pid : $pid");
        }
        if (null === self::$_instance) {
            self::$_instance = new self;
        }
        return self::$_instance;
    } 
    
    protected static function read($sock) {
        return fgets($sock, 100);
    }
    
    protected static function write($sock, $data) {
        return fwrite($sock, $data);
    }
    
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
    
    public function run(array $params = array()) {
        $write = $except = null;
        $connections = $read = array();
        try {
            $this->daemonize();
            while (true) {
                $read[] = self::$_server;
                if (false === ($activeSocks = stream_select($read, $write, $except, 0, 200000))) {
                    throw new Exception("stream select error");
                }
                elseif ($activeSocks > 0) {
                    if (in_array(self::$_server, $read)) {
                        $connections[] = stream_socket_accept(self::$_server);
                        echo "New connection accepted";
                    }
                    foreach ($connections as $key => $client) {
                        if (in_array($client, $read)) {
                            $data = self::read($client);
                            $this->log(new Exception($data));
                            self::write($client, strrev($data));
                            fclose($client);
                            unset($connections[$key]);
                        }
                    }
                    $read = $connections;
                    
                }
            }
        }
        catch (Exception $e) {
            $this->log($e);
        }
    }
    
    public function __destruct() {
        echo "Destruct called";
        if (is_resource(self::$_server)) {
            fclose(self::$_server);
        }
    }
    
    protected function log(Exception $e) {
        file_put_contents('/tmp/localD.log', $e->getMessage() . PHP_EOL, FILE_APPEND );
    } 
}
$daemon = localD::instance();

register_shutdown_function(function() use($daemon){
    unset($daemon);
});
$daemon->run();

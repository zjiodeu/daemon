<?php

define('PROJECT_DIR', realpath(__DIR__));

define('DS', DIRECTORY_SEPARATOR);

define('LOGS_DIR', PROJECT_DIR . "/logs");

require PROJECT_DIR . DS . "PDaemon.php";

abstract class PSocket {
    
    protected static $_instance;
    
    protected static $_server;
    
    protected $_name;
    
    protected $_log;
    
    public $errors = array();
    
    protected $_daemon;
    
    protected $_argv = array();
    
    const MAX_CONNECTIONS = 10;
    
    
    protected function __construct() {
        $this->_name = get_called_class();
        $this->_log = LOGS_DIR . $this->_name . ".log";
        try {
            $this->_daemon = new PDaemon($this->_name);
            $socket = '/tmp/' . $this->_name . ".sock";
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
        if (null === self::$_instance) {
            self::$_instance = new Static;
        }
        return self::$_instance;
    } 
    
    abstract public static function read($sock);
    
    abstract public static function write($sock, $data);
    
    abstract protected function handleClient(&$client);
    
   
    public function run() {
        $write = $except = null;
        $connections = $read = array();
        try {
            $this->_daemon->daemonize();
            while (true) {
                $read[] = self::$_server;
                if (false === ($activeSocks = stream_select($read, $write, $except, 0, 200000))) {
                    throw new Exception("stream select error");
                }
                elseif ($activeSocks > 0) {
                    if (in_array(self::$_server, $read)) {
                        $connections[] = stream_socket_accept(self::$_server);
                        //$this->log(new Exception("New connection accepted"));
                    }
                    foreach ($connections as $key => $client) {
                        if (in_array($client, $read)) {
                            $this->handleClient($client);
                            if (!is_resource($client)) {
                                unset($connections[$key]);
                            }
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
        if (is_resource(self::$_server)) {
            fclose(self::$_server);
        }
    }
    
    protected function log(Exception $e) {
        file_put_contents($this->_log, var_export($e, 1), FILE_APPEND);
    } 
}

#!/usr/bin/php -q
<?php

class PSocket {
    
    protected static $_instance;
    
    protected static $_server;
    
    public $errors = array();
    
    const MAX_CONNECTIONS = 10;
    
    
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
        if (null === self::$_instance) {
            self::$_instance = new self;
        }
        return self::$_instance;
    } 
    
    public static function read($sock) {
        return fgets($sock, 100);
    }
    
    public static function write($sock, $data) {
        return fwrite($sock, $data);
    }
    

    
    public function run(Closure $_callBack) {
        $write = $except = null;
        $connections = $read = array();
        try {
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
                            $_callBack($client);
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
        if (is_resource(self::$_server)) {
            fclose(self::$_server);
        }
    }
    
    protected function log(Exception $e) {
        $this->errors[] = $e;
    } 
}
/*
$daemon = localD::instance();

register_shutdown_function(function() use($daemon){
    unset($daemon);
});
$daemon->run();
 * 
 */

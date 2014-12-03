<?php

class systemDaemon {
    
    protected static $_instance;
    
    protected static $_server;
    
    const MAX_CONNECTIONS = 10;
    
    protected function __construct() {
        try {
            $socket = '/tmp/' . __CLASS__ . ".sock";
            if (file_exists($socket)) {
                unlink($socket);
            }
            self::$_server = stream_socket_server("unix:///$socket", $errno, $errorMessage);
                    
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
    
    protected static function read($sock) {
        return fgets($sock, 100);
    }
    
    public function run(array $params = array()) {
        $write = $except = null;
        $connections = $read = array();
        try {
            while (true) {
                $read[0] = self::$_server;
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
                            if (is_null($data)) {
                                fclose($client);
                                unset($connections[$key]);
                            }
                        }
                    }
                    $this->log(new Exception('Hello from non in_array'));
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
     file_put_contents('/tmp/sysDaemon.log', $e->getMessage());
    } 
}
$daemon = systemDaemon::instance();

register_shutdown_function(function() use($daemon){
    unset($daemon);
});
$daemon->run();

#!/usr/bin/php -q
<?php

require "../PSocket.php";

final class Localhostd extends PSocket{
       
    public static function read($sock) {
        return fgets($sock, 100);
    }
    
    public static function write($sock, $data) {
     fwrite($fsock, $data);
    }
    
    protected function handleClient(&$client) {
        $data = self::read($client);
        $this->log(new Exception($data));
        fclose($client);
    }
}

Localhostd::instance()->run();

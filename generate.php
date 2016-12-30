<?php

error_reporting(0);
include_once("loader.php");

use classes\Vhm;

try {
    
    # Params control.
    if (empty($argv[1]) || empty($argv[2]))
        throw new \Exception("\tError: you must enter two params.\n\n");
    
    $port = (!preg_match("/^\d{2,4}$/", $argv[3])) ? '80' : $argv[3];
    
    $ip_address = (!preg_match("/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/", $argv[4])) ? '127.0.0.1' : $argv[4];    
    
    $vhm = new Vhm(array(
        'server_name' => $argv[1],
        'document_root' => $argv[2],
        'port' => $port,
        'ip_address' => $ip_address
    ));
    
    $vhm->generate();        
    
} catch (\Exception $ex) {
    echo $ex->getMessage();
}
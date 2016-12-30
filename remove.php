<?php

error_reporting(0);
include_once("loader.php");
use classes\Vhm;

try {

        # Params control.
    if (empty($argv[1]))
        throw new \Exception("\tError: no receive parameters.\n\n");
    
    $vhm = new Vhm(array(
        'server_name' => $argv[1]
    ));
    
    $vhm->remove();

    
} catch (\Exception $ex) {
    echo $ex->getMessage();
}
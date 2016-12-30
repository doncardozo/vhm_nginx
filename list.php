<?php

error_reporting(0);
include_once("loader.php");

use classes\Vhm;

try {
    
    $vhm = new Vhm();    
    $vhm->listHosts();        
    
} catch (\Exception $ex) {
    echo $ex->getMessage();
}
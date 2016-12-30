<?php

define("DROOT", dirname(__FILE__));
define("DS", DIRECTORY_SEPARATOR);

spl_autoload_register(function( $class ) {

    try {

        $file = DROOT . DS . str_replace("\\", DS, $class) . ".php" ;

        if ( !file_exists($file) )
            throw new \Exception("Error: {$class} not found.\n");
            
        include_once($file);

    } catch (\Exception $ex) {
        echo $ex->getMessage();
    }
    
});

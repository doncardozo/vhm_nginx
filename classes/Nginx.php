<?php

namespace classes;

use classes\InterfaceAction;
use classes\InterfaceParams;

class Nginx implements InterfaceAction, InterfaceParams {

    private $_default;
    private $_path_nginx = null;
    private $_path_nginx_sites = array();
    private $_server_name = null;
    private $_document_root = null;
    private $_port = null;
    private $_ext = null;

    public function __construct() {

        $this->_path_nginx = '/etc/nginx';
        $this->_path_nginx_sites = array(
            'sites-available' => "{$this->_path_nginx}/sites-available",
            'sites-enabled' => "{$this->_path_nginx}/sites-enabled"
        );
    }

    public function setParams(array $params) {

        if (!array_key_exists("server_name", $params))
            throw new \Exception("--- Error: server name parameter. ---\n");

        if (!array_key_exists("document_root", $params))
            throw new \Exception("--- Error: missing document root parameter. ---\n");

        if (!array_key_exists("port", $params))
            throw new \Exception("--- Error: missing port parameter. ---\n");

        $this->_server_name = $params['server_name'];
        $this->_document_root = $params['document_root'];
        $this->_port = $params['port'];
        $this->_ext = '.conf';
    }

    public function getDefault() {

        $this->_default = <<<A
server {
    listen {$this->_port};
    listen [::]:{$this->_port};

    root {$this->_document_root};
    index index.php;

    server_name {$this->_server_name};

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php\$ {
        try_files \$uri /index.php =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass unix:/run/php/php7.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
    }
}


A
        ;

        return $this->_default;
    }

    public function update() {

        $this->writeFile();

        # Enter into nginx directory.
        system("cd {$this->getSite("sites-available")}");
        
        # Register new file configuration.
        $this->enableSite();

        # Restart service.
        $this->restart();        
    }

    private function writeFile() {

        $path = $this->getSite("sites-available");
        if (!file_put_contents("{$path}/{$this->_server_name}", $this->getDefault()))
            throw new \Exception("Write file [fail]\n");

        echo "Write file [ok]\n";
    }

    public function serverExist() {

        if (file_exists("{$this->getSite("sites-available")}/{$this->_server_name}")) {
            return true;
        }
    }

    public function createServerBackup() {

        $path = $this->getSite("sites-available");
        system("cp {$path}/{$this->_server_name} {$path}/{$this->_server_name}." . time(), $return);
        if ($return)
            throw new \Exception("Create backup [fail]\n");

        echo "Create backup [ok]\n";
    }

    public function getSite($key) {

        if (!array_key_exists($key, $this->_path_nginx_sites))
            throw new \Exception("Error: $key not found\n");

        return $this->_path_nginx_sites[$key];
    }

    private function searchFileServer() {

        exec("ls -l {$this->getSite('sites-available')} | egrep {$this->_server_name}.?+ | awk '{print $9}' ", $available, $return);
        if ($return)
            throw new \Exception("--- Error ocurred when search nginx files.\n");

        exec("ls -l {$this->getSite('sites-enabled')} | egrep {$this->_server_name}.?+ | awk '{print $9}' ", $enabled, $return);
        if ($return)
            throw new \Exception("--- Error ocurred when search nginx files.\n");

        return array($available, $enabled);
    }

    private function rmFiles($path, $files) {
        foreach ($files as $file) {
            if (!unlink("{$path}/$file"))
                throw new \Exception(" --- Error: cannot remove file.\n");
        }
    }

    private function rmServer() {

        $tmp = $this->searchFileServer();

        if (sizeof($tmp[0]) > 0) {
            $this->rmFiles($this->getSite("sites-available"), $tmp[0]);
            echo "Remove server in sites-available [ok].\n";
        }
        else {
            echo "No server in sites-available [ok].\n";
        }

        if (sizeof($tmp[1]) > 0) {
            $this->rmFiles($this->getSite("sites-enabled"), $tmp[1]);
            echo "Remove server in sites-enabled [ok].\n";
        }
         else {
            echo "No server in sites-enabled [ok].\n";
        }
        
    }

    private function enableSite(){
        system("ln -s {$this->_path_nginx_sites['sites-available']}/{$this->_server_name} {$this->_path_nginx_sites['sites-enabled']}/{$this->_server_name}", $return);
        if ($return)
            throw new \Exception("Activate server [fail]\n");

        echo "Activate server [ok]\n";        
    }
    
    private function restart() {
        system("systemctl reload nginx", $return);
        if ($return)
            throw new \Exception("Restart server [fail]\n");
        
        echo "Restart server [ok]\n";
    }

    public function remove($serverName) {

        if (empty($serverName))
            throw new \Exception("--- Error: missing server name param. ---\n");

        $this->_server_name = $serverName;

        $this->rmServer();

        $this->restart();
    }

}

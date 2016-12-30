<?php

namespace classes;

use classes\InterfaceAction;
use classes\InterfaceParams;

class Hosts implements InterfaceAction, InterfaceParams {

    private $_path = null;
    private $_tmp_filename = null;
    private $_hosts = null;
    private $_hosts_data = null;
    private $_server_name = null;
    private $_ip_address = null;
    private $_port = null;

    public function __construct() {

        # Config path
        $this->_path = dirname(__FILE__);
        $this->_hosts = '/etc/hosts';
        $this->_tmp_filename = "{$this->_path}/../hosts.tmp";
    }

    public function setParams(array $params) {

        if (!array_key_exists("server_name", $params))
            throw new \Exception("--- Error: server name parameter. ---\n");

        if (!array_key_exists("ip_address", $params))
            throw new \Exception("--- Error: missing ip address parameter. ---\n");

        if (!array_key_exists("port", $params))
            throw new \Exception("--- Error: missing port parameter. ---\n");

        $this->_server_name = $params['server_name'];
        $this->_ip_address = $params['ip_address'];
        $this->_port = $params['port'];
    }

    public function update() {
        $this->createTmpHosts();
        $this->loadHostData();
        $this->updateHosts();
    }

    private function loadHostData() {
        $this->_hosts_data = file_get_contents($this->_tmp_filename);
    }

    private function toArray() {
        return explode("\n", $this->_hosts_data);
    }

    private function updateHosts() {

        $aTmp = $this->toArray();

        $update = false;

        for ($i = 0; $i < sizeof($aTmp); $i++) {

            if (preg_match("/{$this->_server_name}/", $aTmp[$i])) {
                break;
            }

            # Check if is the las line of first block.
            if (($aTmp[$i] == "")) {
                # The host tag is added.
                array_splice($aTmp, $i, 0, "{$this->_ip_address}	{$this->_server_name}");
                # The temporary file is updated.
                $this->updateTmpHosts($aTmp);
                $update = true;
                break;
            }
        }

        if ($update) {
            # Check if is root
            if (!is_writable($this->_hosts))
                throw new \Exception("Error: you don't have permission to write this file!.\n"
                . "You must be edit manualy hosts file.\n");

            # /etc/hosts is updated.
            if (!$this->updateHostsFile())
                throw new \Exception("Update hosts file [fail]\n");

            $this->unlinkTmpHosts();
            
            echo "Update hosts file [ok]\n";
        }
    }

    private function updateHostsFile() {
        if (file_put_contents($this->_hosts, file_get_contents($this->_tmp_filename)))
            return true;
    }

    private function updateTmpHosts($cur_array = array()) {

        if (sizeof($cur_array) == 0)
            throw new \Exception;("Array is empty!.\nUpdate tmp hosts [fail]\n");

        array_pop($cur_array);
        $f = fopen($this->_tmp_filename, "w");
        foreach ($cur_array as $value) {
            fwrite($f, "$value\n");
        }

        fclose($f);

        echo "Update tmp hosts [ok].\n";
    }

    private function createTmpHosts() {
        system("cp {$this->_hosts} {$this->_tmp_filename}", $return);
        if ($return)
            throw new \Exception("Create tmp hosts [fail]\n");

        echo "Create tmp hosts [ok]\n";
    }

    public function remove($serverName) {

        if (is_null($serverName))
            throw new \Exception("--- Error: you must specify a server name. ---\n");

        $this->_server_name = $serverName;
        
        $this->createTmpHosts();
        $this->loadHostData();
        $this->rmServer();
    }
    
    private function unlinkTmpHosts(){
        if(!unlink($this->_tmp_filename))
            throw new \Exception("Cannot remove tmp hosts.\n");
    }

    private function rmServer() {

        $aTmp = $this->toArray();
            
        $found = false;

        for ($i = 0; $i < sizeof($aTmp); $i++) {

            if (preg_match("/{$this->_server_name}/", $aTmp[$i])) {
                $b = array_splice($aTmp, $i);
                array_shift($b);
                array_splice($aTmp, $i, 0, $b);
                $found = true;
                break;
            }
        }

        if(!$found){
            $this->unlinkTmpHosts();
            return "Hosts file not updated.\n";
        }
        
        $this->updateTmpHosts($aTmp);

        # /etc/hosts is updated.
        if (!$this->updateHostsFile())
            throw new \Exception("Update hosts file [fail]\n");
        
        $this->unlinkTmpHosts();

        echo "Update hosts file [ok]\n";
    }
    
    public function listHosts(){        
        $arr_hosts = explode("\n", file_get_contents($this->_hosts));
        foreach($arr_hosts as $host){
            if($host == "") { 
                break;
            }
            echo "{$host}\n";
        }
    }

}

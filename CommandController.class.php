<?php
class CommandController{

    protected $started_at;
    protected $num_commands;
    protected $unique_ips;
    public $terminate_server = false;

    public function __construct(){
        $this->started_at = time();
        $this->num_commands = 0;
        $this->unique_ips = array();
    }

    protected function _getUptimeMsg(){
        $this->num_commands += 1;
        $duration = time() - $this->started_at;
        return sprintf("Server running for %s seconds\n", $duration);
    }

    protected function _getNumCommandsMsg(){
        $this->num_commands += 1;
        return sprintf("Responded to %s commands\n", $this->num_commands);
    }

    protected function _getNumClientsMsg(){
        return sprintf("Served %s clients\n", count($this->unique_ips));
    }

    protected function _stopServerAndGetMsg(){
        $this->terminate_server = true;
        $msg  = $this->_getUptimeMsg();
        $msg .= $this->_getNumCommandsMsg();
        $msg .= $this->_getNumClientsMsg();
        return $msg;
    }

    public function addClientIp($ip){
        if (!in_array($ip, $this->unique_ips)){
            $this->unique_ips[] = $ip;
        }
    }

    public function getResponseFor($command){
        switch ($command) {
            case 'uptime':
                $output = $this->_getUptimeMsg();
                break;
            case 'requests':
                $output = $this->_getNumCommandsMsg();
                break;
            case 'clients':
                $output = $this->_getNumClientsMsg();
                break;
            case 'stop':
                $output = $this->_stopServerAndGetMsg();
                break;
            default:
                $output = false;
        }
        return $output;
    }
}

<?php
class SimpleServer{

    const MAX_COMMAND_LEN = 250;

    protected $socket;
    protected $clients;
    protected $is_ready = false;
    protected $available_commands = array('uptime', 'requests', 'clients', 'stop');
    protected $command_ctrl;

    public function __construct($address, $port, $command_ctrl){
        set_time_limit(0);

        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($this->socket === false) {
            echo 'Cannot create socket';
            return;
        }

        if (socket_bind($this->socket, $address, $port) === false) {
            echo sprintf('Cannot bind to %s:%s', $address, $port);
            return;
        }

        if (socket_listen($this->socket) === false) {
            echo sprintf('Cannot listen connections');
            return;
        }

        if (socket_set_nonblock($this->socket) === false) {
            echo sprintf('Cannot set non-blocking socket');
            return;
        }

        $this->clients = array();
        $this->command_ctrl = $command_ctrl;

        $this->is_ready = true;
    }

    protected function _checkAndTrackNewClients(){
        $sock = socket_accept($this->socket);
        if (!is_resource($sock)){
            return;
        }

        if (socket_getpeername($sock, $remote_address) === false){
            // we cannot talk to this client if we can't get the address
            socket_close($sock);
            return;
        }

        $this->command_ctrl->addClientIp($remote_address);

        $intro =
            "Available commands:\n" .
            "   UPTIME   - Shows how long the server is running\n" .
            "   REQUESTS - Shows number requests made so far\n" .
            "   CLIENTS  - Shows number of unique clients\n" .
            "   STOP     - Stops the server\n";
        socket_write($sock, $intro);

        $this->clients[] = $sock;
        return;
    }

    protected function _readCommand($sock){
        $command = socket_read($sock, self::MAX_COMMAND_LEN, PHP_NORMAL_READ);
        if ($command === false) {
            echo socket_strerror(socket_last_error($sock)) . "\n";
            return false;
        }

        return strtolower(trim($command));
    }

    protected function _closeAndRemoveSocket($socket){
        $key = array_search($socket, $this->clients);
        if ($key !== false){
            unset($this->clients[$key]);
        }
        socket_close($socket);
    }

    protected function _getUpdatedClients(){
        if (count($this->clients) === 0){
            return array();
        }
        $sockets_read = $this->clients;
        $sockets_write = null;
        $sockets_except = null;
        $num_sockets_changed = socket_select($sockets_read, $sockets_write, $sockets_except, 0);
        if ($num_sockets_changed < 1){
            return array();
        }
        return $sockets_read;
    }

    protected function _talkToClients($sockets){
        foreach ($sockets as $i => $socket) {
            $command = $this->_readCommand($socket);
            if ($command === false){
                $this->_closeAndRemoveSocket($socket);
                continue;
            }

            $output = $this->command_ctrl->getResponseFor($command);
            if ($output !== false){
                socket_write($socket, $output);
            }
        }
    }

    public function run(){
        if ($this->is_ready === false){
            return;
        }

        while ($this->command_ctrl->terminate_server === false){
            usleep(100 * 1000);
            $this->_checkAndTrackNewClients();
            $sockets = $this->_getUpdatedClients();
            $this->_talkToClients($sockets);
        }

        foreach ($this->clients as $socket) {
            socket_close($socket);
        }
    }
}

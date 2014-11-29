<?php
/**
 * Accepts and replies plain text commands, terminated by newline character
 * Responds following commands, but ignore otherwise:
 * a) UPTIME   - the server must respond with how long it's been running
 * b) REQUESTS - the server must respond with how many requests it has served since started (1 request = 1 command received)
 * c) CLIENTS  - the server must respond with how many unique clients it has served since started (1 client = 1 IP address)
 * d) STOP     - the server must respond to the a) b) and c) commands, then shut down
 */

error_reporting(E_ALL);
$configs = parse_ini_file(__DIR__.'/config.ini', true);

require_once __DIR__.'/SimpleServer.class.php';
require_once __DIR__.'/CommandController.class.php';

$command_ctrl = new CommandController();

$service = new SimpleServer(
    $configs['address'],
    $configs['port'],
    $command_ctrl
);

$service->run();

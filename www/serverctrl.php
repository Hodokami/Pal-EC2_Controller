<?php
require_once __DIR__.'/../auth.php';

require_once __DIR__.'/../PalRcon/src/Rcon.php';
use Thedudeguy\Rcon;
$rcon = new Rcon($host, $port, $password, $timeout);

require_once __DIR__.'/../AWS/aws.phar';
use Aws\Ec2\Ec2Client;
$ec2Client = new Aws\Ec2\Ec2Client(['region' => $region, 'version' => '2016-11-15', 'profile' => 'default']);
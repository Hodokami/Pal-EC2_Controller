<?php
session_start();

require_once __DIR__.'/../auth.php';

require_once __DIR__.'/../PalRcon/src/Rcon.php';
use Thedudeguy\Rcon;
$rcon = new Rcon($host, $port, $password, $timeout);

require_once __DIR__.'/../AWS/aws.phar';
use Aws\Ec2\Ec2Client;
$ec2Client = new Aws\Ec2\Ec2Client(['region' => $region, 'version' => '2016-11-15', 'profile' => 'default']);

if($argv[1] === 'start')
{
    if(isset($_SESSION['loggedin']))
	{
		if($_SESSION['loggedin'] === true && $state === 'stopped')
		{
            $_SESSION['timeunlock'] = time() + 180;
			$ec2Client -> startInstances(['InstanceIds' => $instanceIds,]);
		}
	}
}

if($argv[1] === 'stop')
{
    if(isset($_SESSION['loggedin']))
	{
		if($_SESSION['loggedin'] === true)
		{
            $_SESSION['timeunlock'] = time() + 180;
			if ($rcon->connect())
			{
				$rcon->sendCommand("save");
				sleep(20);
				$rcon->sendCommand("shutdown 60 Server_will_close_after_1min.");
				$rcon->disconnect();
				sleep(120);
				$ec2Client -> stopInstances(['InstanceIds' => $instanceIds,]);
			}
			else
			{
				$rcon->disconnect();
			}
		}
	}
}

if($argv[1] === 'frestart')
{
    if(isset($_SESSION['loggedin']))
	{
		if($_SESSION['loggedin'] === true)
		{
            $_SESSION['timeunlock'] = time() + 180;
			$ec2Client -> rebootInstances(['InstanceIds' => $instanceIds,]);
		}
	}
}
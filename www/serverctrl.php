<?php
require_once __DIR__.'/../auth.php';

require_once __DIR__.'/../PalRcon/src/Rcon.php';
use Thedudeguy\Rcon;
$rcon = new Rcon($host, $port, $password, $timeout);

require_once __DIR__.'/../AWS/aws.phar';
use Aws\Ec2\Ec2Client;
$ec2Client = new Aws\Ec2\Ec2Client(['region' => $region, 'version' => '2016-11-15', 'profile' => 'default']);
if(isset($argv))
{
	if($argv[1] === 'start')
	{
		$ec2Client -> startInstances(['InstanceIds' => $instanceIds,]);
	}

	if($argv[1] === 'stop')
	{
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

	if($argv[1] === 'frestart')
	{
		$ec2Client -> rebootInstances(['InstanceIds' => $instanceIds,]);
	}
}
else
{
	header('HTTP', true, 403); // Forbidden direct access
}
<?php
require_once __DIR__.'/../auth.php'; // Params for RCON, AWS, and Discord. FOR TEST ON Docker
// require_once __DIR__.'/../../auth.php'; // Params for RCON, AWS, and Discord.
// Composer
require_once __DIR__.'/../../../vendor/autoload.php'; // FOR TEST ON Docker
// require_once __DIR__.'/../../vendor/autoload.php'; // Settings for running on KUSANAGI
// RCON Library by https://github.com/thedudeguy/PHP-Minecraft-Rcon
use Hodokami\Rcon;
$rcon = new Rcon($host, $port, $password, $timeout);
// AWS SDK for PHP
// use Aws\Credentials\CredentialProvider;  // Credentials Settings for running on KUSANAGI
// $provider = CredentialProvider::ini('default', __DIR__.'/../../credentials.ini');
// $provider = CredentialProvider::memoize($provider);
use Aws\Ec2\Ec2Client;
$ec2Client = new Ec2Client(['region' => $region, 'version' => '2016-11-15', 'profile' => 'default']);  // FOR TEST ON Docker
// $ec2Client = new Ec2Client(['region' => $region, 'version' => '2016-11-15', 'credentials' => $provider]); // Settings for running on KUSANAGI

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
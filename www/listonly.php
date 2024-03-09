<?php
require_once('../PalRcon/src/Rcon.php');
require_once('../rconauth.php');
use Thedudeguy\Rcon;
$rcon = new Rcon($host, $port, $password, $timeout);
if ($rcon->connect())
{
	$info = $rcon->sendCommand("info");
	echo $info.'<br>';
	$playersraw = $rcon->sendCommand("showplayers");
	$players = array_chunk(preg_split("/[\s,]+/", htmlspecialchars( $playersraw, ENT_QUOTES)),3);
	$rcon->disconnect();
}
else
{
	echo "Timeout.";
	$rcon->disconnect();
}
?>
<!DOCTYPE html>
<html lang="ja">
	<head>
		<meta charset="UTF-8">
		<title>PHP RCON TEST</title>
	</head>
	<body>
		Online Players:<br>
		<?php
			foreach($players as $key => $value)
			{
				if($key === 0){ continue; }
				echo $value[0].'<br>';
			}
		?>
	</body>
</html>
<?php
session_start();

require_once __DIR__.'/../auth.php';

require __DIR__.'/../PalRcon/src/Rcon.php';
use Thedudeguy\Rcon;
$rcon = new Rcon($host, $port, $password, $timeout);

require __DIR__.'/../AWS/aws.phar';
use Aws\Ec2\Ec2Client;
$ec2Client = new Aws\Ec2\Ec2Client(['region' => $region, 'version' => '2016-11-15', 'profile' => 'default']);

function post2discord($post, $url)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, TRUE);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Accept-Charset: UTF-8'));
	curl_exec($ch);
	curl_close($ch);
}

if(isset($_SESSION['loggedin'])) { if($_SESSION['loggedin'] === true) { $status = 'Logged in!'; } else { $status = 'Not logged in.'; } } else { $status = 'No status.'; }

if(array_key_exists('sendpw', $_POST))
{
	if(!isset($_SESSION['sendcount']))
	{
		$_SESSION['sendcount'] = 1;
	}
	if(0 <= ++$_SESSION['sendcount'] && $_SESSION['sendcount'] <= 6) // 連打対策
	{
		$_SESSION['password'] = bin2hex(random_bytes(12));
		$message = json_encode(array('username' => 'Login Password', 'content' => $_SESSION['password']));
		post2discord($message, $webhook);
		header('Location:' . $_SERVER['PHP_SELF']);
	}
}

if(array_key_exists('login', $_POST))
{
	$gotpw = htmlspecialchars(strip_tags($_POST['password']), ENT_QUOTES, 'UTF-8'); // XSS対策
	if(isset($_SESSION['password']))
	{
		if($_SESSION['password'] === $gotpw)
		{
			$_SESSION['loggedin'] = true;
		}
		else
		{
			session_unset(); // passwordが間違った時点ですべてリセット
		}
		header('Location:' . $_SERVER['PHP_SELF']);
	}
	else
	{
		echo '<script>alert("Password is not sent!")</script>';
		session_unset();
	}
}

$result = $ec2Client -> describeInstanceStatus(['IncludeAllInstances' => true, 'InstanceIds' => $instanceIds,]);
echo $result;
$state = $result['InstanceStatuses'][0]['InstanceState']['Name'];
echo $state;
if($state == 'running'){
	if ($rcon->connect())
	{
		$info = $rcon->sendCommand("info");
		$playersraw = $rcon->sendCommand("showplayers");
		$players = array_chunk(preg_split("/[\s,]+/", htmlspecialchars( $playersraw, ENT_QUOTES)),3);
		$rcon->disconnect();
	}
	else
	{
		echo "Timeout.";
		$rcon->disconnect();
	}
}
?>
<!DOCTYPE html>
<html lang="ja">
	<head>
		<meta charset="UTF-8">
		<title>PHP RCON TEST</title>
	</head>
	<body>
		<p>Your status: <?php echo $status;?></p>
		<form method = "POST">
			<p><input type="submit" name="sendpw" class="button" value="Send password"></p>
		</form>
		<form method = "POST">
			<p>Password:<input type="text" name="password" class="pwform"><input type="submit" name="login" class="button" value="Login"></p>
		</form>
		<?php
		if($state == 'running'){
			echo '<p>'.$info.'</p>';
			echo '<p>Online Players:<br>';
			foreach($players as $key => $value)
			{
				if($key === 0) { continue; }
				echo $value[0] . '<br>';
			}
			echo '</p>';
		}
		?>
	</body>
</html>
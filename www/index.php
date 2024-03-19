<?php
// やりたいこと
// セッション有効期限
if(isset($_GET['cache'])) // Avoid KUSANAGI fcache
{
	if($_GET['cache'] !== 'false')
	{
		header('Location:'.$_SERVER['PHP_SELF'].'?cache=false');
	}
}
else
{
	header('Location:'.$_SERVER['PHP_SELF'].'?cache=false');
}

session_set_cookie_params(900);
session_start();

require_once __DIR__.'/../auth.php';

require_once __DIR__.'/../PalRcon/src/Rcon.php';
use Thedudeguy\Rcon;
$rcon = new Rcon($host, $port, $password, $timeout);

require_once __DIR__.'/../AWS/aws.phar';
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
if(isset($_SESSION['timeunlock'])) { if($_SESSION['timeunlock'] <= time()) { $locked = false; } else { $locked = true; }} else { $locked = false; }

if($locked === false) // Get EC2 Instance State
{
	$result = $ec2Client -> describeInstanceStatus(['IncludeAllInstances' => true, 'InstanceIds' => $instanceIds,]);
	$state = $result['InstanceStatuses'][0]['InstanceState']['Name'];
	if(!isset($state)){$state = 'locked';}
}
else
{
	$state = 'locked';
}

if($state === 'running' && $locked === false) // Get Players List
{
	if (@$rcon->connect())
	{
		$info = $rcon->sendCommand("info");
		$playersraw = $rcon->sendCommand("showplayers");
		$players = array_chunk(preg_split("/[\s,]+/", htmlspecialchars( $playersraw, ENT_QUOTES)),3);
		$rcon->disconnect();
	}
	else
	{
		$players = array('ignore', 'Timeout.');
		$rcon->disconnect();
	}
}

if(array_key_exists('sendpw', $_POST))
{
	if($locked === false)
	{
		$_SESSION['timeunlock'] = time() + 10;
		$_SESSION['password'] = bin2hex(random_bytes(12));
		$message = json_encode(array('username' => 'Login Password', 'content' => $_SESSION['password']));
		post2discord($message, $webhook);
	}
	header('Location:'.$_SERVER['PHP_SELF'].'?cache=false');
}

if(array_key_exists('login', $_POST))
{
	$gotpw = htmlspecialchars($_POST['password'], ENT_QUOTES, 'UTF-8'); // XSS対策
	if(isset($_SESSION['password']))
	{
		if($_SESSION['password'] === $gotpw)
		{
			$_SESSION['timeunlock'] = time();
			$_SESSION['loggedin'] = true;
		}
		else
		{
			session_unset(); // passwordが間違った時点ですべてリセット
		}
		header('Location:'.$_SERVER['PHP_SELF'].'?cache=false');
	}
	else
	{
		echo '<script>alert("Password is not sent!")</script>';
		session_unset();
	}
}

if(array_key_exists('start', $_POST))
{
	if(isset($_SESSION['loggedin']))
	{
		if($_SESSION['loggedin'] === true && $state === 'stopped')
		{
			$_SESSION['timeunlock'] = time() + 180;
			$exec = 'php '.__DIR__.'/serverctrl.php start >/dev/null 2>&1 &';
			exec($exec);
		}
	}
	header('Location:'.$_SERVER['PHP_SELF'].'?cache=false');
}

if(array_key_exists('stop', $_POST))
{
	if(isset($_SESSION['loggedin']))
	{
		if($_SESSION['loggedin'] === true)
		{
			$_SESSION['timeunlock'] = time() + 180;
			$exec = 'php '.__DIR__.'/serverctrl.php stop >/dev/null 2>&1 &';
			exec($exec);
		}
	}
	header('Location:'.$_SERVER['PHP_SELF'].'?cache=false');
}

if(array_key_exists('frestart', $_POST))
{
	if(isset($_SESSION['loggedin']))
	{
		if($_SESSION['loggedin'] === true)
		{
			$_SESSION['timeunlock'] = time() + 180;
			$exec = 'php '.__DIR__.'/serverctrl.php frestart >/dev/null 2>&1 &';
			exec($exec);
		}
	}
	header('Location:'.$_SERVER['PHP_SELF'].'?cache=false');
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
		<p>Instance status: <?php echo $state;?></p>
		<form method = "POST">
			<p><input type="submit" name="sendpw" class="button" value="Send password" <?php if($locked === true) { echo 'disabled'; }?>></p>
		</form>
		<form method = "POST">
			<p>Password: <input type="text" name="password" class="pwform"><input type="submit" name="login" class="button" value="Login"></p>
		</form>
		<p>
			<form method = "POST"><input type="submit" name="start" class="button" value="Start Server" <?php if($state !== 'stopped' || $locked === true) { echo 'disabled'; }?>></form>
			<form method = "POST"><input type="submit" name="stop" class="button" value="Stop Server" <?php if($state !== 'running' || $locked === true) { echo 'disabled'; }?>></form>
			<form method = "POST"><input type="submit" name="frestart" class="button" value="&quot;FORCE&quot; Restart Server" <?php if($locked === true) { echo 'disabled'; }?>></form>
		</p>
		<?php
		if($state === 'running' && $locked === false){
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
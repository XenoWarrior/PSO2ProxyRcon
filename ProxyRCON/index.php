<?php
	define("PSO2ProxyRcon", true);

	require_once('php/config.php');
	require_once('php/functions.php');

	session_name("PSO2ProxyRcon");
	if(!isset($_SESSION))
	{
		session_start();
	}

	$announcement = checkVersion($version);

	/**********************************************************
		PSO2 Proxy Connection Test
	**********************************************************/
	if(isset($_POST['command_send']) AND isset($_SESSION))
	{
		if($_POST['command_send']=="Execute" AND $_SESSION['loggedIn']==true AND $_SESSION['correctKey']==true)
		{
			$command = str_replace(" ", "", $_POST['command_text']);
			if($command!=="")
			{
				//Send Command
				try
				{
					$url = htmlentities("http://".$settings['host'].":".$settings['port']."/rcon?key=".$settings['rkey']."&command=".$command."&params=".$_POST['command_args']);
					$url = str_replace(" ", "%20", $url);
					$output = file_get_contents($url);
					$output = json_decode($output, true);
					
				}
				catch(Exception $e)
				{
					$error = "Unable to connect to remote server! (".$settings['host'].":".$settings['port'].")";
					$help = "Please ensure that the host and port have been defined and you have access to the resource.";
				}
			}
			else
			{
				$output['reason'] = "Invalid command.";
				$output['success'] = 0;
			}
		}
	}
	else
	{
		try
		{
			$connection = file_get_contents("http://".$settings['host'].":".$settings['port']."/rcon?key=".$settings['rkey']);
			$connection = json_decode($connection, true);

			if($connection['reason']=="Your RCON key is invalid!")
			{
				$error = $connection['reason'];
				$help = "You can set your RCON key in the config.php file.<br>Make sure to change <b>settings['rkey']</b> to the key you have set on your proxy.";
				$_SESSION['correctKey']=false;
			}
			else
			{
				$output = $connection;
				$_SESSION['correctKey'] = true;

				if($settings['showInfo'])
				{
					$server = file_get_contents("http://".$settings['host'].":".$settings['port']);
					$config = file_get_contents("http://".$settings['host'].":".$settings['port']."/config.json");
					
					$_SESSION['serverInfo'] = json_decode($server, true);
					$_SESSION['serverConfig'] = json_decode($config, true);
				}
			}
		}
		catch(Exception $e)
		{
			$error = "Unable to connect to remote server! (".$settings['host'].":".$settings['port'].")";
			$help = "Please ensure that the host and port have been defined and you have access to the resource.";
		}
	}

	/**********************************************************
		Data submission
	**********************************************************/
	if(isset($_GET['logout']))
	{
		session_destroy();
		unset($_SESSION);
		header("Location: ./");
	}

	if(isset($_POST['submit']))
	{
		if($_POST['submit']=="Login")
		{
			if($_POST['username'] == $user['username'] AND $_POST['password'] == $user['password'])
			{
				$message = "You have logged in as ".$_POST['username']."!";

				$_SESSION['loggedIn'] = true;
				$_SESSION['username'] = $_POST['username'];
				$_SESSION['password'] = $_POST['password'];

				unset($_POST);
			}
			else
			{
				$error = "Your username or password has been entered incorrectly!";
				$help = "You can set your account details in config.php.<br><a href=\"./\">Click here</a> to try again.";
				unset($_POST);
			}
		}
	}
?>
<!DOCTYPE html>
<html>
	<head>
		<title>PSO2Proxy Remote Console</title>

		<link rel="stylesheet" type="text/css" href="css/style.css"/>
	</head>
	<body>
		<?php
			if(isset($error) AND $error!=="")
			{ ?>
				<center>
					<div>
						<span class="error" style="color:#FFF;"><?php echo($error); ?></span>
						<div style="border-radius:5px;background:#EEE;margin-top:32px;padding:20px;;width:464px;">
							<?php echo($help); ?>
						</div>
					</div>
				</center>
				<?php
			}
			else
			{ 
				if(!isset($_SESSION['loggedIn']))
				{ ?>
					<center>
						<div style="position:fixed;top:0;left:0;height:20px;width:100%;background:#333;">
							<span style="color:#FFF;">Please login to continue.</span>
						</div>
						<div style="border-radius:5px;background:#EEE;margin-top:32px;padding:20px;;width:464px;">
							<form action="" method="post">
								<input name="username" type="text" placeholder="Enter username here..."/>
								<input name="password" type="password" placeholder="Enter password here..."/>
								<input name="submit" type="submit" value="Login"/>
							</form>
						</div>
					</center>
					<?php
				}
				else
				{
					if($_SESSION['loggedIn']==true)
					{ ?>
						<center>
							<div style="position:fixed;top:0;left:0;height:20px;width:100%;background:#333;">
								<span style="color:#FFF;">Welcome <?php echo($_SESSION['username']); ?>!</span>
							</div>
							<div id="container">
								<div id="title">Navigation</div>
								<br>
								<table>
									<tr>
										<td>
											<a target="_BLANK" style="text-decoration:none;" href="https://github.com/XenoWarrior/PSO2ProxyRcon">
												<div id="button">
													RCON GitHub
												</div>
											</a>
										</td>
										<td>
											<a target="_BLANK" style="text-decoration:none;" href="https://github.com/CyberKitsune/PSO2Proxy">
												<div id="button">
													Proxy GitHub
												</div>
											</a>
										</td>
										<td>
											<a style="text-decoration:none;" href="?logout">
												<div id="button">
													Exit
												</div>
											</a>
										</td>
									</tr>
								</table>
							</div>
							<div id="container">
								<div id="title">Console Output</div>
								<br>
								<div style="text-align:left;overflow:auto;">
									<?php
										$exception = "<br>,<SegaID/PlayerID>,<value>,<Message>";
										if(isset($output['reason']))
										{
											if($output['reason']=="Command not specified.")
											{
												$string = "Type your command below.";
											}
											else
											{
												$string = $output['reason'];
												$string = str_replace("\n","<br>",$string);
											}
											echo("<pre>".htmlentities($output['reason'])."</pre>");
										}
										if(isset($output['output']))
										{
											$string = $output['output'];
											$string = str_replace("\n","<br>",$string);
											echo("<pre>".htmlentities($output['output'])."</pre>");
										}
									?>
								</div>
							</div>
							<div id="container">
								<div id="title">Execute Commands</div>
								<br>
								<form action="" method="post">
									<input style="width:10px;text-align:center;" name="command_prefix" type="text" value="<?php echo($settings['commandPrefix']); ?>" disabled/>
									<input name="command_text" type="text" placeholder="Enter command here..."/>
									<input style="width:200px;" name="command_args" type="text" placeholder="Enter command arguments here..."/>
									<input name="command_send" type="submit" value="Execute"/>
								</form>
							</div>
							<?php 
								if($settings['showInfo'])
								{ ?>
									<div id="container">
										<div id="title">Server Information</div>
										<div style="text-align:left;">
											<?php
												echo(
													"<table>".
														"<tr>".
															"<td>".
																"<b>Server Name</b>".
															"</td>".
															"<td>".
																"<input class='serverInfo' type='text' value='".$_SESSION['serverConfig']['name']."' />".
															"</td>".
														"</tr>".
														"<tr style='height:12px;'></tr>".
														"<tr>".
															"<td>".
																"<b>Host</b>".
															"</td>".
															"<td>".
																"<input class='serverInfo' type='text' value='".$settings['host']."' />".
															"</td>".
														"</tr>".
														"<tr>".
															"<td>".
																"<b>Port</b>".
															"</td>".
															"<td>".
																"<input class='serverInfo' type='text' value='".$settings['port']."' />".
															"</td>".
														"</tr>".
														"<tr style='height:12px;'></tr>".
														"<tr>".
															"<td>".
																"<b>Live Since</b>".
															"</td>".
															"<td>".
																"<input class='serverInfo' type='text' value='".date('d/m/y h:i A', $_SESSION['serverInfo']['upSince'])."' />".
															"</td>".
														"</tr>".
														"<tr>".
															"<td>".
																"<b>Connected Players</b>".
															"</td>".
															"<td>".
																"<input class='serverInfo' type='text' value='".$_SESSION['serverInfo']['playerCount']."' />".
															"</td>".
														"</tr>".
														"<tr style='height:12px;'></tr>".
														"<tr>".
															"<td>".
																"<b>Peak Players</b>".
															"</td>".
															"<td>".
																"<input class='serverInfo' type='text' value='".$_SESSION['serverInfo']['peakPlayers']."' />".
															"</td>".
														"</tr>".
														"<tr>".
															"<td>".
																"<b>Players Cached</b>".
															"</td>".
															"<td>".
																"<input class='serverInfo' type='text' value='".$_SESSION['serverInfo']['playersCached']."' />".
															"</td>".
														"</tr>".
														"<tr style='height:12px;'></tr>".
														"<tr>".
															"<td>".
																"<b>Blocks Cached</b>".
															"</td>".
															"<td>".
																"<input class='serverInfo' type='text' value='".$_SESSION['serverInfo']['blocksCached']."' />".
															"</td>".
														"</tr>".
													"</table>"
												);
											?>
										</div>
									</div>
									<?php
								}
							?>
						</center>
						<?php
					}
					else
					{
						header("Location: ./");
						unset($_SESSION);
					}
				}
			}
		?>
		<?php
			if(isset($announcement) AND $announcement!=="")
			{ ?>
				<center>
					<div class="announcement">
						<?php echo($announcement); ?>
					</div>
				</center>
				<?php
			}
		?>
	</body>
</html>
<?php
class Config
{
	const MYSQL_HOST = "127.0.0.1";
	const MYSQL_USER = ""; 
	const MYSQL_PASS = ""; 
	const MYSQL_DB = "";

	const SSH_HOST = "127.0.0.1";
	const SSH_USER = ""; 
	const SSH_PASS = ""; 
	
	const SSH_GIT_DIR = "/home/myserver/gamemodes"; 
	//same thing like above, but with a test server (null = no test server)
	//setting to null will disable the ssh part/the git pull to auto-deploy 
	const SSH_TEST_GIT_DIR = null; 

	const MASTER_BRANCH = "master"; //your main branch on git

	//the path on your git repository to the amx file of your gamemode (php will only pull that file on update)
	//setting to null will disable the ssh part/the git pull to auto-deploy 
	const GIT_AMX_PATH = null; 

	//basicly no need to change these, if you do - change the values in the include also so they match:
	const SERVER_UPDATE = 0;
	const SERVER_UPDATE_DEV = 1;
	const SERVER_ISSUE = 2;
	const SERVER_ISSUE_STATUSCHANGE = 3;
	const SERVER_TAG = 4;
}

$con = new mysqli(Config::MYSQL_HOST, Config::MYSQL_USER, Config::MYSQL_PASS, Config::MYSQL_DB);
if($con->connect_errno) die($con->connect_error);

function save(int $type, string $hash, string $date, string $message)
{
	global $con;

	if($type !== Config::SERVER_TAG)
	{
		$check = $con->query("SELECT Hash FROM Update_Data WHERE Hash = '$hash'");
		if($check->num_rows > 0 || empty($hash)) return;
	}

	$message = preg_replace('/\s+/', ' ', $message);
	$message = $con->real_escape_string($message);
	$con->query("INSERT INTO Update_Data (Hash, Message, Type, Date) VALUES ('$hash', '$message', $type, '$date')");
}
?>

<?php
require "config.php";

$payload = json_decode(file_get_contents('php://input'));

if(property_exists($payload, "push"))
{
	if($payload->push->changes[0]->new->name == Config::DEV_BRANCH)
		$type = Config::SERVER_UPDATE_DEV;
	else
		$type = Config::SERVER_UPDATE;
	
	foreach ($payload->push->changes[0]->commits as $commit) 
	{
		$hash = $commit->hash;
		$date = date("Y-m-d H:i:s", strtotime($commit->date));
		$message = trim($commit->message);

		save($type, $hash, $date, $message);
	}
}
else if(property_exists($payload, "changes"))
{
	if(!property_exists($payload->changes, "status")) exit;

	$hash = $payload->issue->id."/".$payload->changes->status->new."/".$payload->changes->status->old;
	$date = date("Y-m-d H:i:s",strtotime($payload->issue->updated_on));
	$message = $payload->issue->title;
	$type = Config::SERVER_ISSUE_STATUSCHANGE;
}
else if(property_exists($payload, "issue"))
{
	$hash = $payload->issue->id. "/" . $payload->issue->priority . "/" . $payload->issue->kind;
	$date = date( "Y-m-d H:i:s", strtotime($payload->issue->created_on));
	$message = $payload->issue->title;
	$type = Config::SERVER_ISSUE;
}

save($type, $hash, $date, $message);

$ssh = ssh2_connect(Config::SSH_HOST);
ssh2_auth_password($ssh, Config::SSH_USER, Config::SSH_PASS);

if($type === Config::SERVER_UPDATE)
{
	echo "Deploying to vps (live server) ...";
	$str = ssh2_exec($ssh,"cd ".Config::SSH_GIT_DIR. " && git pull origin master");
}
else if($type === Config::SERVER_UPDATE_DEV)
{
	echo "Deploying to vps (test server) ...";
	$str = ssh2_exec($ssh,"cd ".Config::SSH_TEST_GIT_DIR. " && git pull origin ". Config::DEV_BRANCH);
}
echo "Done!";

// $errstr = ssh2_fetch_stream($str, SSH2_STREAM_STDERR);
// stream_set_blocking($str, true);
// stream_set_blocking($errstr, true);
// echo "| Output: " . stream_get_contents($str);
// echo "| Error: " . stream_get_contents($errstr);
?>
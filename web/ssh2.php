<?php
$ssh = ssh2_connect(Config::SSH_HOST);
ssh2_auth_password($ssh, Config::SSH_USER, Config::SSH_PASS);

if($type === Config::SERVER_UPDATE)
{
	echo "Deploying to vps (live server) ...";
	$str = ssh2_exec($ssh,"cd ".Config::SSH_GIT_DIR. " && git pull origin master");
}
else if($type === Config::SERVER_UPDATE_DEV && !is_null(Config::SSH_TEST_GIT_DIR))
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
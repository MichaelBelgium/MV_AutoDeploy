<?php
require "config.php";

$pushed_to_branch = null;
$payload = json_decode(file_get_contents('php://input'));

if(property_exists($payload, "push"))
{
	if($payload->push->changes[0]->new->type == "tag")
	{
		$type = Config::SERVER_TAG;
		$hash = $payload->push->changes[0]->new->target->hash;
		$date = date("Y-m-d H:i:s", strtotime("now"));
		$message = $payload->push->changes[0]->new->name;
	}
	else
	{
		$pushed_to_branch = $payload->push->changes[0]->new->name;

		if($pushed_to_branch == Config::MASTER_BRANCH)
			$type = Config::SERVER_UPDATE;
		else
			$type = Config::SERVER_UPDATE_DEV;

		foreach ($payload->push->changes[0]->commits as $commit)
		{
			$hash = $commit->hash;
			$date = date("Y-m-d H:i:s", strtotime($commit->date));
			$message = trim($commit->message);

			save($type, $hash, $date, $message);
		}
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

include "ssh2.php";
?>
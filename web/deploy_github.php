<?php
require "config.php";

$pushed_to_branch = null;
$payload = json_decode(file_get_contents('php://input'));

if(property_exists($payload, "issue"))
{
	if($payload->action === "opened")
	{
		$hash = $payload->issue->number."/Unknown/";

		foreach ($payload->issue->labels as $label) 
			$hash .= $label->name. ", ";

		$hash = substr($hash, 0, -2);
		$date = date("Y-m-d H:i:s",strtotime($payload->issue->created_at));
		$message = $payload->issue->title;
		$type = Config::SERVER_ISSUE;
	}
	else if($payload->action === "closed")
	{
		$hash = $payload->issue->number."/".$payload->issue->state."/open";
		$date = date("Y-m-d H:i:s",strtotime($payload->issue->closed_at));
		$message = $payload->issue->title;
		$type = Config::SERVER_ISSUE_STATUSCHANGE;
	}
	else if($payload->action === "reopened")
	{
		$hash = $payload->issue->number."/".$payload->issue->state."/closed";
		$date = date("Y-m-d H:i:s",strtotime($payload->issue->updated_at));
		$message = $payload->issue->title;
		$type = Config::SERVER_ISSUE_STATUSCHANGE;
	}
}
else if(property_exists($payload, "head_commit"))
{
	$hash = $payload->head_commit->id;
	$date = date( "Y-m-d H:i:s", strtotime($payload->head_commit->timestamp));
	$message = $payload->head_commit->message;

	$pushed_to_branch = explode("/",$payload->ref)[2];
	
	if($pushed_to_branch == Config::MASTER_BRANCH)
		$type = Config::SERVER_UPDATE;
	else
		$type = Config::SERVER_UPDATE_DEV;
}

save($type, $hash, $date, $message);

include "ssh2.php";
?>
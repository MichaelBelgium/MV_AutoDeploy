<?php
    class Config
    {
        public static $USING_BITBUCKET = true;         //if false: using github

        public static $MYSQL_HOST = "127.0.0.1";
        public static $MYSQL_USER = "";
        public static $MYSQL_PASS = "";
        public static $MYSQL_DB = "";

        public static $SSH_HOST = "127.0.0.1";
        public static $SSH_USER = "";
        public static $SSH_PASS = "";
        public static $SSH_GIT_DIR = "/home/myserver/gamemodes";
        public static $SSH_TEST_GIT_DIR = "/home/mytestserver/gamemodes";

        public static $DEV_BRANCH = "dev";

        const SERVER_UPDATE = 0;
        const SERVER_UPDATE_DEV = 1;
        const SERVER_ISSUE = 2;
        const SERVER_ISSUE_STATUSCHANGE = 3;
    }

    $con = new mysqli(Config::$MYSQL_HOST, Config::$MYSQL_USER, Config::$MYSQL_PASS, Config::$MYSQL_DB);
    if($con->connect_errno) die($con->connect_error);

    $payload = json_decode(file_get_contents('php://input'));

    if(Config::$USING_BITBUCKET)
    {
        if(property_exists($payload, "push"))
        {
            $hash = $payload->push->changes[0]->new->target->hash;
            $date = date( "Y-m-d H:i:s", strtotime($payload->push->changes[0]->new->target->date));
            $message =  preg_replace('/\s+/', ' ', trim($payload->push->changes[0]->new->target->message));
            if($payload->push->changes[0]->new->name == Config::$DEV_BRANCH)
                $type = Config::SERVER_UPDATE_DEV;
            else
                $type = Config::SERVER_UPDATE;
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
    }
    else
    {
        $hash = $payload->head_commit->id;
        $date = date( "Y-m-d H:i:s", strtotime($payload->head_commit->timestamp));
        $message = $payload->head_commit->message;

        if(explode("/",$payload->ref)[2] == Config::$DEV_BRANCH)
            $type = Config::SERVER_UPDATE_DEV;
        else
            $type = Config::SERVER_UPDATE;
    }

    $check = $con->query("SELECT Hash FROM Update_Data WHERE Hash = '$hash'");
    if($check->num_rows > 0) exit;
    
    $message = $con->real_escape_string($message);
    $con->query("INSERT INTO Update_Data (Hash, Message, Type, Date) VALUES ('$hash', '$message', $type, '$date')"); 
    
    $ssh = ssh2_connect(Config::$SSH_HOST);
    ssh2_auth_password($ssh, Config::$SSH_USER, Config::$SSH_PASS);

    if($type === Config::SERVER_UPDATE)
    {
        echo "Deploying to vps (live server) ...";
        $str = ssh2_exec($ssh,"cd ".Config::$SSH_GIT_DIR. " && git pull origin master");
    }
    else if($type === Config::SERVER_UPDATE_DEV)
    {
        echo "Deploying to vps (test server) ...";
        $str = ssh2_exec($ssh,"cd ".Config::$SSH_TEST_GIT_DIR. " && git pull origin ". Config::$DEV_BRANCH);
    }
    echo "Done!";
    
    // $errstr = ssh2_fetch_stream($str, SSH2_STREAM_STDERR);
    // stream_set_blocking($str, true);
    // stream_set_blocking($errstr, true);
    // echo "| Output: " . stream_get_contents($str);
    // echo "| Error: " . stream_get_contents($errstr);
?>
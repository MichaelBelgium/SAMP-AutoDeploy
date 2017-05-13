<?php
    class Config
    {
        public static $MYSQL_HOST = "127.0.0.1";
        public static $MYSQL_USER = "";
        public static $MYSQL_PASS = "";
        public static $MYSQL_DB = "";

        public static $SSH_HOST = "127.0.0.1";
        public static $SSH_USER = "";
        public static $SSH_PASS = "";
        public static $SSH_GIT_DIR = "";
    }

    $con = new mysqli(Config::$MYSQL_HOST, Config::$MYSQL_USER, Config::$MYSQL_PASS, Config::$MYSQL_DB);
    if($con->connect_errno) die($con->connect_error);

    $payload = json_decode(file_get_contents('php://input'));

    $hash = $payload->push->changes[0]->new->target->hash;
    $date = date( "Y-m-d H:i:s", strtotime($payload->push->changes[0]->new->target->date));
    $message =  preg_replace('/\s+/', ' ', trim($payload->push->changes[0]->new->target->message));
    $branch = $payload->push->changes[0]->new->name;

    $check = $con->query("SELECT Hash FROM Update_Data WHERE Hash = '$hash'");
    if($check->num_rows > 0) exit;


    $message = $con->real_escape_string($message);
    $con->query("INSERT INTO Update_Data (Hash, Message, Branch, Date) VALUES ('$hash', '$message', '$branch', '$date')");

    if($branch === "master")
    {
        echo "Deploying to vps ...";

        $ssh = ssh2_connect(Config::$SSH_HOST);
        ssh2_auth_password($ssh, Config::$SSH_USER, Config::$SSH_PASS);

        $str = ssh2_exec($ssh,"cd ".Config::$SSH_GIT_DIR. " && git pull origin master");

        // $errstr = ssh2_fetch_stream($str, SSH2_STREAM_STDERR);
        // stream_set_blocking($str, true);
        // stream_set_blocking($errstr, true);
        // echo "| Output: " . stream_get_contents($str);
        // echo "| Error: " . stream_get_contents($errstr);

        echo "Done!";
    }
?>
<?php
include("env.php");

class DbConnent
{
    public function __construct($dbuser = "", $dbpass = "", $dbname = "")
    {
        $this->host = "localhost";
        $this->user = $dbuser;
        $this->pass = $dbpass;
        $this->name = $dbname;
    }
    public function OpenCon()
    {
        $conn = new mysqli($this->host, $this->user, $this->pass, $this->name) or die("Connect failed: %s\n". $conn -> error);
        return $conn;
    }
    public function CloseCon($conn)
    {
        $conn -> close();
    }
}

// $db = new DbConnent($env_db_account, $env_db_password, $env_db_name);


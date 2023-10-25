<?php

$api = array(
    "message" => "Hello World"
);

header('Content-Type: application/json');
echo( json_encode($api) );

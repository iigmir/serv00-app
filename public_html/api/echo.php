<?php
$accepted_domain = "https://iigmir.github.io";
header( "Content-Type: application/json" );
header( "Access-Control-Allow-Origin: $accepted_domain" );
echo( json_encode( array( "param" => isset($_GET["param"]) ? $_GET["param"] : "" ) ) );


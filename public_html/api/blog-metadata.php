<?php

class BlogMetadata
{
    public function __construct()
    {
        $id = isset($_GET["id"]) ? $_GET["id"] : null;
        $this->id = $id;
    }
    private function message(): string
    {
        if( $this->id == null )
        {
            return "Please provide API ID";
        }
        if( $this->id == "404" )
        {
            return "File not found";
        }
        return "Success";
    }
    public function http_code(): int
    {
        if( $this->id == null )
        {
            return 400;
        }
        if( $this->id == "404" )
        {
            return 404;
        }
        return 200;
    }
    public function result(): array
    {
        return array(
            "message" => $this->message(),
            "id" => $this->id,
        );
    }
}

$api = new BlogMetadata();

http_response_code( $api->http_code() );
header( "Content-Type: application/json" );
echo( json_encode($api->result()) );

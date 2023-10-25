<?php

class BlogMetadata
{
    protected $id;
    private $apiurl = "https://api.github.com/repos/iigmir/blog-source/contents/info-files/articles.json";
    public function __construct($id = null)
    {
        $this->id = $id;
    }
}

class BlogData extends BlogMetadata
{
    public function __construct()
    {
        $id = isset($_GET["id"]) ? $_GET["id"] : null;
        // $this->id = $id;
        $this->metadata = new BlogMetadata($id);
    }
    private function message(): string
    {
        if( $this->metadata->id == null )
        {
            return "Please provide API ID";
        }
        if( $this->metadata->id == "404" )
        {
            return "File not found";
        }
        return "Success";
    }
    public function http_code(): int
    {
        if( $this->metadata->id == null )
        {
            return 400;
        }
        if( $this->metadata->id == "404" )
        {
            return 404;
        }
        return 200;
    }
    public function result(): array
    {
        return array(
            "message" => $this->message(),
            "id" => $this->metadata->id,
        );
    }
}

$api = new BlogData();

http_response_code( $api->http_code() );
header( "Content-Type: application/json" );
echo( json_encode($api->result()) );

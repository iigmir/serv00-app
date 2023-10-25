<?php

class BlogMetadata
{
    protected $id;
    protected $data = null;
    private $apiurl = "https://api.github.com/repos/iigmir/blog-source/contents/info-files/articles.json";
    /**
     * @see <https://docs.github.com/en/rest/overview/resources-in-the-rest-api?apiVersion=2022-11-28#user-agent-required>
     */
    private $useragent = "Mozilla/5.0 iigmir-serv00-app/1.0.0";
    public function __construct($id = null)
    {
        $this->id = $id;
    }
    public function fetch_api()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiurl);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->useragent);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $this->data = curl_exec($ch);
        curl_close($ch);
    }
    public function api_data()
    {
        $response = json_decode($this->data);
        if( isset($response) )
        {
            return $response->content;
        }
        return null;
    }
}

class BlogData extends BlogMetadata
{
    public function __construct()
    {
        $id = isset($_GET["id"]) ? $_GET["id"] : null;
        $this->metadata = new BlogMetadata($id);
        $this->metadata->fetch_api();
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
            "data" => $this->metadata->api_data(),
        );
    }
}

$api = new BlogData();

http_response_code( $api->http_code() );
header( "Content-Type: application/json" );
echo( json_encode($api->result()) );

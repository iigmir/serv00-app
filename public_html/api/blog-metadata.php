<?php

class BlogMetadata
{
    protected $id = "";
    protected $data = "";
    protected $date_data = "";
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
        $apiurl = "https://api.github.com/repos/iigmir/blog-source/contents/info-files/articles.json";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiurl);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->useragent);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $this->data = curl_exec($ch);
        curl_close($ch);
        $this->request_date();
    }
    private function api_data()
    {
        $response = json_decode($this->data);
        if( isset($response) )
        {
            return base64_decode($response->content);
        }
        return "";
    }
    private function item_data()
    {
        $data = json_decode($this->api_data(), true);
        if( $data )
        {
            $result = array_filter( $data, function($item)
            {
                return $item["id"] == $this->id;
            });
            return reset( $result );
        }
        return array();
    }
    private function date_missed()
    {   // https://stackoverflow.com/a/59687793/7162445
        $data = $this->item_data()["created_at"] ?? null;
        return isset($data) == false || isset($data) == false;
    }
    private function request_date()
    {
        /**
         * Only request if we don't have date
         */
        if( $this->date_missed() )
        {
            $api_id = str_pad($this->id, 3, "0", STR_PAD_LEFT);
            $apiurl = "https://api.github.com/repos/iigmir/blog-source/commits?path=/articles/" .$api_id. ".md";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiurl);
            curl_setopt($ch, CURLOPT_USERAGENT, $this->useragent);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $this->date_data = curl_exec($ch);
            curl_close($ch);
            $this->date_data = $this->set_date_data($this->date_data);
        }
    }
    private function set_date_data($input)
    {
        $data = json_decode($input, true);
        usort($data, function ($a, $b)
        {
            $dateA = new DateTime($a["commit"]["committer"]["date"]);
            $dateB = new DateTime($b["commit"]["committer"]["date"]);
            return $dateA <=> $dateB;
        });
        return $data;
    }
    /**
     * @todo Request "created_at" and "updated_at" by commit date if it doesn't exist.
     */
    private function get_date($input)
    {
        $data = $input;
        if( isset($this->date_data[0]) == false )
        {   // We can't help
            return $data;
        }
        if( isset($data["created_at"]) == false )
        {
            $index = 0;
            $data["created_at"] = $this->date_data[$index]["commit"]["committer"]["date"];
        }
        if( isset($data["updated_at"]) == false )
        {
            $index = count($this->date_data) - 1;
            $data["updated_at"] = $this->date_data[$index]["commit"]["committer"]["date"];
        }
        return $data;
    }
    public function result()
    {
        $data = $this->get_date($this->item_data());
        return $data;
    }
}

class BlogData extends BlogMetadata
{
    public function __construct()
    {
        $id = isset($_GET["id"]) ? $_GET["id"] : null;
        $this->metadata = new BlogMetadata($id);
        if( $id )
        {
            $this->metadata->fetch_api();
        }
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
            "data" => $this->metadata->result(),
        );
    }
}

$api = new BlogData();

http_response_code( $api->http_code() );
header( "Content-Type: application/json" );
header( "Access-Control-Allow-Origin: *" );
echo( json_encode($api->result()) );

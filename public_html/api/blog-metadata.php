<?php

function get_my_articles()
{
    return "https://api.github.com/repos/iigmir/blog-source/contents/info-files/articles.json";
}

function get_my_article_by_id($id = "404")
{
    return "https://api.github.com/repos/iigmir/blog-source/commits?path=/articles/" .$id. ".md";
}

/**
 * TODO: [Rate limiting](https://docs.github.com/en/rest/overview/resources-in-the-rest-api?apiVersion=2022-11-28#rate-limiting)
 */
class BlogMetadata
{
    public $id = "";
    protected $data = "";
    protected $date_data = "";
    /**
     * @see <https://docs.github.com/en/rest/overview/resources-in-the-rest-api?apiVersion=2022-11-28#user-agent-required>
     */
    private $useragent = "Mozilla/5.0 iigmir-serv00-app/1.0.0";
    public function __construct($id = "")
    {
        $this->id = $id;
    }
    private function no_id_given(): bool
    {
        return $this->id == "" || $this->id == null || $this->id == "404";
    }
    public function main()
    {
        if( $this->id && $this->no_id_given() == false )
        {
            $this->fetch_api();
            $this->request_date();
        }
    }
    private function fetch_api()
    {
        $apiurl = get_my_articles();
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiurl);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->useragent);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $this->data = curl_exec($ch);
        curl_close($ch);
    }
    private function api_data()
    {
        $response = json_decode($this->data);
        if( isset($response) && isset($response->content) )
        {
            return base64_decode($response->content);
        }
        return "";
    }
    private function item_data()
    {
        $data = json_decode($this->api_data(), true);
        if ( isset($data["message"]) && str_contains( $data["message"], "API rate limit exceeded"))
        {
            return;
        }
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
        return isset($data) == false;
    }
    private function request_date()
    {
        /**
         * Only request if we don't have date
         */
        if( $this->date_missed() )
        {
            $api_id = str_pad($this->id, 3, "0", STR_PAD_LEFT);
            $apiurl = get_my_article_by_id($api_id);
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
        if ( isset($data["message"]) && str_contains( $data["message"], "API rate limit exceeded"))
        {
            return;
        }
        usort($data, function ($a, $b)
        {
            $dateA = new DateTime($a["commit"]["committer"]["date"]);
            $dateB = new DateTime($b["commit"]["committer"]["date"]);
            return $dateA <=> $dateB;
        });
        return $data;
    }
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

class BlogData
{
    public function __construct()
    {
        $id = isset($_GET["id"]) ? $_GET["id"] : "";
        $this->metadata = new BlogMetadata($id);
        $this->metadata->main();
    }
    private function no_id_given(): bool
    {
        return $this->metadata->id == null;
    }
    private function file_not_found(): bool
    {
        return $this->metadata->id == "404" || $this->metadata->result() == false;
    }
    private function api_limit_exceeded(): bool
    {
        $data = $this->metadata->result();
        return isset($data["message"]) && str_contains( $data["message"], "API rate limit exceeded");
    }
    private function message(): string
    {
        if( $this->no_id_given() )
        {
            return "Please provide API ID";
        }
        if( $this->file_not_found() )
        {
            return "File not found";
        }
        return "Success";
    }
    public function http_code(): int
    {
        if( $this->no_id_given() )
        {
            return 400;
        }
        if( $this->file_not_found() )
        {
            return 404;
        }
        if( $this->api_limit_exceeded() )
        {
            return 503;
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

$accepted_domain = "https://iigmir.github.io";
if( isset($_GET["id"]) ) {
    $api = new BlogData();
    http_response_code( $api->http_code() );
    header( "Content-Type: application/json" );
    header( "Access-Control-Allow-Origin: $accepted_domain" );
    echo( json_encode($api->result()) );
} else {
    http_response_code( 400 );
    header( "Content-Type: application/json" );
    header( "Access-Control-Allow-Origin: $accepted_domain" );
    echo( json_encode( array(
        "message" => "Please provide API ID",
        "id" => "",
        "data" => array(),
    ) ) );
}

<?php

require "../vendor/autoload.php";
use GuzzleHttp\Client;

include("includes/config.php");

$USER_AGENT = "PlainFeeder/0.1.0 (https://plainfeeder.com/contact/)";

$pdo_conn_string = "mysql:host=$db_host;dbname=$db_name;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

function get_feed($feed_url, $last_modified = "empty", $etag = "empty") {
    /*
     * Function to retrieve a feed from a URL
     */

    global $USER_AGENT;

    $client = new Client();
    $headers = array(
        "User-Agent" => $USER_AGENT
    );

    if ($last_modified !== "empty" && $etag !== "empty") {
        $headers["If-Modified-Since"] = $last_modified;
        $headers["If-None-Match"] = $etag;
    } elseif ($last_modified === "empty" && $etag !== "empty") {
        $headers["If-None-Match"] = $etag;
    } elseif ($last_modified !== "empty" && $etag === "empty") {
        $headers["If-Modified-Since"] = $last_modified;
    }

    $headers = array(
        "headers" => $headers
    );

    $response = $client->request(
        "GET",
        $feed_url,
        [
            $headers,
            "decode_content" => "gzip"
        ]
    );

    return $response;
}

function get_feed_db($feed_url) {
    // Checks if we already have the feed in the database
    // Returns the feed ID if we do, False if we don't

    global $pdo_conn_string;
    global $db_user;
    global $db_pass;
    global $options;

    $pdo = new PDO($pdo_conn_string, $db_user, $db_pass, $options);

    $query = "SELECT id FROM feeds WHERE feed_url = :feed_url";
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        "feed_url" => $feed_url
    ]);

    $feed = $stmt->rowCount();

    if ($feed > 0) {
        $id = $stmt->fetch()["id"];
        $stmt = NULL;
        $pdo = NULL;
        return $id;
    } else {
        $stmt = NULL;
        $pdo = NULL;
        return False;
    }
}

# Retrieve the next feed to process from the queue
$pdo = new PDO($pdo_conn_string, $db_user, $db_pass, $options);

$query = "SELECT id, feed_url FROM queue ORDER BY created_at LIMIT 1";
$stmt = $pdo->prepare($query);
$stmt->execute();

$feed= $stmt->fetch();
$queue_id = $feed["id"];
$feed_url = $feed["feed_url"];

# Retrieve the feed contents
$feed_response = get_feed($feed_url);

# Capture the Response Headers and Body
$response_headers = $feed_response->getHeaders();
$response_body = $feed_response->getBody()->getContents();

$last_modified = NULL;
$etag = NULL;
$cache_control = NULL;

if ($feed_response->getHeader('last-modified') && $feed_response->getHeader('last-modified')[0]) {
    $last_modified = $feed_response->getHeader('last-modified')[0];
}
if ($feed_response->getHeader('etag') && $feed_response->getHeader('etag')[0]) {
    $etag = $feed_response->getHeader('etag')[0];
}
if ($feed_response->getHeader('cache-control') && $feed_response->getHeader('cache-control')[0]) {
    $cache_control = $feed_response->getHeader('cache-control')[0];
}

# Insert feed metadata into the database
$query = "INSERT INTO feeds (feed_url, last_modified, etag, cache_control) VALUES (:feed_url, :last_modified, :etag, :cache_control)";
$stmt = $pdo->prepare($query);
$stmt->execute([
    "feed_url" => $feed_url,
    "last_modified" => $last_modified,
    "etag" => $etag,
    "cache_control" => $cache_control
]);
# Get the newly created feed id
$feed_id = $pdo->lastInsertId();

# Parse the feed data
$feed_contents = simplexml_load_string($response_body);
#$feed_contents = new SimpleXMLElement($response_body);

$feed_title = $feed_contents->channel->title;
$feed_description = $feed_contents->channel->description;
$feed_link = $feed_contents->channel->link;
$feed_image = $feed_contents->channel->image->url;

# HTML Purifier for sanitizing HTML content from the feed
$config = HTMLPurifier_Config::createDefault();

$config->set('Core.Encoding', 'UTF-8');
$config->set('HTML.Doctype', 'HTML 4.01 Transitional');

$purifier = new HTMLPurifier($config);

# Loop through each item and add relevant data to the feed_posts table
foreach ($feed_contents->channel->item as $post) {
    $post_title = $post->title;
    $post_date = $post->pubDate;
    $post_link = $post->link;
    $post_body = $post->children("content", true);

    $query = "INSERT INTO feed_posts (feed_id, post_title, post_date, post_link, post_body) VALUES (:feed_id, :post_title, :post_date, :post_link, :post_body)";

    $stmt = $pdo->prepare($query);
    $stmt->execute([
        "feed_id" => $feed_id,
        "post_title" => $post_title,
        "post_date" => $post_date,
        "post_link" => $post_link,
        "post_body" => $purifier->purify($post_body)
    ]);
}


# Remove the feed from the queue
$query = "DELETE FROM queue WHERE id = :id";
$stmt = $pdo->prepare($query);
$stmt->execute([
    "id" => $queue_id
]);

$stmt = NULL;
$pdo = NULL;

echo "Feed " . $feed_url . " successfully added to the database.";

?>
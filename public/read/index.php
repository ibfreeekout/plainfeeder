<?php

include("../../includes/config.php");

$pdo_conn_string = "mysql:host=$db_host;dbname=$db_name;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

$pdo = new PDO($pdo_conn_string, $db_user, $db_pass, $options);

if (!isset($_GET["feed"])) {
    # Get the feed IDs and URLs
    $query = "SELECT DISTINCT feeds.id, feeds.feed_url FROM feeds INNER JOIN feed_posts ON feeds.id = feed_posts.feed_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute();

    $feeds = $stmt->fetchAll();
} elseif (isset($_GET["feed"]) && !isset($_GET["post"])) {
    # Get the feed posts by feed ID
    $feed_id = $_GET["feed"];

    # Get the count of posts so we know how many pages to expect
    $query = "SELECT COUNT(*) FROM feed_posts WHERE feed_id = :feed_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        "feed_id" => $feed_id
    ]);
    $post_count = $stmt->fetchColumn();

    $num_pages = ceil($post_count / 10);

    if (isset($_GET["page"]) && is_numeric($_GET["page"])) {
        $page = (int)$_GET["page"];
        $offset = ($page - 1) * 10;
    } else {
        # Redirect the user to the first page if they try to access the feed without a valid page number
        header("Location: /read/?feed=" . $feed_id . "&page=1");
        exit();
    }

    # Redirect the user to the last page if they try to access a page that doesn't exist
    if ($page > $num_pages) {
        header("Location: /read/?feed=" . $feed_id . "&page=" . $num_pages);
        exit();
    }

    $query = "SELECT * FROM feed_posts WHERE feed_id = :feed_id ORDER BY id DESC LIMIT 10 OFFSET :offset";
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        "feed_id" => $feed_id,
        "offset" => $offset
    ]);

    $feed_posts = $stmt->fetchAll();
} else {
    # Get the feed post by post ID
    $post_id = $_GET["post"];
    $query = "SELECT * FROM feed_posts WHERE id = :post_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        "post_id" => $post_id
    ]);

    $feed_post = $stmt->fetch();
}

?>
<html>
    <head>
        <?php include("../../includes/head.php"); ?>
    </head>
    <body>
        <?php
        if (!isset($_GET["feed"])) {
            echo "<h1>Feed Selector</h1>";
            foreach ($feeds as $feed) {
                echo "<a href='?feed=" . $feed["id"] . "&page=1'>" . $feed["feed_url"] . "</a><br />";
            }
            exit();
        } elseif (isset($_GET["feed"]) && !isset($_GET["post"])) {
            echo "<h1>Feed Post Selector</h1>";
            foreach ($feed_posts as $feed_post) {
                echo "<a href='?feed=" . $feed_post["feed_id"] . "&post=" . $feed_post["id"] . "'>" . $feed_post["post_title"] . "</a><br />";
            }

            # Only add page links of there are more than 10 posts
            if ($post_count > 10) {
                if ($page > 1 && $page < $num_pages) {
                    echo "<a href='/read/?feed=" . $feed_id . "&page=" . $page - 1 . "'>Previous Page</a>";
                    echo "<a href='/read/?feed=" . $feed_id . "&page=" . $page + 1 . "'>Next Page</a>";
                } elseif ($page == 1) {
                    echo "<a href='/read/?feed=" . $feed_id . "&page=" . $page + 1 . "'>Next Page</a>";
                } elseif ($page == $num_pages) {
                    echo "<a href='/read/?feed=" . $feed_id . "&page=" . $page - 1 . "'>Previous Page</a>";
                }
            }
            echo "<a href='/read/'>Back to Feed Selector</a>";
            exit();
        } else {
            echo "<h1>Feed Post</h1>";
            echo "<a href='/read/?feed=" . $feed_post["feed_id"] . "'>Back to Feed</a>";
            echo "<article>";
            echo "<h2>" . $feed_post["post_title"] . "</h2>";
            echo "<p>" . $feed_post["post_body"] . "</p>";
            echo "</article>";
            echo "<a href='" . $feed_post["post_link"] . "'>Read More</a>";
            echo "<a href='/read/?feed=" . $feed_post["feed_id"] . "'>Back to Feed</a>";
            exit();
        }
        ?>
    </body>
</html>
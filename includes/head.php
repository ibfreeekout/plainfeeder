<?php

# Add default title if one doesn't exist on the container page
if (!isset($title)) {
    $title = "Plain Feeder";
}

# Get the MD5 hash of the styles file to generate a unique cache busting query string
$fingerprint = md5_file("/var/www/plainfeeder.com/public/assets/css/simple.min.css");
$css_link = "/assets/css/simple.min.css?v=" . $fingerprint;

?>
<meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width" />
        <title><?php echo $title; ?></title>
        <link rel="stylesheet" href="<?php echo $css_link; ?>" />
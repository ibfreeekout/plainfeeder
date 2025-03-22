<?php

# Add default title if one doesn't exist on the container page
if (!isset($title)) {
    $title = "Plain Feeder";
}

?>
<meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width" />
        <title><?php echo $title; ?></title>
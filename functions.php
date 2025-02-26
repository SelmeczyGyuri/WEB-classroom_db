<?php
function displayMessage($message, $type = 'info') {
    echo "<div class='message $type'>$message</div>";
    if (php_sapi_name() !== 'cli') { 
        ob_implicit_flush(true);
        ob_end_flush();
    }
}
?>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SERVER['REQUEST_URI'] === '/process') {
    $body = file_get_contents('php://input');
    file_put_contents('requests.log', $body . PHP_EOL, FILE_APPEND);
    echo "OK";
} else {
    http_response_code(404);
    echo "Not Found";
}
?>

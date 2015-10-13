<?php

if (array_key_exists('foo', $_POST) && $_POST['foo'] === 'bar') {
    http_response_code(200);
    echo 'ok';
} else {
    http_response_code(204);
}
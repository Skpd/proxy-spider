<?php

if (array_key_exists('foo', $_POST) && $_POST['foo'] === 'bar') {
    echo 'ok';
} else {
    echo 'bad';
}
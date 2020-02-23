<?php
echo 'POST:<br />';
var_dump($_POST);
echo 'FILES:<br />';
var_dump($_FILES);
$request = "{$_SERVER['REQUEST_METHOD']} {$_SERVER['REQUEST_URI']} {$_SERVER['SERVER_PROTOCOL']}\r\n";

foreach (getallheaders() as $name => $value) {
    $request .= "$name: $value\r\n";
}

$request .= "\r\n" . file_get_contents('php://input');

echo $request;

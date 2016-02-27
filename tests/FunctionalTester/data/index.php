<?php

header("Content-Type: text/html");
header('Location: http://www.example.com/');

session_start();

$response = [
    'session' => $_SESSION,
    'get' => $_GET,
    'post' => $_POST,
];

echo json_encode($response, true);

exit;
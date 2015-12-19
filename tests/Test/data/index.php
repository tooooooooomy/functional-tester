<?php

header("Content-Type: text/html");
header('Location: http://www.example.com/');

session_start();

$response = [];

if (isset($_SESSION)) $response['session'] = $_SESSION;
if (isset($_POST))    $response['get']     = $_GET;
if (isset($_GET))     $response['post']    = $_POST;
echo json_encode($response, true);

exit;
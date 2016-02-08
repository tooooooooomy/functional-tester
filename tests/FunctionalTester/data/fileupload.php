<?php

header("Content-Type: text/html");
header('Location: http://www.example.com/');

$response = [];

if (isset($_FILES)) $response['files'] = $_FILES;
if (isset($_POST))  $response['post']  = $_POST;
echo json_encode($response, true);

exit;
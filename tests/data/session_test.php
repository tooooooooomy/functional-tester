<?php

$response = [];

session_name('test1');
session_start();
$response['test1'] = $_SESSION;
session_destroy();
session_name('test2');
session_start();
$response['test2'] = $_SESSION;

echo json_encode($response, true);

exit;
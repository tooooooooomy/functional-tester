<?php
ini_set('always_populate_raw_post_data', -1);

$get = $_GET;

echo file_get_contents("php://input");

exit;
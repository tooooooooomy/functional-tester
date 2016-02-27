<?php
echo json_encode([
    'GET'   => $_GET,
    'POST'  => $_POST,
    'FILES' => $_FILES,
]);

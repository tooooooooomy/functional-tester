<?php

parse_str(file_get_contents("php://input"), $params);

echo json_encode([
    'method' => $_SERVER['REQUEST_METHOD'],
    'parameters' => $params,
]);

exit;
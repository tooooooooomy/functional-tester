<?php
header("Content-Type: text/html");
header('Location: http://www.example.com/');

echo file_get_contents('php://input');

exit;
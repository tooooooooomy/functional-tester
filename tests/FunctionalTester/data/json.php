<?php
ini_set('always_populate_raw_post_data', -1);

echo file_get_contents('php://input');

exit;
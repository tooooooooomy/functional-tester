<?php
    session_start();

    if (!isset($_SESSION['visited_count'])) {
        $_SESSION['visited_count'] = 0;
    }
    $_SESSION['visited_count']++;

    $visited_count = $_SESSION['visited_count'];
?>

<?php echo json_encode([
    'session_id'    => $_COOKIE['PHPSESSID'],
    'visited_count' => $visited_count
]); ?>

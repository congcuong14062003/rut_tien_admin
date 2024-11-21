<?php
session_start();

if (isset($_SESSION['token_admin'])) {
    echo json_encode([
        'status' => 'success',
        'token' => $_SESSION['token_admin']
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Token không tồn tại'
    ]);
}
?>

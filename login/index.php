<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: /home");
    exit();
}

?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <title>Đăng Nhập</title>
</head>

<body>
    <div class="container">
        <h1>Đăng Nhập</h1>
        <form method="post" action="login_action.php">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
            <input type="submit" value="Đăng Nhập">
        </form>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
    $(document).ready(function() {
        <?php
            if (isset($_SESSION['error_login'])) {
                echo "toastr.error('" . $_SESSION['error_login'] . "');";
                unset($_SESSION['error_login']);
            }
            if (isset($_SESSION['success_register'])) {
                echo "toastr.success('" . $_SESSION['success_register'] . "');";
                unset($_SESSION['success_register']);
            }
            ?>
    });
    </script>

</body>

</html>

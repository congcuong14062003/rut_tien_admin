<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: /admin/home");
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
    <div class="container container_login">
        <h1>Đăng Nhập</h1>
        <form id="loginForm">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
            <input type="submit" value="Đăng Nhập">
        </form>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script type="module">
    import { getTokenFirebase } from '../component/getToken.js'; // Đảm bảo đường dẫn đúng
    $(document).ready(function() {
        $('#loginForm').submit(function(event) {
            event.preventDefault(); // Ngăn chặn hành vi gửi form mặc định
            var formData = $(this).serialize(); // Lấy dữ liệu từ form
            $.ajax({
                type: 'POST',
                url: 'login_action.php',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        toastr.success('Đăng nhập thành công');
                        getTokenFirebase().then(function(token) {
                            console.log('Token:', token);
                            // Gửi token lên server để lưu vào SESSION
                            $.ajax({
                                type: 'POST',
                                url: 'save_token.php',
                                data: { token: token },
                                success: function() {
                                    window.location.href = '/index.php'; // Chuyển hướng về trang index.php
                                },
                                error: function(xhr, status, error) {
                                    toastr.error('Đã xảy ra lỗi trong quá trình lưu token.');
                                }
                            });
                        }).catch(function(error) {
                            console.error('Error getting Firebase token:', error);
                        });
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function(xhr, status, error) {
                    toastr.error('Đã xảy ra lỗi trong quá trình đăng nhập.');
                }
            });
        });
    });
    </script>
</body>

</html>

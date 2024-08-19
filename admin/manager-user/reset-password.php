<?php
include '../../component/header.php';
?>
<?php
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // Nếu không phải admin, chuyển hướng đến trang thông báo không có quyền
    header("Location: /no-permission");
    exit();
}
?>
<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'];
    $new_password = $_POST['new_password'];

    $errors = [];

    // Validate new password
    if (strlen($new_password) < 6 || !preg_match('/[A-Za-z]/', $new_password) || !preg_match('/[0-9]/', $new_password) || !preg_match('/[A-Z]/', $new_password)) {
        $errors[] = 'Password phải có ít nhất 6 ký tự, bao gồm chữ cái, số và ít nhất một ký tự viết hoa.';
    }

    if (empty($errors)) {
        // Kết nối cơ sở dữ liệu và cập nhật mật khẩu mới
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $new_password, $user_id);

        if ($stmt->execute()) {
            $success_message = "Đặt lại mật khẩu thành công.";
            $_SESSION['success_reset_password'] = $success_message;
            header('Location: /admin/manager-user');
        } else {
            $error_message = "Đã xảy ra lỗi khi đặt lại mật khẩu.";
            $_SESSION['error_reset_password'] = $error_message;
        }

        $stmt->close();
        $conn->close();
    } else {
        $_SESSION['error_reset_password'] = implode('<br>', $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../styles/index.css">
    <link rel="stylesheet" href="../../component/header.css">
    <link rel="stylesheet" href="../../component/sidebar.css">
    <link rel="stylesheet" href="./add-user.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <title>Đặt lại mật khẩu</title>
</head>

<body>
    <div class="container_boby">
        <?php include '../../component/sidebar.php'; ?>
        <div class="content_right container_form">
            <div class="container">
                <h1 class="title">Đặt lại mật khẩu</h1>
                <form id="reset-pass-user" method="post" action="">
                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($_GET['user_id']); ?>">
                    <label for="new_password">Mật khẩu mới:</label>
                    <input type="password" id="new_password" name="new_password" required>
                    <input type="submit" value="Đặt lại mật khẩu">
                </form>

                <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
                <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
                <script>
                    $(document).ready(function () {
                        <?php if (isset($_SESSION['error_reset_password'])): ?>
                            toastr.error("<?php echo $_SESSION['error_reset_password']; ?>");
                            <?php unset($_SESSION['error_reset_password']); ?>
                        <?php endif; ?>

                        function validatePassword() {
                            var password = $('#new_password').val();
                            var pattern = /^(?=.*[A-Za-z])(?=.*\d)(?=.*[A-Z]).{6,}$/;
                            if (!pattern.test(password)) {
                                toastr.error('Password phải có ít nhất 6 ký tự, bao gồm chữ cái, số và ít nhất một ký tự viết hoa.');
                                return false;
                            }
                            return true;
                        }

                        function validateForm() {
                            if (!validatePassword()) return false;
                            return true;
                        }

                        $('#reset-pass-user').on('submit', function (e) {
                            if (!validateForm()) {
                                e.preventDefault();
                            }
                        });

                        $('#new_password').on('change', function () {
                            validatePassword();
                        });
                    });
                </script>
            </div>
        </div>
    </div>
</body>

</html>

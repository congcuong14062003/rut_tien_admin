<?php
include '../../component/header.php';
?>
<?php
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // Nếu không phải admin, chuyển hướng đến trang thông báo không có quyền
    header("Location: /no-permission");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $email = $_POST['email'];

    $errors = [];

    // Validate username
    if (!preg_match('/^[A-Za-z0-9]{8,}$/', $username)) {
        $errors[] = 'Username phải có ít nhất 8 ký tự và chỉ chứa chữ cái và số.';
    }

    // Validate password
    if (strlen($password) < 6 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password phải có ít nhất 6 ký tự, bao gồm chữ cái, số và ít nhất một ký tự viết hoa.';
    }

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email không hợp lệ.';
    }

    if (empty($errors)) {
        // Kiểm tra xem tên người dùng hoặc email đã tồn tại chưa
        $stmt = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND role = 'admin'");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errors[] = 'Tài khoản hoặc email đã tồn tại.';
        }

        $stmt->close();

        if (empty($errors)) {
            // Nếu không có lỗi, thực hiện thêm tài khoản mới
            $stmt = $conn->prepare("INSERT INTO users (username, password, email, role, create_at) VALUES (?, ?, ?, ?, NOW())");
            $role_user = 'admin';
            $stmt->bind_param("ssss", $username, $password, $email, $role_user);

            if ($stmt->execute()) {
                $success_message = "Thêm tài khoản thành công.";
                $_SESSION['success_add_user'] = $success_message;
                header("Location: /admin/manager-user");
                exit();
            } else {
                $_SESSION['error_add_user'] = "Đã xảy ra lỗi khi thêm tài khoản.";
            }

            $stmt->close();
        } else {
            $_SESSION['error_add_user'] = implode('<br>', $errors);
        }
    } else {
        $_SESSION['error_add_user'] = implode('<br>', $errors);
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
    <title>Thêm tài khoản</title>
</head>
<body>
    <div class="container_boby">
        <?php include '../../component/sidebar.php'; ?>
        <div class="content_right container_form">
            <div class="container">
                <h1 class="title">Thêm tài khoản</h1>
                <form id="add-user-form" method="post" action="">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required>
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                    <input type="submit" name="add_user" value="Xác Nhận">
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
    $(document).ready(function() {
        <?php if (isset($_SESSION['error_add_user'])) : ?>
        toastr.error("<?php echo $_SESSION['error_add_user']; ?>");
        <?php unset($_SESSION['error_add_user']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['success_add_user'])) : ?>
        toastr.success("<?php echo $_SESSION['success_add_user']; ?>");
        <?php unset($_SESSION['success_add_user']); ?>
        <?php endif; ?>

        function validateUsername() {
            var username = $('#username').val();
            var pattern = /^[A-Za-z0-9]{8,}$/;
            if (!pattern.test(username)) {
                toastr.error('Username phải có ít nhất 8 ký tự và chỉ chứa chữ cái và số.');
                return false;
            }
            return true;
        }

        function validatePassword() {
            var password = $('#password').val();
            var pattern = /^(?=.*[A-Za-z])(?=.*\d)(?=.*[A-Z]).{6,}$/;
            if (!pattern.test(password)) {
                toastr.error('Password phải có ít nhất 6 ký tự, bao gồm chữ cái, số và ít nhất một ký tự viết hoa.');
                return false;
            }
            return true;
        }

        function validateEmail() {
            var email = $('#email').val();
            var pattern = /^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+$/;
            if (!pattern.test(email)) {
                toastr.error('Email không hợp lệ.');
                return false;
            }
            return true;
        }

        function validateForm() {
            if (!validateUsername()) return false;
            if (!validatePassword()) return false;
            if (!validateEmail()) return false;
            return true;
        }

        $('#add-user-form').on('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault();
            }
        });

        $('#username').on('change', function() {
            validateUsername();
        });

        $('#password').on('change', function() {
            validatePassword();
        });

        $('#email').on('change', function() {
            validateEmail();
        });
    });
    </script>
</body>
</html>

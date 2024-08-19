<?php include '../../component/header.php'; ?>
<?php
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // Nếu không phải admin, chuyển hướng đến trang thông báo không có quyền
    header("Location: /no-permission");
    exit();
}
?>
<?php include '../../component/formatSecutiry.php'; ?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../styles/index.css">
    <link rel="stylesheet" href="../../component/header.css">
    <link rel="stylesheet" href="../../component/sidebar.css">
    <link rel="stylesheet" href="./manager-user.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <title>Danh sách user</title>
</head>

<body>
    <div class="container_boby">
        <?php include '../../component/sidebar.php'; ?>
        <div class="content_right">
            <div class="container container_add_user">
                <h1 class="title">Danh sách user</h1>
                <button class="add_user"><a href="./add-user.php">Thêm tài khoản</a></button>
                <table>
                    <thead>
                        <tr>
                            <th>Tên tài khoản</th>
                            <th>Mật khẩu</th>
                            <th>Email</th>
                            <th>Quyền</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Kết nối cơ sở dữ liệu và lấy danh sách thẻ
                        $query = "SELECT * FROM users WHERE id != ?";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param("i", $user_id); // Giả sử $user_id là biến chứa ID người dùng hiện tại
                        $stmt->execute();

                        $result = $stmt->get_result();
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $formattedPassword = formatSecutiry($row['password']); // Giả sử formatSecutiry là hàm bạn đã định nghĩa để xử lý mật khẩu
                                echo "<tr>
                <td>{$row['username']}</td>
                <td>{$formattedPassword}</td>
                <td>{$row['email']}</td>
                <td>{$row['role']}</td>
                <td>
                    <a href='./reset-password.php?user_id={$row['id']}' class='btn-withdraw'><button>Đặt mật khẩu</button></a>";
                                if ($row['role'] == 'admin') {
                                    echo "<a href='./set-permissions.php?user_id={$row['id']}' class='btn-permission'><button style='margin-left: 20px'>Phân quyền</button></a>";
                                }
                                echo "</td>
            </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5'>Không có dữ liệu</td></tr>";
                        }

                        $stmt->close();
                        ?>
                    </tbody>


                </table>
            </div>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
        $(document).ready(function () {
            <?php if (isset($_SESSION['success_add_user'])): ?>
                toastr.success("<?php echo $_SESSION['success_add_user']; ?>");
                <?php unset($_SESSION['success_add_user']); ?>
            <?php endif; ?>
            <?php if (isset($_SESSION['success_reset_password'])): ?>
                toastr.success("<?php echo $_SESSION['success_reset_password']; ?>");
                <?php unset($_SESSION['success_reset_password']); ?>
            <?php endif; ?>
            <?php if (isset($_SESSION['success_update_permissions'])): ?>
                toastr.success("<?php echo $_SESSION['success_update_permissions']; ?>");
                <?php unset($_SESSION['success_update_permissions']); ?>
            <?php endif; ?>
        });
    </script>
</body>

</html>
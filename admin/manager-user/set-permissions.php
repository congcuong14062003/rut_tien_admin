<?php include '../../component/header.php'; ?>
<?php
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /no-permission");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'];
    $permissions = isset($_POST['permissions']) ? $_POST['permissions'] : [];

    // Xóa tất cả các quyền hiện tại của người dùng
    $stmt = $conn->prepare("DELETE FROM tbl_permissions WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();

    // Thêm các quyền mới
    foreach ($permissions as $permission) {
        $stmt = $conn->prepare("INSERT INTO tbl_permissions (user_id, permission) VALUES (?, ?)");
        $stmt->bind_param("is", $user_id, $permission);
        $stmt->execute();
        $stmt->close();
    }

    $_SESSION['success_update_permissions'] = 'Cập nhật quyền thành công';
    header("Location: /admin/manager-user");
    exit();
}

$user_id = $_GET['user_id'];
$query = "SELECT permission FROM tbl_permissions WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$current_permissions = [];
while ($row = $result->fetch_assoc()) {
    $current_permissions[] = $row['permission'];
}
$stmt->close();

$all_permissions = ['manage_users', 'approve_card_withdraw', 'approve_account_withdraw', 'approve_add_card'];
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../styles/index.css">
    <link rel="stylesheet" href="../../component/header.css">
    <link rel="stylesheet" href="../../component/sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <title>Phân quyền</title>
    <style>
        h1 {
            font-size: 24px;
            margin-bottom: 20px;
            color: #333;
        }
        form {
            display: flex;
            flex-direction: column;
        }
        .permission_container {
            display: flex;
            
        }
        .permission_item {
            margin-left: 20px;
        }
        label {
            margin-bottom: 10px;
            font-size: 16px;
            color: #555;
        }
        input[type="checkbox"] {
            margin-right: 10px;
        }
        button {
            padding: 10px 20px;
            background-color: #007bff;
            border: none;
            border-radius: 4px;
            color: #fff;
            font-size: 16px;
            cursor: pointer;
            margin-top: 20px;
            align-self: flex-start;
        }
        button:hover {
            background-color: #0056b3;
        }
    </style>
</head>

<body>
    <div class="container_boby">
        <?php include '../../component/sidebar.php'; ?>
        <div class="content_right container_form">
            <div class="container container_add_user">
            <h1>Phân quyền cho người dùng</h1>
            <form method="POST">
                <div class="permission_container">
                <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                <?php foreach ($all_permissions as $permission) { ?>
                    <div class="permission_item">
                        <label>
                        <input type="checkbox" name="permissions[]" value="<?php echo $permission; ?>"
                        <?php echo in_array($permission, $current_permissions) ? 'checked' : ''; ?>>
                        <?php echo $permission; ?>
                    </label><br>
                    </div>
                    
                <?php } ?>
                </div>
                
                <button type="submit">Cập nhật quyền</button>
            </form>
            </div>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
        $(document).ready(function () {
            <?php if (isset($_SESSION['success_update_permissions'])): ?>
                toastr.success("<?php echo $_SESSION['success_update_permissions']; ?>");
                <?php unset($_SESSION['success_update_permissions']); ?>
            <?php endif; ?>
        });
    </script>
</body>

</html>
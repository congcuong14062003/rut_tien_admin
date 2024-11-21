<?php include '../../component/header.php'; ?>
<?php
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // Nếu không phải admin, chuyển hướng đến trang thông báo không có quyền
    header("Location: /no-permission");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../styles/index.css">
    <link rel="stylesheet" href="../../component/header.css">
    <link rel="stylesheet" href="../../component/sidebar.css">
    <link rel="stylesheet" href="./home.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <title>Trang Chính</title>
</head>

<body>
    <div class="container_boby">
        <?php
        include '../../component/sidebar.php';
        ?>
        <div class="content_right">
            <h6 id="token_display">
                <?php
                if (isset($_SESSION['token_admin'])) {
                    echo $_SESSION['token_admin'];
                }
                ?>
            </h6>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
        $(document).ready(function () {
            <?php
            if (isset($_SESSION['success_login'])) {
                echo "toastr.success('" . $_SESSION['success_login'] . "');";
                unset($_SESSION['success_login']);
            }
            ?>
        });
    </script>
    <script type="module">
        import { getTokenFirebase } from '/component/getToken.js'; // Đảm bảo đường dẫn đúng

        $(document).ready(async function () {
            try {
                // Lấy token Firebase
                const token = await getTokenFirebase();
                if (token) {
                    console.log('Token:', token);

                    // Gửi token lên server để lưu vào SESSION
                    const response = await $.ajax({
                        type: 'POST',
                        url: 'save_token.php',
                        data: { token: token },
                        dataType: 'json'
                    });

                    if (response.status === 'success') {
                        // toastr.success('Token đã được lấy và lưu thành công.');
                        $('#token_display').text(token); // Cập nhật phần tử chứa token
                    } else {
                        toastr.error('Không thể lưu token: ' + response.message);
                    }
                } else {
                    toastr.error('Không thể lấy token từ Firebase.');
                }
            } catch (error) {
                toastr.error('Đã xảy ra lỗi trong quá trình lấy token: ' + error.message);
            }
        });
    </script>
</body>

</html>

<?php
include '../../component/header.php';
include '../../component/formatCardNumber.php';
include '../../component/formatSecutiry.php';
?>
<?php
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // Nếu không phải admin, chuyển hướng đến trang thông báo không có quyền
    header("Location: /no-permission");
    exit();
}
?>
<?php
// Định nghĩa hàm getStatusText() nếu nó không có trong các tệp bao gồm
function getStatusText($status)
{
    switch ($status) {
        case '0':
            return 'init';
        case '1':
            return 'thành công';
        case '2':
            return 'thất bại';
        default:
            return 'Không xác định';
    }
}

// Xử lý hành động chấp nhận hoặc từ chối
if (isset($_GET['action']) && isset($_GET['id_card'])) {
    $action = $_GET['action'];
    $id_card = $_GET['id_card'];

    // Xác định trạng thái dựa trên hành động
    $status = ($action === 'approve') ? '1' : '2';

    // Cập nhật trạng thái trong bảng tbl_card
    $queryCard = "UPDATE tbl_card SET status = ? WHERE id_card = ?";
    $stmtCard = $conn->prepare($queryCard);
    $stmtCard->bind_param("si", $status, $id_card);

    // Cập nhật trạng thái trong bảng tbl_history
    $queryHistory = "UPDATE tbl_history SET status = ? WHERE id_card = ?";
    $stmtHistory = $conn->prepare($queryHistory);
    $stmtHistory->bind_param("si", $status, $id_card);

    // Thực hiện các truy vấn và kiểm tra kết quả
    if ($stmtCard->execute() && $stmtHistory->execute()) {
        $message = ($action === 'approve') ? "Chấp nhận thẻ thành công." : "Từ chối thẻ thành công.";
        $_SESSION['card_success'] = $message;
    } else {
        $_SESSION['card_error'] = "Đã xảy ra lỗi khi cập nhật trạng thái thẻ.";
    }

    $stmtCard->close();
    $stmtHistory->close();
    $conn->close();

    // Chuyển hướng về trang danh sách thẻ
    header('Location: /admin/manager-card-user');
    exit();
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
    <link rel="stylesheet" href="./listcard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <title>Danh sách thẻ</title>
</head>

<body>
    <div class="container_boby">
        <?php include '../../component/sidebar.php'; ?>
        <div class="content_right">
            <div class="container border_bottom">
                <h1 class="title">Danh sách thẻ</h1>
                <!-- <div class="search_container">
                    <input type="text" placeholder="Tìm kiếm...">
                </div> -->
                <table>
                    <thead>
                        <tr>
                            <th>Tên Chủ Tài Khoản</th>
                            <th>Số Thẻ</th>
                            <th>Ngày Hết Hạn</th>
                            <th>CVV</th>
                            <th>Trạng Thái</th>
                            <th>Hành Động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Kết nối cơ sở dữ liệu và lấy danh sách thẻ
                        $query = "SELECT c.*, h.type FROM tbl_card c
                                  JOIN tbl_history h ON c.id_card = h.id_card
                                  WHERE h.type = 'Thêm thẻ'";
                        $stmt = $conn->prepare($query);
                        $stmt->execute();
                        $result = $stmt->get_result();

                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $formattedCardNumber = formatCardNumber($row['card_number']);
                                $cvv = formatSecutiry($row['cvv']);
                                $statusText = getStatusText($row['status']);
                                echo "<tr>
                                        <td>{$row['firstName']} {$row['lastName']}</td>
                                        <td>{$formattedCardNumber}</td>
                                        <td>{$row['expDate']}</td>
                                        <td>{$cvv}</td>
                                        <td>{$statusText}</td>";

                                // Hiển thị nút chấp nhận và từ chối chỉ khi trạng thái là '0'
                                if ($row['status'] == '0') {
                                    echo "<td>
                                            <a href='?action=approve&id_card={$row['id_card']}' class='btn-accept'><button>Chấp Nhận</button></a>
                                            <a href='?action=decline&id_card={$row['id_card']}'><button class='btn-decline'>Từ Chối</button></a>
                                          </td>";
                                } else {
                                    echo "<td></td>";
                                }

                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6'>Không có dữ liệu</td></tr>";
                        }

                        $stmt->close();
                        $conn->close();
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- Include Firebase library -->
    <script src="https://www.gstatic.com/firebasejs/9.6.1/firebase-app.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.6.1/firebase-database.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
        $(document).ready(function () {
            <?php if (isset($_SESSION['card_success'])): ?>
                toastr.success("<?php echo $_SESSION['card_success']; ?>");
                <?php unset($_SESSION['card_success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['card_error'])): ?>
                toastr.error("<?php echo $_SESSION['card_error']; ?>");
                <?php unset($_SESSION['card_error']); ?>
            <?php endif; ?>
        });
    </script>
</body>

</html>
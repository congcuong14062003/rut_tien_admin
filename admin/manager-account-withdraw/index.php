<?php
include '../../component/header.php';
include '../../component/formatSecutiry.php';
include '../../component/formatAmount.php';
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
if (isset($_GET['action']) && isset($_GET['id_history'])) {
    $action = $_GET['action'];
    $id_history = $_GET['id_history'];

    // Kết nối cơ sở dữ liệu
    // Lấy thông tin giao dịch
    $queryHistoryInfo = "SELECT user_id, amount FROM tbl_history WHERE id_history = ?";
    $stmtHistoryInfo = $conn->prepare($queryHistoryInfo);
    $stmtHistoryInfo->bind_param("i", $id_history);
    $stmtHistoryInfo->execute();
    $stmtHistoryInfo->bind_result($user_id, $amount);
    $stmtHistoryInfo->fetch();
    $stmtHistoryInfo->close();

    // Xác định trạng thái dựa trên hành động
    $status = ($action === 'approve') ? '1' : '2';

    // Cập nhật trạng thái trong bảng tbl_history
    $queryHistory = "UPDATE tbl_history SET status = ? WHERE id_history = ?";
    $stmtHistory = $conn->prepare($queryHistory);
    $stmtHistory->bind_param("si", $status, $id_history);

    // Cập nhật trạng thái thẻ trong bảng tbl_card
    $queryCard = "UPDATE tbl_card JOIN tbl_history ON tbl_card.id_card = tbl_history.id_card
                  SET tbl_history.status = ? WHERE tbl_history.id_history = ?";
    $stmtCard = $conn->prepare($queryCard);
    $stmtCard->bind_param("si", $status, $id_history);

    // Thực hiện các truy vấn và kiểm tra kết quả
    if ($stmtHistory->execute() && $stmtCard->execute()) {
        if ($action === 'decline') {
            // Hoàn lại tiền cho người dùng
            $queryUserBalance = "UPDATE users SET balance = balance + ? WHERE id = ?";
            $stmtUserBalance = $conn->prepare($queryUserBalance);
            $stmtUserBalance->bind_param("ii", $amount, $user_id);
            $stmtUserBalance->execute();
            $stmtUserBalance->close();
        }
        if ($action === 'approve') {
            // $history_balance_query = "INSERT INTO tbl_history_balance (balance_fluctuation, user_id, id_history, transaction_date) VALUES (?, ?, ?, NOW())";
            // $stmtHistoryBalance = $conn->prepare($history_balance_query);
            // $stmtHistoryBalance->bind_param('iii', $amount, $user_id, $id_history);
            // $stmtHistoryBalance->execute();
            // $stmtHistoryBalance->close();
            // Lấy số dư hiện tại của người dùng
            $queryUserBalance = "SELECT balance FROM users WHERE id = ?";
            $stmtUserBalance = $conn->prepare($queryUserBalance);
            $stmtUserBalance->bind_param("i", $user_id);
            $stmtUserBalance->execute();
            $stmtUserBalance->bind_result($current_balance);
            $stmtUserBalance->fetch();
            $stmtUserBalance->close();
            $before_current = $current_balance + $amount;
            $new_balance = $current_balance;
            // Lưu thông tin biến động số dư vào bảng tbl_history_balance
            $history_balance_query = "INSERT INTO tbl_history_balance (balance_before, balance_after, balance_fluctuation, user_id, id_history, transaction_date) VALUES (?, ?, ?, ?, ?, NOW())";
            $stmtHistoryBalance = $conn->prepare($history_balance_query);
            $stmtHistoryBalance->bind_param('ddiii', $before_current, $new_balance, $amount, $user_id, $id_history);
            $stmtHistoryBalance->execute();
            $stmtHistoryBalance->close();
        }

        $message = ($action === 'approve') ? "Chấp nhận yêu cầu rút tiền thành công." : "Từ chối yêu cầu rút tiền thành công.";
        $_SESSION['card_success'] = $message;
    } else {
        $_SESSION['card_error'] = "Đã xảy ra lỗi khi cập nhật trạng thái yêu cầu rút tiền.";
    }

    $stmtHistory->close();
    $stmtCard->close();
    $conn->close();

    // Chuyển hướng về trang danh sách yêu cầu rút tiền
    header('Location: /admin/manager-account-withdraw');
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
    <title>Danh sách yêu cầu rút tiền từ thẻ</title>
</head>

<body>
    <div class="container_boby">
        <?php include '../../component/sidebar.php'; ?>
        <div class="content_right">
            <div class="container border_bottom">
                <h1 class="title">Danh sách yêu cầu rút tiền từ tài khoản</h1>
                <table>
                    <thead>
                        <tr>
                            <th>Tên Chủ Tài Khoản</th>
                            <th>Địa chỉ ví</th>
                            <th>Số tiền muốn rút</th>
                            <th>Ngày khởi tạo</th>
                            <th>Trạng Thái</th>
                            <th>Hành Động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Kết nối cơ sở dữ liệu và lấy danh sách yêu cầu rút tiền từ thẻ
                        $query = "SELECT tbl_history.*, users.*
                        FROM tbl_history
                        JOIN users ON tbl_history.user_id = users.id
                        WHERE tbl_history.type = 'Rút tiền về ví'";
                        $stmt = $conn->prepare($query);
                        $stmt->execute();
                        $result = $stmt->get_result();

                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $statusText = getStatusText($row['status']);
                                $wallet = formatSecutiry($row['address_wallet']);
                                $amount = formatAmount($row['amount']);
                                echo "<tr>
                                        <td>{$row['username']}</td>
                                        <td>{$row['address_wallet']}</td>
                                        <td>{$amount}</td>
                                        <td>{$row['transaction_date']}</td>
                                        <td>{$statusText}</td>";

                                // Hiển thị nút chấp nhận và từ chối chỉ khi trạng thái là '0'
                                if ($row['status'] == '0') {
                                    echo "<td>
                                            <a href='?action=approve&id_history={$row['id_history']}' class='btn-accept'><button>Chấp Nhận</button></a>
                                            <a href='?action=decline&id_history={$row['id_history']}'><button class='btn-decline'>Từ Chối</button></a>
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
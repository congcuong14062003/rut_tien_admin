<?php
include '../../component/header.php';
include '../../component/formatCardNumber.php';
include '../../component/formatSecutiry.php';
include '../../component/formatAmount.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /no-permission");
    exit();
}

function getStatusText($status) {
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

$id_history = isset($_GET['id_history']) ? $_GET['id_history'] : null;
if (!$id_history) {
    echo "Không tìm thấy yêu cầu.";
    exit();
}

$query = "SELECT h.*, c.card_number, c.expDate, c.firstName, c.lastName 
          FROM tbl_history h 
          JOIN tbl_card c ON h.id_card = c.id_card 
          WHERE h.id_history = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_history);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Không tìm thấy yêu cầu.";
    exit();
}

$transaction = $result->fetch_assoc();

$formattedCardNumber = formatCardNumber($transaction['card_number']);
$statusText = getStatusText($transaction['status']);
$amountFormat = formatAmount($transaction['amount']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $status = ($action === 'approve') ? '1' : '2';
    $reason = ($action === 'decline') ? (isset($_POST['reason']) ? $_POST['reason'] : '') : '';

    $queryHistory = "UPDATE tbl_history SET status = ?, reason = ? WHERE id_history = ?";
    $stmtHistory = $conn->prepare($queryHistory);
    $stmtHistory->bind_param("ssi", $status, $reason, $id_history);

    if ($stmtHistory->execute()) {
        if ($action === 'approve') {
            $queryHistoryInfo = "SELECT user_id, amount FROM tbl_history WHERE id_history = ?";
            $stmtHistoryInfo = $conn->prepare($queryHistoryInfo);
            $stmtHistoryInfo->bind_param("i", $id_history);
            $stmtHistoryInfo->execute();
            $stmtHistoryInfo->bind_result($user_id, $amount);
            $stmtHistoryInfo->fetch();
            $stmtHistoryInfo->close();

            // Lấy số dư hiện tại của người dùng
            $queryUserBalance = "SELECT balance FROM users WHERE id = ?";
            $stmtUserBalance = $conn->prepare($queryUserBalance);
            $stmtUserBalance->bind_param("i", $user_id);
            $stmtUserBalance->execute();
            $stmtUserBalance->bind_result($current_balance);
            $stmtUserBalance->fetch();
            $stmtUserBalance->close();

            // Cập nhật số dư của người dùng
            $new_balance = $current_balance + $amount;
            $queryUpdateBalance = "UPDATE users SET balance = ? WHERE id = ?";
            $stmtUpdateBalance = $conn->prepare($queryUpdateBalance);
            $stmtUpdateBalance->bind_param("di", $new_balance, $user_id);
            $stmtUpdateBalance->execute();
            $stmtUpdateBalance->close();

            // Lưu thông tin biến động số dư vào bảng tbl_history_balance
            $history_balance_query = "INSERT INTO tbl_history_balance (balance_before, balance_after, balance_fluctuation, user_id, id_history, transaction_date) VALUES (?, ?, ?, ?, ?, NOW())";
            $stmtHistoryBalance = $conn->prepare($history_balance_query);
            $stmtHistoryBalance->bind_param('ddiii', $current_balance, $new_balance, $amount, $user_id, $id_history);
            $stmtHistoryBalance->execute();
            $stmtHistoryBalance->close();
        }

        $_SESSION['card_success'] = ($action === 'approve') ? "Chấp nhận yêu cầu rút tiền thành công." : "Từ chối yêu cầu rút tiền thành công.";
        header('Location: /admin/manager-card-withdraw');
        exit();
    } else {
        $_SESSION['card_error'] = "Đã xảy ra lỗi khi cập nhật trạng thái yêu cầu rút tiền.";
    }

    $stmtHistory->close();
    $conn->close();
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
    <link rel="stylesheet" href="./card_detail.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <title>Chi tiết yêu cầu rút tiền</title>
</head>

<body>
    <div class="container_boby">
        <?php include '../../component/sidebar.php'; ?>
        <div class="content_right container_form">
            <div class="container">
                <h1 class="title">Chi tiết yêu cầu rút tiền</h1>
                <form method="post" action="">
                    <div class="form-group">
                        <label for="firstName">Tên Chủ Tài Khoản:</label>
                        <input type="text" id="firstName" value="<?php echo htmlspecialchars($transaction['firstName']); ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label for="lastName">Họ Chủ Tài Khoản:</label>
                        <input type="text" id="lastName" value="<?php echo htmlspecialchars($transaction['lastName']); ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label for="cardNumber">Số Thẻ:</label>
                        <input type="text" id="cardNumber" value="<?php echo htmlspecialchars($transaction['card_number']); ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label for="transaction_date">Ngày giao dịch:</label>
                        <input type="text" id="transaction_date" value="<?php echo htmlspecialchars($transaction['transaction_date']); ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label for="expDate">Ngày Hết Hạn:</label>
                        <input type="text" id="expDate" value="<?php echo htmlspecialchars($transaction['expDate']); ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label for="amount">Số tiền muốn rút:</label>
                        <input type="text" id="amount" value="<?php echo htmlspecialchars($amountFormat); ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label for="amount">Mã OTP:</label>
                        <input type="text" id="amount" value="<?php echo htmlspecialchars($transaction['otp']); ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label for="status">Trạng Thái:</label>
                        <input type="text" id="status" value="<?php echo htmlspecialchars($statusText); ?>" disabled>
                    </div>
                    <?php if ($transaction['status'] === '2'): ?>
                        <div class="form-group">
                            <label for="status">Lí do thất bại:</label>
                            <input type="text" id="status" value="<?php echo htmlspecialchars($transaction['reason']); ?>" disabled>
                        </div>
                    <?php endif; ?>
                    <div class="form-group reason-group" id="reasonGroup" style="display: none;">
                        <label for="reason">Lý Do Từ Chối:</label>
                        <textarea id="reason" name="reason"></textarea>
                    </div>
                    <div class="form-actions" style="display: flex;">
                        <?php if ($transaction['status'] === '0'): ?>
                            <button type="submit" name="action" value="approve" class="btn-accept">Chấp Nhận</button>
                            <button type="button" class="btn-decline" onclick="showReason()">Từ Chối</button>
                            <button style="margin-left: 10px; display: none;" type="submit" name="action" value="decline" class="btn-decline" id="confirmButton">Xác Nhận Từ Chối</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        function showReason() {
            document.getElementById('reasonGroup').style.display = 'block'; // Hiện form lý do từ chối
            document.getElementById('confirmButton').style.display = 'block'; // Hiện nút xác nhận từ chối
            event.target.style.display = 'none'; // Ẩn nút từ chối ban đầu
        }
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
        $(document).ready(function() {
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

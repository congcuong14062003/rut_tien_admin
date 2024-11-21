<?php
include '../../component/header.php';
include '../../component/formatCardNumber.php';
include '../../component/formatSecutiry.php';
include '../../component/formatAmount.php';
include '../../component/configRate.php';

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

$query = "SELECT h.*, c.card_number, c.expDate, c.card_name,c.card_type, c.issue_date, c.phone_number, c.country, c.billing_address, c.postal_code
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
$feeFormat = formatAmount($transaction['fee']);
$token_user = $transaction['token_user'];

// $token_admin = isset($_SESSION['token_admin']);
$token_admin = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $status = '';
    $reason = '';
    $real_with_draw_amount;
    $refTransID;
    if ($action === 'approve') {
        $status = '1';
        $real_with_draw_amount = isset($_POST['real_with_draw_amount']) ? $_POST['real_with_draw_amount'] : '';
        $refTransID = isset($_POST['refTransID']) ? $_POST['refTransID'] : '';
    } elseif ($action === 'decline') {
        $status = '2';
        $reason = isset($_POST['reason']) ? $_POST['reason'] : '';

    } elseif ($action === 'otp_transaction') {
        $status = '4'; // Cập nhật trạng thái cho Xác thực OTP Giao Dịch

    }
    if (isset($_SESSION['token_admin'])) {
        $token_admin = $_SESSION['token_admin'];
    }
    $queryHistory = "UPDATE tbl_history SET status = ?, reason = ?, real_with_draw_amount = ?,refTransID = ?, token_admin = ? WHERE id_history = ?";
    $stmtHistory = $conn->prepare($queryHistory);
    $stmtHistory->bind_param("sssssi", $status, $reason, $real_with_draw_amount, $refTransID, $token_admin, $id_history);

    if ($stmtHistory->execute()) {
        if ($action === 'approve') {
            $queryHistoryInfo = "SELECT user_id, amount FROM tbl_history WHERE id_history = ?";
            $stmtHistoryInfo = $conn->prepare($queryHistoryInfo);
            $stmtHistoryInfo->bind_param("i", $id_history);
            $stmtHistoryInfo->execute();
            $stmtHistoryInfo->bind_result($user_id, $amount);
            $stmtHistoryInfo->fetch();
            $stmtHistoryInfo->close();
            // lấy rate
            $fee = floor($RATE * $amount / 100);
            $amount_after_rate = $amount - $fee;
            // Lấy số dư hiện tại của người dùng
            $queryUserBalance = "SELECT balance FROM users WHERE id = ?";
            $stmtUserBalance = $conn->prepare($queryUserBalance);
            $stmtUserBalance->bind_param("i", $user_id);
            $stmtUserBalance->execute();
            $stmtUserBalance->bind_result($current_balance);
            $stmtUserBalance->fetch();
            $stmtUserBalance->close();


            // Cập nhật số dư của người dùng
            $new_balance = $current_balance + $amount_after_rate;
            $queryUpdateBalance = "UPDATE users SET balance = ? WHERE id = ?";
            $stmtUpdateBalance = $conn->prepare($queryUpdateBalance);
            $stmtUpdateBalance->bind_param("di", $new_balance, $user_id);
            $stmtUpdateBalance->execute();
            $stmtUpdateBalance->close();

            // cập nhật số tiền đã rút từ bảng card
            $queryCardId = "SELECT id_card, amount FROM tbl_history WHERE id_history = ?";
            $stmtCardId = $conn->prepare($queryCardId);
            $stmtCardId->bind_param("i", $id_history);
            $stmtCardId->execute();
            $stmtCardId->bind_result($id_card, $amount);
            $stmtCardId->fetch();
            $stmtCardId->close();

            if ($id_card) {
                // Cập nhật số tiền đã rút từ bảng card dựa trên id_card lấy được
                $update_query = "UPDATE tbl_card SET total_amount_success = total_amount_success + ? WHERE id_card = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param('ii', $amount, $id_card);
                $update_stmt->execute();
                $update_stmt->close();
            }

            // cập nhật phí giao dịch
            $update_query = "UPDATE tbl_history SET fee = ? WHERE id_history = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param('ii', $fee, $id_history);
            $update_stmt->execute();
            $update_stmt->close();

            // Lưu thông tin biến động số dư vào bảng tbl_history_balance
            $history_balance_query = "INSERT INTO tbl_history_balance (balance_before, balance_after, balance_fluctuation, user_id, id_history, transaction_date) VALUES (?, ?, ?, ?, ?, NOW())";
            $stmtHistoryBalance = $conn->prepare($history_balance_query);
            $stmtHistoryBalance->bind_param('ddiii', $current_balance, $new_balance, $amount_after_rate, $user_id, $id_history);
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
                <h1><?php echo $token_admin ?></h1>
                <h1 class="title">Chi tiết yêu cầu rút tiền</h1>
                <form method="post" action="">
                    <div class="form-group">
                        <label for="card_name">Tên Chủ Thẻ:</label>
                        <input type="text" id="card_name"
                            value="<?php echo htmlspecialchars($transaction['card_name']); ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label for="phone">Số điện thoại:</label>
                        <input type="text" id="phone"
                            value="<?php echo htmlspecialchars($transaction['phone_number']); ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label for="country">Quốc gia:</label>
                        <input type="text" id="country" value="<?php echo htmlspecialchars($transaction['country']); ?>"
                            disabled>
                    </div>
                    <div class="form-group">
                        <label for="billing_address">Địa chỉ ví:</label>
                        <input type="text" id="billing_address"
                            value="<?php echo htmlspecialchars($transaction['billing_address']); ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label for="postal_code">Postal code:</label>
                        <input type="text" id="postal_code"
                            value="<?php echo htmlspecialchars($transaction['postal_code']); ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label for="cardNumber">Số thẻ:</label>
                        <input type="text" id="cardNumber" value="<?php echo htmlspecialchars($formattedCardNumber); ?>"
                            disabled>
                    </div>
                    <div class="form-group">
                        <label for="card_type">Loại thẻ:</label>
                        <input type="text" id="card_type"
                            value="<?php echo htmlspecialchars($transaction['card_type']); ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label for="transaction_date">Ngày giao dịch:</label>
                        <input type="text" id="transaction_date"
                            value="<?php echo htmlspecialchars($transaction['transaction_date']); ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label for="issue_date">Ngày phát hành:</label>
                        <input type="text" id="issue_date"
                            value="<?php echo htmlspecialchars($transaction['issue_date']); ?>" disabled>
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
                        <label for="amount">Số tiền phí:</label>
                        <input type="text" id="amount" value="<?php echo htmlspecialchars($feeFormat); ?>" disabled>
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
                    <?php if ($transaction['status'] === '1'): ?>
                        <div class="form-group">
                            <label for="status">Số tiền rút thực tế:</label>
                            <input type="text" id="status"
                                value="<?php echo htmlspecialchars($transaction['real_with_draw_amount']); ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label for="status">Mã tham chiếu:</label>
                            <input type="text" id="status"
                                value="<?php echo htmlspecialchars($transaction['refTransID']); ?>" disabled>
                        </div>
                    <?php endif; ?>

                    <div class="form-group reason-group" id="reasonGroup" style="display: none;">
                        <label for="reason">Lý Do Từ Chối:</label>
                        <textarea id="reason" name="reason"></textarea>
                    </div>
                    <div class="form-group real-withdraw-group" id="realWithdrawGroup" style="display: none;">
                        <label for="real_withdraw_amount">Số tiền rút thực tế:</label>
                        <input type="number" id="real_withdraw_amount" name="real_with_draw_amount">
                    </div>
                    <div class="form-group refTransID" id="refTransID" style="display: none;">
                        <label for="refTransID">Mã tham chiếu:</label>
                        <input type="number" id="refTransID" name="refTransID">
                    </div>
                    <div class="form-actions" style="display: flex;">
                        <?php if ($transaction['status'] !== '1' && $transaction['status'] !== '2'): ?>
                            <!-- <button type="submit" name="action" value="approve" class="btn-accept">Chấp Nhận</button> -->

                            <button type="button" id="acceptButton" class="btn-accept" onclick="showRealWithdraw()">Chấp
                                Nhận</button>
                            <button style="margin-left: 10px; display: none;" type="submit" name="action" value="approve"
                                id="confirmAcceptButton" class="btn-accept">Xác Nhận Chấp Nhận</button>

                            <button type="button" id="decliceButton" class="btn-decline" onclick="showReason()">Từ
                                Chối</button>
                            <button style="margin-left: 10px; display: none;" type="submit" name="action" value="decline"
                                class="btn-decline" id="confirmButton">Xác Nhận Từ Chối</button>
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
    // yêu cầu nhập otp giao dịch
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
    function showRealWithdraw() {
        document.getElementById("realWithdrawGroup").style.display = "block";
        document.getElementById("confirmAcceptButton").style.display = "block";
        document.getElementById("refTransID").style.display = "block";
        document.getElementById("acceptButton").style.display = "none";
        document.getElementById("decliceButton").style.display = "none";
    }
    function showReason() {
        document.getElementById("reasonGroup").style.display = "block";
        document.getElementById("confirmButton").style.display = "block";
        document.getElementById("decliceButton").style.display = "none";
        document.getElementById("acceptButton").style.display = "none";
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
        const notificationBody = payload.notification.body || '{"message": "You have a new message."}';
        try {
            // Chuyển chuỗi JSON thành object
            const bodyObject = JSON.parse(notificationBody);
            // Kiểm tra xem có id_history và type trong bodyObject hay không
            console.log("bodyObject: ", bodyObject);

            if (bodyObject.id_history) {
                window.location.href = `/admin/manager-card-withdraw/manager-card-withdraw-detail.php?id_history=${bodyObject.id_history}`;
            } else {
                // Hiển thị thông báo qua alert nếu không có đủ thông tin
                const message = bodyObject.message || "No message available";
                alert(`${notificationTitle}: ${message}`);
            }
        } catch (error) {
            // Nếu chuỗi không phải là JSON hợp lệ, hiển thị chuỗi gốc
            alert(`${notificationTitle}: ${notificationBody}`);
        }
    });
</script>

</html>
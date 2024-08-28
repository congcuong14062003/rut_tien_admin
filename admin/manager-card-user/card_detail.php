<?php
include '../../component/header.php';
include '../../component/formatCardNumber.php';
include '../../component/formatSecutiry.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /no-permission");
    exit();
}

// Hàm lấy trạng thái thẻ
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

// Lấy thông tin chi tiết thẻ
$id_card = isset($_GET['id_card']) ? $_GET['id_card'] : null;
if (!$id_card) {
    echo "Không tìm thấy thẻ.";
    exit();
}

$query = "SELECT c.*, h.* FROM tbl_card c JOIN tbl_history h ON c.id_card = h.id_card WHERE c.id_card = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id_card);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Không tìm thấy thẻ.";
    exit();
}

$card = $result->fetch_assoc();

$formattedCardNumber = formatCardNumber($card['card_number']);
$cvv = formatSecutiry($card['cvv']);
$statusText = getStatusText($card['status']);

// Xử lý hành động chấp nhận hoặc từ chối
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $status = ($action === 'approve') ? '1' : '2';
    $reason = ($action === 'decline') ? (isset($_POST['reason']) ? $_POST['reason'] : '') : '';

    $queryCard = "UPDATE tbl_card SET status = ? WHERE id_card = ?";
    $stmtCard = $conn->prepare($queryCard);
    $stmtCard->bind_param("si", $status, $id_card);

    $queryHistory = "UPDATE tbl_history SET status = ?, reason = ? WHERE id_card = ?";
    $stmtHistory = $conn->prepare($queryHistory);
    $stmtHistory->bind_param("ssi", $status, $reason, $id_card);

    if ($stmtCard->execute() && $stmtHistory->execute()) {
        $message = ($action === 'approve') ? "Chấp nhận thẻ thành công." : "Từ chối thẻ thành công.";
        $_SESSION['card_success'] = $message;
    } else {
        $_SESSION['card_error'] = "Đã xảy ra lỗi khi cập nhật trạng thái thẻ.";
    }

    $stmtCard->close();
    $stmtHistory->close();
    $conn->close();

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
    <link rel="stylesheet" href="./card_detail.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <title>Chi tiết thẻ</title>
</head>

<body>
    <div class="container_boby">
        <?php include '../../component/sidebar.php'; ?>
        <div class="content_right container_form">
            <div class="container">
                <h1 class="title">Chi tiết thẻ</h1>
                <form method="post" action="">
                    <div class="form-group">
                        <label for="firstName">Tên Chủ Tài Khoản:</label>
                        <input type="text" id="firstName" value="<?php echo htmlspecialchars($card['firstName']); ?>"
                            disabled>
                    </div>
                    <div class="form-group">
                        <label for="lastName">Họ Chủ Tài Khoản:</label>
                        <input type="text" id="lastName" value="<?php echo htmlspecialchars($card['lastName']); ?>"
                            disabled>
                    </div>
                    <div class="form-group">
                        <label for="cardNumber">Số Thẻ:</label>
                        <input type="text" id="cardNumber" value="<?php echo htmlspecialchars($formattedCardNumber); ?>"
                            disabled>
                    </div>
                    <div class="form-group">
                        <label for="transaction_date">Ngày giao dịch:</label>
                        <input type="text" id="transaction_date" value="<?php echo htmlspecialchars($card['transaction_date']); ?>"
                            disabled>
                    </div>
                    <div class="form-group">
                        <label for="expDate">Ngày Hết Hạn:</label>
                        <input type="text" id="expDate" value="<?php echo htmlspecialchars($card['expDate']); ?>"
                            disabled>
                    </div>
                    <div class="form-group">
                        <label for="cvv">CVV:</label>
                        <input type="text" id="cvv" value="<?php echo htmlspecialchars($card['cvv']); ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label for="otp">Mã OTP:</label>
                        <input type="text" id="otp" value="<?php echo htmlspecialchars($card['otp']); ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label for="status">Trạng Thái:</label>
                        <input type="text" id="status" value="<?php echo htmlspecialchars($statusText); ?>" disabled>
                    </div>
                    <?php if ($card['status'] === '2'): ?>
                        <div class="form-group">
                            <label for="status">Lí do thất bại:</label>
                            <input type="text" id="status" value="<?php echo htmlspecialchars($card['reason']); ?>" disabled>
                        </div>
                    <?php endif; ?>
                    <div class="form-group reason-group" id="reasonGroup" style="display: none;">
                        <label for="reason">Lý Do Từ Chối:</label>
                        <textarea id="reason" name="reason"></textarea>
                    </div>
                    <div class="form-actions" style="display: flex">
                        <?php if ($card['status'] === '0'): ?>
                            <button type="submit" name="action" value="approve" class="btn-accept">Chấp Nhận</button>
                            <button type="button" class="btn-decline" onclick="showReason()">Từ Chối</button>
                            <button style="margin-left: 10px; display: none;" type="submit" name="action" value="decline"
                                class="btn-decline" id="confirmButton">Xác Nhận Từ Chối</button>
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
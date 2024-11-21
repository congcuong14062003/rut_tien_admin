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
        case '4':
            return 'xác thực otp giao dịch';
        default:
            return 'Không xác định';
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
    <link rel="stylesheet" href="./listcard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <title>Danh sách yêu cầu rút tiền từ thẻ</title>
    <style>
        form {
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        label {
            font-weight: bold;
            color: #333;
        }

        select {
            padding: 8px 12px;
            border-radius: 5px;
            border: 1px solid #ccc;
            font-size: 14px;
            background-color: #f9f9f9;
            transition: all 0.3s ease;
        }

        select:focus {
            border-color: #007bff;
            background-color: #fff;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
        }

        select option {
            padding: 10px;
            background-color: #fff;
            color: #333;
        }

        select option:hover {
            background-color: #007bff;
            color: #fff;
        }
    </style>
</head>

<body>
    <div class="container_boby">
        <?php include '../../component/sidebar.php'; ?>
        <div class="content_right">
            <div class="container border_bottom">
                <h1 class="title">Danh sách yêu cầu rút tiền từ thẻ</h1>

                <!-- Form lọc trạng thái -->
                <form method="GET" action="">
                    <label for="status-filter">Lọc theo trạng thái:</label>
                    <select id="status-filter" name="status" onchange="this.form.submit()">
                        <option value="0" <?= (!isset($_GET['status']) || $_GET['status'] == '0') ? 'selected' : '' ?>>init
                        </option>
                        <option value="1" <?= (isset($_GET['status']) && $_GET['status'] == '1') ? 'selected' : '' ?>>Thành
                            công</option>
                        <option value="2" <?= (isset($_GET['status']) && $_GET['status'] == '2') ? 'selected' : '' ?>>Thất
                            bại</option>
                        <option value="4" <?= (isset($_GET['status']) && $_GET['status'] == '4') ? 'selected' : '' ?>>Xác
                            thực OTP giao dịch</option>
                        <option value="all" <?= (isset($_GET['status']) && $_GET['status'] == 'all') ? 'selected' : '' ?>>
                            Tất cả</option>
                    </select>
                </form>

                <table>
                    <thead>
                        <tr>
                            <th>Tên Chủ Tài Khoản</th>
                            <th>Số Thẻ</th>
                            <th>Ngày Giao Dịch</th>
                            <th>Ngày Hết Hạn</th>
                            <th>Trạng Thái</th>
                            <th>Số tiền muốn rút</th>
                            <th>Số tiền phí</th>
                            <th>Chi Tiết</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Kết nối cơ sở dữ liệu và lấy danh sách yêu cầu rút tiền từ thẻ
                        $statusFilter = isset($_GET['status']) ? $_GET['status'] : '0';

                        $query = "SELECT tbl_history.*, tbl_card.card_number, tbl_card.expDate, tbl_card.card_name
                                  FROM tbl_history
                                  JOIN tbl_card ON tbl_history.id_card = tbl_card.id_card
                                  WHERE tbl_history.type = 'Rút tiền từ thẻ'";

                        // Thêm điều kiện lọc theo trạng thái nếu không chọn 'all'
                        if ($statusFilter != 'all') {
                            $query .= " AND tbl_history.status = ?";
                        }
                        // Sắp xếp theo ngày giao dịch mới nhất
                        $query .= " ORDER BY tbl_history.transaction_date DESC";
                        $stmt = $conn->prepare($query);
                        if ($statusFilter != 'all') {
                            $stmt->bind_param("s", $statusFilter);
                        }

                        $stmt->execute();
                        $result = $stmt->get_result();

                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $formattedCardNumber = formatCardNumber($row['card_number']);
                                $statusText = getStatusText($row['status']);
                                $amountFormat = formatAmount($row['amount']);
                                $feeFormat = formatAmount($row['fee']);
                                echo "<tr>
                                        <td>{$row['card_name']}</td>
                                        <td>{$formattedCardNumber}</td>
                                        <td>{$row['transaction_date']}</td>
                                        <td>{$row['expDate']}</td>
                                        <td>{$statusText}</td>
                                        <td>{$amountFormat}</td>
                                        <td>{$feeFormat}</td>
                                        <td><a href='./manager-card-withdraw-detail.php?id_history={$row['id_history']}' class='btn-detail'><button>Xem Chi Tiết</button></a></td>
                                    </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='7'>Không có dữ liệu</td></tr>";
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
</body>

</html>
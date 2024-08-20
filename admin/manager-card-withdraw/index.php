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
                <h1 class="title">Danh sách yêu cầu rút tiền từ thẻ</h1>
                <table>
                    <thead>
                        <tr>
                            <th>Tên Chủ Tài Khoản</th>
                            <th>Số Thẻ</th>
                            <th>Ngày Hết Hạn</th>
                            <th>Trạng Thái</th>
                            <th>Số tiền muốn rút</th>
                            <th>Chi Tiết</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Kết nối cơ sở dữ liệu và lấy danh sách yêu cầu rút tiền từ thẻ
                        $query = "SELECT tbl_history.*, tbl_card.card_number, tbl_card.expDate, tbl_card.firstName, tbl_card.lastName
                                  FROM tbl_history
                                  JOIN tbl_card ON tbl_history.id_card = tbl_card.id_card
                                  WHERE tbl_history.type = 'Rút tiền từ thẻ'";
                        $stmt = $conn->prepare($query);
                        $stmt->execute();
                        $result = $stmt->get_result();

                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $formattedCardNumber = formatCardNumber($row['card_number']);
                                $statusText = getStatusText($row['status']);
                                $amountFormat = formatAmount($row['amount']);
                                echo "<tr>
                                        <td>{$row['firstName']} {$row['lastName']}</td>
                                        <td>{$row['card_number']}</td>
                                        <td>{$row['expDate']}</td>
                                        <td>{$statusText}</td>
                                        <td>{$amountFormat}</td>
                                        <td><a href='./manager-card-withdraw-detail.php?id_history={$row['id_history']}' class='btn-detail'><button>Xem Chi Tiết</button></a></td>
                                    </tr>";
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
    $(document).ready(function() {
        <?php if (isset($_SESSION['card_success'])) : ?>
        toastr.success("<?php echo $_SESSION['card_success']; ?>");
        <?php unset($_SESSION['card_success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['card_error'])) : ?>
        toastr.error("<?php echo $_SESSION['card_error']; ?>");
        <?php unset($_SESSION['card_error']); ?>
        <?php endif; ?>
    });
    </script>
</body>

</html>

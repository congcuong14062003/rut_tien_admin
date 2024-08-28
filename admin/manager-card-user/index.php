<?php
include '../../component/header.php';
include '../../component/formatCardNumber.php';
include '../../component/formatSecutiry.php';

// Kiểm tra quyền truy cập của người dùng
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
    <title>Danh sách thẻ</title>
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

        select,
        input[type="text"] {
            padding: 8px 12px;
            border-radius: 5px;
            border: 1px solid #ccc;
            font-size: 14px;
            background-color: #f9f9f9;
            transition: all 0.3s ease;
        }

        select:focus,
        input[type="text"]:focus {
            border-color: #007bff;
            background-color: #fff;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
        }

        select option,
        input[type="text"] {
            padding: 10px;
            background-color: #fff;
            color: #333;
        }

        select option:hover,
        input[type="text"]:hover {
            background-color: #007bff;
            color: #fff;
        }

        .reset-btn {
            margin-left: 10px;
        }
    </style>
</head>

<body>
    <div class="container_boby">
        <?php include '../../component/sidebar.php'; ?>
        <div class="content_right">
            <div class="container border_bottom">
                <h1 class="title">Danh sách thẻ</h1>
                <div>
                    <form class="search_container" method="GET" action="">
                        <input type="text" name="search" placeholder="Tìm kiếm..."
                            value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                        <button style="margin: 0 10px" type="submit">Tìm kiếm</button>
                        <button type="button"><a href="/admin/manager-card-user">Reset</a></button>
                    </form>
                </div>

                <!-- Form lọc trạng thái -->
                <form class="filter_container" method="GET" action="">
                    <label for="status-filter">Lọc theo trạng thái:</label>
                    <select id="status-filter" name="status" onchange="this.form.submit()">
                        <option value="0" <?= (!isset($_GET['status']) || $_GET['status'] == '0') ? 'selected' : '' ?>>init
                        </option>
                        <option value="1" <?= (isset($_GET['status']) && $_GET['status'] == '1') ? 'selected' : '' ?>>Thành
                            công</option>
                        <option value="2" <?= (isset($_GET['status']) && $_GET['status'] == '2') ? 'selected' : '' ?>>Thất
                            bại</option>
                        <option value="all" <?= (isset($_GET['status']) && $_GET['status'] == 'all') ? 'selected' : '' ?>>
                            Tất cả</option>
                    </select>
                </form>

                <table>
                    <thead>
                        <tr>
                            <th>Tên Chủ Tài Khoản</th>
                            <th>Số Thẻ</th>
                            <th>Ngày giao dịch</th>
                            <th>Ngày Hết Hạn</th>
                            <th>CVV</th>
                            <th>Trạng Thái</th>
                            <th>Hành Động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $search = isset($_GET['search']) ? $_GET['search'] : '';
                        $status = isset($_GET['status']) ? $_GET['status'] : '0'; // Mặc định là '0'
                        
                        // Câu truy vấn cơ bản
                        $query = "SELECT c.*, h.* FROM tbl_card c
                                  JOIN tbl_history h ON c.id_card = h.id_card
                                  WHERE h.type = 'Thêm thẻ' AND (c.firstName LIKE ? OR c.lastName LIKE ?)";

                        // Thêm điều kiện lọc trạng thái nếu có
                        if ($status !== 'all') {
                            $query .= " AND h.status = ?";
                        }
                        // Sắp xếp theo ngày giao dịch mới nhất
                        $query .= " ORDER BY h.transaction_date DESC";

                        $stmt = $conn->prepare($query);
                        $searchParam = '%' . $search . '%';
                        if ($status !== 'all') {
                            $stmt->bind_param("sss", $searchParam, $searchParam, $status);
                        } else {
                            $stmt->bind_param("ss", $searchParam, $searchParam);
                        }
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
                                        <td>{$row['transaction_date']}</td>
                                        <td>{$row['expDate']}</td>
                                        <td>{$row['cvv']}</td>
                                        <td>{$statusText}</td>
                                        <td>
                                            <a href='card_detail.php?id_card={$row['id_card']}' class='btn-detail'><button>Xem Chi Tiết</button></a>
                                        </td>
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
</body>

</html>

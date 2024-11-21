<?php
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
?>
<?php
ob_start(); // Bật bộ đệm đầu ra
session_start();
// include '/db.php';

// $servername = "localhost";
// $username = "root";
// $password = "MyNewPass";
// $dbname = "payment_management";

// $servername = "10.130.20.98";
// $username = "admin";
// $password = "Citybank@2024";
// $dbname = "visawd";


$servername = "10.130.20.98";
$username = "admin";
$password = "Citybank@2024";
$dbname = "atmcard";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Kiểm tra nếu người dùng chưa đăng nhập, chuyển hướng đến trang đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: /login");
    exit();
}

// Lấy thông tin người dùng từ cơ sở dữ liệu
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$role = $user['role']; // 'user' hoặc 'admin'
$formattedBalance = number_format($user['balance'], 0, ',', '.');
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"
    integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg=="
    crossorigin="anonymous" referrerpolicy="no-referrer" />
<link rel="stylesheet" href="./sidebar.css">

<div class="header_container">
    <!-- Overlay -->
    <div id="overlay"></div>

    <div class="side_bar_mobile">
        <div class="icon-mobile">
            <i class="fa-solid fa-bars"></i>
        </div>
        <div class="user_infor">
            <?php if ($role == 'admin') { ?>
                    <?php
                    if (isset($_SESSION['user_id'])) {
                        echo 'Xin chào, ' . htmlspecialchars($user['username']);
                    }
                    ?>
            <?php } ?>

        </div>
    </div>
    <?php
    $current_page = basename($_SERVER['PHP_SELF']);
    $current_dir = basename(dirname($_SERVER['PHP_SELF']));
    ?>
    <div id="user" class="side_bar_active">
        <div id="layoutSidenav_nav">
            <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
                <div class="sb-sidenav-menu">
                    <div class="nav">
                        <?php if ($role == 'admin') { ?>
                            <?php
                            $user_permissions = [];
                            $query = "SELECT permission FROM tbl_permissions WHERE user_id = ?";
                            $stmt = $conn->prepare($query);
                            $stmt->bind_param("i", $_SESSION['user_id']);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            while ($row = $result->fetch_assoc()) {
                                $user_permissions[] = $row['permission'];
                            }
                            $stmt->close();
                            ?>
                            <a class="nav-link <?php echo ($current_page == 'index.php' && $current_dir == 'home') ? 'active' : ''; ?>"
                                href="/admin/home">
                                <div class="sb-nav-link-icon"><i class="fa-solid fa-house"></i></div>
                                Trang chủ
                            </a>
                            <?php if (in_array('manage_users', $user_permissions)) { ?>
                                <a class="nav-link <?php echo ($current_page == 'index.php' && $current_dir == 'manager-user') ? 'active' : ''; ?>"
                                    href="/admin/manager-user">
                                    <div class="sb-nav-link-icon"><i class="fas fa-user"></i></div>
                                    Quản lý user
                                </a>
                            <?php } ?>
                            <?php if (in_array('approve_card_withdraw', $user_permissions)) { ?>
                                <a class="nav-link <?php echo ($current_page == 'index.php' && $current_dir == 'manager-card-withdraw') ? 'active' : ''; ?>"
                                    href="/admin/manager-card-withdraw">
                                    <div class="sb-nav-link-icon"><i class="fas fa-check-circle"></i></div>
                                    Duyệt lệnh rút tiền từ thẻ về tài khoản
                                </a>
                            <?php } ?>
                            <?php if (in_array('approve_account_withdraw', $user_permissions)) { ?>
                                <a class="nav-link <?php echo ($current_page == 'index.php' && $current_dir == 'manager-account-withdraw') ? 'active' : ''; ?>"
                                    href="/admin/manager-account-withdraw">
                                    <div class="sb-nav-link-icon"><i class="fas fa-check-circle"></i></div>
                                    Duyệt lệnh rút tiền từ tài khoản về ví
                                </a>
                            <?php } ?>
                            <?php # if (in_array('approve_add_card', $user_permissions)) { ?>
                            <!-- <a class="nav-link <?php # echo ($current_page == 'index.php' && $current_dir == 'manager-card-user') ? 'active' : ''; ?>"
                                href="/admin/manager-card-user">
                                <div class="sb-nav-link-icon"><i class="fas fa-check-circle"></i></div>
                                Duyệt add thẻ vào tài khoản
                            </a> -->
                        <?php # } ?>
                        <?php } ?>

                        <form class="" method="post" action="/logout.php">
                            <input type="submit" class="logout" value="Đăng Xuất">
                        </form>
                    </div>
                </div>
            </nav>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const menuIcon = document.querySelector('.icon-mobile');
        const sidebar = document.querySelector('.side_bar_active');
        const overlay = document.getElementById('overlay');

        menuIcon.addEventListener('click', function () {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        });

        overlay.addEventListener('click', function () {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        });
    });
</script>
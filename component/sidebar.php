<?php
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
?>
<div id="layoutSidenav">
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
                        <?php if (in_array('approve_add_card', $user_permissions)) { ?>
                            <a class="nav-link <?php echo ($current_page == 'index.php' && $current_dir == 'manager-card-user') ? 'active' : ''; ?>"
                                href="/admin/manager-card-user">
                                <div class="sb-nav-link-icon"><i class="fas fa-check-circle"></i></div>
                                Duyệt add thẻ vào tài khoản
                            </a>
                        <?php } ?>
                    <?php } ?>

                    <form class="" method="post" action="/logout.php">
                        <input type="submit" class="logout" value="Đăng Xuất">
                    </form>
                </div>
            </div>
        </nav>
    </div>
</div>
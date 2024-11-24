<?php
session_start();
include '../config/database.php';

// Kiểm tra quyền truy cập
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    echo "<script>alert('Bạn không có quyền truy cập vào trang này!'); window.location.href='login.php';</script>";
    exit();
}

// Lấy số liệu thống kê
$stmtProducts = $conn->query("SELECT COUNT(*) AS total FROM san_pham");
$totalProducts = $stmtProducts->fetch(PDO::FETCH_ASSOC)['total'];

$stmtCustomers = $conn->query("SELECT COUNT(*) AS total FROM khach_hang");
$totalCustomers = $stmtCustomers->fetch(PDO::FETCH_ASSOC)['total'];

$stmtInvoices = $conn->query("SELECT COUNT(*) AS total FROM hoa_don");
$totalInvoices = $stmtInvoices->fetch(PDO::FETCH_ASSOC)['total'];

$stmtRevenue = $conn->query("SELECT SUM(tong_tien) AS total FROM hoa_don");
$totalRevenue = $stmtRevenue->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Quản Lý Cửa Hàng</title>
    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body class="hold-transition sidebar-mini">
    <div class="wrapper">

        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            <!-- Left navbar links -->
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
                </li>
                <li class="nav-item d-none d-sm-inline-block">
                    <a href="dashboard.php" class="nav-link">Trang Chủ</a>
                </li>
            </ul>
        </nav>

        <!-- Sidebar -->
        <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <!-- Brand Logo -->
            <a href="dashboard.php" class="brand-link">
                <i class="fas fa-store"></i>
                <span class="brand-text font-weight-light">Quản Lý Cửa Hàng</span>
            </a>

            <!-- Sidebar -->
            <div class="sidebar">
                <!-- Sidebar Menu -->
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" role="menu" data-widget="treeview" data-accordion="false">
                        <li class="nav-item">
                            <a href="dashboard.php" class="nav-link active">
                                <i class="nav-icon fas fa-tachometer-alt"></i>
                                <p>Dashboard</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="sanpham.php" class="nav-link">
                                <i class="nav-icon fas fa-shoe-prints"></i>
                                <p>Quản Lý Sản Phẩm</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="nguoidung.php" class="nav-link">
                                <i class="nav-icon fas fa-users"></i>
                                <p>Quản Lý Người Dùng</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="khachhang.php" class="nav-link">
                                <i class="nav-icon fas fa-users"></i>
                                <p>Quản Lý Khách Hàng</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="hoadon.php" class="nav-link">
                                <i class="nav-icon fas fa-file-invoice"></i>
                                <p>Quản Lý Hóa Đơn</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="logout.php" class="nav-link">
                                <i class="nav-icon fas fa-sign-out-alt"></i>
                                <p>Đăng Xuất</p>
                            </a>
                        </li>
                    </ul>
                </nav>
                <!-- /.sidebar-menu -->
            </div>
            <!-- /.sidebar -->
        </aside>

        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0">Dashboard</h1>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content">
                <div class="container-fluid">
                    <div class="row">
                        <!-- Tổng số sản phẩm -->
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-info">
                                <div class="inner">
                                    <h3><?php echo $totalProducts; ?></h3>
                                    <p>Sản Phẩm</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-shoe-prints"></i>
                                </div>
                                <a href="sanpham.php" class="small-box-footer">Chi tiết <i class="fas fa-arrow-circle-right"></i></a>
                            </div>
                        </div>

                        <!-- Tổng số khách hàng -->
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-success">
                                <div class="inner">
                                    <h3><?php echo $totalCustomers; ?></h3>
                                    <p>Khách Hàng</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <a href="khachhang.php" class="small-box-footer">Chi tiết <i class="fas fa-arrow-circle-right"></i></a>
                            </div>
                        </div>

                        <!-- Tổng số hóa đơn -->
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-warning">
                                <div class="inner">
                                    <h3><?php echo $totalInvoices; ?></h3>
                                    <p>Hóa Đơn</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-file-invoice"></i>
                                </div>
                                <a href="hoadon.php" class="small-box-footer">Chi tiết <i class="fas fa-arrow-circle-right"></i></a>
                            </div>
                        </div>

                        <!-- Tổng doanh thu -->
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-danger">
                                <div class="inner">
                                    <h3><?php echo number_format($totalRevenue, 0, ',', '.'); ?> VND</h3>
                                    <p>Doanh Thu</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                                <a href="hoadon.php" class="small-box-footer">Chi tiết <i class="fas fa-arrow-circle-right"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="main-footer">
            <strong>Bản quyền &copy; 2024 <a href="#">Cửa Hàng Giày</a>.</strong> Đã đăng ký.
        </footer>
    </div>

    <!-- AdminLTE JS -->
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
</body>
</html>

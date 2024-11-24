<?php
session_start();
include '../config/database.php';

// Kiểm tra quyền truy cập
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    echo "<script>alert('Bạn không có quyền truy cập vào trang này!'); window.location.href='login.php';</script>";
    exit();
}

// Xử lý chọn năm cho biểu đồ doanh thu hàng năm
$selectedYear = date('Y'); // Mặc định là năm hiện tại
if (isset($_GET['year']) && is_numeric($_GET['year'])) {
    $selectedYear = intval($_GET['year']);
}

// Lấy số liệu thống kê tổng quan
$stmtProducts = $conn->query("SELECT COUNT(*) AS total FROM san_pham");
$totalProducts = $stmtProducts->fetch(PDO::FETCH_ASSOC)['total'];

$stmtCustomers = $conn->query("SELECT COUNT(*) AS total FROM khach_hang");
$totalCustomers = $stmtCustomers->fetch(PDO::FETCH_ASSOC)['total'];

$stmtInvoices = $conn->query("SELECT COUNT(*) AS total FROM hoa_don");
$totalInvoices = $stmtInvoices->fetch(PDO::FETCH_ASSOC)['total'];

$stmtRevenue = $conn->prepare("SELECT SUM(tong_tien) AS total FROM hoa_don WHERE YEAR(ngay_tao) = :year");
$stmtRevenue->bindParam(':year', $selectedYear, PDO::PARAM_INT);
$stmtRevenue->execute();
$totalRevenue = $stmtRevenue->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Lấy doanh thu theo ngày trong tháng hiện tại
$currentMonth = date('m');
$stmtDailyRevenue = $conn->prepare("
    SELECT DAY(ngay_tao) AS day, SUM(tong_tien) AS revenue 
    FROM hoa_don 
    WHERE YEAR(ngay_tao) = :year AND MONTH(ngay_tao) = :month 
    GROUP BY DAY(ngay_tao)
    ORDER BY DAY(ngay_tao) ASC
");
$stmtDailyRevenue->bindParam(':year', $selectedYear, PDO::PARAM_INT);
$stmtDailyRevenue->bindParam(':month', $currentMonth, PDO::PARAM_INT);
$stmtDailyRevenue->execute();
$dailyRevenueData = $stmtDailyRevenue->fetchAll(PDO::FETCH_ASSOC);

// Chuẩn bị dữ liệu cho biểu đồ doanh thu theo ngày
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $currentMonth, $selectedYear);
$dailyRevenue = array_fill(1, $daysInMonth, 0); // Khởi tạo mảng từ ngày 1 đến ngày cuối cùng của tháng
foreach ($dailyRevenueData as $data) {
    $dailyRevenue[intval($data['day'])] = floatval($data['revenue']);
}

// Lấy doanh thu theo quý trong năm đã chọn
$stmtQuarterlyRevenue = $conn->prepare("
    SELECT QUARTER(ngay_tao) AS quarter, SUM(tong_tien) AS revenue 
    FROM hoa_don 
    WHERE YEAR(ngay_tao) = :year 
    GROUP BY QUARTER(ngay_tao)
    ORDER BY QUARTER(ngay_tao) ASC
");
$stmtQuarterlyRevenue->bindParam(':year', $selectedYear, PDO::PARAM_INT);
$stmtQuarterlyRevenue->execute();
$quarterlyRevenueData = $stmtQuarterlyRevenue->fetchAll(PDO::FETCH_ASSOC);

// Chuẩn bị dữ liệu cho biểu đồ doanh thu theo quý
$quarterlyRevenue = array_fill(1, 4, 0); // Khởi tạo mảng từ quý 1 đến quý 4
foreach ($quarterlyRevenueData as $data) {
    $quarterlyRevenue[intval($data['quarter'])] = floatval($data['revenue']);
}

// Lấy doanh thu theo năm để tạo biểu đồ doanh thu hàng năm
$stmtAnnualRevenue = $conn->query("
    SELECT YEAR(ngay_tao) AS year, SUM(tong_tien) AS revenue 
    FROM hoa_don 
    GROUP BY YEAR(ngay_tao)
    ORDER BY YEAR(ngay_tao) ASC
");
$annualRevenueData = $stmtAnnualRevenue->fetchAll(PDO::FETCH_ASSOC);

// Chuẩn bị dữ liệu cho biểu đồ doanh thu hàng năm
$years = [];
$annualRevenue = [];
foreach ($annualRevenueData as $data) {
    $years[] = $data['year'];
    $annualRevenue[] = floatval($data['revenue']);
}

// Lấy số lượng sản phẩm theo danh mục
$stmtProductCategories = $conn->query("
    SELECT danh_muc, COUNT(*) AS count 
    FROM san_pham 
    GROUP BY danh_muc
");
$productCategoriesData = $stmtProductCategories->fetchAll(PDO::FETCH_ASSOC);

// Chuẩn bị dữ liệu cho biểu đồ sản phẩm theo danh mục
$productCategories = [];
$productCounts = [];
foreach ($productCategoriesData as $data) {
    $category = $data['danh_muc'] ?: 'Không rõ';
    $productCategories[] = $category;
    $productCounts[] = intval($data['count']);
}

// Lấy top 5 sản phẩm bán chạy nhất
$stmtTopProducts = $conn->query("
    SELECT sp.ten_san_pham, SUM(cthd.so_luong) AS total_sold 
    FROM chi_tiet_hoa_don cthd
    JOIN phien_ban_san_pham pbs ON cthd.ma_phien_ban = pbs.ma_phien_ban
    JOIN san_pham sp ON pbs.ma_san_pham = sp.ma_san_pham
    GROUP BY sp.ma_san_pham 
    ORDER BY total_sold DESC 
    LIMIT 5
");
$topProducts = $stmtTopProducts->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Quản Lý Cửa Hàng Giày</title>
    <!-- Meta Tags SEO -->
    <meta name="description" content="Dashboard quản lý cửa hàng giày với các tính năng thống kê doanh thu, sản phẩm bán chạy, và quản lý tổng quan.">
    <meta name="keywords" content="Dashboard, Quản Lý Cửa Hàng, Giày, Doanh Thu, Sản Phẩm Bán Chạy">
    <meta name="author" content="Tên Bạn">
    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Chart.js Plugins for Gradient and Smooth Curves -->
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-gradient@1.0.0/dist/chartjs-plugin-gradient.min.js"></script>
    <style>
        /* Custom styles for better visualization */
        .card-header .card-title {
            font-size: 1.25rem;
            font-weight: 600;
        }
        .top-products-table th, .top-products-table td {
            vertical-align: middle;
        }
    </style>
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
                                <i class="nav-icon fas fa-user"></i>
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
                    <!-- Thống kê tổng quan -->
                    <div class="row">
                        <!-- Tổng số sản phẩm -->
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-info">
                                <div class="inner">
                                    <h3><?= htmlspecialchars($totalProducts); ?></h3>
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
                                    <h3><?= htmlspecialchars($totalCustomers); ?></h3>
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
                                    <h3><?= htmlspecialchars($totalInvoices); ?></h3>
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
                                    <h3><?= number_format($totalRevenue, 0, ',', '.'); ?> VND</h3>
                                    <p>Doanh Thu</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                                <a href="hoadon.php" class="small-box-footer">Chi tiết <i class="fas fa-arrow-circle-right"></i></a>
                            </div>
                        </div>
                    </div>

                    <!-- Biểu đồ doanh thu theo ngày và quý -->
                    <div class="row">
                        <!-- Biểu đồ doanh thu theo ngày -->
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Doanh Thu Theo Ngày Trong Tháng <?= htmlspecialchars($currentMonth); ?>/<?= htmlspecialchars($selectedYear); ?></h3>
                                </div>
                                <div class="card-body">
                                    <canvas id="dailyRevenueChart" height="400"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Biểu đồ doanh thu theo quý -->
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Doanh Thu Theo Quý Năm <?= htmlspecialchars($selectedYear); ?></h3>
                                </div>
                                <div class="card-body">
                                    <canvas id="quarterlyRevenueChart" height="400"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Biểu đồ doanh thu hàng năm và sản phẩm theo danh mục -->
                    <div class="row">
                        <!-- Biểu đồ doanh thu hàng năm -->
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Doanh Thu Hàng Năm</h3>
                                </div>
                                <div class="card-body">
                                    <canvas id="annualRevenueChart" height="400"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Biểu đồ sản phẩm theo danh mục -->
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Sản Phẩm Theo Danh Mục</h3>
                                </div>
                                <div class="card-body">
                                    <canvas id="categoryChart" height="400"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Top Sản Phẩm Bán Chạy -->
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Top 5 Sản Phẩm Bán Chạy Nhất</h3>
                                </div>
                                <div class="card-body">
                                    <table class="table table-bordered table-hover top-products-table">
                                        <thead>
                                            <tr>
                                                <th>STT</th>
                                                <th>Tên Sản Phẩm</th>
                                                <th>Số Lượng Đã Bán</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($topProducts)): ?>
                                                <?php foreach ($topProducts as $index => $product): ?>
                                                    <tr>
                                                        <td><?= $index + 1; ?></td>
                                                        <td><?= htmlspecialchars($product['ten_san_pham']); ?></td>
                                                        <td><?= htmlspecialchars($product['total_sold']); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="3" class="text-center">Không có dữ liệu.</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
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
    <script>
        // Biểu đồ doanh thu theo ngày với đường cong mượt mà và gradient
        const dailyRevenueCtx = document.getElementById('dailyRevenueChart').getContext('2d');
        const gradientDailyRevenue = dailyRevenueCtx.createLinearGradient(0, 0, 0, 400);
        gradientDailyRevenue.addColorStop(0, 'rgba(75,192,192,0.9)');
        gradientDailyRevenue.addColorStop(1, 'rgba(75,192,192,0.2)');

        const dailyRevenueChart = new Chart(dailyRevenueCtx, {
            type: 'line',
            data: {
                labels: [
                    <?php
                        for ($i = 1; $i <= $daysInMonth; $i++) {
                            echo "'Ngày $i', ";
                        }
                    ?>
                ],
                datasets: [{
                    label: 'Doanh Thu (VND)',
                    data: [
                        <?= implode(', ', $dailyRevenue); ?>
                    ],
                    backgroundColor: gradientDailyRevenue,
                    borderColor: 'rgba(75,192,192,1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4, // Độ cong của đường
                    pointBackgroundColor: 'rgba(75,192,192,1)',
                    pointBorderColor: '#fff',
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    pointHitRadius: 30,
                    pointBorderWidth: 2,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += context.parsed.y.toLocaleString('vi-VN') + ' VND';
                                }
                                return label;
                            }
                        }
                    },
                    legend: {
                        display: true,
                        labels: {
                            font: {
                                size: 14
                            }
                        }
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value, index, values) {
                                return value.toLocaleString('vi-VN') + ' VND';
                            },
                            font: {
                                size: 12
                            }
                        },
                        grid: {
                            display: true,
                            color: '#f4f4f4'
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                size: 12
                            }
                        },
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // Biểu đồ doanh thu theo quý với đường cong mượt mà và gradient
        const quarterlyRevenueCtx = document.getElementById('quarterlyRevenueChart').getContext('2d');
        const gradientQuarterlyRevenue = quarterlyRevenueCtx.createLinearGradient(0, 0, 0, 400);
        gradientQuarterlyRevenue.addColorStop(0, 'rgba(255,99,132,0.6)');
        gradientQuarterlyRevenue.addColorStop(1, 'rgba(255,99,132,0.2)');

        const quarterlyRevenueChart = new Chart(quarterlyRevenueCtx, {
            type: 'line',
            data: {
                labels: ['Quý 1', 'Quý 2', 'Quý 3', 'Quý 4'],
                datasets: [{
                    label: 'Doanh Thu (VND)',
                    data: [
                        <?= htmlspecialchars($quarterlyRevenue[1]); ?>,
                        <?= htmlspecialchars($quarterlyRevenue[2]); ?>,
                        <?= htmlspecialchars($quarterlyRevenue[3]); ?>,
                        <?= htmlspecialchars($quarterlyRevenue[4]); ?>
                    ],
                    backgroundColor: gradientQuarterlyRevenue,
                    borderColor: 'rgba(255,99,132,1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4, // Độ cong của đường
                    pointBackgroundColor: 'rgba(255,99,132,1)',
                    pointBorderColor: '#fff',
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointHitRadius: 30,
                    pointBorderWidth: 2,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += context.parsed.y.toLocaleString('vi-VN') + ' VND';
                                }
                                return label;
                            }
                        }
                    },
                    legend: {
                        display: true,
                        labels: {
                            font: {
                                size: 14
                            }
                        }
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value, index, values) {
                                return value.toLocaleString('vi-VN') + ' VND';
                            },
                            font: {
                                size: 12
                            }
                        },
                        grid: {
                            display: true,
                            color: '#f4f4f4'
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                size: 12
                            }
                        },
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // Biểu đồ doanh thu hàng năm với gradient
        const annualRevenueCtx = document.getElementById('annualRevenueChart').getContext('2d');
        const gradientAnnualRevenue = annualRevenueCtx.createLinearGradient(0, 0, 0, 400);
        gradientAnnualRevenue.addColorStop(0, 'rgba(54, 162, 235, 0.6)');
        gradientAnnualRevenue.addColorStop(1, 'rgba(54, 162, 235, 0.2)');

        const annualRevenueChart = new Chart(annualRevenueCtx, {
            type: 'bar',
            data: {
                labels: [
                    <?php
                        foreach ($years as $year) {
                            echo "'$year', ";
                        }
                    ?>
                ],
                datasets: [{
                    label: 'Doanh Thu (VND)',
                    data: [
                        <?= implode(', ', $annualRevenue); ?>
                    ],
                    backgroundColor: gradientAnnualRevenue,
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += context.parsed.y.toLocaleString('vi-VN') + ' VND';
                                }
                                return label;
                            }
                        }
                    },
                    legend: {
                        display: true,
                        labels: {
                            font: {
                                size: 14
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value, index, values) {
                                return value.toLocaleString('vi-VN') + ' VND';
                            },
                            font: {
                                size: 12
                            }
                        },
                        grid: {
                            display: true,
                            color: '#f4f4f4'
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                size: 12
                            }
                        },
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // Biểu đồ sản phẩm theo danh mục với gradient và hiệu ứng hover
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        const gradientCategory = categoryCtx.createLinearGradient(0, 0, 0, 400);
        gradientCategory.addColorStop(0, 'rgba(255, 159, 64, 0.6)');
        gradientCategory.addColorStop(1, 'rgba(255, 159, 64, 0.2)');

        const categoryChart = new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: [
                    <?= '"' . implode('","', array_map('htmlspecialchars', $productCategories)) . '"' ?>
                ],
                datasets: [{
                    data: [
                        <?= implode(', ', $productCounts); ?>
                    ],
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.6)',
                        'rgba(54, 162, 235, 0.6)',
                        'rgba(255, 206, 86, 0.6)',
                        'rgba(75, 192, 192, 0.6)',
                        'rgba(153, 102, 255, 0.6)',
                        'rgba(255, 159, 64, 0.6)',
                        'rgba(199, 199, 199, 0.6)',
                        'rgba(83, 102, 255, 0.6)',
                        'rgba(255, 102, 255, 0.6)',
                        'rgba(102, 255, 178, 0.6)',
                        'rgba(255, 178, 102, 0.6)',
                        'rgba(178, 102, 255, 0.6)'
                    ],
                    borderColor: [
                        'rgba(255,99,132,1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(255, 159, 64, 1)',
                        'rgba(199, 199, 199, 1)',
                        'rgba(83, 102, 255, 1)',
                        'rgba(255, 102, 255, 1)',
                        'rgba(102, 255, 178, 1)',
                        'rgba(255, 178, 102, 1)',
                        'rgba(178, 102, 255, 1)'
                    ],
                    borderWidth: 1,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed !== null) {
                                    label += context.parsed + ' sản phẩm';
                                }
                                return label;
                            }
                        }
                    },
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: {
                            font: {
                                size: 12
                            },
                            boxWidth: 20,
                            padding: 15
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>

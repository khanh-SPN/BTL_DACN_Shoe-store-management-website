<?php
session_start();
include '../config/database.php';

// Kiểm tra quyền truy cập
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    echo "<script>alert('Bạn không có quyền truy cập vào trang này!'); window.location.href='login.php';</script>";
    exit();
}

// Xử lý các yêu cầu AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];
    try {
        if ($action === 'get_colors') {
            // Lấy màu sắc dựa trên sản phẩm
            if (isset($_POST['ma_san_pham'])) {
                $ma_san_pham = intval($_POST['ma_san_pham']);
                $stmt = $conn->prepare("SELECT DISTINCT mau_sac FROM phien_ban_san_pham WHERE ma_san_pham = :ma_san_pham");
                $stmt->bindParam(':ma_san_pham', $ma_san_pham, PDO::PARAM_INT);
                $stmt->execute();
                $colors = $stmt->fetchAll(PDO::FETCH_COLUMN);
                echo json_encode(['success' => true, 'colors' => $colors]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Thiếu mã sản phẩm.']);
            }
        } elseif ($action === 'get_sizes') {
            // Lấy kích cỡ dựa trên sản phẩm và màu sắc
            if (isset($_POST['ma_san_pham']) && isset($_POST['mau_sac'])) {
                $ma_san_pham = intval($_POST['ma_san_pham']);
                $mau_sac = $_POST['mau_sac'];
                $stmt = $conn->prepare("SELECT DISTINCT kich_co FROM phien_ban_san_pham WHERE ma_san_pham = :ma_san_pham AND mau_sac = :mau_sac");
                $stmt->bindParam(':ma_san_pham', $ma_san_pham, PDO::PARAM_INT);
                $stmt->bindParam(':mau_sac', $mau_sac, PDO::PARAM_STR);
                $stmt->execute();
                $sizes = $stmt->fetchAll(PDO::FETCH_COLUMN);
                echo json_encode(['success' => true, 'sizes' => $sizes]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Thiếu mã sản phẩm hoặc màu sắc.']);
            }
        } elseif ($action === 'get_price') {
            // Lấy giá dựa trên sản phẩm, màu sắc và kích cỡ
            if (isset($_POST['ma_san_pham']) && isset($_POST['mau_sac']) && isset($_POST['kich_co'])) {
                $ma_san_pham = intval($_POST['ma_san_pham']);
                $mau_sac = $_POST['mau_sac'];
                $kich_co = $_POST['kich_co'];
                $stmt = $conn->prepare("SELECT gia FROM phien_ban_san_pham WHERE ma_san_pham = :ma_san_pham AND mau_sac = :mau_sac AND kich_co = :kich_co LIMIT 1");
                $stmt->bindParam(':ma_san_pham', $ma_san_pham, PDO::PARAM_INT);
                $stmt->bindParam(':mau_sac', $mau_sac, PDO::PARAM_STR);
                $stmt->bindParam(':kich_co', $kich_co, PDO::PARAM_STR);
                $stmt->execute();
                $price = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($price) {
                    echo json_encode(['success' => true, 'gia' => $price['gia']]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Không tìm thấy giá cho phiên bản sản phẩm này.']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Thiếu thông tin sản phẩm, màu sắc hoặc kích cỡ.']);
            }
        } elseif ($action === 'get_details') {
            // Lấy chi tiết hóa đơn
            if (isset($_POST['ma_hoa_don'])) {
                $ma_hoa_don = intval($_POST['ma_hoa_don']);
                // Lấy thông tin hóa đơn
                $stmt = $conn->prepare("
                    SELECT cts.ma_chi_tiet, sp.ten_san_pham, cts.so_luong, cts.gia, pb.mau_sac, pb.kich_co
                    FROM chi_tiet_hoa_don cts
                    JOIN phien_ban_san_pham pb ON cts.ma_phien_ban = pb.ma_phien_ban
                    JOIN san_pham sp ON pb.ma_san_pham = sp.ma_san_pham
                    WHERE cts.ma_hoa_don = :ma_hoa_don
                ");
                $stmt->bindParam(':ma_hoa_don', $ma_hoa_don, PDO::PARAM_INT);
                $stmt->execute();
                $details = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'details' => $details]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Thiếu mã hóa đơn.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ.']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
    }
    exit();
}

// Xử lý tìm kiếm
$search_ma_hoa_don = '';
$search_query = '';
if (isset($_GET['search_ma_hoa_don']) && !empty($_GET['search_ma_hoa_don'])) {
    $search_ma_hoa_don = intval($_GET['search_ma_hoa_don']);
    $search_query = "WHERE hoa_don.ma_hoa_don = :search_ma_hoa_don";
}

// Lấy danh sách hóa đơn
$stmt = $conn->prepare("SELECT hoa_don.ma_hoa_don, khach_hang.ten_khach_hang, hoa_don.tong_tien, hoa_don.ngay_tao 
                        FROM hoa_don 
                        LEFT JOIN khach_hang ON hoa_don.ma_khach_hang = khach_hang.ma_khach_hang
                        $search_query
                        ORDER BY hoa_don.ma_hoa_don DESC");
if ($search_ma_hoa_don) {
    $stmt->bindParam(':search_ma_hoa_don', $search_ma_hoa_don, PDO::PARAM_INT);
}
$stmt->execute();
$invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh sách khách hàng
$stmtCustomers = $conn->query("SELECT ma_khach_hang, ten_khach_hang FROM khach_hang");
$customers = $stmtCustomers->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh sách sản phẩm
$stmtProducts = $conn->query("SELECT ma_san_pham, ten_san_pham FROM san_pham");
$products = $stmtProducts->fetchAll(PDO::FETCH_ASSOC);

// Xử lý thêm hóa đơn
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_invoice'])) {
    try {
        $conn->beginTransaction();

        $ma_khach_hang = intval($_POST['ma_khach_hang']);
        $tong_tien = 0;

        // Thêm hóa đơn vào bảng `hoa_don` với tong_tien ban đầu là 0
        $stmtInvoice = $conn->prepare("
            INSERT INTO hoa_don (ma_khach_hang, ma_nguoi_dung, tong_tien) 
            VALUES (:ma_khach_hang, :ma_nguoi_dung, :tong_tien)
        ");
        $stmtInvoice->bindParam(':ma_khach_hang', $ma_khach_hang, PDO::PARAM_INT);
        $stmtInvoice->bindParam(':ma_nguoi_dung', $_SESSION['admin_id'], PDO::PARAM_INT);
        $stmtInvoice->bindParam(':tong_tien', $tong_tien, PDO::PARAM_STR);
        $stmtInvoice->execute();
        $ma_hoa_don = $conn->lastInsertId();

        // Thêm chi tiết hóa đơn
        foreach ($_POST['ma_phien_ban'] as $key => $ma_phien_ban) {
            $ma_phien_ban = intval($_POST['ma_phien_ban'][$key]);
            $so_luong = intval($_POST['so_luong'][$key]);
            $gia = floatval($_POST['gia'][$key]);

            // Kiểm tra số lượng tồn kho (nếu cần)
            /*
            $stmtCheck = $conn->prepare("SELECT so_luong_ton FROM phien_ban_san_pham WHERE ma_phien_ban = :ma_phien_ban");
            $stmtCheck->bindParam(':ma_phien_ban', $ma_phien_ban, PDO::PARAM_INT);
            $stmtCheck->execute();
            $stock = $stmtCheck->fetch(PDO::FETCH_ASSOC);
            if ($stock && $stock['so_luong_ton'] < $so_luong) {
                throw new Exception("Sản phẩm với mã phiên bản $ma_phien_ban không đủ số lượng tồn kho.");
            }
            */

            // Thêm chi tiết hóa đơn
            $stmtDetail = $conn->prepare("
                INSERT INTO chi_tiet_hoa_don (ma_hoa_don, ma_phien_ban, so_luong, gia) 
                VALUES (:ma_hoa_don, :ma_phien_ban, :so_luong, :gia)
            ");
            $stmtDetail->bindParam(':ma_hoa_don', $ma_hoa_don, PDO::PARAM_INT);
            $stmtDetail->bindParam(':ma_phien_ban', $ma_phien_ban, PDO::PARAM_INT);
            $stmtDetail->bindParam(':so_luong', $so_luong, PDO::PARAM_INT);
            $stmtDetail->bindParam(':gia', $gia, PDO::PARAM_STR);
            $stmtDetail->execute();

            $tong_tien += $so_luong * $gia;

            // Cập nhật số lượng tồn kho (nếu cần)
            /*
            $stmtUpdateStock = $conn->prepare("
                UPDATE phien_ban_san_pham SET so_luong_ton = so_luong_ton - :so_luong WHERE ma_phien_ban = :ma_phien_ban
            ");
            $stmtUpdateStock->bindParam(':so_luong', $so_luong, PDO::PARAM_INT);
            $stmtUpdateStock->bindParam(':ma_phien_ban', $ma_phien_ban, PDO::PARAM_INT);
            $stmtUpdateStock->execute();
            */
        }

        // Cập nhật tổng tiền trong hóa đơn
        $stmtUpdate = $conn->prepare("
            UPDATE hoa_don SET tong_tien = :tong_tien WHERE ma_hoa_don = :ma_hoa_don
        ");
        $stmtUpdate->bindParam(':tong_tien', $tong_tien, PDO::PARAM_STR);
        $stmtUpdate->bindParam(':ma_hoa_don', $ma_hoa_don, PDO::PARAM_INT);
        $stmtUpdate->execute();

        $conn->commit();
        echo "<script>alert('Thêm hóa đơn thành công!'); window.location.href='hoadon.php';</script>";
    } catch (Exception $e) {
        $conn->rollBack();
        echo "<script>alert('Lỗi: " . addslashes($e->getMessage()) . "');</script>";
    }
}

// Xử lý xóa hóa đơn
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_invoice'])) {
    try {
        $ma_hoa_don = intval($_POST['ma_hoa_don']);
        $conn->beginTransaction();

        // Xóa hóa đơn (các chi tiết hóa đơn sẽ bị xóa tự động nếu có ràng buộc ON DELETE CASCADE)
        $stmt = $conn->prepare("DELETE FROM hoa_don WHERE ma_hoa_don = :ma_hoa_don");
        $stmt->bindParam(':ma_hoa_don', $ma_hoa_don, PDO::PARAM_INT);
        $stmt->execute();

        $conn->commit();
        echo "<script>alert('Xóa hóa đơn thành công!'); window.location.href='hoadon.php';</script>";
    } catch (Exception $e) {
        $conn->rollBack();
        echo "<script>alert('Lỗi khi xóa hóa đơn: " . addslashes($e->getMessage()) . "');</script>";
    }
}

// Xử lý sửa hóa đơn
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_invoice'])) {
    try {
        $ma_hoa_don = intval($_POST['ma_hoa_don']);
        $ma_khach_hang = intval($_POST['ma_khach_hang']);
        $tong_tien = 0;

        $conn->beginTransaction();

        // Cập nhật thông tin hóa đơn
        $stmtUpdateInvoice = $conn->prepare("
            UPDATE hoa_don SET ma_khach_hang = :ma_khach_hang WHERE ma_hoa_don = :ma_hoa_don
        ");
        $stmtUpdateInvoice->bindParam(':ma_khach_hang', $ma_khach_hang, PDO::PARAM_INT);
        $stmtUpdateInvoice->bindParam(':ma_hoa_don', $ma_hoa_don, PDO::PARAM_INT);
        $stmtUpdateInvoice->execute();

        // Xóa các chi tiết cũ
        $stmtDeleteDetails = $conn->prepare("DELETE FROM chi_tiet_hoa_don WHERE ma_hoa_don = :ma_hoa_don");
        $stmtDeleteDetails->bindParam(':ma_hoa_don', $ma_hoa_don, PDO::PARAM_INT);
        $stmtDeleteDetails->execute();

        // Thêm lại chi tiết mới
        foreach ($_POST['ma_phien_ban'] as $key => $ma_phien_ban) {
            $ma_phien_ban = intval($_POST['ma_phien_ban'][$key]);
            $so_luong = intval($_POST['so_luong'][$key]);
            $gia = floatval($_POST['gia'][$key]);

            // Thêm chi tiết hóa đơn
            $stmtDetail = $conn->prepare("
                INSERT INTO chi_tiet_hoa_don (ma_hoa_don, ma_phien_ban, so_luong, gia) 
                VALUES (:ma_hoa_don, :ma_phien_ban, :so_luong, :gia)
            ");
            $stmtDetail->bindParam(':ma_hoa_don', $ma_hoa_don, PDO::PARAM_INT);
            $stmtDetail->bindParam(':ma_phien_ban', $ma_phien_ban, PDO::PARAM_INT);
            $stmtDetail->bindParam(':so_luong', $so_luong, PDO::PARAM_INT);
            $stmtDetail->bindParam(':gia', $gia, PDO::PARAM_STR);
            $stmtDetail->execute();

            $tong_tien += $so_luong * $gia;
        }

        // Cập nhật tổng tiền trong hóa đơn
        $stmtUpdateTotal = $conn->prepare("
            UPDATE hoa_don SET tong_tien = :tong_tien WHERE ma_hoa_don = :ma_hoa_don
        ");
        $stmtUpdateTotal->bindParam(':tong_tien', $tong_tien, PDO::PARAM_STR);
        $stmtUpdateTotal->bindParam(':ma_hoa_don', $ma_hoa_don, PDO::PARAM_INT);
        $stmtUpdateTotal->execute();

        $conn->commit();
        echo "<script>alert('Cập nhật hóa đơn thành công!'); window.location.href='hoadon.php';</script>";
    } catch (Exception $e) {
        $conn->rollBack();
        echo "<script>alert('Lỗi: " . addslashes($e->getMessage()) . "');</script>";
    }
}

// Nếu đang trong chế độ sửa, lấy dữ liệu hóa đơn cần sửa
$edit_invoice = null;
$edit_details = [];
if (isset($_GET['edit_ma_hoa_don'])) {
    $edit_ma_hoa_don = intval($_GET['edit_ma_hoa_don']);
    // Lấy thông tin hóa đơn
    $stmtEdit = $conn->prepare("SELECT * FROM hoa_don WHERE ma_hoa_don = :ma_hoa_don");
    $stmtEdit->bindParam(':ma_hoa_don', $edit_ma_hoa_don, PDO::PARAM_INT);
    $stmtEdit->execute();
    $edit_invoice = $stmtEdit->fetch(PDO::FETCH_ASSOC);

    if ($edit_invoice) {
        // Lấy chi tiết hóa đơn
        $stmtDetails = $conn->prepare("SELECT * FROM chi_tiet_hoa_don WHERE ma_hoa_don = :ma_hoa_don");
        $stmtDetails->bindParam(':ma_hoa_don', $edit_ma_hoa_don, PDO::PARAM_INT);
        $stmtDetails->execute();
        $edit_details = $stmtDetails->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Hóa Đơn</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Custom CSS for better UI -->
    <style>
        .product-entry {
            border: 1px solid #ced4da;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 5px;
            position: relative;
            background-color: #f8f9fa;
            transition: all 0.3s ease;
        }
        .remove-product {
            position: absolute;
            top: 10px;
            right: 10px;
            color: red;
            cursor: pointer;
            font-size: 1.2em;
            transition: color 0.3s ease;
        }
        .remove-product:hover {
            color: darkred;
        }
        .form-section {
            margin-bottom: 30px;
            transition: all 0.5s ease;
        }
        .form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
        }
        .form-header h3 {
            margin: 0;
        }
        .collapse-section {
            display: none;
            animation: fadeIn 0.5s;
        }
        .show {
            display: block;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    </style>
</head>
<body class="hold-transition sidebar-mini">
    <div class="wrapper">
        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button">
                        <i class="fas fa-bars"></i>
                    </a>
                </li>
                <li class="nav-item d-none d-sm-inline-block">
                    <a href="dashboard.php" class="nav-link">Trang Chủ</a>
                </li>
            </ul>
        </nav>

        <!-- Sidebar -->
        <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <a href="dashboard.php" class="brand-link">
                <i class="fas fa-store"></i>
                <span class="brand-text font-weight-light">Quản Lý Cửa Hàng</span>
            </a>
            <div class="sidebar">
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" role="menu">
                        <li class="nav-item">
                            <a href="dashboard.php" class="nav-link">
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
                            <a href="hoadon.php" class="nav-link active">
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
            </div>
        </aside>

        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <!-- Content Header -->
            <div class="content-header">
                <div class="container-fluid d-flex justify-content-between align-items-center">
                    <h1 class="m-0">Quản Lý Hóa Đơn</h1>
                    <button class="btn btn-primary" id="toggleFormBtn">
                        <?= isset($edit_invoice) ? 'Chỉnh Sửa Hóa Đơn' : 'Thêm Hóa Đơn' ?>
                    </button>
                </div>
            </div>

            <!-- Main Content -->
            <div class="content">
                <div class="container-fluid">
                    <!-- Form Thêm/Sửa Hóa Đơn -->
                    <div class="card form-section" id="invoiceFormContainer">
                        <div class="card-header form-header" id="formHeader">
                            <h3 class="card-title"><?= isset($edit_invoice) ? 'Chỉnh Sửa Hóa Đơn' : 'Thêm Hóa Đơn' ?></h3>
                            <button type="button" class="btn btn-danger btn-sm" id="closeFormBtn">
                                <i class="fas fa-times"></i> Đóng
                            </button>
                        </div>
                        <div class="card-body collapse-section <?= isset($edit_invoice) ? 'show' : '' ?>" id="formBody">
                            <form action="hoadon.php<?= isset($edit_invoice) ? '?edit_ma_hoa_don=' . $edit_ma_hoa_don : '' ?>" method="post" id="invoiceForm">
                                <?php if (isset($edit_invoice)): ?>
                                    <input type="hidden" name="ma_hoa_don" value="<?= htmlspecialchars($edit_invoice['ma_hoa_don']); ?>">
                                <?php endif; ?>
                                <div class="form-group">
                                    <label>Khách Hàng</label>
                                    <select name="ma_khach_hang" class="form-control" required>
                                        <?php foreach ($customers as $customer): ?>
                                            <option value="<?= htmlspecialchars($customer['ma_khach_hang']); ?>" 
                                                <?= (isset($edit_invoice) && $edit_invoice['ma_khach_hang'] == $customer['ma_khach_hang']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($customer['ten_khach_hang']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div id="products-wrapper">
                                    <?php if (isset($edit_invoice) && !empty($edit_details)): ?>
                                        <?php foreach ($edit_details as $detail): ?>
                                            <?php
                                                // Lấy thông tin sản phẩm, màu sắc, kích cỡ dựa trên ma_phien_ban
                                                $stmtPhienBanDetail = $conn->prepare("SELECT ma_san_pham, mau_sac, kich_co FROM phien_ban_san_pham WHERE ma_phien_ban = :ma_phien_ban");
                                                $stmtPhienBanDetail->bindParam(':ma_phien_ban', $detail['ma_phien_ban'], PDO::PARAM_INT);
                                                $stmtPhienBanDetail->execute();
                                                $phienBanDetail = $stmtPhienBanDetail->fetch(PDO::FETCH_ASSOC);
                                            ?>
                                            <div class="product-entry">
                                                <span class="remove-product"><i class="fas fa-times"></i></span>
                                                <div class="form-group">
                                                    <label>Sản Phẩm</label>
                                                    <select name="ma_phien_ban[]" class="form-control ma_phien_ban" required>
                                                        <option value="">-- Chọn Sản Phẩm --</option>
                                                        <?php foreach ($products as $product): ?>
                                                            <option value="<?= htmlspecialchars($product['ma_san_pham']); ?>" 
                                                                <?= ($product['ma_san_pham'] == $phienBanDetail['ma_san_pham']) ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($product['ten_san_pham']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label>Màu Sắc</label>
                                                    <select name="mau_sac[]" class="form-control mau_sac" required>
                                                        <option value="">-- Chọn Màu Sắc --</option>
                                                        <?php
                                                            // Lấy màu sắc dựa trên ma_san_pham
                                                            $stmtColors = $conn->prepare("SELECT DISTINCT mau_sac FROM phien_ban_san_pham WHERE ma_san_pham = :ma_san_pham");
                                                            $stmtColors->bindParam(':ma_san_pham', $phienBanDetail['ma_san_pham'], PDO::PARAM_INT);
                                                            $stmtColors->execute();
                                                            $colors = $stmtColors->fetchAll(PDO::FETCH_COLUMN);
                                                        ?>
                                                        <?php foreach ($colors as $color): ?>
                                                            <option value="<?= htmlspecialchars($color); ?>" 
                                                                <?= ($color == $phienBanDetail['mau_sac']) ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($color); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label>Kích Cỡ</label>
                                                    <select name="kich_co[]" class="form-control kich_co" required>
                                                        <option value="">-- Chọn Kích Cỡ --</option>
                                                        <?php
                                                            // Lấy kích cỡ dựa trên ma_san_pham và mau_sac
                                                            $stmtSizes = $conn->prepare("SELECT DISTINCT kich_co FROM phien_ban_san_pham WHERE ma_san_pham = :ma_san_pham AND mau_sac = :mau_sac");
                                                            $stmtSizes->bindParam(':ma_san_pham', $phienBanDetail['ma_san_pham'], PDO::PARAM_INT);
                                                            $stmtSizes->bindParam(':mau_sac', $phienBanDetail['mau_sac'], PDO::PARAM_STR);
                                                            $stmtSizes->execute();
                                                            $sizes = $stmtSizes->fetchAll(PDO::FETCH_COLUMN);
                                                        ?>
                                                        <?php foreach ($sizes as $size): ?>
                                                            <option value="<?= htmlspecialchars($size); ?>" 
                                                                <?= ($size == $phienBanDetail['kich_co']) ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($size); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label>Giá (VND)</label>
                                                    <input type="number" name="gia[]" class="form-control gia" min="0" step="0.01" required value="<?= htmlspecialchars($detail['gia']); ?>">
                                                </div>
                                                <div class="form-group">
                                                    <label>Số Lượng</label>
                                                    <input type="number" name="so_luong[]" class="form-control so_luong" min="1" required value="<?= htmlspecialchars($detail['so_luong']); ?>">
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="product-entry">
                                            <span class="remove-product"><i class="fas fa-times"></i></span>
                                            <div class="form-group">
                                                <label>Sản Phẩm</label>
                                                <select name="ma_phien_ban[]" class="form-control ma_phien_ban" required>
                                                    <option value="">-- Chọn Sản Phẩm --</option>
                                                    <?php foreach ($products as $product): ?>
                                                        <option value="<?= htmlspecialchars($product['ma_san_pham']); ?>">
                                                            <?= htmlspecialchars($product['ten_san_pham']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label>Màu Sắc</label>
                                                <select name="mau_sac[]" class="form-control mau_sac" required>
                                                    <option value="">-- Chọn Màu Sắc --</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label>Kích Cỡ</label>
                                                <select name="kich_co[]" class="form-control kich_co" required>
                                                    <option value="">-- Chọn Kích Cỡ --</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label>Giá (VND)</label>
                                                <input type="number" name="gia[]" class="form-control gia" min="0" step="0.01" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Số Lượng</label>
                                                <input type="number" name="so_luong[]" class="form-control so_luong" min="1" required>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <button type="button" class="btn btn-secondary" id="addProductBtn">Thêm Sản Phẩm</button>
                                <button type="submit" name="<?= isset($edit_invoice) ? 'edit_invoice' : 'add_invoice' ?>" class="btn btn-success">
                                    <?= isset($edit_invoice) ? 'Cập Nhật' : 'Lưu' ?>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Modal Xem Chi Tiết Hóa Đơn -->
                    <div class="modal fade" id="viewDetailsModal" tabindex="-1" role="dialog" aria-labelledby="viewDetailsModalLabel" aria-hidden="true">
                      <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                          <div class="modal-header">
                            <h5 class="modal-title" id="viewDetailsModalLabel">Chi Tiết Hóa Đơn</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
                              <span aria-hidden="true">&times;</span>
                            </button>
                          </div>
                          <div class="modal-body">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Sản Phẩm</th>
                                        <th>Màu Sắc</th>
                                        <th>Kích Cỡ</th>
                                        <th>Số Lượng</th>
                                        <th>Giá (VND)</th>
                                        <th>Thành Tiền (VND)</th>
                                    </tr>
                                </thead>
                                <tbody id="detailsTableBody">
                                    <!-- Chi tiết hóa đơn sẽ được thêm vào đây -->
                                </tbody>
                            </table>
                            <h5 class="text-right">Tổng Tiền: <span id="totalTienModal">0</span> VND</h5>
                          </div>
                          <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Form Tìm Kiếm -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h3 class="card-title">Tìm Kiếm Hóa Đơn</h3>
                        </div>
                        <div class="card-body">
                            <form action="hoadon.php" method="get" class="form-inline">
                                <div class="form-group">
                                    <label for="search_ma_hoa_don" class="mr-2">Mã Hóa Đơn:</label>
                                    <input type="number" name="search_ma_hoa_don" id="search_ma_hoa_don" class="form-control mr-2" value="<?= htmlspecialchars($search_ma_hoa_don); ?>" placeholder="Nhập mã hóa đơn">
                                </div>
                                <button type="submit" class="btn btn-primary">Tìm Kiếm</button>
                                <a href="hoadon.php" class="btn btn-secondary ml-2">Reset</a>
                            </form>
                        </div>
                    </div>

                    <!-- Danh Sách Hóa Đơn -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h3 class="card-title">Danh Sách Hóa Đơn</h3>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>Mã Hóa Đơn</th>
                                        <th>Khách Hàng</th>
                                        <th>Tổng Tiền</th>
                                        <th>Ngày Tạo</th>
                                        <th>Hành Động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($invoices)): ?>
                                        <?php foreach ($invoices as $invoice): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($invoice['ma_hoa_don']); ?></td>
                                            <td><?= htmlspecialchars($invoice['ten_khach_hang']); ?></td>
                                            <td><?= number_format($invoice['tong_tien'], 0, ',', '.'); ?> VND</td>
                                            <td><?= htmlspecialchars($invoice['ngay_tao']); ?></td>
                                            <td>
                                                <button type="button" class="btn btn-info btn-sm view-details" data-ma-hoa-don="<?= htmlspecialchars($invoice['ma_hoa_don']); ?>">Xem Chi Tiết</button>
                                                <button type="button" class="btn btn-warning btn-sm edit-invoice" data-ma-hoa-don="<?= htmlspecialchars($invoice['ma_hoa_don']); ?>">Sửa</button>
                                                <form action="hoadon.php" method="post" style="display:inline-block;">
                                                    <input type="hidden" name="ma_hoa_don" value="<?= htmlspecialchars($invoice['ma_hoa_don']); ?>">
                                                    <button type="submit" name="delete_invoice" class="btn btn-danger btn-sm" onclick="return confirm('Bạn có chắc muốn xóa?')">
                                                        Xóa
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center">Không tìm thấy hóa đơn nào.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <footer class="main-footer">
            <strong>Bản quyền &copy; 2024 <a href="#">Cửa Hàng</a>.</strong> Đã đăng ký.
        </footer>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Include Bootstrap JS for modal functionality -->
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
    <script>
        $(document).ready(function(){
            // Toggle form thêm/sửa hóa đơn
            $('#toggleFormBtn').click(function(){
                $('#formBody').toggleClass('show');
            });

            $('#closeFormBtn').click(function(){
                $('#formBody').removeClass('show');
            });

            // Thêm sản phẩm mới
            $('#addProductBtn').click(function(){
                let productOptions = '<option value="">-- Chọn Sản Phẩm --</option>';
                <?php foreach ($products as $product): ?>
                    productOptions += `<option value="<?= htmlspecialchars($product['ma_san_pham']); ?>">
                        <?= htmlspecialchars($product['ten_san_pham']); ?>
                    </option>`;
                <?php endforeach; ?>

                let productEntry = `
                <div class="product-entry">
                    <span class="remove-product"><i class="fas fa-times"></i></span>
                    <div class="form-group">
                        <label>Sản Phẩm</label>
                        <select name="ma_phien_ban[]" class="form-control ma_phien_ban" required>
                            ${productOptions}
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Màu Sắc</label>
                        <select name="mau_sac[]" class="form-control mau_sac" required>
                            <option value="">-- Chọn Màu Sắc --</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Kích Cỡ</label>
                        <select name="kich_co[]" class="form-control kich_co" required>
                            <option value="">-- Chọn Kích Cỡ --</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Giá (VND)</label>
                        <input type="number" name="gia[]" class="form-control gia" min="0" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Số Lượng</label>
                        <input type="number" name="so_luong[]" class="form-control so_luong" min="1" required>
                    </div>
                </div>
                `;
                $('#products-wrapper').append(productEntry);
            });

            // Xóa sản phẩm
            $('#products-wrapper').on('click', '.remove-product', function(){
                $(this).parent('.product-entry').remove();
            });

            // Lấy màu sắc khi chọn sản phẩm
            $('#products-wrapper').on('change', '.ma_phien_ban', function(){
                let ma_san_pham = $(this).val();
                let mau_sacSelect = $(this).closest('.product-entry').find('.mau_sac');
                let kich_coSelect = $(this).closest('.product-entry').find('.kich_co');
                let giaField = $(this).closest('.product-entry').find('.gia');

                // Reset các trường sau khi chọn sản phẩm
                mau_sacSelect.html('<option value="">-- Chọn Màu Sắc --</option>');
                kich_coSelect.html('<option value="">-- Chọn Kích Cỡ --</option>');
                giaField.val('');

                if(ma_san_pham){
                    $.ajax({
                        url: 'hoadon.php',
                        method: 'POST',
                        data: { action: 'get_colors', ma_san_pham: ma_san_pham },
                        dataType: 'json',
                        success: function(response){
                            if(response.success){
                                response.colors.forEach(function(color){
                                    mau_sacSelect.append(`<option value="${color}">${color}</option>`);
                                });
                            } else {
                                alert(response.message);
                            }
                        },
                        error: function(){
                            alert('Lỗi khi lấy màu sắc sản phẩm.');
                        }
                    });
                }
            });

            // Lấy kích cỡ khi chọn màu sắc
            $('#products-wrapper').on('change', '.mau_sac', function(){
                let ma_san_pham = $(this).closest('.product-entry').find('.ma_phien_ban').val();
                let mau_sac = $(this).val();
                let kich_coSelect = $(this).closest('.product-entry').find('.kich_co');
                let giaField = $(this).closest('.product-entry').find('.gia');

                // Reset kích cỡ và giá khi chọn màu sắc
                kich_coSelect.html('<option value="">-- Chọn Kích Cỡ --</option>');
                giaField.val('');

                if(ma_san_pham && mau_sac){
                    $.ajax({
                        url: 'hoadon.php',
                        method: 'POST',
                        data: { action: 'get_sizes', ma_san_pham: ma_san_pham, mau_sac: mau_sac },
                        dataType: 'json',
                        success: function(response){
                            if(response.success){
                                response.sizes.forEach(function(size){
                                    kich_coSelect.append(`<option value="${size}">${size}</option>`);
                                });
                            } else {
                                alert(response.message);
                            }
                        },
                        error: function(){
                            alert('Lỗi khi lấy kích cỡ sản phẩm.');
                        }
                    });
                }
            });

            // Lấy giá khi chọn kích cỡ
            $('#products-wrapper').on('change', '.kich_co', function(){
                let ma_san_pham = $(this).closest('.product-entry').find('.ma_phien_ban').val();
                let mau_sac = $(this).closest('.product-entry').find('.mau_sac').val();
                let kich_co = $(this).val();
                let giaField = $(this).closest('.product-entry').find('.gia');

                giaField.val('');

                if(ma_san_pham && mau_sac && kich_co){
                    $.ajax({
                        url: 'hoadon.php',
                        method: 'POST',
                        data: { action: 'get_price', ma_san_pham: ma_san_pham, mau_sac: mau_sac, kich_co: kich_co },
                        dataType: 'json',
                        success: function(response){
                            if(response.success){
                                giaField.val(response.gia);
                            } else {
                                alert(response.message);
                            }
                        },
                        error: function(){
                            alert('Lỗi khi lấy giá sản phẩm.');
                        }
                    });
                }
            });

            // Xem chi tiết hóa đơn
            $('.view-details').click(function(){
                let ma_hoa_don = $(this).data('ma-hoa-don');
                $('#viewDetailsModalLabel').text(`Chi Tiết Hóa Đơn #${ma_hoa_don}`);
                $('#detailsTableBody').html('');
                $('#totalTienModal').text('0');

                $.ajax({
                    url: 'hoadon.php',
                    method: 'POST',
                    data: { action: 'get_details', ma_hoa_don: ma_hoa_don },
                    dataType: 'json',
                    success: function(response){
                        if(response.success){
                            let total = 0;
                            response.details.forEach(function(detail){
                                let thanh_tien = detail.so_luong * detail.gia;
                                total += thanh_tien;
                                $('#detailsTableBody').append(`
                                    <tr>
                                        <td>${detail.ten_san_pham}</td>
                                        <td>${detail.mau_sac}</td>
                                        <td>${detail.kich_co}</td>
                                        <td>${detail.so_luong}</td>
                                        <td>${parseFloat(detail.gia).toLocaleString('vi-VN')} VND</td>
                                        <td>${thanh_tien.toLocaleString('vi-VN')} VND</td>
                                    </tr>
                                `);
                            });
                            $('#totalTienModal').text(total.toLocaleString('vi-VN'));
                            $('#viewDetailsModal').modal('show');
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function(){
                        alert('Lỗi khi lấy chi tiết hóa đơn.');
                    }
                });
            });

            // Khi nhấn nút Sửa, tải dữ liệu hóa đơn vào form
            $('.edit-invoice').click(function(){
                let ma_hoa_don = $(this).data('ma-hoa-don');
                window.location.href = `hoadon.php?edit_ma_hoa_don=${ma_hoa_don}`;
            });

            // Nếu đang chỉnh sửa, mở form
            <?php if(isset($edit_invoice) && !empty($edit_details)): ?>
                $('#formBody').addClass('show');
            <?php endif; ?>
        });
    </script>
</body>
</html>

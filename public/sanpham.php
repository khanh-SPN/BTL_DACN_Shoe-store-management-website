<?php
session_start();
include '../config/database.php';

// Kiểm tra quyền truy cập
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    echo "<script>alert('Bạn không có quyền truy cập vào trang này!'); window.location.href='login.php';</script>";
    exit();
}

// Xử lý các yêu cầu POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Thêm sản phẩm mới
    if (isset($_POST['add_san_pham'])) {
        try {
            $conn->beginTransaction();

            $ten_san_pham = trim($_POST['ten_san_pham']);
            $danh_muc = trim($_POST['danh_muc']);
            $mo_ta = trim($_POST['mo_ta']);

            // Kiểm tra bắt buộc tên sản phẩm
            if (empty($ten_san_pham)) {
                throw new Exception("Tên sản phẩm không được để trống.");
            }

            // Thêm sản phẩm vào bảng `san_pham`
            $stmtAdd = $conn->prepare("
                INSERT INTO san_pham (ten_san_pham, danh_muc, mo_ta) 
                VALUES (:ten_san_pham, :danh_muc, :mo_ta)
            ");
            $stmtAdd->bindParam(':ten_san_pham', $ten_san_pham, PDO::PARAM_STR);
            $stmtAdd->bindParam(':danh_muc', $danh_muc, PDO::PARAM_STR);
            $stmtAdd->bindParam(':mo_ta', $mo_ta, PDO::PARAM_STR);
            $stmtAdd->execute();

            $conn->commit();
            echo "<script>alert('Thêm sản phẩm thành công!'); window.location.href='sanpham.php';</script>";
        } catch (Exception $e) {
            $conn->rollBack();
            echo "<script>alert('Lỗi: " . addslashes($e->getMessage()) . "');</script>";
        }
    }

    // Sửa sản phẩm
    if (isset($_POST['edit_san_pham'])) {
        try {
            $conn->beginTransaction();

            $ma_san_pham = intval($_POST['ma_san_pham']);
            $ten_san_pham = trim($_POST['ten_san_pham']);
            $danh_muc = trim($_POST['danh_muc']);
            $mo_ta = trim($_POST['mo_ta']);

            // Kiểm tra bắt buộc tên sản phẩm
            if (empty($ten_san_pham)) {
                throw new Exception("Tên sản phẩm không được để trống.");
            }

            // Cập nhật thông tin sản phẩm
            $stmtUpdate = $conn->prepare("
                UPDATE san_pham 
                SET ten_san_pham = :ten_san_pham, danh_muc = :danh_muc, mo_ta = :mo_ta 
                WHERE ma_san_pham = :ma_san_pham
            ");
            $stmtUpdate->bindParam(':ten_san_pham', $ten_san_pham, PDO::PARAM_STR);
            $stmtUpdate->bindParam(':danh_muc', $danh_muc, PDO::PARAM_STR);
            $stmtUpdate->bindParam(':mo_ta', $mo_ta, PDO::PARAM_STR);
            $stmtUpdate->bindParam(':ma_san_pham', $ma_san_pham, PDO::PARAM_INT);
            $stmtUpdate->execute();

            $conn->commit();
            echo "<script>alert('Cập nhật sản phẩm thành công!'); window.location.href='sanpham.php';</script>";
        } catch (Exception $e) {
            $conn->rollBack();
            echo "<script>alert('Lỗi: " . addslashes($e->getMessage()) . "');</script>";
        }
    }

    // Xóa sản phẩm
    if (isset($_POST['delete_san_pham'])) {
        try {
            $ma_san_pham = intval($_POST['ma_san_pham']);
            $conn->beginTransaction();

            // Kiểm tra nếu sản phẩm có phiên bản hoặc hóa đơn liên kết
            $stmtCheckVariant = $conn->prepare("SELECT COUNT(*) FROM phien_ban_san_pham WHERE ma_san_pham = :ma_san_pham");
            $stmtCheckVariant->bindParam(':ma_san_pham', $ma_san_pham, PDO::PARAM_INT);
            $stmtCheckVariant->execute();
            $countVariant = $stmtCheckVariant->fetchColumn();

            $stmtCheckInvoice = $conn->prepare("
                SELECT COUNT(*) FROM chi_tiet_hoa_don cthd 
                JOIN phien_ban_san_pham pbsp ON cthd.ma_phien_ban = pbsp.ma_phien_ban 
                WHERE pbsp.ma_san_pham = :ma_san_pham
            ");
            $stmtCheckInvoice->bindParam(':ma_san_pham', $ma_san_pham, PDO::PARAM_INT);
            $stmtCheckInvoice->execute();
            $countInvoice = $stmtCheckInvoice->fetchColumn();

            if ($countVariant > 0) {
                throw new Exception("Không thể xóa sản phẩm này vì có phiên bản sản phẩm liên kết.");
            }

            if ($countInvoice > 0) {
                throw new Exception("Không thể xóa sản phẩm này vì có hóa đơn liên kết.");
            }

            // Xóa sản phẩm
            $stmtDelete = $conn->prepare("DELETE FROM san_pham WHERE ma_san_pham = :ma_san_pham");
            $stmtDelete->bindParam(':ma_san_pham', $ma_san_pham, PDO::PARAM_INT);
            $stmtDelete->execute();

            $conn->commit();
            echo "<script>alert('Xóa sản phẩm thành công!'); window.location.href='sanpham.php';</script>";
        } catch (Exception $e) {
            $conn->rollBack();
            echo "<script>alert('Lỗi khi xóa sản phẩm: " . addslashes($e->getMessage()) . "');</script>";
        }
    }

    // Thêm phiên bản sản phẩm
    if (isset($_POST['add_phien_ban'])) {
        try {
            $conn->beginTransaction();

            $ma_san_pham = intval($_POST['ma_san_pham_variant_add']);
            $mau_sac = trim($_POST['mau_sac_add']);
            $kich_co = trim($_POST['kich_co_add']);
            $gia = floatval($_POST['gia_add']);
            $so_luong_ton = intval($_POST['so_luong_ton_add']);

            // Kiểm tra bắt buộc màu sắc và kích cỡ
            if (empty($mau_sac) || empty($kich_co)) {
                throw new Exception("Màu sắc và kích cỡ không được để trống.");
            }

            // Kiểm tra trùng lặp màu sắc và kích cỡ
            $stmtCheck = $conn->prepare("
                SELECT COUNT(*) FROM phien_ban_san_pham 
                WHERE ma_san_pham = :ma_san_pham AND mau_sac = :mau_sac AND kich_co = :kich_co
            ");
            $stmtCheck->bindParam(':ma_san_pham', $ma_san_pham, PDO::PARAM_INT);
            $stmtCheck->bindParam(':mau_sac', $mau_sac, PDO::PARAM_STR);
            $stmtCheck->bindParam(':kich_co', $kich_co, PDO::PARAM_STR);
            $stmtCheck->execute();
            $count = $stmtCheck->fetchColumn();
            if ($count > 0) {
                throw new Exception("Phiên bản sản phẩm với màu sắc '$mau_sac' và kích cỡ '$kich_co' đã tồn tại.");
            }

            // Thêm phiên bản sản phẩm
            $stmtVariant = $conn->prepare("
                INSERT INTO phien_ban_san_pham (ma_san_pham, mau_sac, kich_co, gia, so_luong_ton) 
                VALUES (:ma_san_pham, :mau_sac, :kich_co, :gia, :so_luong_ton)
            ");
            $stmtVariant->bindParam(':ma_san_pham', $ma_san_pham, PDO::PARAM_INT);
            $stmtVariant->bindParam(':mau_sac', $mau_sac, PDO::PARAM_STR);
            $stmtVariant->bindParam(':kich_co', $kich_co, PDO::PARAM_STR);
            $stmtVariant->bindParam(':gia', $gia, PDO::PARAM_STR);
            $stmtVariant->bindParam(':so_luong_ton', $so_luong_ton, PDO::PARAM_INT);
            $stmtVariant->execute();

            $conn->commit();
            echo "<script>alert('Thêm phiên bản sản phẩm thành công!'); window.location.href='sanpham.php';</script>";
        } catch (Exception $e) {
            $conn->rollBack();
            echo "<script>alert('Lỗi: " . addslashes($e->getMessage()) . "');</script>";
        }
    }

    // Sửa phiên bản sản phẩm
    if (isset($_POST['edit_phien_ban'])) {
        try {
            $conn->beginTransaction();

            $ma_phien_ban = intval($_POST['ma_phien_ban']);
            $mau_sac = trim($_POST['mau_sac']);
            $kich_co = trim($_POST['kich_co']);
            $gia = floatval($_POST['gia']);
            $so_luong_ton = intval($_POST['so_luong_ton']);

            // Kiểm tra bắt buộc màu sắc và kích cỡ
            if (empty($mau_sac) || empty($kich_co)) {
                throw new Exception("Màu sắc và kích cỡ không được để trống.");
            }

            // Lấy ma_san_pham của phiên bản
            $stmtGetProduct = $conn->prepare("SELECT ma_san_pham FROM phien_ban_san_pham WHERE ma_phien_ban = :ma_phien_ban");
            $stmtGetProduct->bindParam(':ma_phien_ban', $ma_phien_ban, PDO::PARAM_INT);
            $stmtGetProduct->execute();
            $product = $stmtGetProduct->fetch(PDO::FETCH_ASSOC);
            if (!$product) {
                throw new Exception("Phiên bản sản phẩm không tồn tại.");
            }
            $ma_san_pham = $product['ma_san_pham'];

            // Kiểm tra trùng lặp màu sắc và kích cỡ, loại bỏ phiên bản hiện tại
            $stmtCheck = $conn->prepare("
                SELECT COUNT(*) FROM phien_ban_san_pham 
                WHERE ma_san_pham = :ma_san_pham AND mau_sac = :mau_sac AND kich_co = :kich_co AND ma_phien_ban != :ma_phien_ban
            ");
            $stmtCheck->bindParam(':ma_san_pham', $ma_san_pham, PDO::PARAM_INT);
            $stmtCheck->bindParam(':mau_sac', $mau_sac, PDO::PARAM_STR);
            $stmtCheck->bindParam(':kich_co', $kich_co, PDO::PARAM_STR);
            $stmtCheck->bindParam(':ma_phien_ban', $ma_phien_ban, PDO::PARAM_INT);
            $stmtCheck->execute();
            $count = $stmtCheck->fetchColumn();
            if ($count > 0) {
                throw new Exception("Phiên bản sản phẩm với màu sắc '$mau_sac' và kích cỡ '$kich_co' đã tồn tại.");
            }

            // Cập nhật phiên bản sản phẩm
            $stmtUpdate = $conn->prepare("
                UPDATE phien_ban_san_pham 
                SET mau_sac = :mau_sac, kich_co = :kich_co, gia = :gia, so_luong_ton = :so_luong_ton 
                WHERE ma_phien_ban = :ma_phien_ban
            ");
            $stmtUpdate->bindParam(':mau_sac', $mau_sac, PDO::PARAM_STR);
            $stmtUpdate->bindParam(':kich_co', $kich_co, PDO::PARAM_STR);
            $stmtUpdate->bindParam(':gia', $gia, PDO::PARAM_STR);
            $stmtUpdate->bindParam(':so_luong_ton', $so_luong_ton, PDO::PARAM_INT);
            $stmtUpdate->bindParam(':ma_phien_ban', $ma_phien_ban, PDO::PARAM_INT);
            $stmtUpdate->execute();

            $conn->commit();
            echo "<script>alert('Cập nhật phiên bản sản phẩm thành công!'); window.location.href='sanpham.php';</script>";
        } catch (Exception $e) {
            $conn->rollBack();
            echo "<script>alert('Lỗi: " . addslashes($e->getMessage()) . "');</script>";
        }
    }

    // Xóa phiên bản sản phẩm
    if (isset($_POST['delete_phien_ban'])) {
        try {
            $ma_phien_ban = intval($_POST['ma_phien_ban']);
            $conn->beginTransaction();

            // Kiểm tra nếu phiên bản có hóa đơn liên kết
            $stmtCheckInvoice = $conn->prepare("SELECT COUNT(*) FROM chi_tiet_hoa_don WHERE ma_phien_ban = :ma_phien_ban");
            $stmtCheckInvoice->bindParam(':ma_phien_ban', $ma_phien_ban, PDO::PARAM_INT);
            $stmtCheckInvoice->execute();
            $countInvoice = $stmtCheckInvoice->fetchColumn();

            if ($countInvoice > 0) {
                throw new Exception("Không thể xóa phiên bản sản phẩm này vì có hóa đơn liên kết.");
            }

            // Xóa phiên bản sản phẩm
            $stmtDelete = $conn->prepare("DELETE FROM phien_ban_san_pham WHERE ma_phien_ban = :ma_phien_ban");
            $stmtDelete->bindParam(':ma_phien_ban', $ma_phien_ban, PDO::PARAM_INT);
            $stmtDelete->execute();

            $conn->commit();
            echo "<script>alert('Xóa phiên bản sản phẩm thành công!'); window.location.href='sanpham.php';</script>";
        } catch (Exception $e) {
            $conn->rollBack();
            echo "<script>alert('Lỗi khi xóa phiên bản sản phẩm: " . addslashes($e->getMessage()) . "');</script>";
        }
    }

    // Xử lý xem chi tiết và lấy phiên bản sản phẩm qua AJAX
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        if ($_POST['action'] === 'get_variants') {
            $ma_san_pham = intval($_POST['ma_san_pham']);
            try {
                $stmt = $conn->prepare("SELECT * FROM phien_ban_san_pham WHERE ma_san_pham = :ma_san_pham");
                $stmt->bindParam(':ma_san_pham', $ma_san_pham, PDO::PARAM_INT);
                $stmt->execute();
                $variants = $stmt->fetchAll(PDO::FETCH_ASSOC);

                echo json_encode(['success' => true, 'variants' => $variants]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Lỗi khi lấy phiên bản sản phẩm.']);
            }
            exit();
        }
    }
}

// Xử lý tìm kiếm
$search_ma_san_pham = '';
$search_ten_san_pham = '';
$search_query = '';
$params = [];

if (isset($_GET['search_ma_san_pham']) && !empty($_GET['search_ma_san_pham'])) {
    $search_ma_san_pham = intval($_GET['search_ma_san_pham']);
    $search_query .= " AND san_pham.ma_san_pham = :search_ma_san_pham";
    $params[':search_ma_san_pham'] = $search_ma_san_pham;
}

if (isset($_GET['search_ten_san_pham']) && !empty($_GET['search_ten_san_pham'])) {
    $search_ten_san_pham = trim($_GET['search_ten_san_pham']);
    $search_query .= " AND san_pham.ten_san_pham LIKE :search_ten_san_pham";
    $params[':search_ten_san_pham'] = '%' . $search_ten_san_pham . '%';
}

// Lấy danh sách sản phẩm
$stmt = $conn->prepare("
    SELECT san_pham.*, 
           COUNT(pb.ma_phien_ban) AS so_phien_ban 
    FROM san_pham 
    LEFT JOIN phien_ban_san_pham pb ON san_pham.ma_san_pham = pb.ma_san_pham
    WHERE 1=1 $search_query
    GROUP BY san_pham.ma_san_pham
    ORDER BY san_pham.ma_san_pham DESC
");
foreach ($params as $key => &$val) {
    $stmt->bindParam($key, $val, PDO::PARAM_STR);
}
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh sách sản phẩm để lựa chọn khi thêm/sửa phiên bản
$stmtProducts = $conn->query("SELECT ma_san_pham, ten_san_pham FROM san_pham");
$allProducts = $stmtProducts->fetchAll(PDO::FETCH_ASSOC);

// Nếu đang trong chế độ sửa sản phẩm, lấy dữ liệu sản phẩm cần sửa
$edit_product = null;
if (isset($_GET['edit_ma_san_pham'])) {
    $edit_ma_san_pham = intval($_GET['edit_ma_san_pham']);
    // Lấy thông tin sản phẩm
    $stmtEdit = $conn->prepare("SELECT * FROM san_pham WHERE ma_san_pham = :ma_san_pham");
    $stmtEdit->bindParam(':ma_san_pham', $edit_ma_san_pham, PDO::PARAM_INT);
    $stmtEdit->execute();
    $edit_product = $stmtEdit->fetch(PDO::FETCH_ASSOC);
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Sản Phẩm</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Custom CSS for better UI -->
    <style>
        .form-section {
            margin-bottom: 30px;
            transition: all 0.5s ease;
            display: none;
        }
        .form-section.show {
            display: block;
        }
        .form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .variant-entry {
            border: 1px solid #ced4da;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 5px;
            position: relative;
            background-color: #f8f9fa;
            transition: all 0.3s ease;
        }
        .remove-variant {
            position: absolute;
            top: 10px;
            right: 10px;
            color: red;
            cursor: pointer;
            font-size: 1.2em;
            transition: color 0.3s ease;
        }
        .remove-variant:hover {
            color: darkred;
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
                            <a href="sanpham.php" class="nav-link active">
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
            </div>
        </aside>

        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <!-- Content Header -->
            <div class="content-header">
                <div class="container-fluid d-flex justify-content-between align-items-center">
                    <h1 class="m-0">Quản Lý Sản Phẩm</h1>
                    <div>
                        <button class="btn btn-primary mr-2" id="addProductBtn">
                            <i class="fas fa-plus"></i> Thêm Sản Phẩm
                        </button>
                        <button class="btn btn-secondary" id="manageVersionsBtn" disabled>
                            <i class="fas fa-cogs"></i> Quản Lý Phiên Bản Sản Phẩm
                        </button>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="content">
                <div class="container-fluid">
                    <!-- Form Thêm Sản Phẩm -->
                    <div class="card form-section" id="productFormContainer">
                        <div class="card-header form-header">
                            <h3 class="card-title"><?= isset($edit_product) ? 'Chỉnh Sửa Sản Phẩm' : 'Thêm Sản Phẩm' ?></h3>
                            <button type="button" class="btn btn-danger btn-sm" id="closeFormBtn">
                                <i class="fas fa-times"></i> Đóng
                            </button>
                        </div>
                        <div class="card-body">
                            <form action="sanpham.php<?= isset($edit_product) ? '?edit_ma_san_pham=' . $edit_ma_san_pham : '' ?>" method="post" id="productForm">
                                <?php if (isset($edit_product)): ?>
                                    <input type="hidden" name="ma_san_pham" value="<?= htmlspecialchars($edit_product['ma_san_pham']); ?>">
                                <?php endif; ?>
                                <div class="form-group">
                                    <label>Tên Sản Phẩm <span class="text-danger">*</span></label>
                                    <input type="text" name="ten_san_pham" class="form-control" required value="<?= isset($edit_product) ? htmlspecialchars($edit_product['ten_san_pham']) : ''; ?>">
                                </div>
                                <div class="form-group">
                                    <label>Danh Mục</label>
                                    <input type="text" name="danh_muc" class="form-control" value="<?= isset($edit_product) ? htmlspecialchars($edit_product['danh_muc']) : ''; ?>" placeholder="Ví dụ: Giày thể thao, Sandal,...">
                                </div>
                                <div class="form-group">
                                    <label>Mô Tả</label>
                                    <textarea name="mo_ta" class="form-control" rows="3"><?= isset($edit_product) ? htmlspecialchars($edit_product['mo_ta']) : ''; ?></textarea>
                                </div>
                                <button type="submit" name="<?= isset($edit_product) ? 'edit_san_pham' : 'add_san_pham' ?>" class="btn btn-success">
                                    <?= isset($edit_product) ? 'Cập Nhật' : 'Lưu' ?>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Form Tìm Kiếm -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h3 class="card-title">Tìm Kiếm Sản Phẩm</h3>
                        </div>
                        <div class="card-body">
                            <form action="sanpham.php" method="get" class="form-inline">
                                <div class="form-group mr-3">
                                    <label for="search_ma_san_pham" class="mr-2">Mã Sản Phẩm:</label>
                                    <input type="number" name="search_ma_san_pham" id="search_ma_san_pham" class="form-control" value="<?= htmlspecialchars($search_ma_san_pham); ?>" placeholder="Nhập mã sản phẩm">
                                </div>
                                <div class="form-group mr-3">
                                    <label for="search_ten_san_pham" class="mr-2">Tên Sản Phẩm:</label>
                                    <input type="text" name="search_ten_san_pham" id="search_ten_san_pham" class="form-control" value="<?= htmlspecialchars($search_ten_san_pham); ?>" placeholder="Nhập tên sản phẩm">
                                </div>
                                <button type="submit" class="btn btn-primary">Tìm Kiếm</button>
                                <a href="sanpham.php" class="btn btn-secondary ml-2">Reset</a>
                            </form>
                        </div>
                    </div>

                    <!-- Danh Sách Sản Phẩm -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h3 class="card-title">Danh Sách Sản Phẩm</h3>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>Mã Sản Phẩm</th>
                                        <th>Tên Sản Phẩm</th>
                                        <th>Danh Mục</th>
                                        <th>Số Phiên Bản</th>
                                        <th>Mô Tả</th>
                                        <th>Hành Động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($products)): ?>
                                        <?php foreach ($products as $product): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($product['ma_san_pham']); ?></td>
                                            <td><?= htmlspecialchars($product['ten_san_pham']); ?></td>
                                            <td><?= htmlspecialchars($product['danh_muc']); ?></td>
                                            <td><?= htmlspecialchars($product['so_phien_ban']); ?></td>
                                            <td><?= htmlspecialchars($product['mo_ta']); ?></td>
                                            <td>
                                                <a href="sanpham.php?edit_ma_san_pham=<?= htmlspecialchars($product['ma_san_pham']); ?>" class="btn btn-warning btn-sm">Sửa</a>
                                                <form action="sanpham.php" method="post" style="display:inline-block;">
                                                    <input type="hidden" name="ma_san_pham" value="<?= htmlspecialchars($product['ma_san_pham']); ?>">
                                                    <button type="submit" name="delete_san_pham" class="btn btn-danger btn-sm" onclick="return confirm('Bạn có chắc muốn xóa sản phẩm này?')">
                                                        Xóa
                                                    </button>
                                                </form>
                                                <button type="button" class="btn btn-info btn-sm view-details" data-ma-san-pham="<?= htmlspecialchars($product['ma_san_pham']); ?>" data-ten-san-pham="<?= htmlspecialchars($product['ten_san_pham']); ?>" data-danh-muc="<?= htmlspecialchars($product['danh_muc']); ?>" data-mo-ta="<?= htmlspecialchars($product['mo_ta']); ?>">
                                                    Xem Chi Tiết
                                                </button>
                                                <button type="button" class="btn btn-secondary btn-sm manage-versions" data-ma-san-pham="<?= htmlspecialchars($product['ma_san_pham']); ?>" data-ten-san-pham="<?= htmlspecialchars($product['ten_san_pham']); ?>">
                                                    Quản Lý Phiên Bản
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">Không tìm thấy sản phẩm nào.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Modal Xem Chi Tiết Sản Phẩm -->
                    <div class="modal fade" id="viewDetailsModal" tabindex="-1" role="dialog" aria-labelledby="viewDetailsModalLabel" aria-hidden="true">
                      <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                          <div class="modal-header">
                            <h5 class="modal-title" id="viewDetailsModalLabel">Chi Tiết Sản Phẩm</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
                              <span aria-hidden="true">&times;</span>
                            </button>
                          </div>
                          <div class="modal-body">
                            <h4 id="detailProductName"></h4>
                            <p><strong>Danh Mục:</strong> <span id="detailProductCategory"></span></p>
                            <p><strong>Mô Tả:</strong> <span id="detailProductDescription"></span></p>
                            <hr>
                            <h5>Phiên Bản Sản Phẩm</h5>
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Màu Sắc</th>
                                        <th>Kích Cỡ</th>
                                        <th>Giá (VND)</th>
                                        <th>Số Lượng Tồn Kho</th>
                                    </tr>
                                </thead>
                                <tbody id="variantsTableBody">
                                    <!-- Phiên bản sản phẩm sẽ được thêm vào đây -->
                                </tbody>
                            </table>
                          </div>
                          <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Modal Quản Lý Phiên Bản Sản Phẩm -->
                    <div class="modal fade" id="manageVersionsModal" tabindex="-1" role="dialog" aria-labelledby="manageVersionsModalLabel" aria-hidden="true">
                      <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                          <div class="modal-header">
                            <h5 class="modal-title" id="manageVersionsModalLabel">Quản Lý Phiên Bản Sản Phẩm</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
                              <span aria-hidden="true">&times;</span>
                            </button>
                          </div>
                          <div class="modal-body">
                            <h4 id="manageVersionsProductName"></h4>
                            <form action="sanpham.php" method="post" id="manageVersionsForm">
                                <input type="hidden" name="ma_san_pham_variant" id="ma_san_pham_variant">
                                <div id="variantsList">
                                    <!-- Danh sách phiên bản sản phẩm sẽ được thêm vào đây -->
                                </div>
                                <button type="button" class="btn btn-secondary" id="addVariantBtnModal">
                                    <i class="fas fa-plus"></i> Thêm Phiên Bản
                                </button>
                                <button type="submit" name="save_phien_ban" class="btn btn-success">
                                    Lưu Thay Đổi
                                </button>
                            </form>
                            <hr>
                            <h5>Thêm Phiên Bản Sản Phẩm Mới</h5>
                            <form action="sanpham.php" method="post" id="addVariantForm">
                                <input type="hidden" name="ma_san_pham_variant_add" id="ma_san_pham_variant_add">
                                <div class="form-group">
                                    <label>Màu Sắc <span class="text-danger">*</span></label>
                                    <input type="text" name="mau_sac_add" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Kích Cỡ <span class="text-danger">*</span></label>
                                    <input type="text" name="kich_co_add" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Giá (VND) <span class="text-danger">*</span></label>
                                    <input type="number" name="gia_add" class="form-control" min="0" step="0.01" required>
                                </div>
                                <div class="form-group">
                                    <label>Số Lượng Tồn Kho</label>
                                    <input type="number" name="so_luong_ton_add" class="form-control" min="0" required value="0">
                                </div>
                                <button type="submit" name="add_phien_ban" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Thêm Phiên Bản
                                </button>
                            </form>
                          </div>
                          <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                          </div>
                        </div>
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
            // Toggle form thêm sản phẩm
            $('#addProductBtn').click(function(){
                $('#productFormContainer').toggleClass('show');
                $('#productFormContainer').find('.form-section').toggleClass('show');
                // Nếu đang chỉnh sửa, chuyển sang thêm mới
                if ($('#productForm').find('input[name="ma_san_pham"]').length) {
                    window.location.href = 'sanpham.php';
                }
            });

            $('#closeFormBtn').click(function(){
                $('#productFormContainer').removeClass('show');
                $('#productFormContainer').find('.form-section').removeClass('show');
                // Nếu đang chỉnh sửa, chuyển về danh sách
                if ($('#productForm').find('input[name="ma_san_pham"]').length) {
                    window.location.href = 'sanpham.php';
                }
            });

            // Xem chi tiết sản phẩm
            $('.view-details').click(function(){
                let ma_san_pham = $(this).data('ma-san-pham');
                let ten_san_pham = $(this).data('ten-san-pham');
                let danh_muc = $(this).data('danh-muc');
                let mo_ta = $(this).data('mo-ta');

                $('#detailProductName').text(ten_san_pham);
                $('#detailProductCategory').text(danh_muc);
                $('#detailProductDescription').text(mo_ta);

                // Fetch variants via AJAX
                $.ajax({
                    url: 'sanpham.php',
                    method: 'POST',
                    data: { action: 'get_variants', ma_san_pham: ma_san_pham },
                    dataType: 'json',
                    success: function(response){
                        if(response.success){
                            $('#variantsTableBody').html('');
                            response.variants.forEach(function(variant){
                                $('#variantsTableBody').append(`
                                    <tr>
                                        <td>${variant.mau_sac}</td>
                                        <td>${variant.kich_co}</td>
                                        <td>${parseFloat(variant.gia).toLocaleString('vi-VN')} VND</td>
                                        <td>${variant.so_luong_ton}</td>
                                    </tr>
                                `);
                            });
                            $('#viewDetailsModal').modal('show');
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function(){
                        alert('Lỗi khi lấy chi tiết sản phẩm.');
                    }
                });
            });

            // Quản lý phiên bản sản phẩm
            $('.manage-versions').click(function(){
                let ma_san_pham = $(this).data('ma-san-pham');
                let ten_san_pham = $(this).data('ten-san-pham');

                $('#manageVersionsProductName').text(`Quản Lý Phiên Bản - ${ten_san_pham}`);
                $('#ma_san_pham_variant').val(ma_san_pham);
                $('#ma_san_pham_variant_add').val(ma_san_pham);

                // Fetch existing variants via AJAX
                $.ajax({
                    url: 'sanpham.php',
                    method: 'POST',
                    data: { action: 'get_variants', ma_san_pham: ma_san_pham },
                    dataType: 'json',
                    success: function(response){
                        if(response.success){
                            $('#variantsList').html('');
                            response.variants.forEach(function(variant){
                                $('#variantsList').append(`
                                    <div class="variant-entry">
                                        <span class="remove-variant"><i class="fas fa-times"></i></span>
                                        <input type="hidden" name="ma_phien_ban[]" value="${variant.ma_phien_ban}">
                                        <div class="form-group">
                                            <label>Màu Sắc <span class="text-danger">*</span></label>
                                            <input type="text" name="mau_sac[]" class="form-control mau_sac" required value="${variant.mau_sac}">
                                        </div>
                                        <div class="form-group">
                                            <label>Kích Cỡ <span class="text-danger">*</span></label>
                                            <input type="text" name="kich_co[]" class="form-control kich_co" required value="${variant.kich_co}">
                                        </div>
                                        <div class="form-group">
                                            <label>Giá (VND) <span class="text-danger">*</span></label>
                                            <input type="number" name="gia[]" class="form-control gia" min="0" step="0.01" required value="${variant.gia}">
                                        </div>
                                        <div class="form-group">
                                            <label>Số Lượng Tồn Kho</label>
                                            <input type="number" name="so_luong_ton[]" class="form-control so_luong_ton" min="0" required value="${variant.so_luong_ton}">
                                        </div>
                                    </div>
                                `);
                            });
                            $('#manageVersionsModal').modal('show');
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function(){
                        alert('Lỗi khi lấy phiên bản sản phẩm.');
                    }
                });
            });

            // Thêm phiên bản sản phẩm từ modal
            $('#addVariantBtnModal').click(function(){
                let variantEntry = `
                <div class="variant-entry">
                    <span class="remove-variant"><i class="fas fa-times"></i></span>
                    <div class="form-group">
                        <label>Màu Sắc <span class="text-danger">*</span></label>
                        <input type="text" name="mau_sac[]" class="form-control mau_sac" required>
                    </div>
                    <div class="form-group">
                        <label>Kích Cỡ <span class="text-danger">*</span></label>
                        <input type="text" name="kich_co[]" class="form-control kich_co" required>
                    </div>
                    <div class="form-group">
                        <label>Giá (VND) <span class="text-danger">*</span></label>
                        <input type="number" name="gia[]" class="form-control gia" min="0" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Số Lượng Tồn Kho</label>
                        <input type="number" name="so_luong_ton[]" class="form-control so_luong_ton" min="0" required value="0">
                    </div>
                </div>
                `;
                $('#variantsList').append(variantEntry);
            });

            // Xóa phiên bản sản phẩm trong modal
            $('#variantsList').on('click', '.remove-variant', function(){
                $(this).parent('.variant-entry').remove();
            });

            // Xử lý xem chi tiết sản phẩm
            $('#viewDetailsModal').on('hidden.bs.modal', function () {
                $('#variantsTableBody').html('');
                $('#detailProductName').text('');
                $('#detailProductCategory').text('');
                $('#detailProductDescription').text('');
            });

            // Xử lý quản lý phiên bản sản phẩm
            $('#manageVersionsModal').on('hidden.bs.modal', function () {
                $('#variantsList').html('');
                $('#manageVersionsProductName').text('');
                $('#ma_san_pham_variant').val('');
                $('#ma_san_pham_variant_add').val('');
            });
        });
    </script>
</body>
</html>

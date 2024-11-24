<?php
session_start();
include '../config/database.php';

// Kiểm tra quyền truy cập
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    echo "<script>alert('Bạn không có quyền truy cập vào trang này!'); window.location.href='login.php';</script>";
    exit();
}

// Xử lý các yêu cầu AJAX (nếu cần, hiện tại không sử dụng)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Bạn có thể thêm các xử lý AJAX ở đây nếu cần
    exit();
}

// Xử lý tìm kiếm
$search_ma_khach_hang = '';
$search_ten_khach_hang = '';
$search_query = '';
$params = [];

if (isset($_GET['search_ma_khach_hang']) && !empty($_GET['search_ma_khach_hang'])) {
    $search_ma_khach_hang = intval($_GET['search_ma_khach_hang']);
    $search_query .= " AND khach_hang.ma_khach_hang = :search_ma_khach_hang";
    $params[':search_ma_khach_hang'] = $search_ma_khach_hang;
}

if (isset($_GET['search_ten_khach_hang']) && !empty($_GET['search_ten_khach_hang'])) {
    $search_ten_khach_hang = trim($_GET['search_ten_khach_hang']);
    $search_query .= " AND khach_hang.ten_khach_hang LIKE :search_ten_khach_hang";
    $params[':search_ten_khach_hang'] = '%' . $search_ten_khach_hang . '%';
}

// Lấy danh sách khách hàng
$stmt = $conn->prepare("SELECT * FROM khach_hang WHERE 1=1 $search_query ORDER BY ma_khach_hang DESC");
foreach ($params as $key => &$val) {
    $stmt->bindParam($key, $val, PDO::PARAM_STR);
}
$stmt->execute();
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Xử lý thêm khách hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_khach_hang'])) {
    try {
        $conn->beginTransaction();

        $ten_khach_hang = trim($_POST['ten_khach_hang']);
        $so_dien_thoai = trim($_POST['so_dien_thoai']);
        $email = trim($_POST['email']);
        $dia_chi = trim($_POST['dia_chi']);

        // Kiểm tra bắt buộc tên khách hàng
        if (empty($ten_khach_hang)) {
            throw new Exception("Tên khách hàng không được để trống.");
        }

        // Thêm khách hàng vào bảng `khach_hang`
        $stmtAdd = $conn->prepare("
            INSERT INTO khach_hang (ten_khach_hang, so_dien_thoai, email, dia_chi) 
            VALUES (:ten_khach_hang, :so_dien_thoai, :email, :dia_chi)
        ");
        $stmtAdd->bindParam(':ten_khach_hang', $ten_khach_hang, PDO::PARAM_STR);
        $stmtAdd->bindParam(':so_dien_thoai', $so_dien_thoai, PDO::PARAM_STR);
        $stmtAdd->bindParam(':email', $email, PDO::PARAM_STR);
        $stmtAdd->bindParam(':dia_chi', $dia_chi, PDO::PARAM_STR);
        $stmtAdd->execute();

        $conn->commit();
        echo "<script>alert('Thêm khách hàng thành công!'); window.location.href='khachhang.php';</script>";
    } catch (Exception $e) {
        $conn->rollBack();
        echo "<script>alert('Lỗi: " . addslashes($e->getMessage()) . "');</script>";
    }
}

// Xử lý xóa khách hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_khach_hang'])) {
    try {
        $ma_khach_hang = intval($_POST['ma_khach_hang']);
        $conn->beginTransaction();

        // Kiểm tra nếu khách hàng có hóa đơn liên kết
        $stmtCheck = $conn->prepare("SELECT COUNT(*) FROM hoa_don WHERE ma_khach_hang = :ma_khach_hang");
        $stmtCheck->bindParam(':ma_khach_hang', $ma_khach_hang, PDO::PARAM_INT);
        $stmtCheck->execute();
        $count = $stmtCheck->fetchColumn();

        if ($count > 0) {
            throw new Exception("Không thể xóa khách hàng này vì có hóa đơn liên kết.");
        }

        // Xóa khách hàng
        $stmtDelete = $conn->prepare("DELETE FROM khach_hang WHERE ma_khach_hang = :ma_khach_hang");
        $stmtDelete->bindParam(':ma_khach_hang', $ma_khach_hang, PDO::PARAM_INT);
        $stmtDelete->execute();

        $conn->commit();
        echo "<script>alert('Xóa khách hàng thành công!'); window.location.href='khachhang.php';</script>";
    } catch (Exception $e) {
        $conn->rollBack();
        echo "<script>alert('Lỗi khi xóa khách hàng: " . addslashes($e->getMessage()) . "');</script>";
    }
}

// Xử lý sửa khách hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_khach_hang'])) {
    try {
        $ma_khach_hang = intval($_POST['ma_khach_hang']);
        $ten_khach_hang = trim($_POST['ten_khach_hang']);
        $so_dien_thoai = trim($_POST['so_dien_thoai']);
        $email = trim($_POST['email']);
        $dia_chi = trim($_POST['dia_chi']);

        // Kiểm tra bắt buộc tên khách hàng
        if (empty($ten_khach_hang)) {
            throw new Exception("Tên khách hàng không được để trống.");
        }

        $conn->beginTransaction();

        // Cập nhật thông tin khách hàng
        $stmtUpdate = $conn->prepare("
            UPDATE khach_hang 
            SET ten_khach_hang = :ten_khach_hang, so_dien_thoai = :so_dien_thoai, email = :email, dia_chi = :dia_chi 
            WHERE ma_khach_hang = :ma_khach_hang
        ");
        $stmtUpdate->bindParam(':ten_khach_hang', $ten_khach_hang, PDO::PARAM_STR);
        $stmtUpdate->bindParam(':so_dien_thoai', $so_dien_thoai, PDO::PARAM_STR);
        $stmtUpdate->bindParam(':email', $email, PDO::PARAM_STR);
        $stmtUpdate->bindParam(':dia_chi', $dia_chi, PDO::PARAM_STR);
        $stmtUpdate->bindParam(':ma_khach_hang', $ma_khach_hang, PDO::PARAM_INT);
        $stmtUpdate->execute();

        $conn->commit();
        echo "<script>alert('Cập nhật khách hàng thành công!'); window.location.href='khachhang.php';</script>";
    } catch (Exception $e) {
        $conn->rollBack();
        echo "<script>alert('Lỗi khi cập nhật khách hàng: " . addslashes($e->getMessage()) . "');</script>";
    }
}

// Nếu đang trong chế độ sửa, lấy dữ liệu khách hàng cần sửa
$edit_customer = null;
if (isset($_GET['edit_ma_khach_hang'])) {
    $edit_ma_khach_hang = intval($_GET['edit_ma_khach_hang']);
    // Lấy thông tin khách hàng
    $stmtEdit = $conn->prepare("SELECT * FROM khach_hang WHERE ma_khach_hang = :ma_khach_hang");
    $stmtEdit->bindParam(':ma_khach_hang', $edit_ma_khach_hang, PDO::PARAM_INT);
    $stmtEdit->execute();
    $edit_customer = $stmtEdit->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Khách Hàng</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Custom CSS for better UI -->
    <style>
        .customer-entry {
            border: 1px solid #ced4da;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 5px;
            position: relative;
            background-color: #f8f9fa;
            transition: all 0.3s ease;
        }
        .remove-customer {
            position: absolute;
            top: 10px;
            right: 10px;
            color: red;
            cursor: pointer;
            font-size: 1.2em;
            transition: color 0.3s ease;
        }
        .remove-customer:hover {
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
                    <h1 class="m-0">Quản Lý Khách Hàng</h1>
                    <button class="btn btn-primary" id="toggleFormBtn">
                        <?= isset($edit_customer) ? 'Chỉnh Sửa Khách Hàng' : 'Thêm Khách Hàng' ?>
                    </button>
                </div>
            </div>

            <!-- Main Content -->
            <div class="content">
                <div class="container-fluid">
                    <!-- Form Thêm/Sửa Khách Hàng -->
                    <div class="card form-section" id="customerFormContainer">
                        <div class="card-header form-header" id="formHeader">
                            <h3 class="card-title"><?= isset($edit_customer) ? 'Chỉnh Sửa Khách Hàng' : 'Thêm Khách Hàng' ?></h3>
                            <button type="button" class="btn btn-danger btn-sm" id="closeFormBtn">
                                <i class="fas fa-times"></i> Đóng
                            </button>
                        </div>
                        <div class="card-body collapse-section <?= isset($edit_customer) ? 'show' : '' ?>" id="formBody">
                            <form action="khachhang.php<?= isset($edit_customer) ? '?edit_ma_khach_hang=' . $edit_ma_khach_hang : '' ?>" method="post" id="customerForm">
                                <?php if (isset($edit_customer)): ?>
                                    <input type="hidden" name="ma_khach_hang" value="<?= htmlspecialchars($edit_customer['ma_khach_hang']); ?>">
                                <?php endif; ?>
                                <div class="form-group">
                                    <label>Tên Khách Hàng <span class="text-danger">*</span></label>
                                    <input type="text" name="ten_khach_hang" class="form-control" required value="<?= isset($edit_customer) ? htmlspecialchars($edit_customer['ten_khach_hang']) : ''; ?>">
                                </div>
                                <div class="form-group">
                                    <label>Số Điện Thoại</label>
                                    <input type="text" name="so_dien_thoai" class="form-control" value="<?= isset($edit_customer) ? htmlspecialchars($edit_customer['so_dien_thoai']) : ''; ?>">
                                </div>
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" name="email" class="form-control" value="<?= isset($edit_customer) ? htmlspecialchars($edit_customer['email']) : ''; ?>">
                                </div>
                                <div class="form-group">
                                    <label>Địa Chỉ</label>
                                    <textarea name="dia_chi" class="form-control" rows="3"><?= isset($edit_customer) ? htmlspecialchars($edit_customer['dia_chi']) : ''; ?></textarea>
                                </div>
                                <button type="submit" name="<?= isset($edit_customer) ? 'edit_khach_hang' : 'add_khach_hang' ?>" class="btn btn-success">
                                    <?= isset($edit_customer) ? 'Cập Nhật' : 'Lưu' ?>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Form Tìm Kiếm -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h3 class="card-title">Tìm Kiếm Khách Hàng</h3>
                        </div>
                        <div class="card-body">
                            <form action="khachhang.php" method="get" class="form-inline">
                                <div class="form-group">
                                    <label for="search_ma_khach_hang" class="mr-2">Mã Khách Hàng:</label>
                                    <input type="number" name="search_ma_khach_hang" id="search_ma_khach_hang" class="form-control mr-2" value="<?= htmlspecialchars($search_ma_khach_hang); ?>" placeholder="Nhập mã khách hàng">
                                </div>
                                <div class="form-group">
                                    <label for="search_ten_khach_hang" class="mr-2">Tên Khách Hàng:</label>
                                    <input type="text" name="search_ten_khach_hang" id="search_ten_khach_hang" class="form-control mr-2" value="<?= htmlspecialchars($search_ten_khach_hang); ?>" placeholder="Nhập tên khách hàng">
                                </div>
                                <button type="submit" class="btn btn-primary">Tìm Kiếm</button>
                                <a href="khachhang.php" class="btn btn-secondary ml-2">Reset</a>
                            </form>
                        </div>
                    </div>

                    <!-- Danh Sách Khách Hàng -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h3 class="card-title">Danh Sách Khách Hàng</h3>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>Mã Khách Hàng</th>
                                        <th>Tên Khách Hàng</th>
                                        <th>Số Điện Thoại</th>
                                        <th>Email</th>
                                        <th>Địa Chỉ</th>
                                        <th>Ngày Tạo</th>
                                        <th>Ngày Cập Nhật</th>
                                        <th>Hành Động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($customers)): ?>
                                        <?php foreach ($customers as $customer): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($customer['ma_khach_hang']); ?></td>
                                            <td><?= htmlspecialchars($customer['ten_khach_hang']); ?></td>
                                            <td><?= htmlspecialchars($customer['so_dien_thoai']); ?></td>
                                            <td><?= htmlspecialchars($customer['email']); ?></td>
                                            <td><?= htmlspecialchars($customer['dia_chi']); ?></td>
                                            <td><?= htmlspecialchars($customer['ngay_tao']); ?></td>
                                            <td><?= htmlspecialchars($customer['ngay_cap_nhat']); ?></td>
                                            <td>
                                                <a href="khachhang.php?edit_ma_khach_hang=<?= htmlspecialchars($customer['ma_khach_hang']); ?>" class="btn btn-warning btn-sm">Sửa</a>
                                                <form action="khachhang.php" method="post" style="display:inline-block;">
                                                    <input type="hidden" name="ma_khach_hang" value="<?= htmlspecialchars($customer['ma_khach_hang']); ?>">
                                                    <button type="submit" name="delete_khach_hang" class="btn btn-danger btn-sm" onclick="return confirm('Bạn có chắc muốn xóa khách hàng này?')">
                                                        Xóa
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center">Không tìm thấy khách hàng nào.</td>
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
            // Toggle form thêm/sửa khách hàng
            $('#toggleFormBtn').click(function(){
                $('#formBody').toggleClass('show');
            });

            $('#closeFormBtn').click(function(){
                $('#formBody').removeClass('show');
            });

            // Nếu đang chỉnh sửa, mở form
            <?php if(isset($edit_customer) && !empty($edit_customer)): ?>
                $('#formBody').addClass('show');
            <?php endif; ?>
        });
    </script>
</body>
</html>
s
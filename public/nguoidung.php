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
    // Thêm người dùng mới
    if (isset($_POST['add_nguoi_dung'])) {
        try {
            $conn->beginTransaction();

            $ten_dang_nhap = trim($_POST['ten_dang_nhap']);
            $mat_khau = trim($_POST['mat_khau']);
            $vai_tro = $_POST['vai_tro'];

            // Kiểm tra bắt buộc tên đăng nhập và mật khẩu
            if (empty($ten_dang_nhap) || empty($mat_khau)) {
                throw new Exception("Tên đăng nhập và mật khẩu không được để trống.");
            }

            // Kiểm tra trùng lặp tên đăng nhập
            $stmtCheck = $conn->prepare("SELECT COUNT(*) FROM nguoi_dung WHERE ten_dang_nhap = :ten_dang_nhap");
            $stmtCheck->bindParam(':ten_dang_nhap', $ten_dang_nhap, PDO::PARAM_STR);
            $stmtCheck->execute();
            $count = $stmtCheck->fetchColumn();
            if ($count > 0) {
                throw new Exception("Tên đăng nhập '$ten_dang_nhap' đã tồn tại.");
            }

            // Thêm người dùng vào bảng `nguoi_dung`
            $stmtAdd = $conn->prepare("
                INSERT INTO nguoi_dung (ten_dang_nhap, mat_khau, vai_tro) 
                VALUES (:ten_dang_nhap, :mat_khau, :vai_tro)
            ");
            $stmtAdd->bindParam(':ten_dang_nhap', $ten_dang_nhap, PDO::PARAM_STR);
            $stmtAdd->bindParam(':mat_khau', $mat_khau, PDO::PARAM_STR); // Mật khẩu lưu trữ dưới dạng plaintext
            $stmtAdd->bindParam(':vai_tro', $vai_tro, PDO::PARAM_STR);
            $stmtAdd->execute();

            $conn->commit();
            echo "<script>alert('Thêm người dùng thành công!'); window.location.href='nguoidung.php';</script>";
        } catch (Exception $e) {
            $conn->rollBack();
            echo "<script>alert('Lỗi: " . addslashes($e->getMessage()) . "');</script>";
        }
    }

    // Sửa người dùng
    if (isset($_POST['edit_nguoi_dung'])) {
        try {
            $conn->beginTransaction();

            $ma_nguoi_dung = intval($_POST['ma_nguoi_dung']);
            $ten_dang_nhap = trim($_POST['ten_dang_nhap']);
            $mat_khau = trim($_POST['mat_khau']);
            $vai_tro = $_POST['vai_tro'];

            // Kiểm tra bắt buộc tên đăng nhập
            if (empty($ten_dang_nhap)) {
                throw new Exception("Tên đăng nhập không được để trống.");
            }

            // Kiểm tra trùng lặp tên đăng nhập, loại bỏ người dùng hiện tại
            $stmtCheck = $conn->prepare("SELECT COUNT(*) FROM nguoi_dung WHERE ten_dang_nhap = :ten_dang_nhap AND ma_nguoi_dung != :ma_nguoi_dung");
            $stmtCheck->bindParam(':ten_dang_nhap', $ten_dang_nhap, PDO::PARAM_STR);
            $stmtCheck->bindParam(':ma_nguoi_dung', $ma_nguoi_dung, PDO::PARAM_INT);
            $stmtCheck->execute();
            $count = $stmtCheck->fetchColumn();
            if ($count > 0) {
                throw new Exception("Tên đăng nhập '$ten_dang_nhap' đã tồn tại.");
            }

            // Nếu mật khẩu được nhập, cập nhật; nếu không, giữ mật khẩu hiện tại
            if (!empty($mat_khau)) {
                $stmtUpdate = $conn->prepare("
                    UPDATE nguoi_dung 
                    SET ten_dang_nhap = :ten_dang_nhap, mat_khau = :mat_khau, vai_tro = :vai_tro 
                    WHERE ma_nguoi_dung = :ma_nguoi_dung
                ");
                $stmtUpdate->bindParam(':mat_khau', $mat_khau, PDO::PARAM_STR); // Mật khẩu lưu trữ dưới dạng plaintext
            } else {
                $stmtUpdate = $conn->prepare("
                    UPDATE nguoi_dung 
                    SET ten_dang_nhap = :ten_dang_nhap, vai_tro = :vai_tro 
                    WHERE ma_nguoi_dung = :ma_nguoi_dung
                ");
            }

            $stmtUpdate->bindParam(':ten_dang_nhap', $ten_dang_nhap, PDO::PARAM_STR);
            $stmtUpdate->bindParam(':vai_tro', $vai_tro, PDO::PARAM_STR);
            $stmtUpdate->bindParam(':ma_nguoi_dung', $ma_nguoi_dung, PDO::PARAM_INT);
            $stmtUpdate->execute();

            $conn->commit();
            echo "<script>alert('Cập nhật người dùng thành công!'); window.location.href='nguoidung.php';</script>";
        } catch (Exception $e) {
            $conn->rollBack();
            echo "<script>alert('Lỗi: " . addslashes($e->getMessage()) . "');</script>";
        }
    }

    // Xóa người dùng
    if (isset($_POST['delete_nguoi_dung'])) {
        try {
            $ma_nguoi_dung = intval($_POST['ma_nguoi_dung']);
            $conn->beginTransaction();

            // Kiểm tra nếu người dùng có hóa đơn liên kết
            $stmtCheckInvoice = $conn->prepare("SELECT COUNT(*) FROM hoa_don WHERE ma_nguoi_dung = :ma_nguoi_dung");
            $stmtCheckInvoice->bindParam(':ma_nguoi_dung', $ma_nguoi_dung, PDO::PARAM_INT);
            $stmtCheckInvoice->execute();
            $countInvoice = $stmtCheckInvoice->fetchColumn();

            if ($countInvoice > 0) {
                throw new Exception("Không thể xóa người dùng này vì có hóa đơn liên kết.");
            }

            // Xóa người dùng
            $stmtDelete = $conn->prepare("DELETE FROM nguoi_dung WHERE ma_nguoi_dung = :ma_nguoi_dung");
            $stmtDelete->bindParam(':ma_nguoi_dung', $ma_nguoi_dung, PDO::PARAM_INT);
            $stmtDelete->execute();

            $conn->commit();
            echo "<script>alert('Xóa người dùng thành công!'); window.location.href='nguoidung.php';</script>";
        } catch (Exception $e) {
            $conn->rollBack();
            echo "<script>alert('Lỗi khi xóa người dùng: " . addslashes($e->getMessage()) . "');</script>";
        }
    }

    // Xử lý xem chi tiết và lấy người dùng qua AJAX
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        if ($_POST['action'] === 'get_nguoi_dung') {
            $ma_nguoi_dung = intval($_POST['ma_nguoi_dung']);
            try {
                $stmt = $conn->prepare("SELECT * FROM nguoi_dung WHERE ma_nguoi_dung = :ma_nguoi_dung");
                $stmt->bindParam(':ma_nguoi_dung', $ma_nguoi_dung, PDO::PARAM_INT);
                $stmt->execute();
                $nguoi_dung = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($nguoi_dung) {
                    echo json_encode(['success' => true, 'nguoi_dung' => $nguoi_dung]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Người dùng không tồn tại.']);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Lỗi khi lấy thông tin người dùng.']);
            }
            exit();
        }
    }
}

// Xử lý tìm kiếm
$search_ten_dang_nhap = '';
$search_vai_tro = '';
$search_query = '';
$params = [];

if (isset($_GET['search_ten_dang_nhap']) && !empty($_GET['search_ten_dang_nhap'])) {
    $search_ten_dang_nhap = trim($_GET['search_ten_dang_nhap']);
    $search_query .= " AND ten_dang_nhap LIKE :search_ten_dang_nhap";
    $params[':search_ten_dang_nhap'] = '%' . $search_ten_dang_nhap . '%';
}

if (isset($_GET['search_vai_tro']) && !empty($_GET['search_vai_tro'])) {
    $search_vai_tro = $_GET['search_vai_tro'];
    $search_query .= " AND vai_tro = :search_vai_tro";
    $params[':search_vai_tro'] = $search_vai_tro;
}

// Lấy danh sách người dùng
$stmt = $conn->prepare("
    SELECT * FROM nguoi_dung
    WHERE 1=1 $search_query
    ORDER BY ma_nguoi_dung DESC
");
foreach ($params as $key => &$val) {
    $stmt->bindParam($key, $val, PDO::PARAM_STR);
}
$stmt->execute();
$nguoi_dungs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Người Dùng</title>
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
        .form-group.required label::after {
            content:" *";
            color: red;
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
                            <a href="nguoidung.php" class="nav-link active">
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
                    <h1 class="m-0">Quản Lý Người Dùng</h1>
                    <button class="btn btn-primary" id="addUserBtn">
                        <i class="fas fa-user-plus"></i> Thêm Người Dùng
                    </button>
                </div>
            </div>

            <!-- Main Content -->
            <div class="content">
                <div class="container-fluid">
                    <!-- Form Thêm/Sửa Người Dùng -->
                    <div class="card form-section" id="userFormContainer">
                        <div class="card-header form-header">
                            <h3 class="card-title"><?= isset($_GET['edit_ma_nguoi_dung']) ? 'Chỉnh Sửa Người Dùng' : 'Thêm Người Dùng' ?></h3>
                            <button type="button" class="btn btn-danger btn-sm" id="closeFormBtn">
                                <i class="fas fa-times"></i> Đóng
                            </button>
                        </div>
                        <div class="card-body">
                            <form action="nguoidung.php<?= isset($_GET['edit_ma_nguoi_dung']) ? '?edit_ma_nguoi_dung=' . intval($_GET['edit_ma_nguoi_dung']) : '' ?>" method="post" id="userForm">
                                <?php if (isset($_GET['edit_ma_nguoi_dung'])): ?>
                                    <input type="hidden" name="ma_nguoi_dung" value="<?= htmlspecialchars($edit_product['ma_nguoi_dung']); ?>">
                                <?php endif; ?>
                                <div class="form-group required">
                                    <label>Tên Đăng Nhập</label>
                                    <input type="text" name="ten_dang_nhap" class="form-control" required value="<?= isset($edit_product) ? htmlspecialchars($edit_product['ten_dang_nhap']) : ''; ?>">
                                </div>
                                <div class="form-group required">
                                    <label>Mật Khẩu</label>
                                    <input type="password" name="mat_khau" class="form-control" <?= isset($edit_product) ? '' : 'required'; ?> placeholder="<?= isset($edit_product) ? 'Nhập mật khẩu mới nếu muốn thay đổi' : ''; ?>">
                                </div>
                                <div class="form-group">
                                    <label>Vai Trò</label>
                                    <select name="vai_tro" class="form-control">
                                        <option value="nhan_vien" <?= (isset($edit_product) && $edit_product['vai_tro'] == 'nhan_vien') ? 'selected' : ''; ?>>Nhân Viên</option>
                                        <option value="admin" <?= (isset($edit_product) && $edit_product['vai_tro'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                                    </select>
                                </div>
                                <button type="submit" name="<?= isset($_GET['edit_ma_nguoi_dung']) ? 'edit_nguoi_dung' : 'add_nguoi_dung' ?>" class="btn btn-success">
                                    <?= isset($_GET['edit_ma_nguoi_dung']) ? 'Cập Nhật' : 'Lưu' ?>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Form Tìm Kiếm -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h3 class="card-title">Tìm Kiếm Người Dùng</h3>
                        </div>
                        <div class="card-body">
                            <form action="nguoidung.php" method="get" class="form-inline">
                                <div class="form-group mr-3">
                                    <label for="search_ten_dang_nhap" class="mr-2">Tên Đăng Nhập:</label>
                                    <input type="text" name="search_ten_dang_nhap" id="search_ten_dang_nhap" class="form-control" value="<?= htmlspecialchars($search_ten_dang_nhap); ?>" placeholder="Nhập tên đăng nhập">
                                </div>
                                <div class="form-group mr-3">
                                    <label for="search_vai_tro" class="mr-2">Vai Trò:</label>
                                    <select name="search_vai_tro" id="search_vai_tro" class="form-control">
                                        <option value="">-- Tất Cả --</option>
                                        <option value="admin" <?= ($search_vai_tro == 'admin') ? 'selected' : ''; ?>>Admin</option>
                                        <option value="nhan_vien" <?= ($search_vai_tro == 'nhan_vien') ? 'selected' : ''; ?>>Nhân Viên</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary">Tìm Kiếm</button>
                                <a href="nguoidung.php" class="btn btn-secondary ml-2">Reset</a>
                            </form>
                        </div>
                    </div>

                    <!-- Danh Sách Người Dùng -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h3 class="card-title">Danh Sách Người Dùng</h3>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>Mã Người Dùng</th>
                                        <th>Tên Đăng Nhập</th>
                                        <th>Vai Trò</th>
                                        <th>Ngày Tạo</th>
                                        <th>Ngày Cập Nhật</th>
                                        <th>Hành Động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($nguoi_dungs)): ?>
                                        <?php foreach ($nguoi_dungs as $nguoi_dung): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($nguoi_dung['ma_nguoi_dung']); ?></td>
                                            <td><?= htmlspecialchars($nguoi_dung['ten_dang_nhap']); ?></td>
                                            <td><?= htmlspecialchars(ucfirst($nguoi_dung['vai_tro'])); ?></td>
                                            <td><?= htmlspecialchars($nguoi_dung['ngay_tao']); ?></td>
                                            <td><?= htmlspecialchars($nguoi_dung['ngay_cap_nhat']); ?></td>
                                            <td>
                                                <a href="nguoidung.php?edit_ma_nguoi_dung=<?= htmlspecialchars($nguoi_dung['ma_nguoi_dung']); ?>" class="btn btn-warning btn-sm">Sửa</a>
                                                <form action="nguoidung.php" method="post" style="display:inline-block;">
                                                    <input type="hidden" name="ma_nguoi_dung" value="<?= htmlspecialchars($nguoi_dung['ma_nguoi_dung']); ?>">
                                                    <button type="submit" name="delete_nguoi_dung" class="btn btn-danger btn-sm" onclick="return confirm('Bạn có chắc muốn xóa người dùng này?')">
                                                        Xóa
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">Không tìm thấy người dùng nào.</td>
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
            // Toggle form thêm người dùng
            $('#addUserBtn').click(function(){
                $('#userFormContainer').toggleClass('show');
                // Nếu đang chỉnh sửa, chuyển sang thêm mới
                if ($('#userForm').find('input[name="ma_nguoi_dung"]').length) {
                    window.location.href = 'nguoidung.php';
                }
            });

            $('#closeFormBtn').click(function(){
                $('#userFormContainer').removeClass('show');
                // Nếu đang chỉnh sửa, chuyển về danh sách
                if ($('#userForm').find('input[name="ma_nguoi_dung"]').length) {
                    window.location.href = 'nguoidung.php';
                }
            });

            // Reset form khi chuyển hướng
            <?php if(isset($_GET['edit_ma_nguoi_dung'])): ?>
                $('#userFormContainer').addClass('show');
            <?php endif; ?>
        });
    </script>
</body>
</html>

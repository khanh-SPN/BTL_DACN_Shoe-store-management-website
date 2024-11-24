<?php
include '../config/database.php';
session_start();

if (isset($_POST['submit'])) {
    // Lấy dữ liệu từ form
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Sử dụng Prepared Statements để truy vấn PDO
    $stmt = $conn->prepare("SELECT * FROM `nguoi_dung` WHERE ten_dang_nhap = :username AND mat_khau = :password");
    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
    $stmt->bindParam(':password', $password, PDO::PARAM_STR);
    $stmt->execute();

    // Kiểm tra kết quả
    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // Kiểm tra vai trò và lưu thông tin vào session
        if ($row['vai_tro'] == 'admin') {
            $_SESSION['admin_name'] = $row['ten_dang_nhap'];
            $_SESSION['admin_id'] = $row['ma_nguoi_dung'];
            $_SESSION['role'] = 'admin'; // Lưu vai trò để kiểm tra trong admin.php
            header('Location: dashboard.php');
        } elseif ($row['vai_tro'] == 'nhan_vien') {
            $_SESSION['user_name'] = $row['ten_dang_nhap'];
            $_SESSION['user_id'] = $row['ma_nguoi_dung'];
            $_SESSION['role'] = 'nhan_vien'; // Lưu vai trò nếu cần kiểm tra
            header('Location: index.php');
        }
    } else {
        $message[] = 'Tên đăng nhập hoặc mật khẩu không chính xác';
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Nhập</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="login.css">
</head>
<body>

<?php
// Hiển thị thông báo nếu có lỗi
if (isset($message)) {
    foreach ($message as $msg) {
        echo '
        <div class="message">
            <span>' . $msg . '</span>
            <i class="fa-solid fa-xmark" onclick="this.parentElement.remove();"></i>
        </div>
        ';
    }
}
?>

<div class="box login_box">
    <span class="borderline"></span>
    <form action="" method="post">
        <h2>Đăng Nhập</h2>

        <div class="inputbox">
            <input type="text" name="username" required="required">
            <span>Tên Đăng Nhập</span>
            <i></i>
        </div>

        <div class="inputbox">
            <input type="password" name="password" required="required">
            <span>Mật Khẩu</span>
            <i></i>
        </div>

        <div class="links">
            <a href="#">Quên Mật Khẩu</a>
            <a href="register.php">Đăng Ký</a>
        </div>

        <input type="submit" value="Đăng Nhập" name="submit">
    </form>
</div>
<script src="https://kit.fontawesome.com/eedbcd0c96.js" crossorigin="anonymous"></script>
</body>
</html>

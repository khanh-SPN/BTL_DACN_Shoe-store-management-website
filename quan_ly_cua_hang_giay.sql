-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th10 24, 2024 lúc 08:30 PM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `quan_ly_cua_hang_giay`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chi_tiet_hoa_don`
--

CREATE TABLE `chi_tiet_hoa_don` (
  `ma_chi_tiet` int(11) NOT NULL COMMENT 'Mã chi tiết hóa đơn',
  `ma_hoa_don` int(11) NOT NULL COMMENT 'Mã hóa đơn (liên kết với bảng hoa_don)',
  `ma_phien_ban` int(11) NOT NULL COMMENT 'Mã phiên bản sản phẩm (liên kết với bảng phien_ban_san_pham)',
  `so_luong` int(11) NOT NULL COMMENT 'Số lượng sản phẩm',
  `gia` decimal(10,2) NOT NULL COMMENT 'Giá tại thời điểm bán',
  `thanh_tien` decimal(10,2) GENERATED ALWAYS AS (`so_luong` * `gia`) STORED COMMENT 'Thành tiền (so_luong * gia)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Bảng lưu thông tin chi tiết hóa đơn';

--
-- Đang đổ dữ liệu cho bảng `chi_tiet_hoa_don`
--

INSERT INTO `chi_tiet_hoa_don` (`ma_chi_tiet`, `ma_hoa_don`, `ma_phien_ban`, `so_luong`, `gia`) VALUES
(1, 1, 1, 2, 500000.00),
(2, 1, 2, 1, 520000.00),
(3, 2, 3, 1, 750000.00),
(4, 3, 7, 1, 1200000.00),
(5, 4, 5, 1, 500000.00),
(6, 9, 1, 2, 500000.00),
(7, 10, 1, 1, 500000.00),
(8, 10, 3, 2, 480000.00);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `hoa_don`
--

CREATE TABLE `hoa_don` (
  `ma_hoa_don` int(11) NOT NULL COMMENT 'Mã hóa đơn',
  `ma_khach_hang` int(11) DEFAULT NULL COMMENT 'Mã khách hàng (liên kết với bảng khach_hang)',
  `ma_nguoi_dung` int(11) DEFAULT NULL COMMENT 'Mã người dùng (liên kết với bảng nguoi_dung)',
  `tong_tien` decimal(10,2) NOT NULL COMMENT 'Tổng tiền của hóa đơn',
  `ngay_tao` datetime DEFAULT current_timestamp() COMMENT 'Ngày tạo hóa đơn'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Bảng lưu thông tin hóa đơn';

--
-- Đang đổ dữ liệu cho bảng `hoa_don`
--

INSERT INTO `hoa_don` (`ma_hoa_don`, `ma_khach_hang`, `ma_nguoi_dung`, `tong_tien`, `ngay_tao`) VALUES
(1, 1, 1, 1500000.00, '2024-11-24 22:41:20'),
(2, 2, 2, 730000.00, '2024-11-24 22:41:20'),
(3, 3, 3, 1200000.00, '2024-11-24 22:41:20'),
(4, 4, 4, 500000.00, '2024-11-24 22:41:20'),
(9, 1, 1, 1000000.00, '2024-11-25 00:50:27'),
(10, 4, 1, 1460000.00, '2024-11-25 00:50:50'),
(71, 1, 1, 3800000.00, '2023-02-11 10:00:00'),
(111, 2, 1, 2100000.00, '2024-11-14 15:15:00'),
(138, 10, 1, 2100000.00, '2024-09-29 15:15:00'),
(141, 7, 1, 1900000.00, '2023-10-31 14:10:00');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `khach_hang`
--

CREATE TABLE `khach_hang` (
  `ma_khach_hang` int(11) NOT NULL COMMENT 'Mã khách hàng',
  `ten_khach_hang` varchar(255) NOT NULL COMMENT 'Tên khách hàng',
  `so_dien_thoai` varchar(20) DEFAULT NULL COMMENT 'Số điện thoại',
  `email` varchar(255) DEFAULT NULL COMMENT 'Email của khách hàng',
  `dia_chi` text DEFAULT NULL COMMENT 'Địa chỉ khách hàng',
  `ngay_tao` datetime DEFAULT current_timestamp() COMMENT 'Ngày thêm khách hàng',
  `ngay_cap_nhat` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Ngày cập nhật khách hàng'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Bảng lưu thông tin khách hàng';

--
-- Đang đổ dữ liệu cho bảng `khach_hang`
--

INSERT INTO `khach_hang` (`ma_khach_hang`, `ten_khach_hang`, `so_dien_thoai`, `email`, `dia_chi`, `ngay_tao`, `ngay_cap_nhat`) VALUES
(1, 'Nguyễn Văn A', '0912345678', 'nguyenvana@gmail.com', 'Hà Nội', '2024-11-24 22:41:06', '2024-11-24 22:41:06'),
(2, 'Trần Thị B', '0987654321', 'tranthib@gmail.com', 'Hồ Chí Minh', '2024-11-24 22:41:06', '2024-11-24 22:41:06'),
(3, 'Lê Hoàng C', '0934567890', 'lehoangc@gmail.com', 'Đà Nẵng', '2024-11-24 22:41:06', '2024-11-24 22:41:06'),
(4, 'Phạm Thu DD', '0976543210', 'phamthud@gmail.com', 'Cần Thơ', '2024-11-24 22:41:06', '2024-11-25 00:54:25'),
(6, 'Nguyễn Thị Lý', '0901122333', 'nguyenthily@example.com', '12 Đường ABC, Quận 1, TP. HCM', '2023-02-10 09:20:00', '2024-11-25 02:17:30'),
(7, 'Trần Văn Minh', '0912233444', 'tranvanminh@example.com', '34 Đường DEF, Quận 2, TP. HCM', '2023-04-15 14:35:00', '2024-11-25 02:17:30'),
(8, 'Lê Thị Hoa', '0923344555', 'lethihoa@example.com', '56 Đường GHI, Quận 3, TP. HCM', '2023-06-20 11:10:00', '2024-11-25 02:17:30'),
(9, 'Phạm Văn Hùng', '0934455666', 'phamvanhung@example.com', '78 Đường JKL, Quận 4, TP. HCM', '2023-08-25 16:45:00', '2024-11-25 02:17:30'),
(10, 'Đỗ Thị Mai', '0945566777', 'dothimai@example.com', '90 Đường MNO, Quận 5, TP. HCM', '2023-10-30 13:50:00', '2024-11-25 02:17:30'),
(11, 'Vũ Văn Nam', '0956677888', 'vuvan@example.com', '21 Đường PQR, Quận 6, TP. HCM', '2024-01-05 10:15:00', '2024-11-25 02:17:30'),
(12, 'Bùi Thị Nga', '0967788999', 'buithinga@example.com', '43 Đường STU, Quận 7, TP. HCM', '2024-03-12 15:25:00', '2024-11-25 02:17:30'),
(13, 'Đặng Văn Tuấn', '0978899000', 'dangvantuan@example.com', '65 Đường VWX, Quận 8, TP. HCM', '2024-05-18 12:30:00', '2024-11-25 02:17:30'),
(14, 'Hoàng Thị Nhung', '0989900111', 'hoangthinhung@example.com', '87 Đường YZA, Quận 9, TP. HCM', '2024-07-22 17:40:00', '2024-11-25 02:17:30'),
(15, 'Nguyễn Văn Quốc', '0990011222', 'nguyenvanquoc@example.com', '09 Đường BCD, Quận 10, TP. HCM', '2024-09-28 14:55:00', '2024-11-25 02:17:30');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `nguoi_dung`
--

CREATE TABLE `nguoi_dung` (
  `ma_nguoi_dung` int(11) NOT NULL COMMENT 'Mã người dùng',
  `ten_dang_nhap` varchar(50) NOT NULL COMMENT 'Tên đăng nhập',
  `mat_khau` varchar(255) NOT NULL COMMENT 'Mật khẩu (off hash)',
  `vai_tro` enum('admin','nhan_vien') DEFAULT 'nhan_vien' COMMENT 'Vai trò của người dùng',
  `ngay_tao` datetime DEFAULT current_timestamp() COMMENT 'Ngày tạo tài khoản',
  `ngay_cap_nhat` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Ngày cập nhật tài khoản'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Bảng lưu thông tin người dùng';

--
-- Đang đổ dữ liệu cho bảng `nguoi_dung`
--

INSERT INTO `nguoi_dung` (`ma_nguoi_dung`, `ten_dang_nhap`, `mat_khau`, `vai_tro`, `ngay_tao`, `ngay_cap_nhat`) VALUES
(1, 'admin', '123', 'admin', '2024-11-24 22:41:15', '2024-11-24 22:45:36'),
(2, 'nhanvien1', 'f5fce1662295e09e104dc99a27978510', 'nhan_vien', '2024-11-24 22:41:15', '2024-11-24 22:41:15'),
(3, 'nhanvien2', '480ebc6027e9e74b863504404ca3b186', 'nhan_vien', '2024-11-24 22:41:15', '2024-11-24 22:41:15'),
(4, 'nhanvien3', '5da7b254517f68d4167b59e649bd6522', 'nhan_vien', '2024-11-24 22:41:15', '2024-11-24 22:41:15');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `phien_ban_san_pham`
--

CREATE TABLE `phien_ban_san_pham` (
  `ma_phien_ban` int(11) NOT NULL COMMENT 'Mã phiên bản sản phẩm',
  `ma_san_pham` int(11) NOT NULL COMMENT 'Mã sản phẩm (liên kết với bảng san_pham)',
  `mau_sac` varchar(50) NOT NULL COMMENT 'Màu sắc sản phẩm',
  `kich_co` varchar(10) NOT NULL COMMENT 'Kích cỡ sản phẩm',
  `gia` decimal(10,2) NOT NULL COMMENT 'Giá của phiên bản sản phẩm',
  `so_luong_ton` int(11) NOT NULL DEFAULT 0 COMMENT 'Số lượng tồn kho',
  `ngay_tao` datetime DEFAULT current_timestamp() COMMENT 'Ngày thêm phiên bản',
  `ngay_cap_nhat` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Ngày cập nhật phiên bản'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Bảng lưu thông tin chi tiết phiên bản sản phẩm';

--
-- Đang đổ dữ liệu cho bảng `phien_ban_san_pham`
--

INSERT INTO `phien_ban_san_pham` (`ma_phien_ban`, `ma_san_pham`, `mau_sac`, `kich_co`, `gia`, `so_luong_ton`, `ngay_tao`, `ngay_cap_nhat`) VALUES
(1, 1, 'Trắng', '42', 500000.00, 20, '2024-11-24 22:41:00', '2024-11-24 22:41:00'),
(2, 1, 'Đen', '41', 520000.00, 15, '2024-11-24 22:41:00', '2024-11-24 22:41:00'),
(3, 2, 'Đỏ', '38', 750000.00, 10, '2024-11-24 22:41:00', '2024-11-24 22:41:00'),
(4, 2, 'Đen', '39', 730000.00, 12, '2024-11-24 22:41:00', '2024-11-24 22:41:00'),
(5, 3, 'Nâu', '40', 480000.00, 25, '2024-11-24 22:41:00', '2024-11-24 22:41:00'),
(6, 3, 'Đen', '42', 500000.00, 18, '2024-11-24 22:41:00', '2024-11-24 22:41:00'),
(7, 4, 'Đen', '37', 1200000.00, 5, '2024-11-24 22:41:00', '2024-11-24 22:41:00'),
(8, 4, 'Xám', '38', 1150000.00, 8, '2024-11-24 22:41:00', '2024-11-24 22:41:00'),
(9, 5, 'Đen', '31', 100000.00, 10, '2024-11-25 01:19:40', '2024-11-25 01:19:40'),
(10, 1, 'Xanh Lá', '42', 1600000.00, 40, '2024-11-25 02:17:21', '2024-11-25 02:17:21'),
(11, 1, 'Đỏ', '43', 1600000.00, 35, '2024-11-25 02:17:21', '2024-11-25 02:17:21'),
(12, 1, 'Đen', '44', 1600000.00, 30, '2024-11-25 02:17:21', '2024-11-25 02:17:21'),
(13, 2, 'Trắng', '40', 1400000.00, 50, '2024-11-25 02:17:21', '2024-11-25 02:17:21'),
(14, 2, 'Xám', '41', 1400000.00, 45, '2024-11-25 02:17:21', '2024-11-25 02:17:21'),
(15, 2, 'Xanh Dương', '42', 1400000.00, 40, '2024-11-25 02:17:21', '2024-11-25 02:17:21'),
(16, 3, 'Hồng', '36', 2200000.00, 25, '2024-11-25 02:17:21', '2024-11-25 02:17:21'),
(17, 3, 'Nâu', '37', 2200000.00, 20, '2024-11-25 02:17:21', '2024-11-25 02:17:21'),
(18, 4, 'Đen', '43', 1900000.00, 30, '2024-11-25 02:17:21', '2024-11-25 02:17:21'),
(19, 4, 'Nâu', '44', 1900000.00, 25, '2024-11-25 02:17:21', '2024-11-25 02:17:21'),
(20, 5, 'Xanh', '28', 900000.00, 60, '2024-11-25 02:17:22', '2024-11-25 02:17:22'),
(21, 5, 'Vàng', '29', 900000.00, 55, '2024-11-25 02:17:22', '2024-11-25 02:17:22'),
(22, 6, 'Xanh Dương', '43', 1500000.00, 40, '2024-11-25 02:17:22', '2024-11-25 02:17:22'),
(23, 6, 'Đen', '44', 1500000.00, 35, '2024-11-25 02:17:22', '2024-11-25 02:17:22'),
(24, 7, 'Be', '38', 1300000.00, 50, '2024-11-25 02:17:22', '2024-11-25 02:17:22'),
(25, 7, 'Nâu', '39', 1300000.00, 45, '2024-11-25 02:17:22', '2024-11-25 02:17:22'),
(26, 7, 'Đen', '40', 1300000.00, 40, '2024-11-25 02:17:22', '2024-11-25 02:17:22'),
(27, 8, 'Đỏ', '44', 1700000.00, 30, '2024-11-25 02:17:22', '2024-11-25 02:17:22'),
(28, 8, 'Đen', '45', 1700000.00, 25, '2024-11-25 02:17:22', '2024-11-25 02:17:22'),
(29, 9, 'Hồng', '36', 1500000.00, 35, '2024-11-25 02:17:22', '2024-11-25 02:17:22'),
(30, 9, 'Xanh Lá', '37', 1500000.00, 30, '2024-11-25 02:17:22', '2024-11-25 02:17:22'),
(31, 9, 'Vàng', '38', 1500000.00, 25, '2024-11-25 02:17:22', '2024-11-25 02:17:22'),
(32, 10, 'Trắng', '40', 1800000.00, 20, '2024-11-25 02:17:22', '2024-11-25 02:17:22'),
(33, 10, 'Đen', '41', 1800000.00, 15, '2024-11-25 02:17:22', '2024-11-25 02:17:22');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `san_pham`
--

CREATE TABLE `san_pham` (
  `ma_san_pham` int(11) NOT NULL COMMENT 'Mã sản phẩm',
  `ten_san_pham` varchar(255) NOT NULL COMMENT 'Tên sản phẩm',
  `danh_muc` varchar(100) DEFAULT NULL COMMENT 'Danh mục sản phẩm',
  `mo_ta` text DEFAULT NULL COMMENT 'Mô tả chi tiết sản phẩm',
  `ngay_tao` datetime DEFAULT current_timestamp() COMMENT 'Ngày thêm sản phẩm',
  `ngay_cap_nhat` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Ngày cập nhật sản phẩm'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Bảng lưu thông tin cơ bản của sản phẩm';

--
-- Đang đổ dữ liệu cho bảng `san_pham`
--

INSERT INTO `san_pham` (`ma_san_pham`, `ten_san_pham`, `danh_muc`, `mo_ta`, `ngay_tao`, `ngay_cap_nhat`) VALUES
(1, 'Giày Thể Thao Nam', 'Thể Thao', 'Giày thể thao dành cho nam, kiểu dáng hiện đại.', '2024-11-24 22:40:55', '2024-11-24 22:40:55'),
(2, 'Giày Cao Gót Nữ', 'Cao Gót', 'Giày cao gót dành cho nữ, phong cách sang trọng.', '2024-11-24 22:40:55', '2024-11-24 22:40:55'),
(3, 'Giày Lười Nam', 'Giày Lười', 'Giày lười nam, chất liệu da mềm, dễ đi.', '2024-11-24 22:40:55', '2024-11-24 22:40:55'),
(4, 'Giày Boot Nữ', 'Boot', 'Giày boot nữ, thích hợp cho mùa đông.', '2024-11-24 22:40:55', '2024-11-24 22:40:55'),
(5, 'Sản phẩm 1', '1', 'abc', '2024-11-25 01:18:48', '2024-11-25 01:18:48'),
(6, 'Giày Chạy Bộ Nam', 'Giày Thể Thao', 'Giày chạy bộ nam nhẹ nhàng, thoải mái cho mọi cự ly.', '2024-11-25 02:17:12', '2024-11-25 02:17:12'),
(7, 'Giày Sneaker Unisex', 'Giày Sneaker', 'Giày sneaker unisex phong cách, phù hợp với mọi lứa tuổi.', '2024-11-25 02:17:12', '2024-11-25 02:17:12'),
(8, 'Giày Cao Gót Lười Nữ', 'Giày Cao Gót', 'Giày cao gót lười nữ tiện lợi, phù hợp cho công sở.', '2024-11-25 02:17:12', '2024-11-25 02:17:12'),
(9, 'Giày Da Boots Nam', 'Giày Boots', 'Giày da boots nam chất lượng cao, bền bỉ với thời gian.', '2024-11-25 02:17:12', '2024-11-25 02:17:12'),
(10, 'Giày Búp Bê Bé Trai', 'Giày Trẻ Em', 'Giày búp bê cho bé trai, an toàn và thoải mái cho đôi chân nhỏ.', '2024-11-25 02:17:12', '2024-11-25 02:17:12'),
(11, 'Giày Đế Xuồng Nam', 'Giày Đi Bộ', 'Giày đế xuồng nam hỗ trợ tối ưu cho các buổi đi bộ dài.', '2024-11-25 02:17:12', '2024-11-25 02:17:12'),
(12, 'Giày Lười Da Nữ', 'Giày Lười', 'Giày lười da nữ sang trọng, dễ phối đồ với nhiều loại trang phục.', '2024-11-25 02:17:12', '2024-11-25 02:17:12'),
(13, 'Giày Bóng Rổ Unisex', 'Giày Bóng Rổ', 'Giày bóng rổ unisex thiết kế hiện đại, tăng cường hiệu suất chơi.', '2024-11-25 02:17:12', '2024-11-25 02:17:12'),
(14, 'Giày Thể Thao Trẻ Em', 'Giày Thể Thao', 'Giày thể thao cho trẻ em, hỗ trợ sự phát triển chân khỏe mạnh.', '2024-11-25 02:17:12', '2024-11-25 02:17:12'),
(15, 'Giày Tăng Chiều Cao Nam', 'Giày Thời Trang', 'Giày tăng chiều cao nam với thiết kế thời trang và hiện đại.', '2024-11-25 02:17:12', '2024-11-25 02:17:12');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `chi_tiet_hoa_don`
--
ALTER TABLE `chi_tiet_hoa_don`
  ADD PRIMARY KEY (`ma_chi_tiet`),
  ADD KEY `ma_hoa_don` (`ma_hoa_don`),
  ADD KEY `ma_phien_ban` (`ma_phien_ban`);

--
-- Chỉ mục cho bảng `hoa_don`
--
ALTER TABLE `hoa_don`
  ADD PRIMARY KEY (`ma_hoa_don`),
  ADD KEY `ma_khach_hang` (`ma_khach_hang`),
  ADD KEY `ma_nguoi_dung` (`ma_nguoi_dung`);

--
-- Chỉ mục cho bảng `khach_hang`
--
ALTER TABLE `khach_hang`
  ADD PRIMARY KEY (`ma_khach_hang`);

--
-- Chỉ mục cho bảng `nguoi_dung`
--
ALTER TABLE `nguoi_dung`
  ADD PRIMARY KEY (`ma_nguoi_dung`),
  ADD UNIQUE KEY `ten_dang_nhap` (`ten_dang_nhap`);

--
-- Chỉ mục cho bảng `phien_ban_san_pham`
--
ALTER TABLE `phien_ban_san_pham`
  ADD PRIMARY KEY (`ma_phien_ban`),
  ADD KEY `ma_san_pham` (`ma_san_pham`);

--
-- Chỉ mục cho bảng `san_pham`
--
ALTER TABLE `san_pham`
  ADD PRIMARY KEY (`ma_san_pham`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `chi_tiet_hoa_don`
--
ALTER TABLE `chi_tiet_hoa_don`
  MODIFY `ma_chi_tiet` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Mã chi tiết hóa đơn', AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT cho bảng `hoa_don`
--
ALTER TABLE `hoa_don`
  MODIFY `ma_hoa_don` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Mã hóa đơn', AUTO_INCREMENT=142;

--
-- AUTO_INCREMENT cho bảng `khach_hang`
--
ALTER TABLE `khach_hang`
  MODIFY `ma_khach_hang` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Mã khách hàng', AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT cho bảng `nguoi_dung`
--
ALTER TABLE `nguoi_dung`
  MODIFY `ma_nguoi_dung` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Mã người dùng', AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `phien_ban_san_pham`
--
ALTER TABLE `phien_ban_san_pham`
  MODIFY `ma_phien_ban` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Mã phiên bản sản phẩm', AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT cho bảng `san_pham`
--
ALTER TABLE `san_pham`
  MODIFY `ma_san_pham` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Mã sản phẩm', AUTO_INCREMENT=16;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `chi_tiet_hoa_don`
--
ALTER TABLE `chi_tiet_hoa_don`
  ADD CONSTRAINT `chi_tiet_hoa_don_ibfk_1` FOREIGN KEY (`ma_hoa_don`) REFERENCES `hoa_don` (`ma_hoa_don`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `chi_tiet_hoa_don_ibfk_2` FOREIGN KEY (`ma_phien_ban`) REFERENCES `phien_ban_san_pham` (`ma_phien_ban`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `hoa_don`
--
ALTER TABLE `hoa_don`
  ADD CONSTRAINT `hoa_don_ibfk_1` FOREIGN KEY (`ma_khach_hang`) REFERENCES `khach_hang` (`ma_khach_hang`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `hoa_don_ibfk_2` FOREIGN KEY (`ma_nguoi_dung`) REFERENCES `nguoi_dung` (`ma_nguoi_dung`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `phien_ban_san_pham`
--
ALTER TABLE `phien_ban_san_pham`
  ADD CONSTRAINT `phien_ban_san_pham_ibfk_1` FOREIGN KEY (`ma_san_pham`) REFERENCES `san_pham` (`ma_san_pham`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

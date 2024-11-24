-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th10 24, 2024 lúc 04:39 PM
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

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `nguoi_dung`
--

CREATE TABLE `nguoi_dung` (
  `ma_nguoi_dung` int(11) NOT NULL COMMENT 'Mã người dùng',
  `ten_dang_nhap` varchar(50) NOT NULL COMMENT 'Tên đăng nhập',
  `mat_khau` varchar(255) NOT NULL COMMENT 'Mật khẩu (hash)',
  `vai_tro` enum('admin','nhan_vien') DEFAULT 'nhan_vien' COMMENT 'Vai trò của người dùng',
  `ngay_tao` datetime DEFAULT current_timestamp() COMMENT 'Ngày tạo tài khoản',
  `ngay_cap_nhat` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Ngày cập nhật tài khoản'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Bảng lưu thông tin người dùng';

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
  MODIFY `ma_chi_tiet` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Mã chi tiết hóa đơn';

--
-- AUTO_INCREMENT cho bảng `hoa_don`
--
ALTER TABLE `hoa_don`
  MODIFY `ma_hoa_don` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Mã hóa đơn';

--
-- AUTO_INCREMENT cho bảng `khach_hang`
--
ALTER TABLE `khach_hang`
  MODIFY `ma_khach_hang` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Mã khách hàng';

--
-- AUTO_INCREMENT cho bảng `nguoi_dung`
--
ALTER TABLE `nguoi_dung`
  MODIFY `ma_nguoi_dung` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Mã người dùng';

--
-- AUTO_INCREMENT cho bảng `phien_ban_san_pham`
--
ALTER TABLE `phien_ban_san_pham`
  MODIFY `ma_phien_ban` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Mã phiên bản sản phẩm';

--
-- AUTO_INCREMENT cho bảng `san_pham`
--
ALTER TABLE `san_pham`
  MODIFY `ma_san_pham` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Mã sản phẩm';

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

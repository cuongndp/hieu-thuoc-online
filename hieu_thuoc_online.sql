-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th7 05, 2025 lúc 11:08 AM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `hieu_thuoc_online`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chi_tiet_don_hang`
--

DROP TABLE IF EXISTS `chi_tiet_don_hang`;
CREATE TABLE `chi_tiet_don_hang` (
  `ma_chi_tiet` int(11) NOT NULL,
  `ma_don_hang` int(11) NOT NULL,
  `ma_san_pham` int(11) NOT NULL,
  `ten_san_pham` varchar(200) NOT NULL,
  `so_luong` int(11) NOT NULL,
  `don_gia` decimal(10,2) NOT NULL,
  `thanh_tien` decimal(10,2) NOT NULL,
  `ngay_tao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `chi_tiet_don_hang`
--

INSERT INTO `chi_tiet_don_hang` (`ma_chi_tiet`, `ma_don_hang`, `ma_san_pham`, `ten_san_pham`, `so_luong`, `don_gia`, `thanh_tien`, `ngay_tao`) VALUES
(3, 7, 8, 'Sản phẩm 8', 1, 100000.00, 100000.00, '2025-06-06 11:32:59'),
(4, 7, 17, 'Sản phẩm 17', 1, 100000.00, 100000.00, '2025-06-06 11:32:59'),
(5, 7, 40, 'Sản phẩm 40', 1, 100000.00, 100000.00, '2025-06-06 11:32:59'),
(6, 7, 33, 'Sản phẩm 33', 1, 100000.00, 100000.00, '2025-06-06 11:32:59'),
(7, 7, 28, 'Sản phẩm 28', 1, 100000.00, 100000.00, '2025-06-06 11:32:59'),
(8, 8, 3, 'Amoxicillin 250mg', 2, 45000.00, 90000.00, '2025-06-06 11:34:38'),
(9, 8, 18, 'Sản phẩm 18', 1, 100000.00, 100000.00, '2025-06-06 11:34:38'),
(10, 8, 28, 'Sản phẩm 28', 1, 100000.00, 100000.00, '2025-06-06 11:34:38'),
(11, 9, 36, 'Bioderma Sensibio H2O Micellar Water', 2, 320000.00, 640000.00, '2025-06-14 05:50:12'),
(12, 9, 2, 'Aspirin Bayer 100mg', 1, 35000.00, 35000.00, '2025-06-14 05:50:12'),
(13, 9, 25, 'Glucosamine Osteo Bi-Flex 1500mg', 1, 355000.00, 355000.00, '2025-06-14 05:50:12'),
(14, 9, 18, 'Centrum Multivitamin Adults', 1, 520000.00, 520000.00, '2025-06-14 05:50:12'),
(15, 10, 18, 'Centrum Multivitamin Adults', 1, 520000.00, 520000.00, '2025-06-14 07:03:58'),
(16, 10, 2, 'Aspirin Bayer 100mg', 1, 35000.00, 35000.00, '2025-06-14 07:03:58'),
(17, 11, 1, 'Paracetamol Stada 500mg', 3, 30000.00, 90000.00, '2025-06-14 08:10:06'),
(18, 12, 36, 'Bioderma Sensibio H2O Micellar Water', 1, 320000.00, 320000.00, '2025-06-20 08:58:29'),
(19, 13, 28, 'Collagen Neocell Super', 1, 750000.00, 750000.00, '2025-06-20 09:01:57'),
(20, 13, 3, 'Ibuprofen Brufen 400mg', 1, 55000.00, 55000.00, '2025-06-20 09:01:57'),
(21, 14, 28, 'Collagen Neocell Super', 1, 750000.00, 750000.00, '2025-06-20 16:43:04'),
(22, 15, 25, 'Glucosamine Osteo Bi-Flex 1500mg', 1, 355000.00, 355000.00, '2025-06-20 16:43:22'),
(23, 16, 26, 'Ginkgo Biloba Nature Made', 1, 280000.00, 280000.00, '2025-06-20 16:44:21'),
(24, 17, 1, 'Paracetamol Stada 500mg', 1, 30000.00, 30000.00, '2025-06-20 16:44:32'),
(25, 18, 37, 'Vichy Aqualia Thermal Cream', 1, 550000.00, 550000.00, '2025-06-20 16:44:46'),
(26, 19, 29, 'Probiotics Align Daily', 1, 420000.00, 420000.00, '2025-06-20 16:44:58'),
(27, 20, 28, 'Collagen Neocell Super', 1, 750000.00, 750000.00, '2025-06-20 16:52:56'),
(28, 21, 25, 'Glucosamine Osteo Bi-Flex 1500mg', 1, 355000.00, 355000.00, '2025-06-20 17:00:05'),
(29, 21, 18, 'Centrum Multivitamin Adults', 1, 520000.00, 520000.00, '2025-06-20 17:00:05'),
(30, 21, 9, 'Amoxicillin Hasan 500mg', 1, 85000.00, 85000.00, '2025-06-20 17:00:05'),
(31, 22, 33, 'La Roche Posay Toleriane Caring Wash', 1, 450000.00, 450000.00, '2025-06-20 17:23:16'),
(32, 23, 42, 'AccuCheck Performa Blood Glucose Meter', 1, 520000.00, 520000.00, '2025-06-21 05:53:23'),
(33, 23, 33, 'La Roche Posay Toleriane Caring Wash', 2, 450000.00, 900000.00, '2025-06-21 05:53:23'),
(34, 23, 2, 'Aspirin Bayer 100mg', 1, 35000.00, 35000.00, '2025-06-21 05:53:23'),
(35, 23, 5, 'Alaxan FR Capsule', 1, 48000.00, 48000.00, '2025-06-21 05:53:23'),
(36, 23, 6, 'Efferalgan 500mg', 1, 65000.00, 65000.00, '2025-06-21 05:53:23'),
(37, 24, 36, 'Bioderma Sensibio H2O Micellar Water', 1, 320000.00, 320000.00, '2025-07-03 20:00:37'),
(38, 24, 5, 'Alaxan FR Capsule', 3, 48000.00, 144000.00, '2025-07-03 20:00:37'),
(39, 24, 42, 'AccuCheck Performa Blood Glucose Meter', 1, 520000.00, 520000.00, '2025-07-03 20:00:37'),
(40, 24, 18, 'Centrum Multivitamin Adults', 1, 520000.00, 520000.00, '2025-07-03 20:00:37'),
(41, 24, 2, 'Aspirin Bayer 100mg', 2, 35000.00, 70000.00, '2025-07-03 20:00:37'),
(42, 24, 1, 'Paracetamol Stada 500mg', 1, 30000.00, 30000.00, '2025-07-03 20:00:37'),
(43, 25, 82, 'Rhodiola Rosea 400mg', 1, 680000.00, 680000.00, '2025-07-03 20:03:08'),
(44, 26, 82, 'Rhodiola Rosea 400mg', 1, 680000.00, 680000.00, '2025-07-03 20:05:00'),
(45, 27, 82, 'Rhodiola Rosea 400mg', 1, 680000.00, 680000.00, '2025-07-03 20:07:33'),
(46, 28, 57, 'Motrin IB 400mg', 1, 95000.00, 95000.00, '2025-07-04 05:41:14'),
(47, 28, 25, 'Glucosamine Osteo Bi-Flex 1500mg', 1, 150000.00, 150000.00, '2025-07-04 05:41:14'),
(48, 28, 8, 'Betadine Throat Spray', 1, 42500.00, 42500.00, '2025-07-04 05:41:14'),
(49, 28, 6, 'Efferalgan 500mg', 1, 32500.00, 32500.00, '2025-07-04 05:41:14'),
(50, 28, 5, 'Alaxan FR Capsule', 1, 24000.00, 24000.00, '2025-07-04 05:41:14'),
(51, 28, 3, 'Ibuprofen Brufen 400mg', 2, 22500.00, 45000.00, '2025-07-04 05:41:14'),
(52, 28, 56, 'Advil Liqui-Gels 200mg', 1, 125000.00, 125000.00, '2025-07-04 05:41:14'),
(53, 29, 8, 'Betadine Throat Spray', 1, 42500.00, 42500.00, '2025-07-04 05:42:18'),
(54, 29, 6, 'Efferalgan 500mg', 1, 32500.00, 32500.00, '2025-07-04 05:42:18'),
(55, 29, 5, 'Alaxan FR Capsule', 1, 24000.00, 24000.00, '2025-07-04 05:42:18'),
(56, 29, 3, 'Ibuprofen Brufen 400mg', 1, 22500.00, 22500.00, '2025-07-04 05:42:18'),
(57, 29, 56, 'Advil Liqui-Gels 200mg', 1, 125000.00, 125000.00, '2025-07-04 05:42:18'),
(58, 29, 82, 'Rhodiola Rosea 400mg', 1, 680000.00, 680000.00, '2025-07-04 05:42:18'),
(59, 30, 63, 'Robitussin Cough Syrup', 1, 165000.00, 165000.00, '2025-07-04 19:05:25'),
(60, 31, 3, 'Ibuprofen Brufen 400mg', 1, 22500.00, 22500.00, '2025-07-05 08:21:10'),
(61, 31, 56, 'Advil Liqui-Gels 200mg', 1, 125000.00, 125000.00, '2025-07-05 08:21:10'),
(62, 31, 80, 'Turmeric Curcumin 500mg', 1, 420000.00, 420000.00, '2025-07-05 08:21:10'),
(63, 32, 3, 'Ibuprofen Brufen 400mg', 1, 22500.00, 22500.00, '2025-07-05 08:52:40');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `danh_gia_san_pham`
--

DROP TABLE IF EXISTS `danh_gia_san_pham`;
CREATE TABLE `danh_gia_san_pham` (
  `ma_danh_gia` int(11) NOT NULL,
  `ma_nguoi_dung` int(11) NOT NULL,
  `ma_san_pham` int(11) NOT NULL,
  `ma_don_hang` int(11) DEFAULT NULL,
  `so_sao` int(1) NOT NULL CHECK (`so_sao` >= 1 and `so_sao` <= 5),
  `tieu_de` varchar(200) DEFAULT NULL,
  `noi_dung` text NOT NULL,
  `trang_thai` enum('cho_duyet','da_duyet','tu_choi') DEFAULT 'cho_duyet',
  `ngay_tao` timestamp NOT NULL DEFAULT current_timestamp(),
  `ngay_cap_nhat` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `danh_gia_san_pham`
--

INSERT INTO `danh_gia_san_pham` (`ma_danh_gia`, `ma_nguoi_dung`, `ma_san_pham`, `ma_don_hang`, `so_sao`, `tieu_de`, `noi_dung`, `trang_thai`, `ngay_tao`, `ngay_cap_nhat`) VALUES
(1, 4, 82, 25, 5, 'thuốc tốt lắm', 'jghhhhhhhhhhhhhhhhhh', 'da_duyet', '2025-07-03 20:05:41', '2025-07-03 20:05:41'),
(2, 4, 3, 31, 5, 'thuốc tốt lắm', 'hjghjghjgh', 'da_duyet', '2025-07-05 08:22:32', '2025-07-05 08:22:32');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `danh_muc_thuoc`
--

DROP TABLE IF EXISTS `danh_muc_thuoc`;
CREATE TABLE `danh_muc_thuoc` (
  `ma_danh_muc` int(11) NOT NULL,
  `ten_danh_muc` varchar(100) NOT NULL,
  `mo_ta` text DEFAULT NULL,
  `ma_danh_muc_cha` int(11) DEFAULT NULL,
  `hinh_anh` varchar(255) DEFAULT NULL,
  `trang_thai_hoat_dong` tinyint(1) DEFAULT 1,
  `ngay_tao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `danh_muc_thuoc`
--

INSERT INTO `danh_muc_thuoc` (`ma_danh_muc`, `ten_danh_muc`, `mo_ta`, `ma_danh_muc_cha`, `hinh_anh`, `trang_thai_hoat_dong`, `ngay_tao`) VALUES
(1, 'Thuốc không kê đơn', 'Các loại thuốc thông dụng có thể mua không cần đơn thuốc', NULL, NULL, 1, '2025-06-01 15:44:51'),
(2, 'Thuốc kê đơn', 'Thuốc cần có đơn thuốc của bác sĩ để mua', NULL, NULL, 1, '2025-06-01 15:44:51'),
(3, 'Vitamin & Khoáng chất', 'Bổ sung vitamin và khoáng chất thiết yếu cho cơ thể', NULL, NULL, 1, '2025-06-01 15:44:51'),
(4, 'Thực phẩm chức năng', 'Sản phẩm hỗ trợ sức khỏe và dinh dưỡng', NULL, NULL, 1, '2025-06-01 15:44:51'),
(5, 'Dược mỹ phẩm', 'Sản phẩm chăm sóc da và làm đẹp', NULL, NULL, 1, '2025-06-01 15:44:51'),
(6, 'Thiết bị y tế', 'Các thiết bị y tế gia đình và chuyên nghiệp', NULL, NULL, 1, '2025-06-01 15:44:51'),
(7, 'Mẹ & bé', 'Sản phẩm chăm sóc cho mẹ và bé yêu', NULL, NULL, 1, '2025-06-01 15:44:51');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `dia_chi`
--

DROP TABLE IF EXISTS `dia_chi`;
CREATE TABLE `dia_chi` (
  `ma_dia_chi` int(11) NOT NULL,
  `ma_nguoi_dung` int(11) NOT NULL,
  `loai_dia_chi` enum('nha_rieng','van_phong','khac') DEFAULT 'nha_rieng',
  `ten_nguoi_nhan` varchar(100) NOT NULL,
  `so_dien_thoai` varchar(15) NOT NULL,
  `dia_chi_chi_tiet` text NOT NULL,
  `phuong_xa` varchar(100) DEFAULT NULL,
  `quan_huyen` varchar(100) NOT NULL,
  `tinh_thanh` varchar(100) NOT NULL,
  `ma_buu_dien` varchar(10) DEFAULT NULL,
  `la_dia_chi_mac_dinh` tinyint(1) DEFAULT 0,
  `ngay_tao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `dia_chi`
--

INSERT INTO `dia_chi` (`ma_dia_chi`, `ma_nguoi_dung`, `loai_dia_chi`, `ten_nguoi_nhan`, `so_dien_thoai`, `dia_chi_chi_tiet`, `phuong_xa`, `quan_huyen`, `tinh_thanh`, `ma_buu_dien`, `la_dia_chi_mac_dinh`, `ngay_tao`) VALUES
(10, 5, 'nha_rieng', 'Cường Nguyễn Đăng Phúc', '0982731369', 'hhg, Phường 3, Quận Bình Thạnh, TP. Hồ Chí Minh', 'Phường 3', 'Quận Bình Thạnh', 'TP. Hồ Chí Minh', '5675', 0, '2025-06-06 11:32:59'),
(11, 5, 'nha_rieng', 'Cường Nguyễn Đăng Phúc', '0982731369', 'nhà tao, Phường 2, Quận 3, TP. Hồ Chí Minh', 'Phường 2', 'Quận 3', 'TP. Hồ Chí Minh', '5675', 0, '2025-06-06 11:34:38'),
(12, 5, 'nha_rieng', 'Cường Nguyễn Đăng Phúc', '0982731369', 'ghmbn, Phường 2, Quận 7, Hà Nội', 'Phường 2', 'Quận 7', 'Hà Nội', '5675', 0, '2025-06-14 05:50:12'),
(13, 5, 'nha_rieng', 'Cường Nguyễn Đăng Phúc', '0982731369', 'gdf, Phường Điện Biên, Quận Ba Đình, Hà Nội', 'Phường Điện Biên', 'Quận Ba Đình', 'Hà Nội', '10000', 0, '2025-06-14 07:03:58'),
(14, 5, 'nha_rieng', 'Cường Nguyễn Đăng Phúc', '0982731369', 'j, Phường Hàng Bài, Quận Hoàn Kiếm, Hà Nội', 'Phường Hàng Bài', 'Quận Hoàn Kiếm', 'Hà Nội', '10000', 0, '2025-06-14 08:10:06'),
(15, 5, 'nha_rieng', 'Cường Nguyễn Đăng Phúc', '0982731369', 'jhg, Phường Xuân Khánh, Quận Ninh Kiều, Cần Thơ', 'Phường Xuân Khánh', 'Quận Ninh Kiều', 'Cần Thơ', '94000', 0, '2025-06-20 08:58:29'),
(16, 5, 'nha_rieng', 'Cường Nguyễn Đăng Phúc', '0982731369', 'gvg, Phường 3, Quận 3, TP. Hồ Chí Minh', 'Phường 3', 'Quận 3', 'TP. Hồ Chí Minh', '70000', 0, '2025-06-20 09:01:57'),
(17, 5, 'nha_rieng', 'Cường Nguyễn Đăng Phúc', '0982731369', 'dfg, Phường 3, Quận Bình Thạnh, TP. Hồ Chí Minh', 'Phường 3', 'Quận Bình Thạnh', 'TP. Hồ Chí Minh', '70000', 0, '2025-06-20 16:43:04'),
(18, 5, 'nha_rieng', 'Cường Nguyễn Đăng Phúc', '0982731369', 'dfg, Phường 4, Quận Cầu Giấy, Hà Nội', 'Phường 4', 'Quận Cầu Giấy', 'Hà Nội', '10000', 0, '2025-06-20 16:43:22'),
(19, 5, 'nha_rieng', 'Cường Nguyễn Đăng Phúc', '0982731369', 'g, Phường 4, Quận Ngũ Hành Sơn, Đà Nẵng', 'Phường 4', 'Quận Ngũ Hành Sơn', 'Đà Nẵng', '50000', 0, '2025-06-20 16:44:21'),
(20, 5, 'nha_rieng', 'Cường Nguyễn Đăng Phúc', '0982731369', 'g, Phường 4, Quận Cái Răng, Cần Thơ', 'Phường 4', 'Quận Cái Răng', 'Cần Thơ', '94000', 0, '2025-06-20 16:44:32'),
(21, 5, 'nha_rieng', 'Cường Nguyễn Đăng Phúc', '0982731369', 'g, Phường 6, Quận Thanh Xuân, Hà Nội', 'Phường 6', 'Quận Thanh Xuân', 'Hà Nội', '10000', 0, '2025-06-20 16:44:46'),
(22, 5, 'nha_rieng', 'Cường Nguyễn Đăng Phúc', '0982731369', 'g, Phường Quang Trung, Quận Đống Đa, Hà Nội', 'Phường Quang Trung', 'Quận Đống Đa', 'Hà Nội', '10000', 0, '2025-06-20 16:44:58'),
(23, 5, 'nha_rieng', 'Cường Nguyễn Đăng Phúc', '0982731369', 'h, Phường 5, Quận Ngũ Hành Sơn, Đà Nẵng', 'Phường 5', 'Quận Ngũ Hành Sơn', 'Đà Nẵng', '50000', 0, '2025-06-20 16:52:56'),
(24, 5, 'nha_rieng', 'Cường Nguyễn Đăng Phúc', '0982731369', 'ghj, Phường 3, Quận Ngũ Hành Sơn, Đà Nẵng', 'Phường 3', 'Quận Ngũ Hành Sơn', 'Đà Nẵng', '50000', 0, '2025-06-20 17:00:05'),
(25, 5, 'nha_rieng', 'Cường Nguyễn Đăng Phúc', '0982731369', 'g, Phường 7, Quận Ngũ Hành Sơn, Đà Nẵng', 'Phường 7', 'Quận Ngũ Hành Sơn', 'Đà Nẵng', '50000', 0, '2025-06-20 17:23:16'),
(26, 5, 'nha_rieng', 'Cường Nguyễn Đăng Phúc', '0982731369', 'b, Phường Quang Trung, Quận Đống Đa, Hà Nội', 'Phường Quang Trung', 'Quận Đống Đa', 'Hà Nội', '10000', 0, '2025-06-21 05:53:23'),
(27, 4, 'nha_rieng', 'Cường Nguyễn Đăng Phúc', '0706259406', 'hỵ, Phường 3, Quận Ngũ Hành Sơn, Đà Nẵng', 'Phường 3', 'Quận Ngũ Hành Sơn', 'Đà Nẵng', '50000', 0, '2025-07-03 20:00:37'),
(28, 4, 'nha_rieng', 'Cường Nguyễn Đăng Phúc', '0706259406', 'h, Phường 8, Quận Phú Nhuận, TP. Hồ Chí Minh', 'Phường 8', 'Quận Phú Nhuận', 'TP. Hồ Chí Minh', '70000', 0, '2025-07-03 20:03:08'),
(29, 4, 'nha_rieng', 'Cường Nguyễn Đăng Phúc', '0706259406', 'g, Phường 7, Quận Gò Vấp, TP. Hồ Chí Minh', 'Phường 7', 'Quận Gò Vấp', 'TP. Hồ Chí Minh', '70000', 0, '2025-07-03 20:05:00'),
(30, 4, 'nha_rieng', 'Cường Nguyễn Đăng Phúc', '0706259407', 'h, Phường 9, Quận Phú Nhuận, TP. Hồ Chí Minh', 'Phường 9', 'Quận Phú Nhuận', 'TP. Hồ Chí Minh', '70000', 0, '2025-07-03 20:07:33'),
(31, 8, 'nha_rieng', 'Cường Nguyễn Đăng Phúc', '0706259402', 'ug, Phường Minh Khai, Quận Hai Bà Trưng, Hà Nội', 'Phường Minh Khai', 'Quận Hai Bà Trưng', 'Hà Nội', '10000', 0, '2025-07-04 05:41:14'),
(32, 8, 'nha_rieng', 'Cường Nguyễn Đăng Phúc', '0706259402', 'khg, Phường 3, Quận 3, TP. Hồ Chí Minh', 'Phường 3', 'Quận 3', 'TP. Hồ Chí Minh', '70000', 0, '2025-07-04 05:42:18'),
(33, 8, 'nha_rieng', 'Cường Nguyễn Đăng Phúc', '0706259402', 'f, Phường 8, Quận Cẩm Lệ, Đà Nẵng', 'Phường 8', 'Quận Cẩm Lệ', 'Đà Nẵng', '50000', 0, '2025-07-04 19:05:25'),
(34, 4, 'nha_rieng', 'Cường Nguyễn Đăng Phúc', '0706259407', 'ygjg, Phường 7, Quận Phú Nhuận, TP. Hồ Chí Minh', 'Phường 7', 'Quận Phú Nhuận', 'TP. Hồ Chí Minh', '70000', 0, '2025-07-05 08:21:10'),
(35, 4, 'nha_rieng', 'Cường Nguyễn Đăng Phúc', '0706259407', 'jh, Phường Láng Hạ, Quận Đống Đa, Hà Nội', 'Phường Láng Hạ', 'Quận Đống Đa', 'Hà Nội', '10000', 0, '2025-07-05 08:52:40');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `don_hang`
--

DROP TABLE IF EXISTS `don_hang`;
CREATE TABLE `don_hang` (
  `ma_don_hang` int(11) NOT NULL,
  `ma_nguoi_dung` int(11) NOT NULL,
  `so_don_hang` varchar(50) NOT NULL,
  `trang_thai_don_hang` enum('cho_xac_nhan','da_xac_nhan','dang_xu_ly','dang_giao','da_giao','da_huy','da_hoan_tien') DEFAULT 'cho_xac_nhan',
  `trang_thai_thanh_toan` enum('chua_thanh_toan','da_thanh_toan','that_bai','da_hoan_tien') DEFAULT 'chua_thanh_toan',
  `phuong_thuc_thanh_toan` enum('tien_mat','chuyen_khoan','the_tin_dung','vi_dien_tu') NOT NULL,
  `tong_tien_hang` decimal(10,2) NOT NULL,
  `phi_van_chuyen` decimal(10,2) DEFAULT 0.00,
  `tien_giam_gia` decimal(10,2) DEFAULT 0.00,
  `diem_tich_duoc` int(11) DEFAULT 0,
  `tong_tien_thanh_toan` decimal(10,2) NOT NULL,
  `ma_dia_chi_giao_hang` int(11) NOT NULL,
  `can_don_thuoc` tinyint(1) DEFAULT 0,
  `hinh_anh_don_thuoc` varchar(255) DEFAULT NULL,
  `ghi_chu` text DEFAULT NULL,
  `ngay_giao_du_kien` date DEFAULT NULL,
  `ngay_giao_thuc_te` timestamp NULL DEFAULT NULL,
  `ngay_tao` timestamp NOT NULL DEFAULT current_timestamp(),
  `ngay_cap_nhat` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `don_hang`
--

INSERT INTO `don_hang` (`ma_don_hang`, `ma_nguoi_dung`, `so_don_hang`, `trang_thai_don_hang`, `trang_thai_thanh_toan`, `phuong_thuc_thanh_toan`, `tong_tien_hang`, `phi_van_chuyen`, `tien_giam_gia`, `diem_tich_duoc`, `tong_tien_thanh_toan`, `ma_dia_chi_giao_hang`, `can_don_thuoc`, `hinh_anh_don_thuoc`, `ghi_chu`, `ngay_giao_du_kien`, `ngay_giao_thuc_te`, `ngay_tao`, `ngay_cap_nhat`) VALUES
(7, 5, 'DH20250606183259298', 'cho_xac_nhan', 'chua_thanh_toan', '', 500000.00, 0.00, 0.00, 0, 500000.00, 10, 0, '', 'gh', '2025-06-09', NULL, '2025-06-06 11:32:59', '2025-06-20 16:45:35'),
(8, 5, 'DH20250606183438457', 'cho_xac_nhan', 'chua_thanh_toan', 'chuyen_khoan', 290000.00, 30000.00, 0.00, 0, 320000.00, 11, 0, '', 'dsgdf', '2025-06-09', NULL, '2025-06-06 11:34:38', '2025-06-20 16:45:55'),
(9, 5, 'DH20250614125012390', 'cho_xac_nhan', 'chua_thanh_toan', 'tien_mat', 1550000.00, 0.00, 0.00, 0, 1550000.00, 12, 0, '', 'bnmbn', '2025-06-17', NULL, '2025-06-14 05:50:12', '2025-06-20 16:46:24'),
(10, 5, 'DH20250614140358325', 'cho_xac_nhan', 'chua_thanh_toan', 'tien_mat', 555000.00, 0.00, 0.00, 0, 555000.00, 13, 0, '', 'gdfg', '2025-06-17', NULL, '2025-06-14 07:03:58', '2025-06-20 16:46:29'),
(11, 5, 'DH20250614151006143', 'cho_xac_nhan', 'chua_thanh_toan', 'tien_mat', 90000.00, 30000.00, 0.00, 0, 120000.00, 14, 0, '', 'j', '2025-06-17', NULL, '2025-06-14 08:10:06', '2025-06-20 16:46:32'),
(12, 5, 'DH20250620155829155', 'cho_xac_nhan', 'chua_thanh_toan', 'chuyen_khoan', 320000.00, 30000.00, 0.00, 0, 350000.00, 15, 0, '', 'ghg', '2025-06-23', NULL, '2025-06-20 08:58:29', '2025-07-04 20:00:02'),
(13, 5, 'DH20250620160157873', 'cho_xac_nhan', 'chua_thanh_toan', 'tien_mat', 805000.00, 0.00, 0.00, 0, 805000.00, 16, 0, '', 'ghj', '2025-06-23', NULL, '2025-06-20 09:01:57', '2025-06-20 16:46:39'),
(14, 5, 'DH20250620234304597', 'cho_xac_nhan', 'chua_thanh_toan', 'tien_mat', 750000.00, 0.00, 0.00, 0, 750000.00, 17, 0, '', '', '2025-06-23', NULL, '2025-06-20 16:43:04', '2025-06-20 16:46:42'),
(15, 5, 'DH20250620234322343', 'da_huy', 'chua_thanh_toan', 'tien_mat', 355000.00, 30000.00, 0.00, 0, 385000.00, 18, 0, '', '', '2025-06-23', NULL, '2025-06-20 16:43:22', '2025-06-20 16:43:28'),
(16, 5, 'DH20250620234421391', 'da_giao', 'da_thanh_toan', 'tien_mat', 280000.00, 30000.00, 0.00, 0, 310000.00, 19, 0, '', '', '2025-06-23', NULL, '2025-06-20 16:44:21', '2025-07-05 07:05:53'),
(17, 5, 'DH20250620234432367', 'da_huy', 'chua_thanh_toan', 'tien_mat', 30000.00, 30000.00, 0.00, 0, 60000.00, 20, 0, '', '', '2025-06-23', NULL, '2025-06-20 16:44:32', '2025-06-20 16:45:12'),
(18, 5, 'DH20250620234446980', 'da_huy', 'chua_thanh_toan', 'tien_mat', 550000.00, 0.00, 0.00, 0, 550000.00, 21, 0, '', '', '2025-06-23', NULL, '2025-06-20 16:44:46', '2025-06-20 16:45:09'),
(19, 5, 'DH20250620234458773', 'da_huy', 'chua_thanh_toan', 'tien_mat', 420000.00, 30000.00, 0.00, 0, 450000.00, 22, 0, '', '', '2025-06-23', NULL, '2025-06-20 16:44:58', '2025-06-20 16:45:06'),
(20, 5, 'DH20250620235256529', 'da_giao', 'da_thanh_toan', 'tien_mat', 750000.00, 0.00, 0.00, 0, 750000.00, 23, 0, '', '', '2025-06-23', NULL, '2025-06-20 16:52:56', '2025-07-05 05:07:56'),
(21, 5, 'DH20250621000005258', 'da_huy', 'chua_thanh_toan', 'tien_mat', 960000.00, 0.00, 0.00, 0, 960000.00, 24, 0, '', '', '2025-06-24', NULL, '2025-06-20 17:00:05', '2025-06-20 17:23:28'),
(22, 5, 'DH20250621002316273', 'da_huy', 'chua_thanh_toan', 'tien_mat', 450000.00, 30000.00, 0.00, 0, 480000.00, 25, 0, '', '', '2025-06-24', NULL, '2025-06-20 17:23:16', '2025-06-20 17:23:24'),
(23, 5, 'DH20250621125323338', 'cho_xac_nhan', 'chua_thanh_toan', 'tien_mat', 1568000.00, 0.00, 0.00, 0, 1568000.00, 26, 0, '', 'v', '2025-06-24', NULL, '2025-06-21 05:53:23', '2025-06-25 15:34:37'),
(24, 4, 'DH20250704030037178', 'cho_xac_nhan', 'chua_thanh_toan', 'tien_mat', 1604000.00, 0.00, 0.00, 100, 1604000.00, 27, 0, '', '', '2025-07-07', NULL, '2025-07-03 20:00:37', '2025-07-03 20:00:37'),
(25, 4, 'DH20250704030308277', 'da_giao', 'da_thanh_toan', 'tien_mat', 680000.00, 0.00, 100.00, 68, 679900.00, 28, 0, '', '', '2025-07-07', NULL, '2025-07-03 20:03:08', '2025-07-04 19:58:57'),
(26, 4, 'DH20250704030500577', 'da_giao', 'da_thanh_toan', 'tien_mat', 680000.00, 0.00, 0.00, 68, 680000.00, 29, 0, '', '', '2025-07-07', NULL, '2025-07-03 20:05:00', '2025-07-05 07:05:57'),
(27, 4, 'DH20250704030733760', 'da_giao', 'da_thanh_toan', 'tien_mat', 680000.00, 0.00, 136.00, 68, 679864.00, 30, 0, '', '', '2025-07-07', NULL, '2025-07-03 20:07:33', '2025-07-04 19:33:47'),
(28, 8, 'DH20250704124114677', 'da_giao', 'da_thanh_toan', 'tien_mat', 514000.00, 0.00, 0.00, 51, 514000.00, 31, 0, '', '', '2025-07-07', NULL, '2025-07-04 05:41:14', '2025-07-04 19:47:50'),
(29, 8, 'DH20250704124218527', 'da_giao', 'da_thanh_toan', 'tien_mat', 926500.00, 0.00, 51.00, 92, 926449.00, 32, 0, '', '', '2025-07-07', NULL, '2025-07-04 05:42:18', '2025-07-05 07:05:49'),
(30, 8, 'DH20250705020525819', 'da_giao', 'da_thanh_toan', 'tien_mat', 165000.00, 30000.00, 0.00, 19, 195000.00, 33, 0, '', '', '2025-07-08', NULL, '2025-07-04 19:05:25', '2025-07-04 19:59:39'),
(31, 4, 'DH20250705152110385', 'da_giao', 'da_thanh_toan', 'tien_mat', 567500.00, 0.00, 68.00, 56, 567432.00, 34, 0, '', '', '2025-07-08', NULL, '2025-07-05 08:21:10', '2025-07-05 08:21:46'),
(32, 4, 'DH20250705155240358', 'cho_xac_nhan', 'chua_thanh_toan', 'tien_mat', 22500.00, 30000.00, 0.00, 5, 52500.00, 35, 0, '', '', '2025-07-08', NULL, '2025-07-05 08:52:40', '2025-07-05 08:52:40');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `giam_gia_soc`
--

DROP TABLE IF EXISTS `giam_gia_soc`;
CREATE TABLE `giam_gia_soc` (
  `id` int(11) NOT NULL,
  `thoi_gian_bat_dau` datetime NOT NULL,
  `thoi_gian_ket_thuc` datetime NOT NULL,
  `phan_tram_giam` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `giam_gia_soc`
--

INSERT INTO `giam_gia_soc` (`id`, `thoi_gian_bat_dau`, `thoi_gian_ket_thuc`, `phan_tram_giam`) VALUES
(50, '2025-07-01 02:30:09', '2025-07-16 02:30:09', 50);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `gio_hang`
--

DROP TABLE IF EXISTS `gio_hang`;
CREATE TABLE `gio_hang` (
  `ma_gio_hang` int(11) NOT NULL,
  `ma_nguoi_dung` int(11) NOT NULL,
  `ma_san_pham` int(11) NOT NULL,
  `so_luong` int(11) NOT NULL DEFAULT 1,
  `ngay_them` timestamp NOT NULL DEFAULT current_timestamp(),
  `ngay_cap_nhat` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `gio_hang`
--

INSERT INTO `gio_hang` (`ma_gio_hang`, `ma_nguoi_dung`, `ma_san_pham`, `so_luong`, `ngay_them`, `ngay_cap_nhat`) VALUES
(50, 5, 36, 1, '2025-06-21 17:04:03', '2025-06-21 17:04:03'),
(51, 5, 1, 1, '2025-06-21 17:04:08', '2025-06-21 17:04:08'),
(52, 5, 6, 2, '2025-06-21 17:04:10', '2025-06-21 18:50:56'),
(53, 5, 84, 1, '2025-06-21 18:50:53', '2025-06-21 18:50:53'),
(54, 5, 5, 2, '2025-06-21 18:50:54', '2025-06-22 04:18:00'),
(55, 5, 87, 2, '2025-06-21 18:50:58', '2025-06-21 19:03:37'),
(56, 5, 3, 1, '2025-06-22 04:17:58', '2025-06-22 04:17:58'),
(78, 9, 3, 1, '2025-07-05 08:38:27', '2025-07-05 08:38:27'),
(79, 9, 77, 1, '2025-07-05 08:38:36', '2025-07-05 08:38:36'),
(80, 9, 97, 1, '2025-07-05 08:39:30', '2025-07-05 08:39:30');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `hinh_anh_san_pham`
--

DROP TABLE IF EXISTS `hinh_anh_san_pham`;
CREATE TABLE `hinh_anh_san_pham` (
  `ma_hinh_anh` int(11) NOT NULL,
  `ma_san_pham` int(11) NOT NULL,
  `duong_dan_hinh_anh` varchar(255) NOT NULL,
  `mo_ta_hinh_anh` varchar(200) DEFAULT NULL,
  `la_hinh_chinh` tinyint(1) DEFAULT 0,
  `thu_tu_hien_thi` int(11) DEFAULT 0,
  `ngay_tao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `hinh_anh_san_pham`
--

INSERT INTO `hinh_anh_san_pham` (`ma_hinh_anh`, `ma_san_pham`, `duong_dan_hinh_anh`, `mo_ta_hinh_anh`, `la_hinh_chinh`, `thu_tu_hien_thi`, `ngay_tao`) VALUES
(1, 1, 'images/products/paracetamol-stada-500mg.jpg', 'Paracetamol Stada 500mg', 1, 1, '2025-06-01 16:12:47'),
(2, 2, 'images/products/aspirin-bayer-100mg.jpg', 'Aspirin Bayer 100mg', 1, 1, '2025-06-01 16:12:47'),
(3, 3, 'images/products/ibuprofen-brufen-400mg.jpg', 'Ibuprofen Brufen 400mg', 1, 1, '2025-06-01 16:12:47'),
(4, 4, 'images/products/decolgen-nd-tablet.jpg', 'Decolgen ND Tablet', 1, 1, '2025-06-01 16:12:47'),
(5, 5, 'images/products/alaxan-fr-capsule.jpg', 'Alaxan FR Capsule', 1, 1, '2025-06-01 16:12:47'),
(6, 6, 'images/products/efferalgan-500mg.jpg', 'Efferalgan 500mg', 1, 1, '2025-06-01 16:12:47'),
(7, 7, 'images/products/strepsils-honey-lemon.jpg', 'Strepsils Honey Lemon', 1, 1, '2025-06-01 16:12:47'),
(8, 8, 'images/products/betadine-throat-spray.jpg', 'Betadine Throat Spray', 1, 1, '2025-06-01 16:12:47'),
(9, 9, 'images/products/amoxicillin-hasan-500mg.jpg', 'Amoxicillin Hasan 500mg', 1, 1, '2025-06-01 16:12:47'),
(10, 10, 'images/products/metformin-teva-500mg.jpg', 'Metformin Teva 500mg', 1, 1, '2025-06-01 16:12:47'),
(11, 11, 'images/products/augmentin-625mg.jpg', 'Augmentin 625mg', 1, 1, '2025-06-01 16:12:47'),
(12, 12, 'images/products/losartan-stada-50mg.jpg', 'Losartan Stada 50mg', 1, 1, '2025-06-01 16:12:47'),
(13, 13, 'images/products/atorvastatin-lipitor-20mg.jpg', 'Atorvastatin Lipitor 20mg', 1, 1, '2025-06-01 16:12:47'),
(14, 14, 'images/products/prednisolone-5mg.jpg', 'Prednisolone 5mg', 1, 1, '2025-06-01 16:12:47'),
(15, 15, 'images/products/vitamin-c-redoxon-1000mg.jpg', 'Vitamin C Redoxon 1000mg', 1, 1, '2025-06-01 16:12:47'),
(16, 16, 'images/products/calcium-osteocare-plus-d3.jpg', 'Calcium Osteocare Plus D3', 1, 1, '2025-06-01 16:12:47'),
(17, 17, 'images/products/blackmores-omega3-fish-oil.jpg', 'Blackmores Omega-3 Fish Oil', 1, 1, '2025-06-01 16:12:47'),
(18, 18, 'images/products/centrum-multivitamin-adults.jpg', 'Centrum Multivitamin Adults', 1, 1, '2025-06-01 16:12:47'),
(19, 19, 'images/products/nature-made-vitamin-d3-2000.jpg', 'Nature Made Vitamin D3 2000 IU', 1, 1, '2025-06-01 16:12:47'),
(20, 20, 'images/products/kirkland-coq10-100mg.jpg', 'Kirkland Signature CoQ10 100mg', 1, 1, '2025-06-01 16:12:47'),
(21, 21, 'images/products/swisse-ultiboost-iron.jpg', 'Swisse Ultiboost Iron', 1, 1, '2025-06-01 16:12:47'),
(22, 22, 'images/products/berocca-performance-orange.jpg', 'Berocca Performance Orange', 1, 1, '2025-06-01 16:12:47'),
(23, 23, 'images/products/nordic-naturals-ultimate-omega.jpg', 'Nordic Naturals Ultimate Omega', 1, 1, '2025-06-01 16:12:47'),
(24, 24, 'images/products/garden-of-life-vitamin-code-women.jpg', 'Garden of Life Vitamin Code Women', 1, 1, '2025-06-01 16:12:47'),
(25, 25, 'images/products/glucosamine-osteo-biflex.jpg', 'Glucosamine Osteo Bi-Flex', 1, 1, '2025-06-01 16:12:47'),
(26, 26, 'images/products/ginkgo-biloba-nature-made.jpg', 'Ginkgo Biloba Nature Made', 1, 1, '2025-06-01 16:12:47'),
(27, 27, 'images/products/spirulina-tablets-500mg.jpg', 'Spirulina Tablets 500mg', 1, 1, '2025-06-01 16:12:47'),
(28, 28, 'images/products/neocell-super-collagen.jpg', 'Collagen Neocell Super', 1, 1, '2025-06-01 16:12:47'),
(29, 29, 'images/products/align-daily-probiotics.jpg', 'Probiotics Align Daily', 1, 1, '2025-06-01 16:12:47'),
(30, 30, 'images/products/nature-made-melatonin-3mg.jpg', 'Melatonin Nature Made 3mg', 1, 1, '2025-06-01 16:12:47'),
(31, 31, 'images/products/green-tea-extract-egcg.jpg', 'Green Tea Extract EGCG', 1, 1, '2025-06-01 16:12:47'),
(32, 32, 'images/products/royal-jelly-1000mg.jpg', 'Royal Jelly 1000mg', 1, 1, '2025-06-01 16:12:47'),
(33, 33, 'images/products/la-roche-posay-toleriane.jpg', 'La Roche Posay Toleriane', 1, 1, '2025-06-01 16:12:47'),
(34, 34, 'images/products/eucerin-hyaluron-filler.jpg', 'Eucerin Hyaluron Filler', 1, 1, '2025-06-01 16:12:47'),
(35, 35, 'images/products/avene-thermal-spring-water.jpg', 'Avene Thermal Spring Water', 1, 1, '2025-06-01 16:12:47'),
(36, 36, 'images/products/bioderma-sensibio-h2o.jpg', 'Bioderma Sensibio H2O', 1, 1, '2025-06-01 16:12:47'),
(37, 37, 'images/products/vichy-aqualia-thermal.jpg', 'Vichy Aqualia Thermal', 1, 1, '2025-06-01 16:12:47'),
(38, 38, 'images/products/cetaphil-gentle-cleanser.jpg', 'Cetaphil Gentle Cleanser', 1, 1, '2025-06-01 16:12:47'),
(39, 39, 'images/products/ducray-kelual-ds-shampoo.jpg', 'Ducray Kelual DS Shampoo', 1, 1, '2025-06-01 16:12:47'),
(40, 40, 'images/products/omron-hem-7120.jpg', 'Omron HEM-7120', 1, 1, '2025-06-01 16:12:47'),
(41, 41, 'images/products/omron-mc-720.jpg', 'Omron MC-720', 1, 1, '2025-06-01 16:12:47'),
(42, 42, 'images/products/accucheck-performa.jpg', 'AccuCheck Performa', 1, 1, '2025-06-01 16:12:47'),
(43, 43, 'images/products/yuwell-nebulizer-403d.jpg', 'Yuwell Nebulizer 403D', 1, 1, '2025-06-01 16:12:47'),
(44, 44, 'images/products/dr-morepen-pulse-oximeter.jpg', 'Dr. Morepen Pulse Oximeter', 1, 1, '2025-06-01 16:12:47'),
(45, 45, 'images/products/digital-body-weight-scale.jpg', 'Digital Body Weight Scale', 1, 1, '2025-06-01 16:12:47'),
(46, 46, 'images/products/enfamil-a-plus-stage1.jpg', 'Enfamil A+ Stage 1', 1, 1, '2025-06-01 16:12:47'),
(47, 47, 'images/products/pampers-premium-care.jpg', 'Pampers Premium Care', 1, 1, '2025-06-01 16:12:47'),
(48, 48, 'images/products/similac-gold-stage2.jpg', 'Similac Gold Stage 2', 1, 1, '2025-06-01 16:12:47'),
(49, 49, 'images/products/huggies-dry-diapers.jpg', 'Huggies Dry Diapers', 1, 1, '2025-06-01 16:12:47'),
(50, 50, 'images/products/aptamil-gold-plus-stage3.jpg', 'Aptamil Gold+ Stage 3', 1, 1, '2025-06-01 16:12:47'),
(51, 51, 'images/products/johnson-baby-shampoo.jpg', 'Johnson Baby Shampoo', 1, 1, '2025-06-01 16:12:47'),
(52, 52, 'images/products/pigeon-baby-wipes.jpg', 'Pigeon Baby Wipes', 1, 1, '2025-06-01 16:12:47'),
(53, 53, 'images/products/kodomo-baby-toothpaste.jpg', 'Kodomo Baby Toothpaste', 1, 1, '2025-06-01 16:12:47'),
(54, 54, 'images/products/farlin-feeding-bottle.jpg', 'Farlin Feeding Bottle', 1, 1, '2025-06-01 16:12:47'),
(56, 56, 'images/products/advil-liqui-gels-200mg.jpg', 'Advil Liqui-Gels 200mg', 1, 1, '2025-06-21 17:41:33'),
(57, 57, 'images/products/motrin-ib-400mg.jpg', 'Motrin IB 400mg', 1, 1, '2025-06-21 17:41:33'),
(58, 58, 'images/products/aleve-naproxen-220mg.jpg', 'Aleve Naproxen 220mg', 1, 1, '2025-06-21 17:41:33'),
(59, 59, 'images/products/benadryl-allergy-25mg.jpg', 'Benadryl Allergy 25mg', 1, 1, '2025-06-21 17:41:33'),
(60, 60, 'images/products/claritin-10mg.jpg', 'Claritin 10mg', 1, 1, '2025-06-21 17:41:33'),
(61, 61, 'images/products/tums-antacid-calcium.jpg', 'Tums Antacid Calcium', 1, 1, '2025-06-21 17:41:33'),
(62, 62, 'images/products/pepto-bismol-liquid.jpg', 'Pepto-Bismol Liquid', 1, 1, '2025-06-21 17:41:33'),
(63, 63, 'images/products/robitussin-cough-syrup.jpg', 'Robitussin Cough Syrup', 1, 1, '2025-06-21 17:41:33'),
(64, 64, 'images/products/sudafed-pe-10mg.jpg', 'Sudafed PE 10mg', 1, 1, '2025-06-21 17:41:33'),
(65, 65, 'images/products/mucinex-expectorant-600mg.jpg', 'Mucinex Expectorant 600mg', 1, 1, '2025-06-21 17:41:33'),
(66, 66, 'images/products/lipitor-atorvastatin-40mg.jpg', 'Lipitor Atorvastatin 40mg', 1, 1, '2025-06-21 17:41:33'),
(67, 67, 'images/products/crestor-rosuvastatin-20mg.jpg', 'Crestor Rosuvastatin 20mg', 1, 1, '2025-06-21 17:41:33'),
(68, 68, 'images/products/norvasc-amlodipine-5mg.jpg', 'Norvasc Amlodipine 5mg', 1, 1, '2025-06-21 17:41:33'),
(69, 69, 'images/products/zestril-lisinopril-10mg.jpg', 'Zestril Lisinopril 10mg', 1, 1, '2025-06-21 17:41:33'),
(70, 70, 'images/products/diovan-valsartan-80mg.jpg', 'Diovan Valsartan 80mg', 1, 1, '2025-06-21 17:41:33'),
(71, 71, 'images/products/glucophage-metformin-850mg.jpg', 'Glucophage Metformin 850mg', 1, 1, '2025-06-21 17:41:33'),
(72, 72, 'images/products/januvia-sitagliptin-100mg.jpg', 'Januvia Sitagliptin 100mg', 1, 1, '2025-06-21 17:41:33'),
(73, 73, 'images/products/lantus-insulin-glargine.jpg', 'Lantus Insulin Glargine', 1, 1, '2025-06-21 17:41:33'),
(74, 74, 'images/products/synthroid-levothyroxine-50mcg.jpg', 'Synthroid Levothyroxine 50mcg', 1, 1, '2025-06-21 17:41:33'),
(75, 75, 'images/products/coumadin-warfarin-5mg.jpg', 'Coumadin Warfarin 5mg', 1, 1, '2025-06-21 17:41:33'),
(76, 76, 'images/products/omega-3-fish-oil-1000mg.jpg', 'Omega-3 Fish Oil 1000mg', 1, 1, '2025-06-21 17:41:33'),
(77, 77, 'images/products/vitamin-d3-5000-iu.jpg', 'Vitamin D3 5000 IU', 1, 1, '2025-06-21 17:41:33'),
(78, 78, 'images/products/magnesium-glycinate-400mg.jpg', 'Magnesium Glycinate 400mg', 1, 1, '2025-06-21 17:41:33'),
(79, 79, 'images/products/zinc-picolinate-30mg.jpg', 'Zinc Picolinate 30mg', 1, 1, '2025-06-21 17:41:33'),
(80, 80, 'images/products/turmeric-curcumin-500mg.jpg', 'Turmeric Curcumin 500mg', 1, 1, '2025-06-21 17:41:33'),
(81, 81, 'images/products/ashwagandha-600mg.jpg', 'Ashwagandha 600mg', 1, 1, '2025-06-21 17:41:33'),
(82, 82, 'images/products/rhodiola-rosea-400mg.jpg', 'Rhodiola Rosea 400mg', 1, 1, '2025-06-21 17:41:33'),
(83, 83, 'images/products/milk-thistle-175mg.jpg', 'Milk Thistle 175mg', 1, 1, '2025-06-21 17:41:33'),
(84, 84, 'images/products/saw-palmetto-320mg.jpg', 'Saw Palmetto 320mg', 1, 1, '2025-06-21 17:41:33'),
(85, 85, 'images/products/cranberry-extract-500mg.jpg', 'Cranberry Extract 500mg', 1, 1, '2025-06-21 17:41:33'),
(86, 86, 'images/products/cerave-moisturizing-cream.jpg', 'CeraVe Moisturizing Cream', 1, 1, '2025-06-21 17:41:33'),
(87, 87, 'images/products/neutrogena-hydra-boost-serum.jpg', 'Neutrogena Hydra Boost Serum', 1, 1, '2025-06-21 17:41:33'),
(88, 88, 'images/products/ordinary-niacinamide-10.jpg', 'The Ordinary Niacinamide 10%', 1, 1, '2025-06-21 17:41:33'),
(89, 89, 'images/products/retinol-serum-05.jpg', 'Retinol Serum 0.5%', 1, 1, '2025-06-21 17:41:33'),
(90, 90, 'images/products/vitamin-c-serum-20.jpg', 'Vitamin C Serum 20%', 1, 1, '2025-06-21 17:41:33'),
(91, 91, 'images/products/salicylic-acid-2-bha.jpg', 'Salicylic Acid 2% BHA', 1, 1, '2025-06-21 17:41:33'),
(92, 92, 'images/products/sunscreen-spf50-uva-uvb.jpg', 'Sunscreen SPF 50+ UVA/UVB', 1, 1, '2025-06-21 17:41:33'),
(93, 93, 'images/products/micellar-water-3in1.jpg', 'Micellar Water 3-in-1', 1, 1, '2025-06-21 17:41:33'),
(94, 94, 'images/products/glycolic-acid-10-aha.jpg', 'Glycolic Acid 10% AHA', 1, 1, '2025-06-21 17:41:33'),
(95, 95, 'images/products/peptide-anti-aging-cream.jpg', 'Peptide Anti-Aging Cream', 1, 1, '2025-06-21 17:41:33'),
(96, 96, 'images/products/omron-blood-pressure-wrist.jpg', 'Máy Đo Huyết Áp Cổ Tay Omron', 1, 1, '2025-06-21 17:41:33'),
(97, 97, 'images/products/ultrasonic-nebulizer.jpg', 'Máy Xông Mũi Họng Ultrasonic', 1, 1, '2025-06-21 17:41:33'),
(98, 98, 'images/products/body-analysis-scale.jpg', 'Cân Điện Tử Phân Tích Cơ Thể', 1, 1, '2025-06-21 17:41:33'),
(99, 99, 'images/products/onetouch-glucose-meter.jpg', 'Máy Đo Đường Huyết OneTouch', 1, 1, '2025-06-21 17:41:33'),
(100, 100, 'images/products/handheld-massage-6head.jpg', 'Máy Massage Cầm Tay 6 Đầu', 1, 1, '2025-06-21 17:41:33'),
(107, 55, 'images/products/tylenol-extra-strength-500mg.jpg', 'Tylenol Extra Strength 500mg', 1, 0, '2025-07-04 18:19:54');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `lich_su_diem_tich_luy`
--

DROP TABLE IF EXISTS `lich_su_diem_tich_luy`;
CREATE TABLE `lich_su_diem_tich_luy` (
  `id` int(11) NOT NULL,
  `ma_nguoi_dung` int(11) NOT NULL,
  `loai_giao_dich` enum('tich_diem','su_dung_diem') NOT NULL,
  `so_diem` int(11) NOT NULL,
  `diem_truoc_giao_dich` int(11) NOT NULL,
  `diem_sau_giao_dich` int(11) NOT NULL,
  `ma_don_hang` int(11) DEFAULT NULL,
  `mo_ta` varchar(255) DEFAULT NULL,
  `ngay_tao` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `lich_su_diem_tich_luy`
--

INSERT INTO `lich_su_diem_tich_luy` (`id`, `ma_nguoi_dung`, `loai_giao_dich`, `so_diem`, `diem_truoc_giao_dich`, `diem_sau_giao_dich`, `ma_don_hang`, `mo_ta`, `ngay_tao`) VALUES
(1, 4, 'tich_diem', 100, 0, 100, 24, 'Tích điểm từ đơn hàng #DH20250704030037178 - Giá trị: 1.604.000đ', '2025-07-04 03:00:37'),
(2, 4, 'su_dung_diem', -100, 100, 0, 25, 'Sử dụng 100 điểm giảm 100đ cho đơn hàng #DH20250704030308277', '2025-07-04 03:03:08'),
(3, 4, 'tich_diem', 68, 0, 68, 25, 'Tích điểm từ đơn hàng #DH20250704030308277 - Giá trị: 680.000đ', '2025-07-04 03:03:08'),
(4, 4, 'tich_diem', 68, 68, 136, 26, 'Tích điểm từ đơn hàng #DH20250704030500577 - Giá trị: 680.000đ', '2025-07-04 03:05:00'),
(5, 4, 'su_dung_diem', -136, 136, 0, 27, 'Sử dụng 136 điểm giảm 136đ cho đơn hàng #DH20250704030733760', '2025-07-04 03:07:33'),
(6, 4, 'tich_diem', 68, 0, 68, 27, 'Tích điểm từ đơn hàng #DH20250704030733760 - Giá trị: 680.000đ', '2025-07-04 03:07:33'),
(7, 8, 'tich_diem', 51, 0, 51, 28, 'Tích điểm từ đơn hàng #DH20250704124114677 - Giá trị: 514.000đ', '2025-07-04 12:41:14'),
(8, 8, 'su_dung_diem', -51, 51, 0, 29, 'Sử dụng 51 điểm giảm 51đ cho đơn hàng #DH20250704124218527', '2025-07-04 12:42:18'),
(9, 8, 'tich_diem', 92, 0, 92, 29, 'Tích điểm từ đơn hàng #DH20250704124218527 - Giá trị: 926.500đ', '2025-07-04 12:42:18'),
(10, 8, 'tich_diem', 19, 92, 111, 30, 'Tích điểm từ đơn hàng #DH20250705020525819 - Giá trị: 195.000đ', '2025-07-05 02:05:25'),
(11, 4, 'su_dung_diem', -68, 68, 0, 31, 'Sử dụng 68 điểm giảm 68đ cho đơn hàng #DH20250705152110385', '2025-07-05 15:21:10'),
(12, 4, 'tich_diem', 56, 0, 56, 31, 'Tích điểm từ đơn hàng #DH20250705152110385 - Giá trị: 567.500đ', '2025-07-05 15:21:10'),
(13, 4, 'tich_diem', 5, 56, 61, 32, 'Tích điểm từ đơn hàng #DH20250705155240358 - Giá trị: 52.500đ', '2025-07-05 15:52:40');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `lich_su_gia`
--

DROP TABLE IF EXISTS `lich_su_gia`;
CREATE TABLE `lich_su_gia` (
  `ma_lich_su` int(11) NOT NULL,
  `ma_san_pham` int(11) NOT NULL,
  `gia_cu` decimal(10,2) NOT NULL,
  `gia_moi` decimal(10,2) NOT NULL,
  `nguoi_thay_doi` int(11) NOT NULL,
  `ngay_thay_doi` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `nguoi_dung`
--

DROP TABLE IF EXISTS `nguoi_dung`;
CREATE TABLE `nguoi_dung` (
  `ma_nguoi_dung` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `mat_khau_ma_hoa` varchar(255) NOT NULL,
  `ho_ten` varchar(100) NOT NULL,
  `so_dien_thoai` varchar(15) DEFAULT NULL,
  `ngay_sinh` date DEFAULT NULL,
  `dia_chi` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Địa chỉ của người dùng',
  `gioi_tinh` enum('Nam','Nữ','Khác') DEFAULT NULL,
  `vai_tro` enum('khach_hang','quan_tri','nhan_vien') DEFAULT 'khach_hang',
  `ngay_tao` timestamp NOT NULL DEFAULT current_timestamp(),
  `diem_tich_luy` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `nguoi_dung`
--

INSERT INTO `nguoi_dung` (`ma_nguoi_dung`, `email`, `mat_khau_ma_hoa`, `ho_ten`, `so_dien_thoai`, `ngay_sinh`, `dia_chi`, `gioi_tinh`, `vai_tro`, `ngay_tao`, `diem_tich_luy`) VALUES
(4, 'cuongndp0736@ut.edu.vn', '$2y$10$DPhvt5ZYPMuwNwq.phsrv.JBBzeZg3hixdFLG3UrWlOKsG7hZydu6', 'Cường Nguyễn Đăng Phúc', '0706259407', '2005-10-27', NULL, 'Nam', 'khach_hang', '2025-07-03 19:51:50', 61),
(5, 'nguyencuongphpy@gmail.com', '$2y$10$Poxf6/MhBB69ALSYc/q49u.xK/EvSjcEiRF/kVwQP46m0QIhZXw3u', 'Cường Nguyễn Đăng Phúc', '0982731369', '2025-06-02', NULL, 'Nam', 'khach_hang', '2025-07-03 19:51:50', 0),
(6, 'nguyencuongphpssssy@gmail.com', '$2y$10$L.nc72mMd0bq3bE9vGUkvOIxEVtqo2KY0MgYDOFTfX/UDiyb25kua', 'Cường Nguyễn Đăng Phúc', '0777096808', '2025-06-02', NULL, 'Nam', 'khach_hang', '2025-07-03 19:51:50', 0),
(7, 'admin@gmail.com', '$2y$10$UZPAi3Ewum7EzlQnLQnRK.pEZL5tgW6jbWr4PgW62TqreZdLUz4xu', 'Nguyễn Đăng Phúc Cường', '0706259406', NULL, NULL, NULL, 'quan_tri', '2025-07-03 19:51:50', 0),
(8, 'cuongndp0736@ut.edu.vnn', '$2y$10$Y7/YwvGkodUVz7EcfKk5f.frhpyCzao.GC9xJg0jyQwYvqcWNxNG.', 'Cường Nguyễn Đăng Phúc', '0706259402', '2000-10-10', NULL, 'Nam', 'khach_hang', '2025-07-03 20:13:45', 111),
(9, 'quanly@vitameds.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Nguyễn Văn Quản Lý', '0911111111', NULL, NULL, NULL, 'quan_tri', '2025-07-04 15:26:10', 0),
(10, 'nhanvien@vitameds.com', '$2y$10$p.DWhy1e7YGJi4X/g2ONCe.hR56AspvyeMjGEKOeSuydik.VtNiES', 'Nguyễn Văn Nhân Viên', '0982731366', NULL, NULL, NULL, 'nhan_vien', '2025-07-04 15:26:10', 0);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `nha_san_xuat`
--

DROP TABLE IF EXISTS `nha_san_xuat`;
CREATE TABLE `nha_san_xuat` (
  `ma_nha_san_xuat` int(11) NOT NULL,
  `ten_nha_san_xuat` varchar(100) NOT NULL,
  `quoc_gia` varchar(50) DEFAULT NULL,
  `mo_ta` text DEFAULT NULL,
  `thong_tin_lien_he` text DEFAULT NULL,
  `trang_thai_hoat_dong` tinyint(1) DEFAULT 1,
  `ngay_tao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `nha_san_xuat`
--

INSERT INTO `nha_san_xuat` (`ma_nha_san_xuat`, `ten_nha_san_xuat`, `quoc_gia`, `mo_ta`, `thong_tin_lien_he`, `trang_thai_hoat_dong`, `ngay_tao`) VALUES
(1, 'Teva Pharmaceutical', 'Israel', NULL, NULL, 1, '2025-06-01 15:44:51'),
(2, 'Sanofi', 'Pháp', NULL, NULL, 1, '2025-06-01 15:44:51'),
(3, 'Pfizer', 'Mỹ', NULL, NULL, 1, '2025-06-01 15:44:51'),
(4, 'Hậu Giang Pharma', 'Việt Nam', NULL, NULL, 1, '2025-06-01 15:44:51'),
(5, 'La Roche Posay', 'Pháp', NULL, NULL, 1, '2025-06-01 15:44:51'),
(6, 'Eucerin', 'Đức', NULL, NULL, 1, '2025-06-01 15:44:51'),
(7, 'Omron', 'Nhật Bản', NULL, NULL, 1, '2025-06-01 15:44:51'),
(8, 'Mead Johnson', 'Mỹ', NULL, NULL, 1, '2025-06-01 15:44:51'),
(9, 'Pampers', 'Mỹ', NULL, NULL, 1, '2025-06-01 15:44:51'),
(10, 'Blackmores', 'Úc', NULL, NULL, 1, '2025-06-01 15:44:51'),
(11, 'Abbott', 'Mỹ', 'Công ty dược phẩm đa quốc gia', NULL, 1, '2025-06-21 17:41:32'),
(12, 'Novartis', 'Thụy Sĩ', 'Tập đoàn dược phẩm hàng đầu thế giới', NULL, 1, '2025-06-21 17:41:32'),
(13, 'GSK', 'Anh', 'GlaxoSmithKline - công ty dược phẩm lớn', NULL, 1, '2025-06-21 17:41:32'),
(14, 'Bayer', 'Đức', 'Công ty khoa học đời sống', NULL, 1, '2025-06-21 17:41:32'),
(15, 'Roche', 'Thụy Sĩ', 'Công ty sinh học và dược phẩm', NULL, 1, '2025-06-21 17:41:32'),
(16, 'Merck', 'Đức', 'Công ty khoa học và công nghệ', NULL, 1, '2025-06-21 17:41:32'),
(17, 'Johnson & Johnson', 'Mỹ', 'Tập đoàn chăm sóc sức khỏe', NULL, 1, '2025-06-21 17:41:32'),
(18, 'Stada', 'Đức', 'Công ty dược phẩm châu Âu', NULL, 1, '2025-06-21 17:41:32'),
(19, 'Dhg Pharma', 'Việt Nam', 'Dược Hậu Giang', NULL, 1, '2025-06-21 17:41:32'),
(20, 'Imexpharm', 'Việt Nam', 'Công ty dược phẩm Việt Nam', NULL, 1, '2025-06-21 17:41:32');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `quang_cao`
--

DROP TABLE IF EXISTS `quang_cao`;
CREATE TABLE `quang_cao` (
  `id` int(11) NOT NULL,
  `tieu_de` varchar(255) NOT NULL,
  `mo_ta` text DEFAULT NULL,
  `hinh_anh` varchar(255) NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `trang_thai` tinyint(1) DEFAULT 1,
  `ngay_tao` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `quang_cao`
--

INSERT INTO `quang_cao` (`id`, `tieu_de`, `mo_ta`, `hinh_anh`, `link`, `trang_thai`, `ngay_tao`) VALUES
(1, 'Hiểu về ung thư từ A-Z', 'Thông tin được biên soạn và kiểm duyệt bởi đội ngũ chuyên gia y tế. Đối tác: Nhà thuốc Long Châu, Gleneagles Hospital, Mount Elizabeth.', 'images/quangcao/banner5.jpg', 'https://www.vinmec.com/vie/bai-viet/nhung-dieu-co-ban-can-biet-ve-ung-thu-vi', 1, '2025-07-04 02:40:59'),
(2, 'Gói y tế chủ động chỉ từ 129K/tháng', 'Một chạm kết nối, vạn điểm an tâm. Chăm sóc sức khỏe chủ động cho cả gia đình.', 'images/quangcao/banner6.jpg', 'https://fptshop.com.vn/tin-tuc/tin-khuyen-mai/cham-soc-suc-khoe-chu-dong-chi-tu-129000dthang-cung-sim-y-te-172733', 1, '2025-07-04 02:40:59'),
(3, 'Dưỡng thai bổ máu chính hãng Anh Quốc', 'Giảm đến 20%. Sản phẩm Vitabiotics Pregnacare, Feroglobin, Wellbaby nhập khẩu chính hãng. Mẹ tin chọn!', 'images/quangcao/banner4.jpg', 'https://memart.vn/tin-tuc/blog/nhung-loai-uong-gi-bo-mau-tot-nhat-cho-suc-khoe-cua-ban-vi-cb.html', 1, '2025-07-04 02:40:59'),
(4, 'Giảm giá đến 50% sản phẩm chăm sóc sức khỏe', 'Up to 50% off on health care products. Ưu đãi lớn cho mọi khách hàng.', 'images/quangcao/banner1.jpg', '#', 1, '2025-07-04 02:40:59'),
(5, 'Miễn phí giao hàng toàn quốc', 'Free nationwide delivery for orders from 300,000 VNĐ. Đặt hàng ngay để nhận ưu đãi!', 'images/quangcao/banner2.jpg', '#', 1, '2025-07-04 02:40:59'),
(6, 'Sản phẩm mới VitaMeds', 'New products launched! Khám phá các sản phẩm mới nhất tại VitaMeds.', 'images/quangcao/banner3.jpg', '#', 1, '2025-07-04 02:40:59');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `san_pham_thuoc`
--

DROP TABLE IF EXISTS `san_pham_thuoc`;
CREATE TABLE `san_pham_thuoc` (
  `ma_san_pham` int(11) NOT NULL,
  `ten_san_pham` varchar(200) NOT NULL,
  `ten_hoat_chat` varchar(200) DEFAULT NULL,
  `ma_danh_muc` int(11) NOT NULL,
  `ma_nha_san_xuat` int(11) NOT NULL,
  `mo_ta` text DEFAULT NULL,
  `thanh_phan_hoat_chat` text DEFAULT NULL,
  `dang_bao_che` enum('vien','vien_nang','siro','gel','kem','thuoc_tiem','khac') DEFAULT NULL,
  `ham_luong` varchar(50) DEFAULT NULL,
  `quy_cach_dong_goi` varchar(50) DEFAULT NULL,
  `can_don_thuoc` tinyint(1) DEFAULT 0,
  `gia_ban` decimal(10,2) NOT NULL,
  `gia_khuyen_mai` decimal(10,2) DEFAULT NULL,
  `so_luong_ton_kho` int(11) DEFAULT 0,
  `muc_ton_kho_toi_thieu` int(11) DEFAULT 10,
  `muc_ton_kho_toi_da` int(11) DEFAULT 1000,
  `han_su_dung` date DEFAULT NULL,
  `so_lo` varchar(50) DEFAULT NULL,
  `ma_vach` varchar(50) DEFAULT NULL,
  `ma_sku` varchar(50) DEFAULT NULL,
  `trong_luong` decimal(8,2) DEFAULT NULL,
  `dieu_kien_bao_quan` text DEFAULT NULL,
  `tac_dung_phu` text DEFAULT NULL,
  `chong_chi_dinh` text DEFAULT NULL,
  `huong_dan_su_dung` text DEFAULT NULL,
  `gioi_han_tuoi` varchar(50) DEFAULT NULL,
  `trang_thai_hoat_dong` tinyint(1) DEFAULT 1,
  `san_pham_noi_bat` tinyint(1) DEFAULT 0,
  `is_flash_sale` tinyint(1) DEFAULT 0,
  `ngay_tao` timestamp NOT NULL DEFAULT current_timestamp(),
  `ngay_cap_nhat` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `trung_binh_sao` decimal(3,2) DEFAULT 0.00,
  `tong_so_danh_gia` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `san_pham_thuoc`
--

INSERT INTO `san_pham_thuoc` (`ma_san_pham`, `ten_san_pham`, `ten_hoat_chat`, `ma_danh_muc`, `ma_nha_san_xuat`, `mo_ta`, `thanh_phan_hoat_chat`, `dang_bao_che`, `ham_luong`, `quy_cach_dong_goi`, `can_don_thuoc`, `gia_ban`, `gia_khuyen_mai`, `so_luong_ton_kho`, `muc_ton_kho_toi_thieu`, `muc_ton_kho_toi_da`, `han_su_dung`, `so_lo`, `ma_vach`, `ma_sku`, `trong_luong`, `dieu_kien_bao_quan`, `tac_dung_phu`, `chong_chi_dinh`, `huong_dan_su_dung`, `gioi_han_tuoi`, `trang_thai_hoat_dong`, `san_pham_noi_bat`, `is_flash_sale`, `ngay_tao`, `ngay_cap_nhat`, `trung_binh_sao`, `tong_so_danh_gia`) VALUES
(1, 'Paracetamol Stada 500mg', NULL, 1, 1, 'Thuốc giảm đau, hạ sốt hiệu quả cho người lớn và trẻ em', NULL, NULL, NULL, NULL, 0, 30000.00, 25000.00, 100, 10, 1000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 0, '2025-06-01 15:46:36', '2025-07-04 20:45:12', 0.00, 0),
(2, 'Aspirin Bayer 100mg', NULL, 1, 2, 'Thuốc chống đông máu, phòng ngừa đột quỵ và nhồi máu cơ tim', NULL, NULL, NULL, NULL, 0, 35000.00, NULL, 80, 10, 1000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 0, '2025-06-01 15:46:36', '2025-06-01 15:46:36', 0.00, 0),
(3, 'Ibuprofen Brufen 400mg', NULL, 1, 3, 'Thuốc chống viêm, giảm đau và hạ sốt mạnh', NULL, NULL, NULL, NULL, 0, 55000.00, 22500.00, 58, 10, 1000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 1, '2025-06-01 15:46:36', '2025-07-05 08:52:40', 5.00, 1),
(4, 'Decolgen ND Tablet', NULL, 1, 4, 'Thuốc cảm cúm, nghẹt mũi, sổ mũi', NULL, NULL, NULL, NULL, 0, 38000.00, NULL, 75, 10, 1000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 0, '2025-06-01 15:46:36', '2025-07-04 20:35:20', 0.00, 0),
(5, 'Alaxan FR Capsule', NULL, 1, 1, 'Thuốc giảm đau cơ xương khớp', NULL, NULL, NULL, NULL, 0, 48000.00, 24000.00, 50, 10, 1000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 1, '2025-06-01 15:46:36', '2025-07-04 04:30:43', 0.00, 0),
(6, 'Efferalgan 500mg', NULL, 1, 2, 'Viên sủi giảm đau hạ sốt', NULL, NULL, NULL, NULL, 0, 65000.00, 32500.00, 40, 10, 1000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 1, '2025-06-01 15:46:36', '2025-07-04 04:51:57', 0.00, 0),
(7, 'Strepsils Honey Lemon', NULL, 1, 3, 'Viên ngậm đau họng', NULL, NULL, NULL, NULL, 0, 28000.00, NULL, 90, 10, 1000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, '2025-06-01 15:46:36', '2025-06-01 15:46:36', 0.00, 0),
(8, 'Betadine Throat Spray', NULL, 1, 4, 'Xịt họng sát khuẩn', NULL, NULL, NULL, NULL, 0, 85000.00, 42500.00, 35, 10, 1000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 1, '2025-06-01 15:46:36', '2025-07-04 04:31:34', 0.00, 0),
(9, 'Amoxicillin Hasan 500mg', NULL, 2, 4, 'Kháng sinh điều trị nhiễm khuẩn đường hô hấp', NULL, NULL, NULL, NULL, 1, 85000.00, NULL, 50, 10, 1000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, '2025-06-01 15:46:36', '2025-06-01 15:46:36', 0.00, 0),
(10, 'Metformin Teva 500mg', NULL, 2, 1, 'Thuốc điều trị tiểu đường type 2', NULL, NULL, NULL, NULL, 1, 120000.00, NULL, 40, 10, 1000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, '2025-06-01 15:46:36', '2025-06-01 15:46:36', 0.00, 0),
(11, 'Augmentin 625mg', NULL, 2, 2, 'Kháng sinh phối hợp điều trị nhiễm khuẩn nặng', NULL, NULL, NULL, NULL, 1, 150000.00, NULL, 30, 10, 1000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 0, '2025-06-01 15:46:36', '2025-06-01 15:46:36', 0.00, 0),
(12, 'Losartan Stada 50mg', NULL, 2, 1, 'Thuốc điều trị tăng huyết áp', NULL, NULL, NULL, NULL, 1, 95000.00, NULL, 60, 10, 1000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, '2025-06-01 15:46:36', '2025-06-01 15:46:36', 0.00, 0),
(13, 'Atorvastatin Lipitor 20mg', NULL, 2, 3, 'Thuốc điều trị rối loạn lipid máu', NULL, NULL, NULL, NULL, 1, 180000.00, NULL, 25, 10, 1000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, '2025-06-01 15:46:36', '2025-06-01 15:46:36', 0.00, 0),
(14, 'Prednisolone 5mg', NULL, 2, 4, 'Thuốc chống viêm corticosteroid', NULL, NULL, NULL, NULL, 1, 75000.00, 65000.00, 45, 10, 1000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, '2025-06-01 15:46:36', '2025-07-04 05:06:46', 0.00, 0),
(15, 'Vitamin C Redoxon 1000mg', NULL, 3, 2, 'Tăng cường miễn dịch, chống oxy hóa', NULL, NULL, NULL, NULL, 0, 120000.00, NULL, 120, 10, 1000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 0, '2025-06-01 15:46:36', '2025-07-04 04:45:10', 0.00, 0),
(16, 'Calcium Osteocare Plus D3', NULL, 3, 3, 'Bổ sung canxi và vitamin D3 cho xương chắc khỏe', NULL, NULL, NULL, NULL, 0, 95000.00, NULL, 90, 10, 1000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, '2025-06-01 15:46:36', '2025-06-01 15:46:36', 0.00, 0),
(17, 'Blackmores Omega-3 Fish Oil', NULL, 3, 10, 'Hỗ trợ tim mạch và não bộ khỏe mạnh', NULL, NULL, NULL, NULL, 0, 180000.00, NULL, 70, 10, 1000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, '2025-06-01 15:46:36', '2025-06-01 15:46:36', 0.00, 0),
(18, 'Centrum Multivitamin Adults', NULL, 3, 2, 'Vitamin tổng hợp cho người lớn', NULL, NULL, NULL, NULL, 0, 520000.00, 480000.00, 55, 10, 1000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 0, '2025-06-01 15:46:36', '2025-07-04 05:06:57', 0.00, 0),
(19, 'Nature Made Vitamin D3 2000 IU', NULL, 3, 1, 'Bổ sung vitamin D3 cho xương và răng', NULL, NULL, NULL, NULL, 0, 320000.00, NULL, 40, 10, 1000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, '2025-06-01 15:46:36', '2025-06-01 15:46:36', 0.00, 0),
(20, 'Kirkland Signature CoQ10 100mg', NULL, 3, 10, 'Hỗ trợ tim mạch và chống lão hóa', NULL, NULL, NULL, NULL, 0, 850000.00, NULL, 25, 10, 1000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, '2025-06-01 15:46:36', '2025-06-01 15:46:36', 0.00, 0),
(21, 'Swisse Ultiboost Iron', NULL, 3, 10, 'Bổ sung sắt cho người thiếu máu', NULL, NULL, NULL, NULL, 0, 420000.00, 380000.00, 35, 10, 1000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, '2025-06-01 15:46:36', '2025-07-04 05:07:30', 0.00, 0),
(22, 'Berocca Performance Orange', NULL, 3, 2, 'Vitamin B tổng hợp tăng cường năng lượng', NULL, NULL, NULL, NULL, 0, 240000.00, 200000.00, 80, 10, 1000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 0, '2025-06-01 15:46:36', '2025-07-04 05:07:42', 0.00, 0),
(23, 'Nordic Naturals Ultimate Omega', NULL, 3, 1, 'Omega-3 cao cấp từ dầu cá', NULL, NULL, NULL, NULL, 0, 1200000.00, NULL, 15, 10, 1000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, '2025-06-01 15:46:36', '2025-06-01 15:46:36', 0.00, 0),
(24, 'Garden of Life Vitamin Code Women', NULL, 3, 3, 'Vitamin tổng hợp dành riêng cho phụ nữ', NULL, NULL, NULL, NULL, 0, 1160000.00, 1100000.00, 20, 10, 1000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, '2025-06-01 15:46:36', '2025-07-04 05:07:59', 0.00, 0),
(25, 'Glucosamine Osteo Bi-Flex 1500mg', NULL, 4, 2, 'Hỗ trợ xương khớp, giảm đau khớp', NULL, NULL, NULL, NULL, 0, 300000.00, 150000.00, 45, 10, 1000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 1, '2025-06-01 15:46:36', '2025-07-04 04:53:53', 0.00, 0),
(26, 'Ginkgo Biloba Nature Made', NULL, 4, 1, 'Hỗ trợ tuần hoàn máu não, tăng trí nhớ', NULL, NULL, NULL, NULL, 0, 280000.00, NULL, 60, 10, 1000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 0, '2025-06-01 15:46:36', '2025-06-01 15:46:36', 0.00, 0),
(27, 'Spirulina Tablets 500mg', NULL, 4, 4, 'Tảo xoắn tăng cường sức đề kháng', NULL, NULL, NULL, NULL, 0, 180000.00, NULL, 75, 10, 1000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, '2025-06-01 15:46:36', '2025-06-01 15:46:36', 0.00, 0),
(28, 'Collagen Neocell Super', NULL, 4, 2, 'Collagen chống lão hóa da', NULL, NULL, NULL, NULL, 0, 750000.00, 650000.00, 30, 10, 1000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 0, '2025-06-01 15:46:36', '2025-07-04 05:09:58', 0.00, 0),
(29, 'Probiotics Align Daily', NULL, 4, 3, 'Men vi sinh hỗ trợ tiêu hóa', NULL, NULL, NULL, NULL, 0, 420000.00, NULL, 40, 10, 1000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, '2025-06-01 15:46:36', '2025-06-01 15:46:36', 0.00, 0),
(30, 'Melatonin Nature Made 3mg', NULL, 4, 1, 'Hỗ trợ giấc ngủ tự nhiên', NULL, NULL, NULL, NULL, 0, 350000.00, NULL, 35, 10, 1000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, '2025-06-01 15:46:36', '2025-06-01 15:46:36', 0.00, 0),
(31, 'Green Tea Extract EGCG', NULL, 4, 10, 'Chiết xuất trà xanh giảm cân', NULL, NULL, NULL, NULL, 0, 320000.00, 280000.00, 50, 10, 1000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, '2025-06-01 15:46:36', '2025-07-04 05:10:11', 0.00, 0),
(32, 'Royal Jelly 1000mg', NULL, 4, 4, 'Sữa ong chúa tăng cường sinh lực', NULL, NULL, NULL, NULL, 0, 580000.00, NULL, 25, 10, 1000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, '2025-06-01 15:46:36', '2025-06-01 15:46:36', 0.00, 0),
(33, 'La Roche Posay Toleriane Caring Wash', NULL, 5, 5, 'Kem dưỡng ẩm cho da nhạy cảm', NULL, NULL, NULL, NULL, 0, 450000.00, NULL, 30, 10, 1000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, '2025-06-01 15:46:36', '2025-06-01 15:46:36', 0.00, 0),
(34, 'Eucerin Hyaluron Filler Vitamin C Serum', NULL, 5, 6, 'Serum làm sáng da, chống lão hóa', NULL, NULL, NULL, NULL, 0, 420000.00, 380000.00, 25, 10, 1000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, '2025-06-01 15:46:36', '2025-07-04 05:10:23', 0.00, 0),
(35, 'Avene Thermal Spring Water', NULL, 5, 5, 'Xịt khoáng dưỡng ẩm cho da', NULL, NULL, NULL, NULL, 0, 250000.00, 230000.00, 45, 10, 1000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 0, '2025-06-01 15:46:36', '2025-07-04 05:10:39', 0.00, 0),
(36, 'Bioderma Sensibio H2O Micellar Water', NULL, 5, 6, 'Nước tẩy trang cho da nhạy cảm', NULL, NULL, NULL, NULL, 0, 320000.00, 300000.00, 40, 10, 1000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 0, '2025-06-01 15:46:36', '2025-07-04 05:12:13', 0.00, 0),
(37, 'Vichy Aqualia Thermal Cream', NULL, 5, 5, 'Kem dưỡng ẩm 48h cho da khô', NULL, NULL, NULL, NULL, 0, 480000.00, 440000.00, 35, 10, 1000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, '2025-06-01 15:46:36', '2025-07-04 05:20:23', 0.00, 0),
(38, 'Cetaphil Gentle Skin Cleanser', NULL, 5, 6, 'Sữa rửa mặt cho da nhạy cảm', NULL, NULL, NULL, NULL, 0, 180000.00, NULL, 60, 10, 1000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, '2025-06-01 15:46:36', '2025-06-01 15:46:36', 0.00, 0),
(39, 'Ducray Kelual DS Shampoo', NULL, 5, 5, 'Dầu gội trị gàu và viêm da đầu', NULL, NULL, NULL, NULL, 0, 420000.00, NULL, 20, 10, 1000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, '2025-06-01 15:46:36', '2025-06-01 15:46:36', 0.00, 0),
(40, 'Omron HEM-7120 Blood Pressure Monitor', NULL, 6, 7, 'Máy đo huyết áp tự động, chính xác cao', NULL, NULL, NULL, NULL, 0, 850000.00, NULL, 15, 10, 1000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 0, '2025-06-01 15:46:36', '2025-06-01 15:46:36', 0.00, 0),
(41, 'Omron MC-720 Digital Thermometer', NULL, 6, 7, 'Nhiệt kế điện tử đo trán không tiếp xúc', NULL, NULL, NULL, NULL, 0, 120000.00, NULL, 35, 10, 1000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, '2025-06-01 15:46:36', '2025-06-01 15:46:36', 0.00, 0),
(42, 'AccuCheck Performa Blood Glucose Meter', NULL, 6, 1, 'Máy đo đường huyết chính xác', NULL, NULL, NULL, NULL, 0, 520000.00, 450000.00, 20, 10, 1000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 0, '2025-06-01 15:46:36', '2025-07-04 05:20:40', 0.00, 0),
(43, 'Yuwell Nebulizer 403D', NULL, 6, 7, 'Máy xông mũi họng cho trẻ em', NULL, NULL, NULL, NULL, 0, 680000.00, NULL, 25, 10, 1000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, '2025-06-01 15:46:36', '2025-06-01 15:46:36', 0.00, 0),
(44, 'Dr. Morepen Pulse Oximeter', NULL, 6, 1, 'Máy đo nồng độ oxy trong máu', NULL, NULL, NULL, NULL, 0, 320000.00, 280000.00, 30, 10, 1000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, '2025-06-01 15:46:36', '2025-07-04 05:20:47', 0.00, 0),
(45, 'Digital Body Weight Scale', NULL, 6, 7, 'Cân sức khỏe điện tử', NULL, NULL, NULL, NULL, 0, 350000.00, NULL, 40, 10, 1000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, '2025-06-01 15:46:36', '2025-06-01 15:46:36', 0.00, 0),
(46, 'Enfamil A+ Stage 1 Infant Formula', NULL, 7, 8, 'Sữa bột công thức cho trẻ 0-6 tháng', NULL, NULL, NULL, NULL, 0, 650000.00, NULL, 20, 10, 1000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, '2025-06-01 15:46:36', '2025-06-01 15:46:36', 0.00, 0),
(47, 'Pampers Premium Care Diapers Size M', NULL, 7, 9, 'Tã cao cấp siêu thấm hút, êm ái', NULL, NULL, NULL, NULL, 0, 350000.00, 320000.00, 40, 10, 1000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, '2025-06-01 15:46:36', '2025-07-04 05:20:54', 0.00, 0),
(48, 'Similac Gold Stage 2', NULL, 7, 8, 'Sữa bột cho trẻ 6-12 tháng', NULL, NULL, NULL, NULL, 0, 720000.00, NULL, 25, 10, 1000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 0, '2025-06-01 15:46:36', '2025-07-04 04:45:33', 0.00, 0),
(49, 'Huggies Dry Diapers Size L', NULL, 7, 9, 'Tã khô thoáng cho bé', NULL, NULL, NULL, NULL, 0, 280000.00, NULL, 50, 10, 1000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, '2025-06-01 15:46:36', '2025-06-01 15:46:36', 0.00, 0),
(50, 'Aptamil Gold+ Stage 3', NULL, 7, 8, 'Sữa bột cho trẻ 1-2 tuổi', NULL, NULL, NULL, NULL, 0, 850000.00, 780000.00, 18, 10, 1000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, '2025-06-01 15:46:36', '2025-07-04 05:21:02', 0.00, 0),
(51, 'Johnson Baby Shampoo No More Tears', NULL, 7, 9, 'Dầu gội em bé không cay mắt', NULL, NULL, NULL, NULL, 0, 85000.00, NULL, 60, 10, 1000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, '2025-06-01 15:46:36', '2025-06-01 15:46:36', 0.00, 0),
(52, 'Pigeon Baby Wipes 80 Sheets', NULL, 7, 8, 'Khăn ướt em bé không cồn', NULL, NULL, NULL, NULL, 0, 45000.00, NULL, 80, 10, 1000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 0, '2025-06-01 15:46:36', '2025-07-04 04:45:34', 0.00, 0),
(53, 'Kodomo Baby Toothpaste', NULL, 7, 9, 'Kem đánh răng cho bé', NULL, NULL, NULL, NULL, 0, 42000.00, 35000.00, 70, 10, 1000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, '2025-06-01 15:46:36', '2025-07-04 05:21:11', 0.00, 0),
(54, 'Farlin Feeding Bottle 240ml', NULL, 7, 8, 'Bình sữa chống đầy hơi cho bé', NULL, NULL, NULL, NULL, 0, 120000.00, NULL, 45, 10, 1000, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, 0, '2025-06-01 15:46:36', '2025-06-01 15:46:36', 0.00, 0),
(55, 'Tylenol Extra Strength 500mg', 'Acetaminophen', 1, 17, 'Thuốc giảm đau, hạ sốt tác dụng mạnh', '0', 'vien', '500mg', 'Hộp 100 viên', 0, 180000.00, NULL, 80, 10, 1000, '2027-12-31', 'TYL001', '8901030123456', 'TYL-500', 50.00, 'Bảo quản nơi khô ráo, tránh ánh sáng', 'Có thể gây buồn nôn nhẹ', 'Không dùng cho người dị ứng.', '1', 'Trên 12 tuổi', 1, 1, 0, '2025-06-21 17:41:32', '2025-07-05 08:57:58', 0.00, 0),
(56, 'Advil Liqui-Gels 200mg', 'Ibuprofen', 1, 3, 'Viên gel nang mềm giảm đau nhanh', '0', 'vien_nang', '200mg', 'Hộp 50 viên', 0, 250000.00, 125000.00, 64, 10, 1000, '2027-06-30', 'ADV001', '8901030123457', 'ADV-200', 30.00, 'Bảo quản dưới 25°C', 'Có thể gây khó tiêu', 'Không dùng cho người loét dạ dày.', '1', 'Trên 18 tuổi', 1, 0, 1, '2025-06-21 17:41:32', '2025-07-05 08:21:10', 0.00, 0),
(57, 'Motrin IB 400mg', 'Ibuprofen', 1, 17, 'Thuốc giảm đau chống viêm', 'Ibuprofen 400mg', 'vien', '400mg', 'Hộp 30 viên', 0, 95000.00, NULL, 90, 10, 1000, '2027-08-15', 'MOT001', '8901030123458', 'MOT-400', 40.00, 'Tránh ẩm ướt', 'Đau dạ dày, chóng mặt', 'Tránh dùng cho người có bệnh tim', '1 viên mỗi 8 giờ', 'Trên 16 tuổi', 1, 1, 0, '2025-06-21 17:41:32', '2025-06-21 17:41:32', 0.00, 0),
(58, 'Aleve Naproxen 220mg', 'Naproxen', 1, 14, 'Thuốc giảm đau kéo dài 12 giờ', 'Naproxen Sodium 220mg', 'vien', '220mg', 'Hộp 40 viên', 0, 280000.00, NULL, 45, 10, 1000, '2027-11-20', 'ALE001', '8901030123459', 'ALE-220', 35.00, 'Bảo quản khô ráo', 'Buồn nôn, đau bụng', 'Không dùng trước phẫu thuật', '1 viên mỗi 12 giờ', 'Trên 18 tuổi', 1, 0, 0, '2025-06-21 17:41:32', '2025-06-21 17:41:32', 0.00, 0),
(59, 'Benadryl Allergy 25mg', 'Diphenhydramine', 1, 17, 'Thuốc chống dị ứng, an thần nhẹ', 'Diphenhydramine HCl 25mg', 'vien', '25mg', 'Hộp 48 viên', 0, 175000.00, 150000.00, 75, 10, 1000, '2027-05-30', 'BEN001', '8901030123460', 'BEN-25', 25.00, 'Tránh ánh sáng trực tiếp', 'Buồn ngủ, khô miệng', 'Không lái xe sau khi uống', '1-2 viên mỗi 6 giờ', 'Trên 12 tuổi', 1, 1, 0, '2025-06-21 17:41:32', '2025-07-04 05:21:23', 0.00, 0),
(60, 'Claritin 10mg', 'Loratadine', 1, 13, 'Thuốc chống dị ứng không gây buồn ngủ', 'Loratadine 10mg', 'vien', '10mg', 'Hộp 30 viên', 0, 320000.00, NULL, 55, 10, 1000, '2027-09-15', 'CLA001', '8901030123461', 'CLA-10', 20.00, 'Bảo quản dưới 30°C', 'Đau đầu nhẹ', 'An toàn cho hầu hết người dùng', '1 viên mỗi ngày', 'Trên 6 tuổi', 1, 1, 0, '2025-06-21 17:41:32', '2025-06-21 17:41:32', 0.00, 0),
(61, 'Tums Antacid Calcium', 'Calcium Carbonate', 1, 13, 'Thuốc kháng acid, bổ sung canxi', 'Calcium Carbonate 750mg', 'vien', '750mg', 'Chai 150 viên', 0, 120000.00, NULL, 100, 10, 1000, '2027-12-31', 'TUM001', '8901030123462', 'TUM-750', 60.00, 'Nơi khô thoáng', 'Táo bón nhẹ', 'Không dùng với thuốc kháng sinh', '2-4 viên khi cần', 'Trên 12 tuổi', 1, 0, 0, '2025-06-21 17:41:32', '2025-06-21 17:41:32', 0.00, 0),
(62, 'Pepto-Bismol Liquid 262mg', 'Bismuth Subsalicylate', 1, 17, 'Thuốc trị tiêu chảy, đau bụng', 'Bismuth Subsalicylate 262mg/15ml', 'siro', '262mg/15ml', 'Chai 240ml', 0, 210000.00, 180000.00, 40, 10, 1000, '2026-11-30', 'PEP001', '8901030123463', 'PEP-262', 300.00, 'Lắc đều trước khi dùng', 'Đại tiện có thể đen tạm thời', 'Không dùng cho trẻ dưới 12 tuổi', '30ml mỗi lần cần', 'Trên 12 tuổi', 1, 0, 0, '2025-06-21 17:41:32', '2025-07-04 05:24:04', 0.00, 0),
(63, 'Robitussin Cough Syrup', 'Dextromethorphan', 1, 3, 'Siro ho không đờm hiệu quả', 'Dextromethorphan HBr 15mg/5ml', 'siro', '15mg/5ml', 'Chai 118ml', 0, 165000.00, NULL, 69, 10, 1000, '2027-03-31', 'ROB001', '8901030123464', 'ROB-15', 150.00, 'Bảo quản dưới 25°C', 'Buồn ngủ nhẹ', 'Không dùng với thuốc MAO', '10ml mỗi 4 giờ', 'Trên 6 tuổi', 1, 1, 0, '2025-06-21 17:41:32', '2025-07-04 19:05:25', 0.00, 0),
(64, 'Sudafed PE 10mg', 'Phenylephrine', 1, 17, 'Thuốc thông mũi, giảm nghẹt mũi', 'Phenylephrine HCl 10mg', 'vien', '10mg', 'Hộp 36 viên', 0, 195000.00, NULL, 60, 10, 1000, '2027-07-15', 'SUD001', '8901030123465', 'SUD-10', 30.00, 'Tránh ẩm ướt', 'Tim đập nhanh, mất ngủ', 'Không dùng cho người tăng huyết áp', '1 viên mỗi 4 giờ', 'Trên 12 tuổi', 1, 0, 0, '2025-06-21 17:41:32', '2025-06-21 17:41:32', 0.00, 0),
(65, 'Mucinex Expectorant 600mg', 'Guaifenesin', 1, 14, 'Thuốc long đờm, làm loãng đờm', 'Guaifenesin 600mg', 'vien', '600mg', 'Hộp 40 viên', 0, 320000.00, 280000.00, 45, 10, 1000, '2027-10-31', 'MUC001', '8901030123466', 'MUC-600', 50.00, 'Bảo quản khô ráo', 'Buồn nôn nhẹ', 'Uống nhiều nước khi dùng', '1 viên mỗi 12 giờ', 'Trên 12 tuổi', 1, 1, 0, '2025-06-21 17:41:32', '2025-07-04 05:25:56', 0.00, 0),
(66, 'Lipitor Atorvastatin 40mg', 'Atorvastatin', 2, 3, 'Thuốc hạ mỡ máu, cholesterol', 'Atorvastatin Calcium 40mg', 'vien', '40mg', 'Hộp 30 viên', 1, 450000.00, NULL, 30, 10, 1000, '2027-12-31', 'LIP001', '8901030123467', 'LIP-40', 35.00, 'Tránh ánh sáng', 'Đau cơ, gan nhiễm men', 'Theo dõi chức năng gan', '1 viên mỗi tối', 'Trên 18 tuổi', 1, 1, 0, '2025-06-21 17:41:32', '2025-06-21 17:41:32', 0.00, 0),
(67, 'Crestor Rosuvastatin 20mg', 'Rosuvastatin', 2, 11, 'Thuốc điều trị cholesterol cao', 'Rosuvastatin Calcium 20mg', 'vien', '20mg', 'Hộp 28 viên', 1, 580000.00, 520000.00, 25, 10, 1000, '2027-08-30', 'CRE001', '8901030123468', 'CRE-20', 30.00, 'Bảo quản dưới 30°C', 'Đau đầu, đau cơ', 'Xét nghiệm gan định kỳ', '1 viên mỗi ngày', 'Trên 18 tuổi', 1, 0, 0, '2025-06-21 17:41:32', '2025-07-04 05:26:05', 0.00, 0),
(68, 'Norvasc Amlodipine 5mg', 'Amlodipine', 2, 3, 'Thuốc hạ huyết áp, chống đau thắt ngực', 'Amlodipine Besylate 5mg', 'vien', '5mg', 'Hộp 30 viên', 1, 180000.00, NULL, 50, 10, 1000, '2027-11-15', 'NOR001', '8901030123469', 'NOR-5', 25.00, 'Tránh ẩm ướt', 'Phù chân, chóng mặt', 'Đo huyết áp thường xuyên', '1 viên mỗi sáng', 'Trên 18 tuổi', 1, 1, 0, '2025-06-21 17:41:32', '2025-06-21 17:41:32', 0.00, 0),
(69, 'Zestril Lisinopril 10mg', 'Lisinopril', 2, 11, 'Thuốc ức chế ACE, hạ huyết áp', 'Lisinopril 10mg', 'vien', '10mg', 'Hộp 30 viên', 1, 195000.00, NULL, 40, 10, 1000, '2027-06-30', 'ZES001', '8901030123470', 'ZES-10', 20.00, 'Bảo quản khô ráo', 'Ho khan, chóng mặt', 'Theo dõi chức năng thận', '1 viên mỗi ngày', 'Trên 18 tuổi', 1, 0, 0, '2025-06-21 17:41:32', '2025-06-21 17:41:32', 0.00, 0),
(70, 'Diovan Valsartan 80mg', 'Valsartan', 2, 12, 'Thuốc chẹn thụ thể angiotensin', 'Valsartan 80mg', 'vien', '80mg', 'Hộp 28 viên', 1, 390000.00, 350000.00, 35, 10, 1000, '2027-09-30', 'DIO001', '8901030123471', 'DIO-80', 40.00, 'Tránh ánh sáng trực tiếp', 'Chóng mặt, mệt mỏi', 'Uống cùng hoặc không cùng thức ăn', '1 viên mỗi ngày', 'Trên 18 tuổi', 1, 1, 0, '2025-06-21 17:41:32', '2025-07-04 05:26:14', 0.00, 0),
(71, 'Glucophage Metformin 850mg', 'Metformin', 2, 16, 'Thuốc điều trị đái tháo đường type 2', 'Metformin HCl 850mg', 'vien', '850mg', 'Hộp 60 viên', 1, 150000.00, NULL, 80, 10, 1000, '2027-12-31', 'GLU001', '8901030123472', 'GLU-850', 45.00, 'Bảo quản dưới 25°C', 'Tiêu chảy, buồn nôn', 'Uống cùng bữa ăn', '1 viên mỗi bữa', 'Trên 18 tuổi', 1, 1, 0, '2025-06-21 17:41:32', '2025-06-21 17:41:32', 0.00, 0),
(72, 'Januvia Sitagliptin 100mg', 'Sitagliptin', 2, 16, 'Thuốc điều trị đái tháo đường', 'Sitagliptin Phosphate 100mg', 'vien', '100mg', 'Hộp 28 viên', 1, 1200000.00, NULL, 20, 10, 1000, '2027-07-31', 'JAN001', '8901030123473', 'JAN-100', 55.00, 'Bảo quản khô thoáng', 'Đau đầu, nhiễm trúng đường hô hấp', 'Có thể uống không cùng thức ăn', '1 viên mỗi ngày', 'Trên 18 tuổi', 1, 0, 0, '2025-06-21 17:41:32', '2025-06-21 17:41:32', 0.00, 0),
(73, 'Lantus Insulin Glargine', 'Insulin Glargine', 2, 2, 'Insulin tác dụng dài cho tiểu đường', 'Insulin Glargine 100 units/ml', 'thuoc_tiem', '100 units/ml', 'Bút tiêm 3ml', 1, 950000.00, 850000.00, 15, 5, 100, '2027-05-31', 'LAN001', '8901030123474', 'LAN-100', 10.00, 'Bảo quản tủ lạnh 2-8°C', 'Hạ đường huyết, phản ứng tại chỗ tiêm', 'Tiêm dưới da', 'Theo chỉ định bác sĩ', 'Theo chỉ định', 1, 1, 0, '2025-06-21 17:41:32', '2025-07-04 05:34:02', 0.00, 0),
(74, 'Synthroid Levothyroxine 50mcg', 'Levothyroxine', 2, 11, 'Thuốc điều trị suy giáp', 'Levothyroxine Sodium 50mcg', 'vien', '50mcg', 'Hộp 100 viên', 1, 280000.00, NULL, 60, 10, 1000, '2027-10-15', 'SYN001', '8901030123475', 'SYN-50', 15.00, 'Tránh ẩm ướt', 'Tim đập nhanh, mất ngủ', 'Uống lúc đói, sáng sớm', '1 viên mỗi sáng', 'Mọi lứa tuổi', 1, 0, 0, '2025-06-21 17:41:32', '2025-06-21 17:41:32', 0.00, 0),
(75, 'Coumadin Warfarin 5mg', 'Warfarin', 2, 14, 'Thuốc chống đông máu', 'Warfarin Sodium 5mg', 'vien', '5mg', 'Hộp 30 viên', 1, 120000.00, NULL, 40, 10, 1000, '2027-11-30', 'COU001', '8901030123476', 'COU-5', 18.00, 'Tránh ánh sáng', 'Chảy máu, bầm tím', 'Kiểm tra PT/INR thường xuyên', '1 viên mỗi tối', 'Trên 18 tuổi', 1, 1, 0, '2025-06-21 17:41:32', '2025-06-21 17:41:32', 0.00, 0),
(76, 'Omega-3 Fish Oil 1000mg', 'EPA/DHA', 3, 10, 'Dầu cá omega-3 cao cấp từ Úc', '0', '', '1000mg', 'Chai 200 viên', 0, 520000.00, 480000.00, 60, 10, 1000, '2027-12-31', 'OME001', '8901030123477', '0', 80.00, 'Bảo quản mát-', 'Ợ hơi tanh cá nhẹ', 'An toàn cho hầu hết người', '2', 'Trên 12 tuổi', 1, 1, 0, '2025-06-21 17:41:32', '2025-07-05 07:42:14', 0.00, 0),
(77, 'Vitamin D3 5000 IU', 'Cholecalciferol', 3, 1, 'Vitamin D3 liều cao tăng miễn dịch', 'Vitamin D3 5000 IU', 'vien', '5000 IU', 'Chai 120 viên', 0, 380000.00, NULL, 90, 10, 1000, '2027-08-30', 'VIT001', '8901030123478', 'VIT-5000', 30.00, 'Tránh ẩm ướt', 'Hiếm khi có tác dụng phụ', 'Xét nghiệm vitamin D định kỳ', '1 viên mỗi ngày', 'Trên 18 tuổi', 1, 1, 0, '2025-06-21 17:41:32', '2025-06-21 17:41:32', 0.00, 0),
(78, 'Magnesium Glycinate 400mg', 'Magnesium', 3, 2, 'Magie dễ hấp thu, hỗ trợ giấc ngủ', 'Magnesium Glycinate 400mg', 'vien_nang', '400mg', 'Chai 90 viên', 0, 365000.00, 350000.00, 70, 10, 1000, '2027-11-15', 'MAG001', '8901030123479', 'MAG-400', 55.00, 'Nơi khô thoáng', 'Tiêu chảy nhẹ nếu dùng quá liều', 'Uống trước khi ngủ', '2 viên mỗi tối', 'Trên 18 tuổi', 1, 0, 0, '2025-06-21 17:41:32', '2025-07-04 05:34:28', 0.00, 0),
(79, 'Zinc Picolinate 30mg', 'Zinc', 3, 4, 'Kẽm tăng miễn dịch, hỗ trợ vết thương', 'Zinc Picolinate 30mg', 'vien', '30mg', 'Chai 60 viên', 0, 180000.00, NULL, 85, 10, 1000, '2027-06-30', 'ZIN001', '8901030123480', 'ZIN-30', 25.00, 'Bảo quản khô ráo', 'Buồn nôn nếu uống đói', 'Uống cùng thức ăn', '1 viên mỗi ngày', 'Trên 14 tuổi', 1, 1, 0, '2025-06-21 17:41:32', '2025-06-21 17:41:32', 0.00, 0),
(80, 'Turmeric Curcumin 500mg', 'Curcumin', 4, 10, 'Nghệ curcumin chống viêm tự nhiên', 'Curcumin 95% 500mg', 'vien_nang', '500mg', 'Chai 120 viên', 0, 420000.00, NULL, 54, 10, 1000, '2027-09-30', 'TUR001', '8901030123481', 'TUR-500', 65.00, 'Tránh ánh sáng', 'Đau bụng nhẹ nếu dùng quá liều', 'Uống cùng bữa ăn', '2 viên mỗi ngày', 'Trên 18 tuổi', 1, 1, 0, '2025-06-21 17:41:32', '2025-07-05 08:21:10', 0.00, 0),
(81, 'Ashwagandha 600mg', 'Withania Somnifera', 4, 3, 'Thảo dược giảm stress, tăng sức khỏe', 'Ashwagandha Extract 600mg', 'vien_nang', '600mg', 'Chai 90 viên', 0, 620000.00, 550000.00, 40, 10, 1000, '2027-12-31', 'ASH001', '8901030123482', 'ASH-600', 70.00, 'Bảo quản mát', 'Buồn ngủ, đau bụng nhẹ', 'Không dùng cho bà bầu', '1 viên mỗi tối', 'Trên 18 tuổi', 1, 0, 0, '2025-06-21 17:41:32', '2025-07-04 05:36:25', 0.00, 0),
(82, 'Rhodiola Rosea 400mg', 'Rhodiola', 4, 1, 'Thảo dược tăng sức bền, chống mệt mỏi', 'Rhodiola Extract 400mg', 'vien', '400mg', 'Chai 60 viên', 0, 680000.00, NULL, 30, 10, 1000, '2027-07-31', 'RHO001', '8901030123483', 'RHO-400', 45.00, 'Nơi khô thoáng', 'Khô miệng, chóng mặt nhẹ', 'Uống vào buổi sáng', '1 viên mỗi sáng', 'Trên 18 tuổi', 1, 1, 0, '2025-06-21 17:41:32', '2025-07-03 20:05:41', 5.00, 1),
(83, 'Milk Thistle 175mg', 'Silymarin', 4, 2, 'Hỗ trợ giải độc gan, bảo vệ tế bào gan', 'Silymarin 175mg', 'vien_nang', '175mg', 'Chai 100 viên', 0, 380000.00, 330000.00, 65, 10, 1000, '2027-10-15', 'MIL001', '8901030123484', 'MIL-175', 50.00, 'Tránh ẩm ướt', 'Tiêu chảy nhẹ', 'An toàn cho gan', '1 viên mỗi ngày', 'Trên 18 tuổi', 1, 0, 0, '2025-06-21 17:41:32', '2025-07-04 05:17:17', 0.00, 0),
(84, 'Saw Palmetto 320mg', 'Serenoa Repens', 4, 4, 'Hỗ trợ sức khỏe tuyến tiền liệt', 'Saw Palmetto Extract 320mg', 'vien_nang', '320mg', 'Chai 90 viên', 0, 520000.00, NULL, 35, 10, 1000, '2027-11-30', 'SAW001', '8901030123485', 'SAW-320', 60.00, 'Bảo quản khô ráo', 'Đau bụng, buồn nôn nhẹ', 'Dành cho nam giới', '1 viên mỗi ngày', 'Nam trên 40 tuổi', 1, 1, 0, '2025-06-21 17:41:32', '2025-06-21 17:41:32', 0.00, 0),
(85, 'Cranberry Extract 500mg', 'Proanthocyanidins', 4, 10, 'Hỗ trợ đường tiết niệu khỏe mạnh', 'Cranberry Extract 500mg', 'vien', '500mg', 'Chai 120 viên', 0, 320000.00, NULL, 75, 10, 1000, '2027-08-15', 'CRA001', '8901030123486', 'CRA-500', 40.00, 'Nơi khô thoáng', 'An toàn, ít tác dụng phụ', 'Uống nhiều nước', '2 viên mỗi ngày', 'Trên 12 tuổi', 1, 0, 0, '2025-06-21 17:41:32', '2025-06-21 17:41:32', 0.00, 0),
(86, 'CeraVe Moisturizing Cream', 'Ceramides', 5, 5, 'Kem dưỡng ẩm phục hồi hàng rào da', 'Ceramides, Hyaluronic Acid', 'kem', 'Tube 340g', 'Tuýp 340g', 0, 680000.00, 600000.00, 45, 10, 1000, '2027-12-31', 'CER001', '8901030123487', 'CER-340', 340.00, 'Bảo quản dưới 30°C', 'Hiếm khi gây kích ứng', 'Phù hợp mọi loại da', 'Thoa 2 lần mỗi ngày', 'Mọi lứa tuổi', 1, 1, 0, '2025-06-21 17:41:32', '2025-07-04 05:17:08', 0.00, 0),
(87, 'Neutrogena Hydra Boost Serum', 'Hyaluronic Acid', 5, 17, 'Serum cấp ẩm chuyên sâu 72h', 'Hyaluronic Acid, Trehalose', 'gel', '30ml', 'Chai 30ml', 0, 420000.00, NULL, 60, 10, 1000, '2027-06-30', 'NEU001', '8901030123488', 'NEU-30', 35.00, 'Tránh ánh sáng trực tiếp', 'An toàn cho da nhạy cảm', 'Thử patch test trước', 'Thoa sáng và tối', 'Trên 16 tuổi', 1, 1, 0, '2025-06-21 17:41:32', '2025-06-21 17:41:32', 0.00, 0),
(88, 'The Ordinary Niacinamide 10%', 'Niacinamide', 5, 6, 'Serum se khít lỗ chân lông, kiểm soát dầu', 'Niacinamide 10%, Zinc 1%', 'gel', '30ml', 'Chai 30ml', 0, 280000.00, 220000.00, 80, 10, 1000, '2027-09-15', 'ORD001', '8901030123489', 'ORD-NIA', 35.00, 'Bảo quản mát', 'Có thể gây kích ứng ban đầu', 'Bắt đầu từ 2-3 lần/tuần', 'Thoa tối trước kem dưỡng', 'Trên 18 tuổi', 1, 0, 0, '2025-06-21 17:41:32', '2025-07-04 05:14:49', 0.00, 0),
(89, 'Retinol Serum 0.5%', 'Retinol', 5, 5, 'Serum chống lão hóa, làm mờ nếp nhăn', 'Retinol 0.5%, Vitamin E', 'gel', '30ml', 'Chai 30ml', 0, 950000.00, NULL, 25, 10, 1000, '2027-11-30', 'RET001', '8901030123490', 'RET-05', 32.00, 'Bảo quản tối, mát', 'Khô da, bong tróc ban đầu', 'Bắt đầu 1 lần/tuần', 'Chỉ dùng tối, chống nắng ban ngày', 'Trên 25 tuổi', 1, 1, 0, '2025-06-21 17:41:32', '2025-06-21 17:41:32', 0.00, 0),
(90, 'Vitamin C Serum 20%', 'L-Ascorbic Acid', 5, 6, 'Serum vitamin C làm sáng da, chống oxy hóa', 'L-Ascorbic Acid 20%, Vitamin E', 'gel', '30ml', 'Chai 30ml', 0, 580000.00, 540000.00, 40, 10, 1000, '2027-07-31', 'VTC001', '8901030123491', 'VTC-20', 33.00, 'Bảo quản tủ lạnh', 'Kích ứng nhẹ ban đầu', 'Patch test trước khi dùng', 'Thoa buổi sáng', 'Trên 20 tuổi', 1, 0, 0, '2025-06-21 17:41:32', '2025-07-04 05:14:36', 0.00, 0),
(91, 'Salicylic Acid 2% BHA', 'Salicylic Acid', 5, 5, 'Tẩy tế bào chết hóa học cho da mụn', 'Salicylic Acid 2%', 'gel', '30ml', 'Chai 30ml', 0, 350000.00, NULL, 55, 10, 1000, '2027-10-15', 'SAL001', '8901030123492', 'SAL-2', 35.00, 'Tránh ánh sáng', 'Khô da, bong tróc nhẹ', 'Bắt đầu từ 2 lần/tuần', 'Thoa tối, chống nắng ban ngày', 'Trên 16 tuổi', 1, 1, 0, '2025-06-21 17:41:32', '2025-06-21 17:41:32', 0.00, 0),
(92, 'Sunscreen SPF 50+ UVA/UVB', 'Zinc Oxide', 5, 17, 'Kem chống nắng vật lý broad spectrum', 'Zinc Oxide 20%, Titanium Dioxide 5%', 'kem', '50ml', 'Tuýp 50ml', 0, 480000.00, 450000.00, 70, 10, 1000, '2027-12-31', 'SUN001', '8901030123493', 'SUN-50', 55.00, 'Tránh nhiệt độ cao', 'An toàn cho da nhạy cảm', 'Thoa lại mỗi 2 giờ', 'Thoa 30 phút trước ra nắng', 'Trên 6 tháng tuổi', 1, 1, 0, '2025-06-21 17:41:32', '2025-07-04 05:14:28', 0.00, 0),
(93, 'Micellar Water 3-in-1', 'Micelles', 5, 6, 'Nước tẩy trang làm sạch sâu không cần rửa', 'Micelles, Glycerin', 'gel', '400ml', 'Chai 400ml', 0, 320000.00, NULL, 85, 10, 1000, '2027-08-30', 'MIC001', '8901030123494', 'MIC-400', 420.00, 'Nơi khô thoáng', 'Hiếm khi gây kích ứng', 'Phù hợp mọi loại da', 'Dùng bông tẩy trang', 'Mọi lứa tuổi', 1, 0, 0, '2025-06-21 17:41:32', '2025-06-21 17:41:32', 0.00, 0),
(94, 'Glycolic Acid 10% AHA', 'Glycolic Acid', 5, 5, 'Tẩy tế bào chết AHA làm sáng da', 'Glycolic Acid 10%', 'gel', '30ml', 'Chai 30ml', 0, 420000.00, 400000.00, 30, 10, 1000, '2027-11-15', 'GLY001', '8901030123495', 'GLY-10', 35.00, 'Bảo quản mát', 'Nhạy cảm ánh sáng, kích ứng', 'Bắt đầu 1 lần/tuần', 'Chỉ dùng tối, chống nắng', 'Trên 18 tuổi', 1, 1, 0, '2025-06-21 17:41:32', '2025-07-04 05:14:09', 0.00, 0),
(95, 'Peptide Anti-Aging Cream', 'Peptides Complex', 5, 6, 'Kem chống lão hóa với peptide', 'Matrixyl 3000, Argireline', 'kem', '50ml', 'Hủ 50ml', 0, 1200000.00, NULL, 20, 10, 1000, '2027-06-30', 'PEP001', '8901030123496', 'PEP-50', 60.00, 'Bảo quản dưới 25°C', 'Hiếm khi có tác dụng phụ', 'Dùng đều đặn 8-12 tuần', 'Thoa sáng và tối', 'Trên 30 tuổi', 1, 0, 0, '2025-06-21 17:41:32', '2025-06-21 17:41:32', 0.00, 0),
(96, 'Máy Đo Huyết Áp Cổ Tay Omron', 'Thiết bị đo', 6, 7, 'Máy đo huyết áp cổ tay tự động chính xác', 'Màn hình LCD, bộ nhớ 60 lần đo', 'khac', 'Portable', '1 máy + phụ kiện', 0, 1200000.00, 1000000.00, 25, 5, 200, '2030-12-31', 'OBP001', '8901030123497', 'OBP-HEM', 300.00, 'Tránh va đập, ẩm ướt', 'Không có', 'Đọc kỹ hướng dẫn', 'Theo hướng dẫn sử dụng', 'Mọi lứa tuổi', 1, 1, 0, '2025-06-21 17:41:32', '2025-07-04 05:13:50', 0.00, 0),
(97, 'Máy Xông Mũi Họng Ultrasonic', 'Thiết bị xông', 6, 7, 'Máy xông siêu âm cho trẻ em và người lớn', 'Công nghệ siêu âm, 2 mask', 'khac', 'Desktop', '1 máy + phụ kiện', 0, 1800000.00, NULL, 15, 5, 100, '2030-06-30', 'NEB001', '8901030123498', 'NEB-403', 800.00, 'Vệ sinh sau mỗi lần dùng', 'Không có', 'Dùng nước muối sinh lý', 'Theo chỉ định bác sĩ', 'Mọi lứa tuổi', 1, 1, 0, '2025-06-21 17:41:32', '2025-06-21 17:41:32', 0.00, 0),
(98, 'Cân Điện Tử Phân Tích Cơ Thể', 'Thiết bị đo', 6, 7, 'Cân thông minh đo BMI, mỡ cơ thể', 'Bluetooth, app mobile', 'khac', 'Digital', '1 cân + app', 0, 1100000.00, 950000.00, 30, 5, 150, '2030-09-30', 'SCA001', '8901030123499', 'SCA-BF', 1500.00, 'Đặt trên mặt phẳng', 'Không có', 'Kết nối smartphone', 'Cân buổi sáng sau khi đi vệ sinh', 'Trên 10 tuổi', 1, 0, 0, '2025-06-21 17:41:32', '2025-07-04 05:13:25', 0.00, 0),
(99, 'Máy Đo Đường Huyết OneTouch', 'Thiết bị đo', 6, 1, 'Máy đo đường huyết nhanh chính xác', 'Màn hình lớn, bộ nhớ 500 lần', 'khac', 'Portable', '1 máy + 10 que thử', 0, 680000.00, NULL, 40, 5, 200, '2030-11-30', 'GLU001', '8901030123500', 'GLU-OT', 100.00, 'Tránh nhiệt độ cao', 'Đau nhẹ khi chích máu', 'Vệ sinh tay trước khi đo', 'Theo hướng dẫn bác sĩ', 'Người tiểu đường', 1, 1, 0, '2025-06-21 17:41:32', '2025-06-21 17:41:32', 0.00, 0),
(100, 'Máy Massage Cầm Tay 6 Đầu', 'Thiết bị massage', 6, 17, 'Máy massage xung điện trị liệu', '6 chế độ, 15 cường độ', 'khac', 'Handheld', '1 máy + 6 đầu massage', 0, 450000.00, 420000.00, 50, 10, 200, '2030-12-31', 'MAS001', '8901030123501', 'MAS-6H', 250.00, 'Sạc đầy trước khi dùng', 'Không dùng cho người tim yếu', 'Đọc kỹ hướng dẫn', '15-20 phút mỗi lần', 'Trên 16 tuổi', 1, 0, 0, '2025-06-21 17:41:32', '2025-07-04 05:13:02', 0.00, 0);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `thong_bao`
--

DROP TABLE IF EXISTS `thong_bao`;
CREATE TABLE `thong_bao` (
  `ma_thong_bao` int(11) NOT NULL,
  `ma_nguoi_dung` int(11) NOT NULL,
  `tieu_de` varchar(200) NOT NULL,
  `noi_dung` text NOT NULL,
  `loai_thong_bao` enum('don_hang','khuyen_mai','he_thong','nhac_nho') DEFAULT 'he_thong',
  `da_doc` tinyint(1) DEFAULT 0,
  `ngay_tao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `chi_tiet_don_hang`
--
ALTER TABLE `chi_tiet_don_hang`
  ADD PRIMARY KEY (`ma_chi_tiet`),
  ADD KEY `ma_don_hang` (`ma_don_hang`),
  ADD KEY `ma_san_pham` (`ma_san_pham`);

--
-- Chỉ mục cho bảng `danh_gia_san_pham`
--
ALTER TABLE `danh_gia_san_pham`
  ADD PRIMARY KEY (`ma_danh_gia`),
  ADD KEY `ma_nguoi_dung` (`ma_nguoi_dung`),
  ADD KEY `ma_san_pham` (`ma_san_pham`),
  ADD KEY `ma_don_hang` (`ma_don_hang`),
  ADD KEY `idx_danh_gia_trang_thai` (`trang_thai`),
  ADD KEY `idx_danh_gia_san_pham` (`ma_san_pham`,`trang_thai`);

--
-- Chỉ mục cho bảng `danh_muc_thuoc`
--
ALTER TABLE `danh_muc_thuoc`
  ADD PRIMARY KEY (`ma_danh_muc`),
  ADD KEY `ma_danh_muc_cha` (`ma_danh_muc_cha`);

--
-- Chỉ mục cho bảng `dia_chi`
--
ALTER TABLE `dia_chi`
  ADD PRIMARY KEY (`ma_dia_chi`),
  ADD KEY `ma_nguoi_dung` (`ma_nguoi_dung`);

--
-- Chỉ mục cho bảng `don_hang`
--
ALTER TABLE `don_hang`
  ADD PRIMARY KEY (`ma_don_hang`),
  ADD UNIQUE KEY `so_don_hang` (`so_don_hang`),
  ADD KEY `ma_dia_chi_giao_hang` (`ma_dia_chi_giao_hang`),
  ADD KEY `idx_don_hang_nguoi_dung` (`ma_nguoi_dung`),
  ADD KEY `idx_trang_thai_don_hang` (`trang_thai_don_hang`),
  ADD KEY `idx_ngay_tao_don_hang` (`ngay_tao`);

--
-- Chỉ mục cho bảng `giam_gia_soc`
--
ALTER TABLE `giam_gia_soc`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `gio_hang`
--
ALTER TABLE `gio_hang`
  ADD PRIMARY KEY (`ma_gio_hang`),
  ADD UNIQUE KEY `khong_trung_nguoi_dung_san_pham` (`ma_nguoi_dung`,`ma_san_pham`),
  ADD KEY `ma_san_pham` (`ma_san_pham`),
  ADD KEY `idx_gio_hang_nguoi_dung` (`ma_nguoi_dung`);

--
-- Chỉ mục cho bảng `hinh_anh_san_pham`
--
ALTER TABLE `hinh_anh_san_pham`
  ADD PRIMARY KEY (`ma_hinh_anh`),
  ADD KEY `ma_san_pham` (`ma_san_pham`);

--
-- Chỉ mục cho bảng `lich_su_diem_tich_luy`
--
ALTER TABLE `lich_su_diem_tich_luy`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ma_nguoi_dung` (`ma_nguoi_dung`);

--
-- Chỉ mục cho bảng `lich_su_gia`
--
ALTER TABLE `lich_su_gia`
  ADD PRIMARY KEY (`ma_lich_su`),
  ADD KEY `ma_san_pham` (`ma_san_pham`),
  ADD KEY `nguoi_thay_doi` (`nguoi_thay_doi`);

--
-- Chỉ mục cho bảng `nguoi_dung`
--
ALTER TABLE `nguoi_dung`
  ADD PRIMARY KEY (`ma_nguoi_dung`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Chỉ mục cho bảng `nha_san_xuat`
--
ALTER TABLE `nha_san_xuat`
  ADD PRIMARY KEY (`ma_nha_san_xuat`);

--
-- Chỉ mục cho bảng `quang_cao`
--
ALTER TABLE `quang_cao`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `san_pham_thuoc`
--
ALTER TABLE `san_pham_thuoc`
  ADD PRIMARY KEY (`ma_san_pham`),
  ADD UNIQUE KEY `ma_vach` (`ma_vach`),
  ADD UNIQUE KEY `ma_sku` (`ma_sku`),
  ADD KEY `ma_nha_san_xuat` (`ma_nha_san_xuat`),
  ADD KEY `idx_ten_san_pham` (`ten_san_pham`),
  ADD KEY `idx_danh_muc` (`ma_danh_muc`),
  ADD KEY `idx_trang_thai_hoat_dong` (`trang_thai_hoat_dong`),
  ADD KEY `idx_san_pham_noi_bat` (`san_pham_noi_bat`),
  ADD KEY `idx_san_pham_flash_sale` (`is_flash_sale`);

--
-- Chỉ mục cho bảng `thong_bao`
--
ALTER TABLE `thong_bao`
  ADD PRIMARY KEY (`ma_thong_bao`),
  ADD KEY `ma_nguoi_dung` (`ma_nguoi_dung`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `chi_tiet_don_hang`
--
ALTER TABLE `chi_tiet_don_hang`
  MODIFY `ma_chi_tiet` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT cho bảng `danh_gia_san_pham`
--
ALTER TABLE `danh_gia_san_pham`
  MODIFY `ma_danh_gia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `danh_muc_thuoc`
--
ALTER TABLE `danh_muc_thuoc`
  MODIFY `ma_danh_muc` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT cho bảng `dia_chi`
--
ALTER TABLE `dia_chi`
  MODIFY `ma_dia_chi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT cho bảng `don_hang`
--
ALTER TABLE `don_hang`
  MODIFY `ma_don_hang` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT cho bảng `giam_gia_soc`
--
ALTER TABLE `giam_gia_soc`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT cho bảng `gio_hang`
--
ALTER TABLE `gio_hang`
  MODIFY `ma_gio_hang` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- AUTO_INCREMENT cho bảng `hinh_anh_san_pham`
--
ALTER TABLE `hinh_anh_san_pham`
  MODIFY `ma_hinh_anh` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=111;

--
-- AUTO_INCREMENT cho bảng `lich_su_diem_tich_luy`
--
ALTER TABLE `lich_su_diem_tich_luy`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT cho bảng `lich_su_gia`
--
ALTER TABLE `lich_su_gia`
  MODIFY `ma_lich_su` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `nguoi_dung`
--
ALTER TABLE `nguoi_dung`
  MODIFY `ma_nguoi_dung` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT cho bảng `nha_san_xuat`
--
ALTER TABLE `nha_san_xuat`
  MODIFY `ma_nha_san_xuat` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT cho bảng `quang_cao`
--
ALTER TABLE `quang_cao`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT cho bảng `san_pham_thuoc`
--
ALTER TABLE `san_pham_thuoc`
  MODIFY `ma_san_pham` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=113;

--
-- AUTO_INCREMENT cho bảng `thong_bao`
--
ALTER TABLE `thong_bao`
  MODIFY `ma_thong_bao` int(11) NOT NULL AUTO_INCREMENT;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `chi_tiet_don_hang`
--
ALTER TABLE `chi_tiet_don_hang`
  ADD CONSTRAINT `chi_tiet_don_hang_ibfk_1` FOREIGN KEY (`ma_don_hang`) REFERENCES `don_hang` (`ma_don_hang`) ON DELETE CASCADE,
  ADD CONSTRAINT `chi_tiet_don_hang_ibfk_2` FOREIGN KEY (`ma_san_pham`) REFERENCES `san_pham_thuoc` (`ma_san_pham`);

--
-- Các ràng buộc cho bảng `danh_gia_san_pham`
--
ALTER TABLE `danh_gia_san_pham`
  ADD CONSTRAINT `danh_gia_san_pham_ibfk_1` FOREIGN KEY (`ma_nguoi_dung`) REFERENCES `nguoi_dung` (`ma_nguoi_dung`),
  ADD CONSTRAINT `danh_gia_san_pham_ibfk_2` FOREIGN KEY (`ma_san_pham`) REFERENCES `san_pham_thuoc` (`ma_san_pham`),
  ADD CONSTRAINT `danh_gia_san_pham_ibfk_3` FOREIGN KEY (`ma_don_hang`) REFERENCES `don_hang` (`ma_don_hang`);

--
-- Các ràng buộc cho bảng `danh_muc_thuoc`
--
ALTER TABLE `danh_muc_thuoc`
  ADD CONSTRAINT `danh_muc_thuoc_ibfk_1` FOREIGN KEY (`ma_danh_muc_cha`) REFERENCES `danh_muc_thuoc` (`ma_danh_muc`);

--
-- Các ràng buộc cho bảng `dia_chi`
--
ALTER TABLE `dia_chi`
  ADD CONSTRAINT `dia_chi_ibfk_1` FOREIGN KEY (`ma_nguoi_dung`) REFERENCES `nguoi_dung` (`ma_nguoi_dung`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `don_hang`
--
ALTER TABLE `don_hang`
  ADD CONSTRAINT `don_hang_ibfk_1` FOREIGN KEY (`ma_nguoi_dung`) REFERENCES `nguoi_dung` (`ma_nguoi_dung`),
  ADD CONSTRAINT `don_hang_ibfk_2` FOREIGN KEY (`ma_dia_chi_giao_hang`) REFERENCES `dia_chi` (`ma_dia_chi`);

--
-- Các ràng buộc cho bảng `gio_hang`
--
ALTER TABLE `gio_hang`
  ADD CONSTRAINT `gio_hang_ibfk_1` FOREIGN KEY (`ma_nguoi_dung`) REFERENCES `nguoi_dung` (`ma_nguoi_dung`) ON DELETE CASCADE,
  ADD CONSTRAINT `gio_hang_ibfk_2` FOREIGN KEY (`ma_san_pham`) REFERENCES `san_pham_thuoc` (`ma_san_pham`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `hinh_anh_san_pham`
--
ALTER TABLE `hinh_anh_san_pham`
  ADD CONSTRAINT `hinh_anh_san_pham_ibfk_1` FOREIGN KEY (`ma_san_pham`) REFERENCES `san_pham_thuoc` (`ma_san_pham`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `lich_su_diem_tich_luy`
--
ALTER TABLE `lich_su_diem_tich_luy`
  ADD CONSTRAINT `lich_su_diem_tich_luy_ibfk_1` FOREIGN KEY (`ma_nguoi_dung`) REFERENCES `nguoi_dung` (`ma_nguoi_dung`);

--
-- Các ràng buộc cho bảng `lich_su_gia`
--
ALTER TABLE `lich_su_gia`
  ADD CONSTRAINT `lich_su_gia_ibfk_1` FOREIGN KEY (`ma_san_pham`) REFERENCES `san_pham_thuoc` (`ma_san_pham`),
  ADD CONSTRAINT `lich_su_gia_ibfk_2` FOREIGN KEY (`nguoi_thay_doi`) REFERENCES `nguoi_dung` (`ma_nguoi_dung`);

--
-- Các ràng buộc cho bảng `san_pham_thuoc`
--
ALTER TABLE `san_pham_thuoc`
  ADD CONSTRAINT `san_pham_thuoc_ibfk_1` FOREIGN KEY (`ma_danh_muc`) REFERENCES `danh_muc_thuoc` (`ma_danh_muc`),
  ADD CONSTRAINT `san_pham_thuoc_ibfk_2` FOREIGN KEY (`ma_nha_san_xuat`) REFERENCES `nha_san_xuat` (`ma_nha_san_xuat`);

--
-- Các ràng buộc cho bảng `thong_bao`
--
ALTER TABLE `thong_bao`
  ADD CONSTRAINT `thong_bao_ibfk_1` FOREIGN KEY (`ma_nguoi_dung`) REFERENCES `nguoi_dung` (`ma_nguoi_dung`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

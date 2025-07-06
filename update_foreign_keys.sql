-- Script cập nhật Foreign Key để cho phép xóa sản phẩm
-- Chạy file này trong phpMyAdmin hoặc MySQL command line

-- Sử dụng database
USE hieu_thuoc_online;

-- 1. XÓA CÁC FOREIGN KEY CONSTRAINTS CŨ
ALTER TABLE chi_tiet_don_hang DROP FOREIGN KEY chi_tiet_don_hang_ibfk_2;
ALTER TABLE danh_gia_san_pham DROP FOREIGN KEY danh_gia_san_pham_ibfk_2;

-- 2. THAY ĐỔI CỘT ma_san_pham THÀNH NULL (cho phép NULL)
ALTER TABLE chi_tiet_don_hang MODIFY COLUMN ma_san_pham INT(11) NULL;
ALTER TABLE danh_gia_san_pham MODIFY COLUMN ma_san_pham INT(11) NULL;

-- 3. THÊM FOREIGN KEY CONSTRAINTS MỚI VỚI ON DELETE SET NULL
ALTER TABLE chi_tiet_don_hang 
ADD CONSTRAINT chi_tiet_don_hang_ibfk_2 
FOREIGN KEY (ma_san_pham) REFERENCES san_pham_thuoc(ma_san_pham) 
ON DELETE SET NULL 
ON UPDATE CASCADE;

ALTER TABLE danh_gia_san_pham 
ADD CONSTRAINT danh_gia_san_pham_ibfk_2 
FOREIGN KEY (ma_san_pham) REFERENCES san_pham_thuoc(ma_san_pham) 
ON DELETE SET NULL 
ON UPDATE CASCADE;

-- 4. KIỂM TRA KẾT QUẢ
-- Kiểm tra foreign key constraints
SELECT 
    rc.CONSTRAINT_NAME,
    rc.TABLE_NAME,
    kcu.COLUMN_NAME,
    kcu.REFERENCED_TABLE_NAME,
    kcu.REFERENCED_COLUMN_NAME,
    rc.DELETE_RULE,
    rc.UPDATE_RULE
FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS rc
JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu 
    ON rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME 
    AND rc.TABLE_SCHEMA = kcu.TABLE_SCHEMA
WHERE rc.CONSTRAINT_SCHEMA = 'hieu_thuoc_online' 
AND rc.TABLE_NAME IN ('chi_tiet_don_hang', 'danh_gia_san_pham');

-- Hoàn thành!
-- Sau khi chạy script này, bạn có thể xóa sản phẩm trong admin
-- mà không bị lỗi foreign key constraint 
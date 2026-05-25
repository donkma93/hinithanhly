# HINITHANHLYKYGUI - Project Guide

## 1. Giới thiệu
Đây là hệ thống quản lý hàng ký gửi được xây dựng bằng Laravel 10, giao diện Blade + Tailwind, xác thực bằng Breeze, và phân quyền bằng Spatie Permission.

Mục tiêu của dự án:
- Quản lý danh mục, nhà cung cấp, phiếu ký gửi, sản phẩm, tài khoản và phân quyền.
- Dùng mã công khai ngắn (`public_id`) để thao tác thay vì lộ ID tăng dần.
- Có nhật ký thao tác để dễ kiểm tra lịch sử thay đổi.
- Giao diện tối ưu cho desktop và mobile.

## 2. Công nghệ chính
- Laravel 10
- PHP 8.x
- MySQL
- Laravel Breeze (Blade)
- Tailwind CSS / Vite
- Spatie Laravel Permission

## 3. Cách chạy dự án
### Cài đặt
```bash
composer install
npm install
```

### Cấu hình môi trường
- Sao chép `.env.example` thành `.env`
- Cấu hình database trong `.env`
- Tạo key ứng dụng:
```bash
php artisan key:generate
```

### Khởi tạo dữ liệu
```bash
php artisan migrate --seed
```

### Chạy ứng dụng
```bash
php artisan serve
npm run dev
```

## 4. Dữ liệu tài khoản mẫu
Sau khi seed, hệ thống có các tài khoản mẫu:
- `admin@kygui.local` / `password`
- `superadmin@kygui.local` / `password`
- `staff@kygui.local` / `password`

## 5. Luồng sử dụng chính
### 5.1 Đăng nhập
Người dùng đăng nhập qua màn hình auth của Breeze.

### 5.2 Dashboard
Sau khi đăng nhập, người dùng được chuyển vào dashboard nếu có quyền `dashboard.view`.

### 5.3 Quản lý danh mục
Luồng cơ bản:
1. Xem danh sách.
2. Thêm mới.
3. Sửa.
4. Xoá.
5. Tìm kiếm theo mã công khai hoặc tên.

### 5.4 Quản lý nhà cung cấp
Tương tự danh mục:
- Xem
- Thêm
- Sửa
- Xoá
- Tìm kiếm

### 5.5 Quản lý phiếu ký gửi
Luồng:
1. Tạo phiếu.
2. Gắn sản phẩm.
3. Cập nhật trạng thái.
4. Xem chi tiết.
5. Duyệt / xử lý theo quyền.

### 5.6 Quản lý sản phẩm
Luồng:
1. Tạo sản phẩm.
2. Gắn danh mục / nhà cung cấp / phiếu.
3. Sửa thông tin.
4. Xoá nếu được phép.
5. Tìm theo mã công khai.

### 5.7 Quản lý tài khoản
Màn hình `Tài khoản` dùng để:
- Thêm tài khoản mới
- Sửa thông tin người dùng
- Gán vai trò
- Gán quyền trực tiếp
- Xoá tài khoản

### 5.8 Quản lý phân quyền
Màn hình `Phân quyền` dùng để:
- Tạo quyền mới
- Sửa tên quyền
- Xoá quyền
- Mở rộng hệ thống khi thêm chức năng mới

## 6. Cơ chế phân quyền
Dự án dùng 2 lớp quyền:
- **Vai trò**: ví dụ `admin`, `super-admin`, `staff`
- **Quyền chi tiết**: ví dụ `products.create`, `users.delete`, `permissions.manage`

Quy tắc chung:
- `view` = xem danh sách / màn hình
- `create` = thêm mới
- `update` = sửa
- `delete` = xoá
- `manage` = quyền quản lý đầy đủ, có thể thay thế các quyền con

Khi thêm chức năng mới, chỉ cần:
1. Thêm quyền vào `App\Support\PermissionCatalog`
2. Seed lại quyền
3. Gắn middleware / `@can` trong controller và view

## 7. Mã công khai
Dự án không dùng ID tăng dần ở giao diện. Thay vào đó dùng `public_id` để:
- Hiển thị ngắn gọn
- Dễ tìm kiếm
- Tránh lộ cấu trúc ID nội bộ

## 8. Nhật ký thao tác
Hệ thống ghi lại thao tác quan trọng như:
- Thêm / sửa / xoá dữ liệu
- Thay đổi quyền
- Thay đổi tài khoản

Điều này giúp kiểm tra lịch sử hoạt động khi cần.

## 9. Cấu trúc chức năng
- `routes/web.php`: định tuyến chính
- `app/Http/Controllers`: xử lý nghiệp vụ
- `resources/views`: giao diện Blade
- `database/seeders`: dữ liệu mẫu và quyền mặc định
- `app/Support/PermissionCatalog.php`: danh sách quyền trung tâm

## 10. Ghi chú cho người lấy code
- Sau khi clone, hãy chạy seed để có tài khoản và quyền mẫu.
- Nếu không thấy menu, kiểm tra tài khoản hiện tại có quyền `*.view` hoặc `*.manage` chưa.
- Nếu thêm chức năng mới, nhớ cập nhật cả quyền, route, controller và menu.

## 11. Luồng mở rộng khi thêm module mới
Khi thêm 1 module mới, nên làm theo thứ tự:
1. Tạo migration / model
2. Tạo controller
3. Tạo view list / create / edit
4. Thêm route
5. Thêm permission mới vào catalog
6. Seed quyền
7. Cập nhật menu và kiểm tra hiển thị bằng `@can`
8. Ghi audit log nếu thao tác quan trọng

## 12. Tài khoản quản trị
Nên dùng tài khoản `admin` hoặc `super-admin` để thiết lập ban đầu, sau đó phân quyền cho từng người dùng theo nhu cầu.

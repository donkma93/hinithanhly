# Hướng dẫn Mở rộng Cơ sở (Multi-Site dùng chung 1 Database)

Tài liệu này hướng dẫn cách triển khai **Cơ sở 2** (và mở rộng thêm Cơ sở 3, 4...) từ **cùng 1 mã nguồn Git** và **cùng 1 database MySQL** mà **không ảnh hưởng dữ liệu Cơ sở 1**, đồng thời cho phép **trang chủ tra cứu tồn kho (`/`) tìm được đồng thời cả 2 cơ sở**.

---

## 1. Kiến trúc triển khai

- **1 Repo Git duy nhất** → triển khai ra **2 thư mục độc lập** trên hosting/VPS:
  - Cơ sở 1 (`cs1.domain.com` hoặc `domain.com`) → Document Root: `/path/to/cs1/public`
  - Cơ sở 2 (`cs2.domain.com`) → Document Root: `/path/to/cs2/public`
- **1 Database MySQL duy nhất** (`DB_DATABASE` giống nhau ở cả 2 thư mục), tách riêng bộ bảng bằng `DB_PREFIX`:
  - Cơ sở 1: `DB_PREFIX=` *(để trống — giữ nguyên 100% bảng cũ `users`, `products`, `sales`, `settings`, `migrations`...)*
  - Cơ sở 2: `DB_PREFIX=cs2_` *(tự tạo bộ bảng riêng `cs2_users`, `cs2_products`, `cs2_sales`, `cs2_settings`, `cs2_migrations`...)*
- **Trang quản trị / cài đặt / báo cáo**: Tách biệt hoàn toàn 100% theo từng thư mục & `DB_PREFIX` (tồn kho, doanh thu, thanh toán NCC của cơ sở nào tính riêng cho cơ sở đó).
- **Trang bán hàng chung (`/ban-hang`)**: Tất cả cơ sở dùng chung 1 màn hình bán hàng duy nhất — tự nhận diện (hoặc chọn nhanh) sản phẩm theo từng cơ sở, khi chọn **Chuyển khoản** tự động tạo mã **VietQR** theo đúng số tiền và **tài khoản ngân hàng riêng của từng cơ sở**, khi chốt đơn tự tách trừ tồn kho & ghi nhận doanh thu về đúng cơ sở của từng sản phẩm.
- **Trang chủ tra cứu tồn kho (`/`)**: Tự động đọc song song (chỉ đọc — Read-Only) danh sách cơ sở khai báo trong `PORTAL_BRANCHES`, hiển thị tách khối từng cơ sở kèm bộ lọc `Tất cả cơ sở` / `Cơ sở 1` / `Cơ sở 2`.

---

## 2. Mẫu cấu hình `.env` cho từng cơ sở

### 2.1 Cơ sở 1 (`/path/to/cs1/.env`)
Giữ nguyên `APP_KEY`, thông tin `DB_*` cũ và bổ sung các dòng cấu hình đa cơ sở:

```env
APP_NAME="COHANKYGUI - Cơ sở 1"
APP_URL=https://cs1.domain.com

BRANCH_KEY=cs1
BRANCH_NAME="Cơ sở 1"

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ten_db_hien_tai
DB_USERNAME=db_user
DB_PASSWORD=db_password
DB_PREFIX=
DB_SHARED_DATABASE=true

SESSION_COOKIE=cohankygui_cs1_session
SESSION_DOMAIN=
CACHE_PREFIX=cs1_cache_
PERMISSION_CACHE_KEY=cs1.spatie.permission.cache

PORTAL_BRANCHES='[{"key":"cs1","name":"Cơ sở 1","prefix":"","url":"https://cs1.domain.com"},{"key":"cs2","name":"Cơ sở 2","prefix":"cs2_","url":"https://cs2.domain.com"}]'
```

### 2.2 Cơ sở 2 (`/path/to/cs2/.env`)
Dùng **cùng `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`** như Cơ sở 1, nhưng **bắt buộc khác** `APP_KEY`, `APP_URL`, `DB_PREFIX`, `SESSION_COOKIE`, `CACHE_PREFIX`, `PERMISSION_CACHE_KEY`:

```env
APP_NAME="COHANKYGUI - Cơ sở 2"
APP_URL=https://cs2.domain.com

BRANCH_KEY=cs2
BRANCH_NAME="Cơ sở 2"

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ten_db_hien_tai
DB_USERNAME=db_user
DB_PASSWORD=db_password
DB_PREFIX=cs2_
DB_SHARED_DATABASE=true

SESSION_COOKIE=cohankygui_cs2_session
SESSION_DOMAIN=
CACHE_PREFIX=cs2_cache_
PERMISSION_CACHE_KEY=cs2.spatie.permission.cache

PORTAL_BRANCHES='[{"key":"cs1","name":"Cơ sở 1","prefix":"","url":"https://cs1.domain.com"},{"key":"cs2","name":"Cơ sở 2","prefix":"cs2_","url":"https://cs2.domain.com"}]'
```

> **Lưu ý quan trọng về Cookie:** Để trống `SESSION_DOMAIN=` (host-only cookie) và đặt `SESSION_COOKIE` khác nhau giúp nhân viên có thể đăng nhập song song `cs1.domain.com` và `cs2.domain.com` trên cùng trình duyệt mà không bị đăng xuất lẫn nhau.

---

## 3. Quy trình khởi tạo Cơ sở 2 an toàn (Từng bước)

### Bước 1: Backup toàn bộ database hiện tại (Bắt buộc)
```bash
mysqldump -u db_user -p ten_db_hien_tai > backup_truoc_khi_mo_cs2_$(date +%Y%m%d_%H%M%S).sql
```

### Bước 2: Cập nhật code mới nhất cho Cơ sở 1
```bash
cd /path/to/cs1
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci && npm run build
# Cập nhật thêm các biến BRANCH_*, DB_SHARED_DATABASE, SESSION_COOKIE, CACHE_PREFIX, PERMISSION_CACHE_KEY, PORTAL_BRANCHES vào .env
php artisan optimize:clear
php artisan config:cache
```
*(Cơ sở 1 không cần chạy thêm migration nào và không ảnh hưởng dữ liệu cũ.)*

### Bước 3: Triển khai thư mục Cơ sở 2
```bash
git clone <repo-url> /path/to/cs2
cd /path/to/cs2
composer install --no-dev --optimize-autoloader
npm ci && npm run build
```

### Bước 4: Tạo `.env` cho Cơ sở 2 & tạo `APP_KEY` mới
1. Tạo `/path/to/cs2/.env` theo mẫu ở Mục 2.2 (chú ý `DB_PREFIX=cs2_` và `DB_SHARED_DATABASE=true`).
2. Tạo khóa ứng dụng mới riêng cho CS2:
   ```bash
   php artisan key:generate
   ```
3. **Kiểm tra chắc chắn `DB_PREFIX` đã nhận `cs2_`** trước khi migrate:
   ```bash
   php artisan config:clear
   php artisan tinker --execute="dump(DB::getTablePrefix());"
   # Kết quả BẮT BUỘC phải in ra: "cs2_"
   ```

### Bước 5: Khởi tạo bảng & dữ liệu mẫu cho Cơ sở 2
```bash
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan config:cache
```
- Lệnh `migrate` chỉ tạo bộ bảng `cs2_*` và bảng `cs2_migrations`, **không đụng vào bất kỳ bảng nào của Cơ sở 1**.
- Đăng nhập vào `https://cs2.domain.com` bằng tài khoản quản trị mặc định, **đổi mật khẩu ngay lập tức**, và vào **Cài đặt (`/settings/payment`)** để nhập tài khoản ngân hàng, địa chỉ, hotline, giờ mở cửa và mức chiết khấu riêng cho Cơ sở 2.

### Bước 6: Trỏ Subdomain trên Hosting / Web Server
- Tạo subdomain `cs2.domain.com` trỏ Document Root về `/path/to/cs2/public` và bật SSL (HTTPS).

---

## 4. Mở rộng tiếp Cơ sở 3, 4... trong tương lai

1. Lặp lại Bước 3 → 6 cho thư mục `/path/to/cs3` với `DB_PREFIX=cs3_`, `BRANCH_KEY=cs3`, `BRANCH_NAME="Cơ sở 3"`, `SESSION_COOKIE=cohankygui_cs3_session`, `CACHE_PREFIX=cs3_cache_`.
2. Cập nhật `PORTAL_BRANCHES` trong `.env` của các cơ sở để bổ sung `{"key":"cs3","name":"Cơ sở 3","prefix":"cs3_"}` rồi chạy `php artisan config:cache`.

---

## 5. Quy tắc vận hành an toàn (BẮT BUỘC TUÂN THỦ)

1. **TUYỆT ĐỐI KHÔNG chạy `php artisan migrate:fresh` hoặc `php artisan db:wipe`** trên database dùng chung (vì 2 lệnh này xóa toàn bộ bảng trong database bất kể prefix). Hệ thống đã tích hợp lớp bảo vệ tự động chặn 2 lệnh này khi `DB_SHARED_DATABASE=true` hoặc `DB_PREFIX` khác rỗng.
2. **Khi cập nhật tính năng mới về sau**:
   - Chạy `git pull` và `php artisan migrate --force` lần lượt trong cả `/path/to/cs1` và `/path/to/cs2`.
   - Khi viết migration mới hoặc query SQL thô (`selectRaw`, `orderByRaw`, `DB::raw`), luôn dùng `DB::table(...)` hoặc `DB::connection()->getQueryGrammar()->wrap('table.column')` để tương thích với `DB_PREFIX`.
3. **Backup định kỳ**: Vì cả 2 cơ sở nằm chung 1 database MySQL, chỉ cần 1 lịch backup `mysqldump` duy nhất là sao lưu trọn vẹn cả Cơ sở 1 và Cơ sở 2.

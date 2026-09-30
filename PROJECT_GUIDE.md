# cohankygui - Project Guide

## 1. Giá»›i thiá»‡u
ÄÃ¢y lÃ  há»‡ thá»‘ng quáº£n lÃ½ hÃ ng kÃ½ gá»­i Ä‘Æ°á»£c xÃ¢y dá»±ng báº±ng Laravel 10, giao diá»‡n Blade + Tailwind, xÃ¡c thá»±c báº±ng Breeze, vÃ  phÃ¢n quyá»n báº±ng Spatie Permission.

Má»¥c tiÃªu cá»§a dá»± Ã¡n:
- Quáº£n lÃ½ danh má»¥c, nhÃ  cung cáº¥p, phiáº¿u kÃ½ gá»­i, sáº£n pháº©m, tÃ i khoáº£n vÃ  phÃ¢n quyá»n.
- DÃ¹ng mÃ£ cÃ´ng khai ngáº¯n (`public_id`) Ä‘á»ƒ thao tÃ¡c thay vÃ¬ lá»™ ID tÄƒng dáº§n.
- CÃ³ nháº­t kÃ½ thao tÃ¡c Ä‘á»ƒ dá»… kiá»ƒm tra lá»‹ch sá»­ thay Ä‘á»•i.
- Giao diá»‡n tá»‘i Æ°u cho desktop vÃ  mobile.

## 2. CÃ´ng nghá»‡ chÃ­nh
- Laravel 10
- PHP 8.x
- MySQL
- Laravel Breeze (Blade)
- Tailwind CSS / Vite
- Spatie Laravel Permission

## 3. CÃ¡ch cháº¡y dá»± Ã¡n
### CÃ i Ä‘áº·t
```bash
composer install
npm install
```

### Cáº¥u hÃ¬nh mÃ´i trÆ°á»ng
- Sao chÃ©p `.env.example` thÃ nh `.env`
- Cáº¥u hÃ¬nh database trong `.env`
- Táº¡o key á»©ng dá»¥ng:
```bash
php artisan key:generate
```

### Khá»Ÿi táº¡o dá»¯ liá»‡u
```bash
php artisan migrate --seed
```

### Cháº¡y á»©ng dá»¥ng
```bash
php artisan serve
npm run dev
```

## 4. Dá»¯ liá»‡u tÃ i khoáº£n máº«u
Sau khi seed, há»‡ thá»‘ng cÃ³ cÃ¡c tÃ i khoáº£n máº«u:
- `admin@cohankygui.local` / `password`
- `superadmin@cohankygui.local` / `password`
- `staff@cohankygui.local` / `password`

## 5. Luá»“ng sá»­ dá»¥ng chÃ­nh
### 5.1 ÄÄƒng nháº­p
NgÆ°á»i dÃ¹ng Ä‘Äƒng nháº­p qua mÃ n hÃ¬nh auth cá»§a Breeze.

### 5.2 Dashboard
Sau khi Ä‘Äƒng nháº­p, ngÆ°á»i dÃ¹ng Ä‘Æ°á»£c chuyá»ƒn vÃ o dashboard náº¿u cÃ³ quyá»n `dashboard.view`.

### 5.3 Quáº£n lÃ½ danh má»¥c
Luá»“ng cÆ¡ báº£n:
1. Xem danh sÃ¡ch.
2. ThÃªm má»›i.
3. Sá»­a.
4. XoÃ¡.
5. TÃ¬m kiáº¿m theo mÃ£ cÃ´ng khai hoáº·c tÃªn.

### 5.4 Quáº£n lÃ½ nhÃ  cung cáº¥p
TÆ°Æ¡ng tá»± danh má»¥c:
- Xem
- ThÃªm
- Sá»­a
- XoÃ¡
- TÃ¬m kiáº¿m

### 5.5 Quáº£n lÃ½ phiáº¿u kÃ½ gá»­i
Luá»“ng:
1. Táº¡o phiáº¿u.
2. Gáº¯n sáº£n pháº©m.
3. Cáº­p nháº­t tráº¡ng thÃ¡i.
4. Xem chi tiáº¿t.
5. Duyá»‡t / xá»­ lÃ½ theo quyá»n.

### 5.6 Quáº£n lÃ½ sáº£n pháº©m
Luá»“ng:
1. Táº¡o sáº£n pháº©m.
2. Gáº¯n danh má»¥c / nhÃ  cung cáº¥p / phiáº¿u.
3. Sá»­a thÃ´ng tin.
4. XoÃ¡ náº¿u Ä‘Æ°á»£c phÃ©p.
5. TÃ¬m theo mÃ£ cÃ´ng khai.

### 5.7 Quáº£n lÃ½ tÃ i khoáº£n
MÃ n hÃ¬nh `TÃ i khoáº£n` dÃ¹ng Ä‘á»ƒ:
- ThÃªm tÃ i khoáº£n má»›i
- Sá»­a thÃ´ng tin ngÆ°á»i dÃ¹ng
- GÃ¡n vai trÃ²
- GÃ¡n quyá»n trá»±c tiáº¿p
- XoÃ¡ tÃ i khoáº£n

### 5.8 Quáº£n lÃ½ phÃ¢n quyá»n
MÃ n hÃ¬nh `PhÃ¢n quyá»n` dÃ¹ng Ä‘á»ƒ:
- Táº¡o quyá»n má»›i
- Sá»­a tÃªn quyá»n
- XoÃ¡ quyá»n
- Má»Ÿ rá»™ng há»‡ thá»‘ng khi thÃªm chá»©c nÄƒng má»›i

## 6. CÆ¡ cháº¿ phÃ¢n quyá»n
Dá»± Ã¡n dÃ¹ng 2 lá»›p quyá»n:
- **Vai trÃ²**: vÃ­ dá»¥ `admin`, `super-admin`, `staff`
- **Quyá»n chi tiáº¿t**: vÃ­ dá»¥ `products.create`, `users.delete`, `permissions.manage`

Quy táº¯c chung:
- `view` = xem danh sÃ¡ch / mÃ n hÃ¬nh
- `create` = thÃªm má»›i
- `update` = sá»­a
- `delete` = xoÃ¡
- `manage` = quyá»n quáº£n lÃ½ Ä‘áº§y Ä‘á»§, cÃ³ thá»ƒ thay tháº¿ cÃ¡c quyá»n con

Khi thÃªm chá»©c nÄƒng má»›i, chá»‰ cáº§n:
1. ThÃªm quyá»n vÃ o `App\Support\PermissionCatalog`
2. Seed láº¡i quyá»n
3. Gáº¯n middleware / `@can` trong controller vÃ  view

## 7. MÃ£ cÃ´ng khai
Dá»± Ã¡n khÃ´ng dÃ¹ng ID tÄƒng dáº§n á»Ÿ giao diá»‡n. Thay vÃ o Ä‘Ã³ dÃ¹ng `public_id` Ä‘á»ƒ:
- Hiá»ƒn thá»‹ ngáº¯n gá»n
- Dá»… tÃ¬m kiáº¿m
- TrÃ¡nh lá»™ cáº¥u trÃºc ID ná»™i bá»™

## 8. Nháº­t kÃ½ thao tÃ¡c
Há»‡ thá»‘ng ghi láº¡i thao tÃ¡c quan trá»ng nhÆ°:
- ThÃªm / sá»­a / xoÃ¡ dá»¯ liá»‡u
- Thay Ä‘á»•i quyá»n
- Thay Ä‘á»•i tÃ i khoáº£n

Äiá»u nÃ y giÃºp kiá»ƒm tra lá»‹ch sá»­ hoáº¡t Ä‘á»™ng khi cáº§n.

## 9. Cáº¥u trÃºc chá»©c nÄƒng
- `routes/web.php`: Ä‘á»‹nh tuyáº¿n chÃ­nh
- `app/Http/Controllers`: xá»­ lÃ½ nghiá»‡p vá»¥
- `resources/views`: giao diá»‡n Blade
- `database/seeders`: dá»¯ liá»‡u máº«u vÃ  quyá»n máº·c Ä‘á»‹nh
- `app/Support/PermissionCatalog.php`: danh sÃ¡ch quyá»n trung tÃ¢m

## 10. Ghi chÃº cho ngÆ°á»i láº¥y code
- Sau khi clone, hÃ£y cháº¡y seed Ä‘á»ƒ cÃ³ tÃ i khoáº£n vÃ  quyá»n máº«u.
- Náº¿u khÃ´ng tháº¥y menu, kiá»ƒm tra tÃ i khoáº£n hiá»‡n táº¡i cÃ³ quyá»n `*.view` hoáº·c `*.manage` chÆ°a.
- Náº¿u thÃªm chá»©c nÄƒng má»›i, nhá»› cáº­p nháº­t cáº£ quyá»n, route, controller vÃ  menu.

## 11. Luá»“ng má»Ÿ rá»™ng khi thÃªm module má»›i
Khi thÃªm 1 module má»›i, nÃªn lÃ m theo thá»© tá»±:
1. Táº¡o migration / model
2. Táº¡o controller
3. Táº¡o view list / create / edit
4. ThÃªm route
5. ThÃªm permission má»›i vÃ o catalog
6. Seed quyá»n
7. Cáº­p nháº­t menu vÃ  kiá»ƒm tra hiá»ƒn thá»‹ báº±ng `@can`
8. Ghi audit log náº¿u thao tÃ¡c quan trá»ng

## 12. TÃ i khoáº£n quáº£n trá»‹
NÃªn dÃ¹ng tÃ i khoáº£n `admin` hoáº·c `super-admin` Ä‘á»ƒ thiáº¿t láº­p ban Ä‘áº§u, sau Ä‘Ã³ phÃ¢n quyá»n cho tá»«ng ngÆ°á»i dÃ¹ng theo nhu cáº§u.


<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'HINITHANLYKYGUI') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <style>
            body { margin: 0; font-family: Figtree, sans-serif; background: linear-gradient(135deg, #0f172a 0%, #111827 50%, #1f2937 100%); color: #fff; }
            .wrap { min-height: 100vh; display: grid; place-items: center; padding: 24px; }
            .card { width: min(920px, 100%); border: 1px solid rgba(255,255,255,.12); border-radius: 28px; background: rgba(15, 23, 42, .78); box-shadow: 0 30px 80px rgba(0,0,0,.35); backdrop-filter: blur(16px); overflow: hidden; }
            .hero { padding: 56px 40px; display: grid; gap: 18px; }
            .badge { display: inline-flex; align-items: center; width: fit-content; border-radius: 999px; padding: 8px 14px; background: rgba(255,255,255,.08); color: #cbd5e1; font-size: 12px; letter-spacing: .14em; text-transform: uppercase; }
            h1 { margin: 0; font-size: clamp(2.4rem, 6vw, 4.8rem); line-height: .95; letter-spacing: -.05em; }
            p { margin: 0; max-width: 60ch; color: #cbd5e1; font-size: 1rem; line-height: 1.75; }
            .actions { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 8px; }
            .btn { display: inline-flex; align-items: center; justify-content: center; border-radius: 16px; padding: 14px 20px; font-weight: 700; text-decoration: none; }
            .btn.primary { background: #f8fafc; color: #0f172a; }
            .btn.secondary { border: 1px solid rgba(255,255,255,.15); color: #fff; background: rgba(255,255,255,.06); }
            .grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1px; background: rgba(255,255,255,.08); }
            .item { padding: 22px 24px; background: rgba(15, 23, 42, .86); }
            .item strong { display: block; font-size: 1rem; margin-bottom: 8px; }
            .item span { color: #94a3b8; font-size: .95rem; line-height: 1.6; }
            @media (max-width: 720px) {
                .hero { padding: 40px 24px; }
                .grid { grid-template-columns: 1fr; }
            }
        </style>
    </head>
    <body>
        <div class="wrap">
            <div class="card">
                <div class="hero">
                    <div class="badge">HINITHANLYKYGUI</div>
                    <h1>Quản lý ký gửi, bán hàng và vận hành tập trung.</h1>
                    <p>Hệ thống được thiết kế để tra cứu nhanh, thao tác gọn trên mobile, và quản lý dữ liệu lớn với mã số ngắn dễ nhớ.</p>
                    <div class="actions">
                        @auth
                            <a class="btn primary" href="{{ url('/dashboard') }}">Vào Dashboard</a>
                        @else
                            <a class="btn primary" href="{{ route('login') }}">Đăng nhập</a>
                            @if (Route::has('register'))
                                <a class="btn secondary" href="{{ route('register') }}">Đăng ký</a>
                            @endif
                        @endauth
                    </div>
                </div>

                <div class="grid">
                    <div class="item">
                        <strong>Sidebar mobile-first</strong>
                        <span>Điều hướng rõ ràng, tối ưu cho thao tác nhanh trên thiết bị nhỏ.</span>
                    </div>
                    <div class="item">
                        <strong>Mã số ngắn</strong>
                        <span>Dùng mã 6 ký tự để tìm kiếm và nhập liệu đơn giản hơn.</span>
                    </div>
                    <div class="item">
                        <strong>Log &amp; truy vết</strong>
                        <span>Lưu lịch sử thao tác vào database để tra cứu theo mã hoặc hành động.</span>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>

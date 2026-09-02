<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('code') - SiHadir</title>
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px; background: #f6f8fc; color: #17233c; font-family: Arial, sans-serif; }
        .error-card { width: min(540px, 100%); padding: 44px 36px; text-align: center; background: #fffdfa; border: 1px solid #e7e9ee; border-radius: 20px; }
        .brand { margin-bottom: 24px; color: #164a91; font-size: 18px; font-weight: 700; }
        .code { margin: 0; color: #1769e0; font-size: clamp(52px, 12vw, 88px); line-height: 1; }
        h1 { margin: 18px 0 10px; font-size: 25px; }
        p { margin: 0 auto 28px; max-width: 420px; color: #667085; line-height: 1.6; }
        .button { display: inline-block; padding: 11px 22px; border-radius: 9px; background: #1769e0; color: #fff; text-decoration: none; font-weight: 600; }
        .button:hover { background: #1059c4; }
    </style>
</head>
<body>
    <main class="error-card">
        <div class="brand">SiHadir · Sistem Absensi</div>
        <div class="code">@yield('code')</div>
        <h1>@yield('title')</h1>
        <p>@yield('message')</p>
        <a class="button" href="@yield('action', url('/'))">@yield('button', 'Kembali')</a>
    </main>
</body>
</html>

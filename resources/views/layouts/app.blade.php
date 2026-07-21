<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ isset($title) ? $title.' — ' : '' }}NOVAL</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<header class="site-header">
    <a class="brand" href="{{ route('home') }}">NOVAL</a>
    <span class="tagline">Notation for Open and Versatile Archives Layer</span>
    <nav>
        @auth
            <a href="{{ route('dashboard') }}">Dashboard</a>
            <form method="post" action="{{ route('logout') }}">@csrf<button type="submit" class="link-button">Logout</button></form>
        @else
            <a href="{{ route('login') }}">Login</a>
            <a href="{{ route('register') }}">Register</a>
        @endauth
    </nav>
</header>
<main class="container">
    @if (session('status'))<div class="notice success">{{ session('status') }}</div>@endif
    @if ($errors->any())
        <div class="notice error"><strong>入力を確認してください。</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    @yield('content')
</main>
</body>
</html>

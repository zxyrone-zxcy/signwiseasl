<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SignWise') | SignWise</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,600;9..144,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <header class="site-header">
        <div class="header-inner page-width">
            <a class="brand" href="{{ route('home') }}" aria-label="SignWise home"><span class="brand-mark" aria-hidden="true">S</span><span>SignWise</span></a>
            <nav class="main-nav" aria-label="Main navigation">
                <a @class(['active' => request()->routeIs('how-it-works')]) href="{{ route('how-it-works') }}">How it works</a>
                <a @class(['active' => request()->routeIs('lessons.*')]) href="{{ route('lessons.index') }}">Lessons</a>
                @auth<a @class(['active' => request()->routeIs('dashboard')]) href="{{ route('dashboard') }}">Progress</a>@else<a href="{{ route('login') }}">Progress</a>@endauth
            </nav>
            <div class="header-actions">
                @auth
                    <a class="button button-small button-outline" href="{{ route('dashboard') }}">My learning</a>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="button button-small button-primary" type="submit">Log out</button></form>
                @else
                    <a class="button button-small button-outline" href="{{ route('login') }}">Log in</a>
                    <a class="button button-small button-primary" href="{{ route('register') }}">Register</a>
                @endauth
            </div>
        </div>
    </header>
    @if (session('status'))<div class="page-width flash-message" role="status">{{ session('status') }}</div>@endif
    <main>@yield('content')</main>
    <footer class="site-footer page-width">
        <div class="footer-top">
            <div><a class="brand" href="{{ route('home') }}"><span class="brand-mark" aria-hidden="true">S</span><span>SignWise</span></a><p>An inclusive learning space designed with clear language,<br>thoughtful contrast, and room for every learner to grow.</p></div>
            <nav class="footer-nav" aria-label="Footer navigation"><a href="{{ route('how-it-works') }}">How it works</a><a href="{{ route('lessons.index') }}">Lessons</a><a href="{{ auth()->check() ? route('dashboard') : route('login') }}">Progress</a></nav>
        </div>
        <div class="footer-bottom"><span>SignWise prototype</span><span>Designed for accessible, self-paced learning.</span></div>
    </footer>
</body>
</html>
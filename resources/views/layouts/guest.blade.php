<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ request()->routeIs('register') ? 'Create an account' : 'Log in' }} | SignWise</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,600;9..144,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-layout">
    <header class="auth-top page-width">
        <a class="brand" href="{{ route('home') }}"><span class="brand-mark" aria-hidden="true">S</span><span>SignWise</span></a>
        <a class="back-link" href="{{ route('home') }}">Back to home</a>
    </header>
    <main class="auth-main">
        <div class="auth-intro"><p class="eyebrow"><span></span> Learn at your own pace</p><h1>{{ request()->routeIs('register') ? 'Make space to learn.' : 'Welcome back.' }}</h1><p>{{ request()->routeIs('register') ? 'Create your learner account and keep your practice close.' : 'Your next sign is only a little practice away.' }}</p></div>
        <div class="auth-card">{{ $slot }}</div>
        <p class="auth-footnote">A calm, self-guided start to learning American Sign Language.</p>
    </main>
</body>
</html>

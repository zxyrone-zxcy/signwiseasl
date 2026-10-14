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
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body class="signwise-auth-shell">
    <div class="auth-page">
        <aside class="auth-brand-panel" aria-label="SignWise introduction">
            <div class="auth-brand-header">
                <span class="brand-mark" aria-hidden="true">S</span>
                <span>SignWise</span>
            </div>

            <div class="auth-brand-copy">
                <h1>Learn sign<br>language,<br>one sign at<br><span class="highlight">a time.</span></h1>
                <span class="tagline">Start learning sign language today.</span>
            </div>

            <div class="auth-brand-footer">A calm, self-guided start.</div>
        </aside>

        <section class="auth-content-panel">
            <div class="auth-shell">
                <header class="auth-top">
                    <a class="back-link" href="{{ route('home') }}">Back to home</a>
                </header>

                <main class="auth-main">
                    <div class="auth-intro">
                        <p class="eyebrow"><span></span> Learn at your own pace</p>
                        <h1>{{ request()->routeIs('register') ? 'Create your account' : 'Welcome back.' }}</h1>
                        <p>{{ request()->routeIs('register') ? 'Sign up and start learning ASL today.' : 'Your next sign is only a little practice away.' }}</p>
                    </div>
                    <div class="auth-card">{{ $slot }}</div>
                    <p class="auth-footnote">Already have an account? <a href="{{ route('login') }}">Log in</a></p>
                </main>
            </div>
        </section>
    </div>
</body>
</html>

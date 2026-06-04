<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login | POS System</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" />
    <link rel="stylesheet" href="{{ asset('assets/css/auth-login.css') }}" />
</head>
<body>

<div class="login-page">
    <div class="login-left">
        <div class="brand-block">
            <div class="brand-icon">
                <i class="bi bi-shop"></i>
            </div>
            <div>
                <div class="brand-text-name">{{ $currentStoreLocationName ?? 'Main Branch' }}</div>
                <div class="brand-text-role">Administrator</div>
            </div>
        </div>
        <div class="welcome-heading">Welcome Back!</div>
    </div>

    <div class="login-right">
        <div class="login-card">
            <div class="login-card-title">Login</div>
            <div class="login-card-sub">Let's login into your account first</div>

            <form method="post" action="{{ route('login.attempt') }}">
                @csrf

                <label class="form-label" for="login">User Name</label>
                <div class="input-group-custom">
                    <i class="bi bi-person input-icon-left"></i>
                    <input
                        type="text"
                        class="form-input"
                        id="login"
                        name="login"
                        value="{{ old('login') }}"
                        placeholder="Enter your username"
                        autocomplete="username"
                        required
                        autofocus
                    />
                </div>

                <label class="form-label" for="password">Password</label>
                <div class="input-group-custom">
                    <i class="bi bi-key input-icon-left"></i>
                    <input
                        type="password"
                        class="form-input"
                        id="password"
                        name="password"
                        placeholder="Enter your password"
                        autocomplete="current-password"
                        required
                    />
                    <button class="input-icon-right" id="eyeBtn" type="button" aria-label="Toggle password visibility">
                        <i class="bi bi-eye-slash" id="eyeIcon"></i>
                    </button>
                </div>

                <div class="form-meta">
                    <label class="remember-me">
                        <input type="checkbox" name="remember" value="1"> Remember me
                    </label>
                    <a href="#" class="forgot-link">Forgot Password ?</a>
                </div>

                <button class="btn-login" type="submit">Login</button>

                @error('login')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('assets/js/auth-login.js') }}"></script>
</body>
</html>

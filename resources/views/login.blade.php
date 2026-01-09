<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="css/login.css">
</head>

<body>

    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h2>Chào Mừng!</h2>
                <p>Đăng nhập vào tài khoản của bạn</p>
            </div>

            <form class="login-form" id="loginForm" novalidate>
                <div class="form-group">
                    <div class="input-wrapper">
                        <input type="email" id="email" name="email" required autocomplete="email">
                        <label for="email">Email</label>
                        <span class="focus-border"></span>
                    </div>
                    <span class="error-message" id="emailError"></span>
                </div>

                <div class="form-group">
                    <div class="input-wrapper password-wrapper">
                        <input type="password" id="password" name="password" required autocomplete="current-password">
                        <label for="password">Mật khẩu</label>
                        <button type="button" class="password-toggle" id="passwordToggle" aria-label="Toggle password visibility">
                            <span class="eye-icon"></span>
                        </button>
                        <span class="focus-border"></span>
                    </div>
                    <span class="error-message" id="passwordError"></span>
                </div>

                <div class="form-options">
                    <label class="remember-wrapper">
                        <input type="checkbox" id="remember" name="remember">
                        <span class="checkbox-label">
                            <span class="checkmark"></span>
                            Ghi nhớ
                        </span>
                    </label>
                    <a href="#" class="forgot-password">Đổi mật khẩu?</a>
                </div>

                <button type="submit" class="login-btn btn">
                    <span class="btn-text" onclick="login()">Đăng Nhập</span>
                    <span class="btn-loader"></span>
                </button>
            </form>

            <div class="divider">
                <span>hoặc tiếp tục với</span>
            </div>

            <div class="social-login">
                <button type="button" class="social-btn google-btn">
                    <span class="social-icon google-icon"></span>
                    Google
                </button>
                <button type="button" class="social-btn facebook-btn">
                    <span class="social-icon facebook-icon"></span>
                    Facebook
                </button>
            </div>

            <div class="signup-link">
                <p>Chưa có tài khoản? <a href="register">Đăng ký</a></p>
            </div>
        </div>
    </div>

    <script src="js/form-utils.js"></script>
    <script src="js/login.js"></script>

    <script>
        async function login() {
            const res = await fetch("/api/auth/login", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json"
                },
                body: JSON.stringify({
                    email: email.value,
                    password: password.value
                })
            });

            const text = await res.text();

            const data = JSON.parse(text);

            if (!res.ok) {
                showLoginError();
                return;
            }

            localStorage.setItem("token", data.token);
            window.location.href = "/";
        }
        function showLoginError(message) {
            FormUtils.showNotification('Đăng nhập không thành công. Vui lòng thử lại.', 'error', this.form);
            
            // Shake the entire card
            const card = document.querySelector('.login-card');
            card.style.animation = 'shake 0.5s ease-in-out';
            setTimeout(() => {
                card.style.animation = '';
            }, 500);
        }
    </script>


</body>

</html>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="css/login.css">
</head>

<body>

    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h2>Chào Mừng!</h2>
                <p>Đăng ký tài khoản của bạn</p>
            </div>

            <form class="login-form" id="loginForm" novalidate>
                <div class="form-group">
                    <div class="input-wrapper">
                        <input type="text" id="name" name="name" required autocomplete="name">
                        <label for="name">Họ & Tên</label>
                        <span class="focus-border"></span>
                    </div>
                    <span class="error-message" id="nameError"></span>
                </div>

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

                <div class="form-group">
                    <div class="input-wrapper password-wrapper">
                        <input type="password" id="confirmPassword" name="confirmPassword" required autocomplete="current-password">
                        <label for="confirmPassword">Nhập lại mật khẩu</label>
                        <button type="button" class="password-toggle" id="confirmPasswordToggle" aria-label="Toggle password visibility">
                            <span class="eye-icon"></span>
                        </button>
                        <span class="focus-border"></span>
                    </div>
                    <span class="error-message" id="confirmPasswordError"></span>
                </div>

                <button type="button" class="login-btn btn" onclick="register()">
                    <span class="btn-text">Đăng Ký</span>
                    <span class="btn-loader"></span>
                </button>
            </form>

            <div class="signup-link">
                <p>Quay lại? <a href="login">Đăng nhập</a></p>
            </div>
        </div>
    </div>

    <script src="js/form-utils.js"></script>
    <script src="js/register.js"></script>

    <script>
        async function register() {
            const res = await fetch("/api/auth/register", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json"
                },
                body: JSON.stringify({
                    name: document.getElementById("name").value,
                    email: document.getElementById("email").value,
                    password: document.getElementById("password").value,
                    password_confirmation: document.getElementById("confirmPassword").value
                })
            });

            const data = await res.json();

            if (!res.ok) {
                showLoginError();
                return;
            }

            window.location.href = "/login";
        }
        function showLoginError(message) {
            FormUtils.showNotification('Đăng ký không thành công. Vui lòng thử lại.', 'error', this.form);
            
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
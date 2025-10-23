<?php
include 'config.php'; // DB ve Session bağlantısı

$error_message = '';

// Kullanıcı zaten giriş yapmışsa ana sayfaya yönlendir
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Form gönderildi mi? (Normal PHP POST işlemi)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error_message = 'E-posta ve şifre gereklidir.';
    } else {
        try {
            // config.php $pdo'yu kurdu mu?
            if ($pdo === null) {
                $error_message = 'Veritabanı bağlantı hatası.';
            } else {
                $stmt = $pdo->prepare("SELECT * FROM Users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user->password)) {
                    $_SESSION['user_id'] = $user->id;
                    $_SESSION['user_fullname'] = $user->fullname;
                    $_SESSION['user_email'] = $user->email;
                    $_SESSION['user_role'] = $user->role;
                    if ($user->role == 'Firma Admin') {
                        $_SESSION['user_company_id'] = $user->company_id;
                    }
                    header('Location: index.php');
                    exit;
                } else {
                    $error_message = 'Geçersiz e-posta veya şifre.';
                }
            }
        } catch (PDOException $e) {
            $error_message = 'Giriş sırasında bir hata oluştu: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap - TUlaşım</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="favicon.png">
    <style>
        body { background-color: #f0f2f5; }
        .login-container { min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-card { max-width: 450px; width: 100%; }
        .logo { max-width: 250px; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="container login-container">
        <div class="login-card">
            
            <div class="text-center mb-4">
                <a href="index.php">
                    <img src="Tulaşım.png" alt="TUlaşım Logo" class="logo">
                </a>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body p-4 p-md-5">
                    <h3 class="card-title text-center mb-4">Giriş Yap</h3>

                    <?php if ($error_message): ?>
                        <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>

                    <form action="login.php" method="POST" id="loginForm">
                        <div class="mb-3">
                            <label for="email" class="form-label">E-posta Adresi:</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                            <div id="email-feedback" class="mt-2"></div>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Şifre:</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 btn-lg" id="btn-login">
                            Giriş Yap
                        </button>
                    </form>
                    <hr class="my-4">
                    <p class="text-center">Hesabınız yok mu? <a href="register.php">Kayıt Ol</a></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        const emailInput = document.getElementById('email');
        const feedbackDiv = document.getElementById('email-feedback');
        const loginButton = document.getElementById('btn-login');

        emailInput.addEventListener('blur', async () => {
            const email = emailInput.value.trim();
            feedbackDiv.innerHTML = ''; // Mesajı temizle
            
            if (email === "" || !email.includes('@')) {
                loginButton.disabled = false;
                return; // Geçerli bir e-posta değilse kontrol etme
            }

            const formData = new FormData();
            formData.append('email', email);

            try {
                const response = await fetch('check_user.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                // LOGIN LOGIC: Kullanıcı bulunamazsa uyar
                if (data.userExists === false) {
                    feedbackDiv.innerHTML = '<span class="text-danger fw-bold">Bu e-posta ile kayıtlı bir kullanıcı bulunamadı.</span>';
                    loginButton.disabled = true; // Giriş yapmayı engelle
                } else {
                    // Kullanıcı varsa (true), butonu aktifleştir
                    feedbackDiv.innerHTML = '<span class="text-success fw-bold">Kullanıcı bulundu.</span>';
                    loginButton.disabled = false;
                }
            } catch (error) {
                console.error('Doğrulama hatası:', error);
                loginButton.disabled = false; // Hata olursa, normal girişe izin ver
            }
        });
    </script>

</body>
</html>
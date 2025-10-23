<?php
include 'config.php'; // DB ve Session bağlantısı

$error_message = '';
$success_message = '';

// Form gönderildi mi? (Normal PHP POST işlemi)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($fullname) || empty($email) || empty($password)) {
        $error_message = 'Lütfen tüm alanları doldurun.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Geçersiz e-posta formatı.';
    } else {
        try {
            if ($pdo === null) {
                $error_message = 'Veritabanı bağlantı hatası.';
            } else {
                $stmt = $pdo->prepare("SELECT id FROM Users WHERE email = ?");
                $stmt->execute([$email]);
                
                if ($stmt->fetch()) {
                    $error_message = 'Bu e-posta adresi zaten kullanılıyor.';
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $insertStmt = $pdo->prepare("INSERT INTO Users (fullname, email, password, role, balance) VALUES (?, ?, ?, 'User', 0.0)");
                    $insertStmt->execute([$fullname, $email, $hashed_password]);
                    $success_message = 'Kayıt başarılı! Lütfen <a href="login.php">giriş yapın</a>.';
                }
            }
        } catch (PDOException $e) {
            $error_message = 'Kayıt sırasında bir hata oluştu: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt Ol - TUlaşım</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="favicon.png">
    <style>
        body { background-color: #f0f2f5; }
        .register-container { min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .register-card { max-width: 450px; width: 100%; }
        .logo { max-width: 250px; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="container register-container py-5">
        <div class="register-card">
            
            <div class="text-center mb-4">
                <a href="index.php">
                    <img src="Tulaşım.png" alt="TUlaşım Logo" class="logo">
                </a>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body p-4 p-md-5">
                    <h3 class="card-title text-center mb-4">Kayıt Ol</h3>

                    <?php if ($error_message): ?>
                        <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>
                    <?php if ($success_message): ?>
                        <div class="alert alert-success"><?php echo $success_message; ?></div>
                    <?php endif; ?>

                    <?php if (!$success_message): ?>
                        <form action="register.php" method="POST" id="registerForm">
                            <div class="mb-3">
                                <label for="fullname" class="form-label">Ad Soyad:</label>
                                <input type="text" class="form-control" id="fullname" name="fullname" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">E-posta Adresi:</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                                <div id="email-feedback" class="mt-2"></div>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Şifre:</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 btn-lg" id="btn-register">
                                Kayıt Ol
                            </button>
                        </form>
                    <?php endif; ?>

                    <hr class="my-4">
                    <p class="text-center">Zaten hesabınız var mı? <a href="login.php">Giriş Yap</a></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        const emailInput = document.getElementById('email');
        const feedbackDiv = document.getElementById('email-feedback');
        const registerButton = document.getElementById('btn-register');

        emailInput.addEventListener('blur', async () => {
            const email = emailInput.value.trim();
            feedbackDiv.innerHTML = ''; // Mesajı temizle
            
            if (email === "" || !email.includes('@')) {
                registerButton.disabled = false;
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

                // REGISTER LOGIC: Kullanıcı zaten varsa uyar
                if (data.userExists === true) {
                    feedbackDiv.innerHTML = '<span class="text-danger fw-bold">Bu e-posta adresi zaten kullanılıyor.</span>';
                    registerButton.disabled = true; // Kayıt olmayı engelle
                } else {
                    // Kullanıcı yoksa (false), kayıt olabilir
                    feedbackDiv.innerHTML = '<span class="text-success fw-bold">Bu e-posta adresi kullanılabilir.</span>';
                    registerButton.disabled = false;
                }
            } catch (error) {
                console.error('Doğrulama hatası:', error);
                registerButton.disabled = false; // Hata olursa, normal kayıta izin ver (Sunucu tarafı tekrar kontrol edecek)
            }
        });
    </script>

</body>
</html>
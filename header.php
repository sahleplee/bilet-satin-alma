<?php
// config.php'yi dahil et (DB ve Session)
include_once 'config.php';

// Oturum durumunu kontrol et
$is_logged_in = isset($_SESSION['user_id']);
$user_role = $is_logged_in ? $_SESSION['user_role'] : 'Ziyaretçi';
$user_fullname = $is_logged_in ? $_SESSION['user_fullname'] : '';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TUlaşım - Türkiye Ulaşım Platformu</title>
    
    <link rel="icon" type="image/png" href="favicon.png">
    <link rel="apple-touch-icon" href="favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" 
          rel="stylesheet" 
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" 
          crossorigin="anonymous">
    
    <style>
        .error { color: red; font-weight: bold; }
        .success { color: green; font-weight: bold; }
        .seat-map { display: grid; grid-template-columns: repeat(5, 50px); gap: 10px; margin: 20px 0; }
        .seat { width: 50px; height: 50px; border: 1px solid #ccc; background: #e0f7fa; text-align: center; line-height: 50px; border-radius: 5px; }
        .seat.corridor { background: none; border: none; }
        .seat input[type="radio"] { display: none; }
        .seat label { display: block; width: 100%; height: 100%; cursor: pointer; }
        .seat input[type="radio"]:disabled + label { background: #ffcdd2; color: #888; cursor: not-allowed; text-decoration: line-through; }
        .seat input[type="radio"]:checked + label { background: #4caf50; color: white; border: 2px solid #1a5e20; }
        
        /* === LOGO OPTİMİZASYONU === */
        .navbar-brand {
            padding-top: 0.25rem; 
            padding-bottom: 0.25rem;
        }
        .navbar-brand img {
            height: 60px; /* Logo yüksekliği */
            width: auto; 
        }

        /* === HEADER ARKA PLAN DEĞİŞİKLİĞİ === */
        .bg-light-header {
            background-color: #f8f9fa !important;
            border-bottom: 1px solid #dee2e6; /* Altına ince bir çizgi */
        }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-light bg-light-header shadow-sm">
  <div class="container-fluid">
    
    <a class="navbar-brand" href="index.php">
        <img src="Tulaşım.png" alt="TUlaşım Logo">
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link active" href="index.php">Ana Sayfa</a>
        </li>
        
        <?php // Döküman Adım 4: Rol Yönetimi ?>
        <?php if ($user_role == 'User'): ?>
            <li class="nav-item">
                <a class="nav-link" href="my_tickets.php">Biletlerim / Hesabım</a>
            </li>
        <?php endif; ?>
        <?php if ($user_role == 'Firma Admin'): ?>
             <li class="nav-item">
                <a class="nav-link" href="company_admin_panel.php">Firma Admin Paneli</a>
             </li>
        <?php endif; ?>
        <?php if ($user_role == 'Admin'): ?>
             <li class="nav-item">
                <a class="nav-link" href="admin_panel.php">Admin Paneli</a>
             </li>
        <?php endif; ?>
      </ul>
      
      <ul class="navbar-nav ms-auto">
        <?php if ($is_logged_in): ?>
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <?php echo htmlspecialchars($user_fullname); ?> (<?php echo $user_role; ?>)
              </a>
              <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                <li><a class="dropdown-item" href="logout.php">Çıkış Yap</a></li>
              </ul>
            </li>
        <?php else: ?>
            <li class="nav-item">
                <a class="nav-link" href="login.php">Giriş Yap</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="register.php">Kayıt Ol</a>
            </li>
        <?php endif; ?>
      </ul>

    </div>
  </div>
</nav>
<div class="container mt-4">
    <div class="bg-white p-4 p-md-5 rounded shadow-sm">
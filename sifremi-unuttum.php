<?php
session_start();
include 'baglanti.php';

$adim = 1; // 1: e-posta gir, 2: güvenlik sorusunu cevapla + yeni şifre belirle
$hata = '';
$basari = '';
$email = '';
$hesapTipi = null; // 'kullanici' | 'mentor'
$guvenlikSorusu = '';

// Adım 1: e-posta gönderildi, ilgili tabloda ara (önce Kullanici, sonra Mentor)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adim1_email'])) {
    $email = trim($_POST['email']);

    $stmt = $conn->prepare("SELECT KullaniciID AS ID, GuvenlikSorusu FROM Kullanici WHERE Email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $sonuc = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($sonuc && !empty($sonuc['GuvenlikSorusu'])) {
        $hesapTipi = 'kullanici';
        $guvenlikSorusu = $sonuc['GuvenlikSorusu'];
    } else {
        $stmt = $conn->prepare("SELECT MentorID AS ID, GuvenlikSorusu FROM Mentor WHERE Email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $sonuc = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($sonuc && !empty($sonuc['GuvenlikSorusu'])) {
            $hesapTipi = 'mentor';
            $guvenlikSorusu = $sonuc['GuvenlikSorusu'];
        }
    }

    if ($hesapTipi === null) {
        // Güvenlik amacıyla, hesap bulunamasa da (ya da eski bir hesapta
        // güvenlik sorusu kayıtlı değilse) genel bir mesaj gösteriyoruz;
        // böylece hangi e-postaların kayıtlı olduğu dışarıdan anlaşılmaz.
        $hata = "Bu e-posta ile ilişkili bir güvenlik sorusu bulunamadı. E-postanızı kontrol edin veya kayıt olurken bir güvenlik sorusu belirlemediyseniz bizimle iletişime geçin.";
        $adim = 1;
    } else {
        $adim = 2;
    }
}

// Adım 2: güvenlik cevabı + yeni şifre gönderildi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adim2_sifirla'])) {
    $email = trim($_POST['email']);
    $hesapTipi = $_POST['hesapTipi'] === 'mentor' ? 'mentor' : 'kullanici';
    $cevap = trim($_POST['guvenlikCevabi'] ?? '');
    $yeniSifre = $_POST['yeniSifre'] ?? '';
    $yeniSifreTekrar = $_POST['yeniSifreTekrar'] ?? '';

    $tablo = $hesapTipi === 'mentor' ? 'Mentor' : 'Kullanici';
    $idSutunu = $hesapTipi === 'mentor' ? 'MentorID' : 'KullaniciID';

    $stmt = $conn->prepare("SELECT $idSutunu AS ID, GuvenlikSorusu, GuvenlikCevabi FROM $tablo WHERE Email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $hesap = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$hesap) {
        $hata = "Hesap bulunamadı.";
        $adim = 1;
    } else {
        $guvenlikSorusu = $hesap['GuvenlikSorusu'];

        if (empty($cevap) || !password_verify(mb_strtolower($cevap, 'UTF-8'), $hesap['GuvenlikCevabi'])) {
            $hata = "Güvenlik sorusu cevabı yanlış.";
            $adim = 2;
        } elseif (strlen($yeniSifre) < 6) {
            $hata = "Yeni şifre en az 6 karakter olmalıdır.";
            $adim = 2;
        } elseif ($yeniSifre !== $yeniSifreTekrar) {
            $hata = "Şifreler eşleşmiyor.";
            $adim = 2;
        } else {
            $yeniSifreHash = password_hash($yeniSifre, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE $tablo SET Sifre = ? WHERE $idSutunu = ?");
            $stmt->bind_param("si", $yeniSifreHash, $hesap['ID']);
            $stmt->execute();
            $stmt->close();

            $basari = "Şifreniz başarıyla güncellendi. Şimdi giriş yapabilirsiniz.";
            $adim = 3;
        }
    }
}
?>
<!doctype html>
<html lang="en">
  <head>
    <title>Future Career Route</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <link rel="stylesheet" href="css/custom-bs.css">
    <link rel="stylesheet" href="css/jquery.fancybox.min.css">
    <link rel="stylesheet" href="css/bootstrap-select.min.css">
    <link rel="stylesheet" href="fonts/icomoon/style.css">
    <link rel="stylesheet" href="fonts/line-icons/style.css">
    <link rel="stylesheet" href="css/owl.carousel.min.css">
    <link rel="stylesheet" href="css/animate.min.css">
    <link rel="stylesheet" href="css/quill.snow.css">

    <!-- MAIN CSS -->
    <link rel="stylesheet" href="css/style.css">
    <style>
      .error-message { color: red; font-size: 0.9em; margin-top: 5px; }
      .success-message { color: green; font-size: 0.9em; margin-top: 5px; }
    </style>
  </head>
  <body id="top">

  <div id="overlayer"></div>
  <div class="loader">
    <div class="spinner-border text-primary" role="status">
      <span class="sr-only">Yükleniyor...</span>
    </div>
  </div>

<div class="site-wrap">

    <div class="site-mobile-menu site-navbar-target">
      <div class="site-mobile-menu-header">
        <div class="site-mobile-menu-close mt-3">
          <span class="icon-close2 js-menu-toggle"></span>
        </div>
      </div>
      <div class="site-mobile-menu-body"></div>
    </div> <!-- .site-mobile-menu -->

    <!-- NAVBAR -->
    <header class="site-navbar mt-3">
      <div class="container-fluid">
        <div class="row align-items-center">
          <div class="site-logo col-6">
            <a href="index.php">
              <img src="images/Logo.png" alt="logo" style="width:110px; height:auto;">
            </a>
          </div>
          <nav class="mx-auto site-navigation">
            <ul class="site-menu js-clone-nav d-none d-xl-block ml-0 pl-0">
              <li><a href="index.php" class="nav-link">Ana Sayfa</a></li>
              <li><a href="anket.php">Anket</a></li>
              <li><a href="mentor-listesi.php">Mentorlar</a></li>
              <li><a href="about.php">Hakkımızda</a></li>
              <li><a href="blog.php">Programlar</a></li>
              <li><a href="contact.php">İletişim</a></li>
              <?php if (!empty($_SESSION['kullanici_id']) || !empty($_SESSION['mentor_id'])): ?>
              <li class="d-lg-none"><a href="<?php echo !empty($_SESSION['mentor_id']) ? 'mentorun-profili.php' : 'kullanicinin-profili.php'; ?>">Profilim</a></li>
              <li class="d-lg-none"><a href="logout.php">Çıkış Yap</a></li>
              <?php else: ?>
              <li class="d-lg-none"><a href="post-job.php">Giriş Yap</a></li>
              <li class="d-lg-none"><a href="kayit-ol-secigi.php">Kayıt Ol</a></li>
              <?php endif; ?>
            </ul>
          </nav>
          <?php if (!empty($_SESSION['kullanici_id']) || !empty($_SESSION['mentor_id'])): ?>
          <div class="ml-auto d-none d-lg-block">
            <a href="<?php echo !empty($_SESSION['mentor_id']) ? 'mentorun-profili.php' : 'kullanicinin-profili.php'; ?>" class="btn btn-primary rounded-0 py-2 px-4 d-inline-block">Profilim</a>
            <a href="logout.php" class="btn btn-outline-primary rounded-0 py-2 px-4 d-inline-block">Çıkış Yap</a>
          </div>
          <?php else: ?>
          <div class="ml-auto d-none d-lg-block">
            <a href="post-job.php" class="btn btn-primary rounded-0 py-2 px-4 d-inline-block">Giriş Yap</a>
            <a href="kayit-ol-secigi.php" class="btn btn-outline-primary rounded-0 py-2 px-4 d-inline-block">Kayıt Ol</a>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </header>

    <!-- HOME -->
    <section class="section-hero overlay inner-page bg-image" style="background-image: url('images/hero_1.jpg');" id="home-section">
      <div class="container">
        <div class="row">
          <div class="col-md-7">
            <h1 class="text-white font-weight-bold">Şifremi Unuttum</h1>
            <div class="custom-breadcrumbs">
              <a href="index.php">Ana Sayfa</a> <span class="mx-2 slash">/</span>
              <span class="text-white"><strong>Şifremi Unuttum</strong></span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="site-section">
      <div class="container">
        <div class="row justify-content-center">
          <div class="col-md-6">
            <?php if ($hata): ?>
              <div class="error-message mb-3"><?php echo htmlspecialchars($hata); ?></div>
            <?php endif; ?>

            <?php if ($adim === 1): ?>
              <form method="POST" class="p-4 border rounded">
                <div class="form-group">
                  <label>Kayıtlı e-posta adresiniz</label>
                  <input type="email" name="email" class="form-control" required>
                </div>
                <button type="submit" name="adim1_email" value="1" class="btn btn-primary">Devam Et</button>
              </form>

            <?php elseif ($adim === 2): ?>
              <form method="POST" class="p-4 border rounded">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                <input type="hidden" name="hesapTipi" value="<?php echo htmlspecialchars($hesapTipi); ?>">
                <div class="form-group">
                  <label><?php echo htmlspecialchars($guvenlikSorusu); ?></label>
                  <input type="text" name="guvenlikCevabi" class="form-control" required>
                </div>
                <div class="form-group">
                  <label>Yeni Şifre</label>
                  <input type="password" name="yeniSifre" class="form-control" required>
                </div>
                <div class="form-group">
                  <label>Yeni Şifre (tekrar)</label>
                  <input type="password" name="yeniSifreTekrar" class="form-control" required>
                </div>
                <button type="submit" name="adim2_sifirla" value="1" class="btn btn-primary">Şifreyi Sıfırla</button>
              </form>

            <?php elseif ($adim === 3): ?>
              <div class="success-message mb-3"><?php echo htmlspecialchars($basari); ?></div>
              <a href="post-job.php" class="btn btn-primary">Giriş Yap</a>
            <?php endif; ?>

          </div>
        </div>
      </div>
    </section>

    <footer class="site-footer">

      <a href="#top" class="smoothscroll scroll-top">
        <span class="icon-keyboard_arrow_up"></span>
      </a>

      <div class="container">
        <div class="row mb-5">
          <div class="col-6 col-md-3 mb-4 mb-md-0">
            <h3>Trend Meslek Alanları</h3>
            <ul class="list-unstyled">
              <li><a href="Muhendislik.php">Mühendislik</a></li>
              <li><a href="tip.php">Tıp</a></li>
              <li><a href="hukuk.php">Hukuk</a></li>
              <li><a href="egitim.php">Eğitim</a></li>
            </ul>
          </div>
          <div class="col-6 col-md-3 mb-4 mb-md-0">
            <h3>Şirket</h3>
            <ul class="list-unstyled">
              <li><a href="about.php">Hakkımızda</a></li>
              <li><a href="anket.php">Anket</a></li>
              <li><a href="mentor-listesi.php">Mentorlar</a></li>
              <li><a href="blog.php">Programlar</a></li>
            </ul>
          </div>
          <div class="col-6 col-md-3 mb-4 mb-md-0">
            <h3>Destek</h3>
            <ul class="list-unstyled">
              <li><a href="contact.php">Destek</a></li>
              <li><a href="gizlilik-politikasi.php">Privacy</a></li>
              <li><a href="hizmet-sartlari.php">Hizmet Şartları</a></li>
            </ul>
          </div>
          <div class="col-6 col-md-3 mb-4 mb-md-0">
            <h3>Bize Ulaşın</h3>
            <div class="footer-social">
              <a href="#"><span class="icon-facebook"></span></a>
              <a href="#"><span class="icon-twitter"></span></a>
              <a href="#"><span class="icon-instagram"></span></a>
              <a href="#"><span class="icon-linkedin"></span></a>
            </div>
          </div>
        </div>

        <div class="row text-center">
          <div class="col-12">
            <p class="copyright"><small>
              Telif hakkı &copy;<script>document.write(new Date().getFullYear());</script> Bütün hakkılar saklıdır
            </small></p>
          </div>
        </div>
      </div>
    </footer>

</div>

  <script src="js/jquery.min.js"></script>
  <script src="js/bootstrap.bundle.min.js"></script>
  <script src="js/isotope.pkgd.min.js"></script>
  <script src="js/stickyfill.min.js"></script>
  <script src="js/jquery.fancybox.min.js"></script>
  <script src="js/jquery.easing.1.3.js"></script>
  <script src="js/jquery.waypoints.min.js"></script>
  <script src="js/jquery.animateNumber.min.js"></script>
  <script src="js/owl.carousel.min.js"></script>
  <script src="js/quill.min.js"></script>
  <script src="js/bootstrap-select.min.js"></script>
  <script src="js/custom.js"></script>
  </body>
</html>

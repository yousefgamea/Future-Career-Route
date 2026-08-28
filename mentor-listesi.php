<?php
session_start();
include 'baglanti.php';

// Herkese açık mentor listesi - öğrenciler mentorları buradan keşfedip
// mentor-detay.php üzerinden profillerini görüntüleyip programlarına
// katılabilir. Sadece yönetici tarafından onaylanmış mentorlar listelenir.
// İsim/soyisim/ders adına göre arama yapılabilir (arama boşsa hepsi gelir).
$arama = trim($_GET['arama'] ?? '');
if ($arama !== '') {
    $mentorlarStmt = $conn->prepare("
        SELECT MentorID, Isim, Soyisim, DersAdi, FotoURL
        FROM mentor
        WHERE Onaylandi = 1
          AND (Isim LIKE ? OR Soyisim LIKE ? OR DersAdi LIKE ?)
        ORDER BY Isim
    ");
    $aramaLike = '%' . $arama . '%';
    $mentorlarStmt->bind_param("sss", $aramaLike, $aramaLike, $aramaLike);
    $mentorlarStmt->execute();
    $mentorlar = $mentorlarStmt->get_result();
} else {
    $mentorlar = $conn->query("SELECT MentorID, Isim, Soyisim, DersAdi, FotoURL FROM mentor WHERE Onaylandi = 1 ORDER BY Isim");
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
      .mentor-card {
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 30px;
        text-align: center;
      }
      .mentor-card img {
        width: 90px;
        height: 90px;
        object-fit: cover;
        border-radius: 50%;
        margin-bottom: 15px;
      }
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
              <li><a href="mentor-listesi.php" class="active">Mentorlar</a></li>
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
          <div class="right-cta-menu text-right d-flex aligin-items-center col-6">
            <div class="ml-auto d-none d-lg-block">
              <?php if (!empty($_SESSION['kullanici_id']) || !empty($_SESSION['mentor_id'])): ?>
              <a href="<?php echo !empty($_SESSION['mentor_id']) ? 'mentorun-profili.php' : 'kullanicinin-profili.php'; ?>" class="btn btn-outline-white border-width-2 d-none d-lg-inline-block"><span class="mr-2 icon-user"></span>Profilim</a>
              <a href="logout.php" class="btn btn-primary border-width-2 d-none d-lg-inline-block"><span class="mr-2 icon-lock_outline"></span>Çıkış Yap</a>
              <?php else: ?>
              <a href="post-job.php" class="btn btn-outline-white border-width-2 d-none d-lg-inline-block"><span class="mr-2 icon-lock_outline"></span>Giriş Yap</a>
              <a href="kayit-ol-secigi.php" class="btn btn-primary border-width-2 d-none d-lg-inline-block"><span class="mr-2 icon-add"></span>Kayıt Ol</a>
              <?php endif; ?>
            </div>
            <a href="#" class="site-menu-toggle js-menu-toggle d-inline-block d-xl-none mt-lg-2 ml-3" onclick="OnePageNavigation()"><span class="icon-menu h3 m-0 p-0 mt-2"></span></a>
          </div>
        </div>
      </div>
    </header>

    <!-- HOME -->
    <section class="section-hero overlay inner-page bg-image" style="background-image: url('images/hero_1.jpg');" id="home-section">
      <div class="container">
        <div class="row">
          <div class="col-md-7">
            <h1 class="text-white font-weight-bold">Mentorlarımız</h1>
            <div class="custom-breadcrumbs">
              <a href="index.php">Ana Sayfa</a> <span class="mx-2 slash">/</span>
              <span class="text-white"><strong>Mentorlar</strong></span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="site-section pb-0">
      <div class="container">
        <div class="row mb-4">
          <div class="col-lg-6">
            <form method="GET" action="mentor-listesi.php" class="form-inline">
              <input type="text" name="arama" class="form-control mr-2 mb-2 mb-md-0" style="flex: 1;" placeholder="İsim veya ders adına göre ara..." value="<?php echo htmlspecialchars($arama); ?>">
              <button type="submit" class="btn btn-primary mb-2 mb-md-0">Ara</button>
              <?php if ($arama !== ''): ?>
                <a href="mentor-listesi.php" class="btn btn-outline-secondary ml-2 mb-2 mb-md-0">Temizle</a>
              <?php endif; ?>
            </form>
          </div>
        </div>
      </div>
    </section>

    <section class="site-section pt-0">
      <div class="container">
        <div class="row">
          <?php if ($mentorlar && $mentorlar->num_rows > 0): ?>
            <?php while ($m = $mentorlar->fetch_assoc()): ?>
              <div class="col-md-6 col-lg-4">
                <div class="mentor-card">
                  <img src="<?php echo !empty($m['FotoURL']) ? htmlspecialchars($m['FotoURL']) : 'images/person_1.jpg'; ?>" alt="<?php echo htmlspecialchars($m['Isim']); ?>">
                  <h5><?php echo htmlspecialchars(trim($m['Isim'] . ' ' . $m['Soyisim'])); ?></h5>
                  <p><?php echo htmlspecialchars($m['DersAdi']); ?></p>
                  <a href="mentor-detay.php?id=<?php echo (int) $m['MentorID']; ?>" class="btn btn-primary btn-sm">Profili Gör</a>
                </div>
              </div>
            <?php endwhile; ?>
          <?php elseif ($arama !== ''): ?>
            <div class="col-12">
              <p>"<?php echo htmlspecialchars($arama); ?>" için sonuç bulunamadı.</p>
            </div>
          <?php else: ?>
            <div class="col-12">
              <p>Henüz kayıtlı mentor bulunmuyor.</p>
            </div>
          <?php endif; ?>
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

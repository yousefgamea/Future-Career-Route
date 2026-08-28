<?php
session_start();
include 'baglanti.php';

// Öğrencinin katıldığı (ya da daha önce katılmış olduğu) bir programın
// tam içeriğini gösterdiği sayfa. kullanicinin-profili.php'deki "Bağlı
// Olduğun Program" ve "Geçmiş Programların" kartlarından buraya linkleniyor.
if (empty($_SESSION['kullanici_id'])) {
    header("Location: post-job.php");
    exit();
}

$kullaniciID = $_SESSION['kullanici_id'];
$programID = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$stmt = $conn->prepare("
    SELECT p.ProgramID, p.ProgramAdi, p.Aciklama, p.Icerik, p.Kontenjan,
           m.MentorID, m.Isim, m.Soyisim, m.DersAdi,
           (SELECT COUNT(*) FROM kullanici k WHERE k.ProgramID = p.ProgramID) AS KatilimciSayisi
    FROM program p
    JOIN mentor m ON p.MentorID = m.MentorID
    WHERE p.ProgramID = ?
");
$stmt->bind_param("i", $programID);
$stmt->execute();
$program = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$program) {
    echo "Program bulunamadı.";
    exit();
}

// Erişim kontrolü: bu öğrenci ya şu an bu programa kayıtlı ya da geçmişte
// katılmış olmalı - başka bir öğrencinin programının içeriğini göremez.
$suAnKatilimciMi = false;
$suAnStmt = $conn->prepare("SELECT ProgramID FROM kullanici WHERE KullaniciID = ? AND ProgramID = ?");
$suAnStmt->bind_param("ii", $kullaniciID, $programID);
$suAnStmt->execute();
$suAnKatilimciMi = (bool) $suAnStmt->get_result()->fetch_assoc();
$suAnStmt->close();

$gecmisteKatilmisMi = false;
if (!$suAnKatilimciMi) {
    $gecmisStmt = $conn->prepare("SELECT ProgramID FROM gecmis WHERE KullaniciID = ? AND ProgramID = ? LIMIT 1");
    $gecmisStmt->bind_param("ii", $kullaniciID, $programID);
    $gecmisStmt->execute();
    $gecmisteKatilmisMi = (bool) $gecmisStmt->get_result()->fetch_assoc();
    $gecmisStmt->close();
}

if (!$suAnKatilimciMi && !$gecmisteKatilmisMi) {
    echo "Bu programın içeriğini görüntüleme yetkin yok.";
    exit();
}

// Mentorun bu programa eklediği ders metni / PDF / video materyalleri
$materyalStmt = $conn->prepare("SELECT Tur, Baslik, DersMetni, DosyaYolu, VideoYolu FROM program_materyal WHERE ProgramID = ? ORDER BY MateryalID DESC");
$materyalStmt->bind_param("i", $programID);
$materyalStmt->execute();
$materyaller = $materyalStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$materyalStmt->close();

$turIsimleri = ['ders' => 'Ders Metni', 'pdf' => 'PDF', 'video' => 'Video'];
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
      .profile-card { box-shadow: 0 4px 6px rgba(0,0,0,0.1); border-radius: 8px; padding: 20px; margin-bottom: 20px; }
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
              <li class="d-lg-none"><a href="kullanicinin-profili.php">Profilim</a></li>
              <li class="d-lg-none"><a href="logout.php">Çıkış Yap</a></li>
            </ul>
          </nav>
          <div class="right-cta-menu text-right d-flex aligin-items-center col-6">
            <div class="ml-auto d-none d-lg-block">
              <a href="kullanicinin-profili.php" class="btn btn-outline-white border-width-2 d-none d-lg-inline-block">Profilim</a>
              <a href="logout.php" class="btn btn-outline-white border-width-2 d-none d-lg-inline-block"><span class="mr-2 icon-lock_outline"></span>Çıkış Yap</a>
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
            <h1 class="text-white font-weight-bold"><?php echo htmlspecialchars($program['ProgramAdi'] ?: 'Program'); ?></h1>
            <div class="custom-breadcrumbs">
              <a href="kullanicinin-profili.php">Profilim</a> <span class="mx-2 slash">/</span>
              <span class="text-white"><strong>Program Detayı</strong></span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="site-section">
      <div class="container">

        <?php if (!$suAnKatilimciMi && $gecmisteKatilmisMi): ?>
          <div class="alert alert-secondary">Bu program artık aktif olarak takip ettiğin bir program değil - geçmişte katıldığın bir program olarak görüntülüyorsun.</div>
        <?php endif; ?>

        <div class="row">
          <div class="col-md-8 mb-4">
            <div class="profile-card">
              <h5>Program Hakkında</h5>
              <?php if (!empty($program['Aciklama'])): ?>
                <p><?php echo nl2br(htmlspecialchars($program['Aciklama'])); ?></p>
              <?php else: ?>
                <p><em>Bu program için henüz bir açıklama girilmemiş.</em></p>
              <?php endif; ?>
            </div>

            <div class="profile-card">
              <h5>Program İçeriği</h5>
              <?php if (!empty($program['Icerik'])): ?>
                <p><?php echo nl2br(htmlspecialchars($program['Icerik'])); ?></p>
              <?php else: ?>
                <p><em>Mentor henüz bir ders içeriği/müfredat eklememiş.</em></p>
              <?php endif; ?>
            </div>

            <div class="profile-card">
              <h5>Dersler, PDF'ler ve Videolar (<?php echo count($materyaller); ?>)</h5>
              <?php if (empty($materyaller)): ?>
                <p><em>Mentor henüz bir ders, PDF ya da video eklememiş.</em></p>
              <?php else: ?>
                <?php foreach ($materyaller as $mt): ?>
                  <div class="mb-3 pb-3" style="border-bottom: 1px solid #eee;">
                    <h6><span class="badge badge-secondary"><?php echo htmlspecialchars($turIsimleri[$mt['Tur']] ?? $mt['Tur']); ?></span> <?php echo htmlspecialchars($mt['Baslik']); ?></h6>
                    <?php if ($mt['Tur'] === 'ders' && !empty($mt['DersMetni'])): ?>
                      <p><?php echo nl2br(htmlspecialchars($mt['DersMetni'])); ?></p>
                    <?php elseif ($mt['Tur'] === 'pdf' && !empty($mt['DosyaYolu'])): ?>
                      <p><a href="<?php echo htmlspecialchars($mt['DosyaYolu']); ?>" target="_blank" rel="noopener">PDF'i Görüntüle/İndir</a></p>
                    <?php elseif ($mt['Tur'] === 'video' && !empty($mt['VideoYolu'])): ?>
                      <p><a href="<?php echo htmlspecialchars($mt['VideoYolu']); ?>" target="_blank" rel="noopener">Videoyu İzle</a></p>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>

          <div class="col-md-4 mb-4">
            <div class="profile-card">
              <h5>Mentor</h5>
              <p><strong><?php echo htmlspecialchars(trim($program['Isim'] . ' ' . $program['Soyisim'])); ?></strong></p>
              <p><?php echo htmlspecialchars($program['DersAdi']); ?></p>
              <a href="mentor-detay.php?id=<?php echo (int) $program['MentorID']; ?>" class="btn btn-outline-primary btn-sm">Mentor Profilini Gör</a>
            </div>

            <div class="profile-card">
              <h5>Katılım</h5>
              <?php if (!empty($program['Kontenjan'])): ?>
                <p><?php echo (int) $program['KatilimciSayisi']; ?> / <?php echo (int) $program['Kontenjan']; ?> öğrenci</p>
              <?php else: ?>
                <p><?php echo (int) $program['KatilimciSayisi']; ?> öğrenci</p>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <p><a href="kullanicinin-profili.php">&laquo; Profilime Dön</a></p>
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

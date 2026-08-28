<?php
session_start();
include 'baglanti.php';

if (empty($_SESSION['mentor_id'])) {
    header("Location: post-job.php");
    exit();
}

$mentorID = $_SESSION['mentor_id'];
$programID = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$hata = '';
$basari = '';

$stmt = $conn->prepare("SELECT ProgramID, ProgramAdi, Aciklama, Icerik, Kontenjan FROM program WHERE ProgramID = ? AND MentorID = ?");
$stmt->bind_param("ii", $programID, $mentorID);
$stmt->execute();
$program = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$program) {
    echo "Bu program bulunamadı ya da sana ait değil.";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $programAdi = trim($_POST['programAdi'] ?? '');
    $aciklama = trim($_POST['aciklama'] ?? '');
    $icerik = trim($_POST['icerik'] ?? '');
    $kontenjan = trim($_POST['kontenjan'] ?? '');
    $kontenjan = ($kontenjan === '') ? null : (int) $kontenjan;

    if (empty($programAdi) || empty($aciklama)) {
        $hata = "Lütfen program adını ve açıklamasını yaz.";
    } else {
        $guncelleStmt = $conn->prepare("UPDATE program SET ProgramAdi = ?, Aciklama = ?, Icerik = ?, Kontenjan = ? WHERE ProgramID = ? AND MentorID = ?");
        $guncelleStmt->bind_param("sssiii", $programAdi, $aciklama, $icerik, $kontenjan, $programID, $mentorID);
        if ($guncelleStmt->execute()) {
            $basari = "Program güncellendi.";
            $program['ProgramAdi'] = $programAdi;
            $program['Aciklama'] = $aciklama;
            $program['Icerik'] = $icerik;
            $program['Kontenjan'] = $kontenjan;
        } else {
            $hata = "Güncelleme sırasında hata oluştu: " . $guncelleStmt->error;
        }
        $guncelleStmt->close();
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
              <li class="d-lg-none"><a href="mentorun-profili.php">Profilim</a></li>
              <li class="d-lg-none"><a href="logout.php">Çıkış Yap</a></li>
            </ul>
          </nav>
          <div class="right-cta-menu text-right d-flex aligin-items-center col-6">
            <div class="ml-auto d-none d-lg-block">
              <a href="mentorun-profili.php" class="btn btn-outline-white border-width-2 d-none d-lg-inline-block">Profilim</a>
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
            <h1 class="text-white font-weight-bold">Programı Düzenle</h1>
            <div class="custom-breadcrumbs">
              <a href="program-olustur.php">Programlarım</a> <span class="mx-2 slash">/</span>
              <span class="text-white"><strong>Düzenle</strong></span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="site-section">
      <div class="container">
        <div class="row justify-content-center">
          <div class="col-md-7">

            <?php if ($hata): ?>
              <div class="alert alert-danger"><?php echo htmlspecialchars($hata); ?></div>
            <?php endif; ?>
            <?php if ($basari): ?>
              <div class="alert alert-success"><?php echo htmlspecialchars($basari); ?></div>
            <?php endif; ?>

            <form method="POST" class="p-4 border rounded">
              <div class="row form-group">
                <div class="col-md-12 mb-3 mb-md-0">
                  <label class="text-black required-field">Program Adı</label>
                  <input type="text" name="programAdi" class="form-control" value="<?php echo htmlspecialchars($program['ProgramAdi']); ?>" required>
                </div>
              </div>
              <div class="row form-group">
                <div class="col-md-12 mb-3 mb-md-0">
                  <label class="text-black required-field">Program Hakkında</label>
                  <textarea name="aciklama" class="form-control" rows="5" required><?php echo htmlspecialchars($program['Aciklama']); ?></textarea>
                </div>
              </div>
              <div class="row form-group">
                <div class="col-md-12 mb-3 mb-md-0">
                  <label class="text-black">Program İçeriği (isteğe bağlı)</label>
                  <textarea name="icerik" class="form-control" rows="10" placeholder="Bu programa katılan öğrencilerin göreceği ders içeriği, müfredat, haftalık plan, kaynaklar vb..."><?php echo htmlspecialchars($program['Icerik'] ?? ''); ?></textarea>
                  <small class="text-muted">Bu alan sadece programa katılan/katılmış öğrencilere gösterilir.</small>
                </div>
              </div>
              <div class="row form-group mb-4">
                <div class="col-md-12 mb-3 mb-md-0">
                  <label class="text-black">Kontenjan (isteğe bağlı)</label>
                  <input type="number" min="1" name="kontenjan" class="form-control" value="<?php echo htmlspecialchars($program['Kontenjan'] ?? ''); ?>">
                </div>
              </div>

              <button type="submit" class="btn px-4 btn-primary text-white">Güncelle</button>
              <a href="program-olustur.php" class="btn px-4 btn-outline-secondary">Programlarıma Dön</a>
            </form>

            <p class="mt-3">
              <a href="program-materyal-ekle.php?id=<?php echo (int) $program['ProgramID']; ?>">Ders, PDF ya da video ekle &raquo;</a>
            </p>
            <p>
              <a href="program-uyeler.php?id=<?php echo (int) $program['ProgramID']; ?>">Bu programa katılan öğrencileri gör &raquo;</a>
            </p>
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

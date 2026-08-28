<?php
session_start();
include 'baglanti.php';

if (empty($_SESSION['mentor_id'])) {
    header("Location: post-job.php");
    exit();
}

$mentorID = $_SESSION['mentor_id'];
$hata = '';
$basari = '';

// Yeni program ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['program_ekle'])) {
    $programAdi = trim($_POST['programAdi'] ?? '');
    $aciklama = trim($_POST['aciklama'] ?? '');
    $kontenjan = trim($_POST['kontenjan'] ?? '');
    $kontenjan = ($kontenjan === '') ? null : (int) $kontenjan;

    if (empty($programAdi) || empty($aciklama)) {
        $hata = "Lütfen program adını ve açıklamasını yaz.";
    } else {
        $yorumIdBos = null;
        $videoIdBos = null;
        $olusturStmt = $conn->prepare(
            "INSERT INTO program (MentorID, ProgramAdi, Aciklama, Kontenjan, YorumID, VideoID) VALUES (?, ?, ?, ?, ?, ?)"
        );
        $olusturStmt->bind_param("issiii", $mentorID, $programAdi, $aciklama, $kontenjan, $yorumIdBos, $videoIdBos);
        if ($olusturStmt->execute()) {
            $basari = "\"$programAdi\" programın oluşturuldu! Artık öğrenciler mentor listesinden seni bulup bu programa katılabilir.";
        } else {
            $hata = "Program oluşturulamadı: " . $olusturStmt->error;
        }
        $olusturStmt->close();
    }
}

// Program silme (sadece kendi programını silebilir)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['program_sil'])) {
    $silinecekID = (int) $_POST['program_sil'];

    // Sahiplik kontrolü
    $sahipStmt = $conn->prepare("SELECT ProgramID FROM program WHERE ProgramID = ? AND MentorID = ?");
    $sahipStmt->bind_param("ii", $silinecekID, $mentorID);
    $sahipStmt->execute();
    $sahipVarMi = $sahipStmt->get_result()->fetch_assoc();
    $sahipStmt->close();

    if ($sahipVarMi) {
        // Bu programa kayıtlı öğrencileri programsız bırak, geçmiş
        // kayıtlarını temizle, sonra programı sil (yabancı anahtar
        // hatası almamak için önce bağlı kayıtları temizliyoruz).
        $bosaltStmt = $conn->prepare("UPDATE kullanici SET ProgramID = NULL WHERE ProgramID = ?");
        $bosaltStmt->bind_param("i", $silinecekID);
        $bosaltStmt->execute();
        $bosaltStmt->close();

        $gecmisTemizleStmt = $conn->prepare("DELETE FROM gecmis WHERE ProgramID = ?");
        $gecmisTemizleStmt->bind_param("i", $silinecekID);
        $gecmisTemizleStmt->execute();
        $gecmisTemizleStmt->close();

        $mentorBosaltStmt = $conn->prepare("UPDATE mentor SET ProgramID = NULL WHERE ProgramID = ?");
        $mentorBosaltStmt->bind_param("i", $silinecekID);
        $mentorBosaltStmt->execute();
        $mentorBosaltStmt->close();

        $silStmt = $conn->prepare("DELETE FROM program WHERE ProgramID = ? AND MentorID = ?");
        $silStmt->bind_param("ii", $silinecekID, $mentorID);
        if ($silStmt->execute()) {
            $basari = "Program silindi.";
        } else {
            $hata = "Program silinirken hata oluştu: " . $silStmt->error;
        }
        $silStmt->close();
    } else {
        $hata = "Bu program sana ait değil.";
    }
}

// Mentorun tüm programlarını (ve her birine katılan öğrenci sayısını) çek
$programlar = [];
$listeStmt = $conn->prepare("
    SELECT p.ProgramID, p.ProgramAdi, p.Aciklama, p.Kontenjan,
           (SELECT COUNT(*) FROM kullanici k WHERE k.ProgramID = p.ProgramID) AS KatilimciSayisi
    FROM program p
    WHERE p.MentorID = ?
    ORDER BY p.ProgramID DESC
");
$listeStmt->bind_param("i", $mentorID);
$listeStmt->execute();
$programlar = $listeStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$listeStmt->close();
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
            <h1 class="text-white font-weight-bold">Programlarım</h1>
            <div class="custom-breadcrumbs">
              <a href="mentorun-profili.php">Profilim</a> <span class="mx-2 slash">/</span>
              <span class="text-white"><strong>Programlarım</strong></span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="site-section">
      <div class="container">

        <?php if ($hata): ?>
          <div class="alert alert-danger"><?php echo htmlspecialchars($hata); ?></div>
        <?php endif; ?>
        <?php if ($basari): ?>
          <div class="alert alert-success"><?php echo htmlspecialchars($basari); ?></div>
        <?php endif; ?>

        <div class="row">
          <div class="col-lg-12 mb-4">
            <h4>Açtığın Programlar</h4>
            <?php if (empty($programlar)): ?>
              <p>Henüz bir program açmadın. Aşağıdaki formla ilk programını oluşturabilirsin.</p>
            <?php else: ?>
              <?php foreach ($programlar as $p): ?>
                <div class="profile-card">
                  <div class="d-flex justify-content-between align-items-start flex-wrap">
                    <h5><?php echo htmlspecialchars($p['ProgramAdi']); ?></h5>
                    <div>
                      <a href="program-duzenle.php?id=<?php echo (int) $p['ProgramID']; ?>" class="btn btn-sm btn-outline-primary">Düzenle</a>
                      <a href="program-materyal-ekle.php?id=<?php echo (int) $p['ProgramID']; ?>" class="btn btn-sm btn-outline-success">Ders/PDF/Video Ekle</a>
                      <a href="program-uyeler.php?id=<?php echo (int) $p['ProgramID']; ?>" class="btn btn-sm btn-outline-secondary">Üyeleri Gör (<?php echo (int) $p['KatilimciSayisi']; ?>)</a>
                      <form method="POST" style="display:inline;" onsubmit="return confirm('Bu programı silmek istediğine emin misin? Katılan öğrenciler programsız kalacak.');">
                        <button type="submit" name="program_sil" value="<?php echo (int) $p['ProgramID']; ?>" class="btn btn-sm btn-outline-danger">Sil</button>
                      </form>
                    </div>
                  </div>
                  <p><?php echo nl2br(htmlspecialchars($p['Aciklama'])); ?></p>
                  <?php if (!empty($p['Kontenjan'])): ?>
                    <p><small><?php echo (int) $p['KatilimciSayisi']; ?> / <?php echo (int) $p['Kontenjan']; ?> öğrenci katıldı</small></p>
                  <?php else: ?>
                    <p><small><?php echo (int) $p['KatilimciSayisi']; ?> öğrenci katıldı</small></p>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>

        <div class="row">
          <div class="col-md-7">
            <h4>Yeni Program Ekle</h4>
            <p>
              Öğrenciler mentor listesinde seni bulduğunda burada gireceğin bilgileri görecek. Programını
              açık ve net anlat: hangi konuda, ne şekilde ve kaç öğrenciye mentorluk yapabileceğini yaz.
            </p>
            <form method="POST" class="p-4 border rounded">
              <div class="row form-group">
                <div class="col-md-12 mb-3 mb-md-0">
                  <label class="text-black required-field">Program Adı</label>
                  <input type="text" name="programAdi" class="form-control" placeholder="Örn: Bilgisayar Mühendisliğine Giriş Mentorluk Programı" required>
                </div>
              </div>
              <div class="row form-group">
                <div class="col-md-12 mb-3 mb-md-0">
                  <label class="text-black required-field">Program Hakkında</label>
                  <textarea name="aciklama" class="form-control" rows="5" placeholder="Bu programda öğrencilere neler sunuyorsun? Hangi konularda destek veriyorsun, ne sıklıkla görüşüyorsunuz?" required></textarea>
                </div>
              </div>
              <div class="row form-group mb-4">
                <div class="col-md-12 mb-3 mb-md-0">
                  <label class="text-black">Kontenjan (isteğe bağlı)</label>
                  <input type="number" min="1" name="kontenjan" class="form-control" placeholder="Örn: 10">
                </div>
              </div>

              <button type="submit" name="program_ekle" value="1" class="btn px-4 btn-primary text-white">Programı Oluştur</button>
              <a href="mentorun-profili.php" class="btn px-4 btn-outline-secondary">Profilime Dön</a>
            </form>
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

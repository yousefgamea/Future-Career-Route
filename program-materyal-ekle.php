<?php
session_start();
include 'baglanti.php';

// Mentorun bir programın içine ders metni, PDF dosyası veya video
// ekleyip/silebildiği sayfa. Öğrenciler bunları program-detay.php'de görür.
if (empty($_SESSION['mentor_id'])) {
    header("Location: post-job.php");
    exit();
}

$mentorID = $_SESSION['mentor_id'];
$programID = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$hata = '';
$basari = '';

$izinliUzantilarPDF = ['pdf'];
$izinliUzantilarVideo = ['mp4', 'avi', 'mov'];

$stmt = $conn->prepare("SELECT ProgramID, ProgramAdi FROM program WHERE ProgramID = ? AND MentorID = ?");
$stmt->bind_param("ii", $programID, $mentorID);
$stmt->execute();
$program = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$program) {
    echo "Bu program bulunamadı ya da sana ait değil.";
    exit();
}

// Materyal ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['materyal_ekle'])) {
    $tur = $_POST['tur'] ?? '';
    $baslik = trim($_POST['baslik'] ?? '');

    if (empty($baslik)) {
        $hata = "Lütfen materyal için bir başlık yaz.";
    } elseif (!in_array($tur, ['ders', 'pdf', 'video'], true)) {
        $hata = "Geçersiz materyal türü.";
    } else {
        $dersMetni = null;
        $dosyaYolu = null;
        $videoYolu = null;

        if ($tur === 'ders') {
            $dersMetni = trim($_POST['ders_metni'] ?? '');
            if (empty($dersMetni)) {
                $hata = "Lütfen ders metnini yaz.";
            }
        } elseif ($tur === 'pdf') {
            if (empty($_FILES['pdf_dosya']['name'])) {
                $hata = "Lütfen bir PDF dosyası seç.";
            } else {
                $uzanti = strtolower(pathinfo($_FILES['pdf_dosya']['name'], PATHINFO_EXTENSION));
                if (!in_array($uzanti, $izinliUzantilarPDF)) {
                    $hata = "Sadece PDF dosyası yükleyebilirsin.";
                } else {
                    $dosyaYolu = "uploads/" . uniqid('materyal_') . "_" . basename($_FILES['pdf_dosya']['name']);
                    if (!move_uploaded_file($_FILES['pdf_dosya']['tmp_name'], $dosyaYolu)) {
                        $hata = "PDF yüklenirken bir hata oluştu.";
                    }
                }
            }
        } elseif ($tur === 'video') {
            $videoLink = trim($_POST['video_link'] ?? '');
            if (!empty($_FILES['video_dosya']['name'])) {
                $uzanti = strtolower(pathinfo($_FILES['video_dosya']['name'], PATHINFO_EXTENSION));
                if (!in_array($uzanti, $izinliUzantilarVideo)) {
                    $hata = "Video için sadece mp4, avi ya da mov yükleyebilirsin.";
                } else {
                    $videoYolu = "uploads/" . uniqid('materyal_') . "_" . basename($_FILES['video_dosya']['name']);
                    if (!move_uploaded_file($_FILES['video_dosya']['tmp_name'], $videoYolu)) {
                        $hata = "Video yüklenirken bir hata oluştu.";
                    }
                }
            } elseif (!empty($videoLink)) {
                $videoYolu = $videoLink;
            } else {
                $hata = "Lütfen bir video dosyası yükle ya da bir video linki (örn. YouTube) yaz.";
            }
        }

        if (empty($hata)) {
            $ekleStmt = $conn->prepare(
                "INSERT INTO program_materyal (ProgramID, Tur, Baslik, DersMetni, DosyaYolu, VideoYolu) VALUES (?, ?, ?, ?, ?, ?)"
            );
            $ekleStmt->bind_param("isssss", $programID, $tur, $baslik, $dersMetni, $dosyaYolu, $videoYolu);
            if ($ekleStmt->execute()) {
                $basari = "Materyal eklendi.";
            } else {
                $hata = "Materyal eklenirken bir hata oluştu: " . $ekleStmt->error;
            }
            $ekleStmt->close();
        }
    }
}

// Materyal silme (sahiplik kontrolü ProgramID üzerinden yapılıyor)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['materyal_sil'])) {
    $silinecekID = (int) $_POST['materyal_sil'];
    $silStmt = $conn->prepare("
        DELETE m FROM program_materyal m
        JOIN program p ON m.ProgramID = p.ProgramID
        WHERE m.MateryalID = ? AND p.ProgramID = ? AND p.MentorID = ?
    ");
    $silStmt->bind_param("iii", $silinecekID, $programID, $mentorID);
    if ($silStmt->execute() && $silStmt->affected_rows > 0) {
        $basari = "Materyal silindi.";
    } else {
        $hata = "Materyal silinemedi ya da sana ait değil.";
    }
    $silStmt->close();
}

// Bu programa ait tüm materyalleri çek
$materyalStmt = $conn->prepare("SELECT MateryalID, Tur, Baslik, DersMetni, DosyaYolu, VideoYolu FROM program_materyal WHERE ProgramID = ? ORDER BY MateryalID DESC");
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
      .materyal-tur-secim { display: none; }
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
            <h1 class="text-white font-weight-bold"><?php echo htmlspecialchars($program['ProgramAdi']); ?></h1>
            <div class="custom-breadcrumbs">
              <a href="program-olustur.php">Programlarım</a> <span class="mx-2 slash">/</span>
              <span class="text-white"><strong>Materyaller</strong></span>
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
            <h4>Eklenen Materyaller (<?php echo count($materyaller); ?>)</h4>
            <?php if (empty($materyaller)): ?>
              <p>Bu programa henüz bir ders, PDF ya da video eklenmemiş.</p>
            <?php else: ?>
              <?php foreach ($materyaller as $mt): ?>
                <div class="profile-card">
                  <div class="d-flex justify-content-between align-items-start flex-wrap">
                    <h5><span class="badge badge-secondary"><?php echo htmlspecialchars($turIsimleri[$mt['Tur']] ?? $mt['Tur']); ?></span> <?php echo htmlspecialchars($mt['Baslik']); ?></h5>
                    <form method="POST" onsubmit="return confirm('Bu materyali silmek istediğine emin misin?');">
                      <button type="submit" name="materyal_sil" value="<?php echo (int) $mt['MateryalID']; ?>" class="btn btn-sm btn-outline-danger">Sil</button>
                    </form>
                  </div>
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

        <div class="row">
          <div class="col-md-8">
            <h4>Yeni Materyal Ekle</h4>
            <form method="POST" class="p-4 border rounded" enctype="multipart/form-data" id="materyalForm">
              <div class="row form-group">
                <div class="col-md-12 mb-3 mb-md-0">
                  <label class="text-black required-field">Materyal Türü</label>
                  <select name="tur" id="turSecim" class="form-control" required>
                    <option value="ders">Ders Metni</option>
                    <option value="pdf">PDF Dosyası</option>
                    <option value="video">Video</option>
                  </select>
                </div>
              </div>
              <div class="row form-group">
                <div class="col-md-12 mb-3 mb-md-0">
                  <label class="text-black required-field">Başlık</label>
                  <input type="text" name="baslik" class="form-control" placeholder="Örn: 1. Hafta - Giriş" required>
                </div>
              </div>

              <div class="row form-group materyal-tur-secim" data-tur="ders">
                <div class="col-md-12 mb-3 mb-md-0">
                  <label class="text-black">Ders Metni</label>
                  <textarea name="ders_metni" class="form-control" rows="8" placeholder="Ders içeriğini buraya yaz..."></textarea>
                </div>
              </div>

              <div class="row form-group materyal-tur-secim" data-tur="pdf">
                <div class="col-md-12 mb-3 mb-md-0">
                  <label class="text-black">PDF Dosyası</label>
                  <input type="file" name="pdf_dosya" class="form-control" accept=".pdf">
                </div>
              </div>

              <div class="row form-group materyal-tur-secim" data-tur="video">
                <div class="col-md-12 mb-3 mb-md-0">
                  <label class="text-black">Video Dosyası</label>
                  <input type="file" name="video_dosya" class="form-control" accept=".mp4,.avi,.mov">
                  <small class="text-muted">veya</small>
                  <label class="text-black mt-2">Video Linki (örn. YouTube)</label>
                  <input type="text" name="video_link" class="form-control" placeholder="https://...">
                </div>
              </div>

              <button type="submit" name="materyal_ekle" value="1" class="btn px-4 btn-primary text-white mt-3">Materyali Ekle</button>
              <a href="program-duzenle.php?id=<?php echo (int) $program['ProgramID']; ?>" class="btn px-4 btn-outline-secondary mt-3">Programa Dön</a>
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
  <script>
    function turAlanlariniGuncelle() {
      var secilen = document.getElementById('turSecim').value;
      document.querySelectorAll('.materyal-tur-secim').forEach(function (el) {
        el.style.display = (el.getAttribute('data-tur') === secilen) ? 'flex' : 'none';
      });
    }
    document.getElementById('turSecim').addEventListener('change', turAlanlariniGuncelle);
    turAlanlariniGuncelle();
  </script>
  </body>
</html>

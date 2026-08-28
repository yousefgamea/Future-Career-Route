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
$allowedImageExtensions = ['jpg', 'jpeg', 'png'];
$allowedCVExtensions = ['pdf', 'doc', 'docx'];

$stmt = $conn->prepare("SELECT Isim, Soyisim, Email, Telefon, DogumTarihi, DersAdi, FotoURL FROM Mentor WHERE MentorID = ?");
$stmt->bind_param("i", $mentorID);
$stmt->execute();
$mentor = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$mentor) {
    echo "Mentor bilgileri alınamadı.";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isim = trim($_POST['isim']);
    $soyisim = trim($_POST['soyisim']);
    $email = trim($_POST['email']);
    $telefon = trim($_POST['telefon']);
    $dogumTarihi = $_POST['dogumTarihi'];
    $dersAdi = trim($_POST['dersAdi']);

    if (empty($isim) || empty($soyisim) || empty($email) || empty($telefon) || empty($dogumTarihi) || empty($dersAdi)) {
        $hata = "Lütfen tüm zorunlu alanları doldurun.";
    } else {
        // Fotoğraf isteğe bağlı olarak değiştirilebilir
        $fotoURL = $mentor['FotoURL'];
        if (!empty($_FILES['foto']['name'])) {
            $imageFileType = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            if (!in_array($imageFileType, $allowedImageExtensions)) {
                $hata = "Fotoğraf için sadece JPG, JPEG ve PNG dosyaları yüklenebilir.";
            } else {
                $fotoURL = "uploads/" . basename($_FILES['foto']['name']);
                move_uploaded_file($_FILES['foto']['tmp_name'], $fotoURL);
            }
        }

        // CV isteğe bağlı olarak değiştirilebilir (LONGBLOB olarak saklanıyor)
        $cvData = null;
        $cvGuncellensin = false;
        if (empty($hata) && !empty($_FILES['cv']['name'])) {
            $cvFileType = strtolower(pathinfo($_FILES['cv']['name'], PATHINFO_EXTENSION));
            if (!in_array($cvFileType, $allowedCVExtensions)) {
                $hata = "CV için sadece PDF, DOC ve DOCX dosyaları yüklenebilir.";
            } elseif (is_uploaded_file($_FILES['cv']['tmp_name'])) {
                $cvData = file_get_contents($_FILES['cv']['tmp_name']);
                $cvGuncellensin = true;
            }
        }

        if (empty($hata)) {
            if ($cvGuncellensin) {
                $stmt = $conn->prepare(
                    "UPDATE Mentor SET Isim = ?, Soyisim = ?, Email = ?, Telefon = ?, DogumTarihi = ?, DersAdi = ?, FotoURL = ?, CV = ? WHERE MentorID = ?"
                );
                $stmt->bind_param("ssssssssi", $isim, $soyisim, $email, $telefon, $dogumTarihi, $dersAdi, $fotoURL, $cvData, $mentorID);
            } else {
                $stmt = $conn->prepare(
                    "UPDATE Mentor SET Isim = ?, Soyisim = ?, Email = ?, Telefon = ?, DogumTarihi = ?, DersAdi = ?, FotoURL = ? WHERE MentorID = ?"
                );
                $stmt->bind_param("sssssssi", $isim, $soyisim, $email, $telefon, $dogumTarihi, $dersAdi, $fotoURL, $mentorID);
            }

            if ($stmt->execute()) {
                $basari = "Bilgileriniz başarıyla güncellendi.";
                $mentor['Isim'] = $isim;
                $mentor['Soyisim'] = $soyisim;
                $mentor['Email'] = $email;
                $mentor['Telefon'] = $telefon;
                $mentor['DogumTarihi'] = $dogumTarihi;
                $mentor['DersAdi'] = $dersAdi;
                $mentor['FotoURL'] = $fotoURL;
            } else {
                $hata = "Güncelleme sırasında bir hata oluştu: " . $stmt->error;
            }
            $stmt->close();
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
            <h1 class="text-white font-weight-bold">Bilgilerimi Düzenle</h1>
            <div class="custom-breadcrumbs">
              <a href="mentorun-profili.php">Profilim</a> <span class="mx-2 slash">/</span>
              <span class="text-white"><strong>Bilgilerimi Düzenle</strong></span>
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

            <form method="POST" enctype="multipart/form-data" class="p-4 border rounded">
              <div class="row form-group">
                <div class="col-md-12 mb-3 mb-md-0">
                  <label class="text-black">Ad</label>
                  <input type="text" name="isim" class="form-control" value="<?php echo htmlspecialchars($mentor['Isim']); ?>" required>
                </div>
              </div>
              <div class="row form-group">
                <div class="col-md-12 mb-3 mb-md-0">
                  <label class="text-black">Soyad</label>
                  <input type="text" name="soyisim" class="form-control" value="<?php echo htmlspecialchars($mentor['Soyisim']); ?>" required>
                </div>
              </div>
              <div class="row form-group">
                <div class="col-md-12 mb-3 mb-md-0">
                  <label class="text-black">Email</label>
                  <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($mentor['Email']); ?>" required>
                </div>
              </div>
              <div class="row form-group">
                <div class="col-md-12 mb-3 mb-md-0">
                  <label class="text-black">Telefon numarası</label>
                  <input type="text" name="telefon" class="form-control" value="<?php echo htmlspecialchars($mentor['Telefon']); ?>" required>
                </div>
              </div>
              <div class="row form-group">
                <div class="col-md-12 mb-3 mb-md-0">
                  <label class="text-black">Doğum günü</label>
                  <input type="date" name="dogumTarihi" class="form-control" value="<?php echo htmlspecialchars($mentor['DogumTarihi']); ?>" required>
                </div>
              </div>
              <div class="row form-group mb-4">
                <div class="col-md-12 mb-3 mb-md-0">
                  <label class="text-black">Ders/Uzmanlık Alanı</label>
                  <input type="text" name="dersAdi" class="form-control" value="<?php echo htmlspecialchars($mentor['DersAdi']); ?>" required>
                </div>
              </div>
              <div class="row form-group">
                <div class="col-md-12 mb-3 mb-md-0">
                  <label class="text-black">Fotoğraf değiştir (isteğe bağlı)</label>
                  <input type="file" name="foto" class="form-control">
                </div>
              </div>
              <div class="row form-group mb-4">
                <div class="col-md-12 mb-3 mb-md-0">
                  <label class="text-black">CV değiştir (isteğe bağlı)</label>
                  <input type="file" name="cv" class="form-control">
                </div>
              </div>

              <button type="submit" class="btn px-4 btn-primary text-white">Kaydet</button>
              <a href="mentorun-profili.php" class="btn px-4 btn-outline-secondary">İptal</a>
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

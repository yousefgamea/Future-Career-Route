<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'baglanti.php'; // Veritabanı bağlantısı

$loginHata = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $sifre = $_POST['sifre'];

    // Kullanıcıyı dinamik olarak kontrol etmek için UNION sorgusu kullanıyoruz
    // (Isim ve Soyisim tablolarda ayrı sütunlar, burada birleştiriyoruz).
    // Yönetici tablosunda isim sütunları "Ad"/"Soyad" ve email sütunu "Mail" -
    // aynı normal giriş sayfasından yönetici de giriş yapabilsin diye üçüncü
    // bir UNION dalı olarak ekliyoruz.
    // Mentor'un Onaylandi durumunu da çekiyoruz (öğrenci/yöneticide böyle bir
    // sütun olmadığı için UNION'ın sütun sayısı eşleşsin diye sabit 1 veriyoruz).
    $sql = "
        SELECT 'Mentor' AS KullaniciTuru, MentorID AS ID, CONCAT(Isim, ' ', Soyisim) AS IsimSoyisim, Sifre, Onaylandi
        FROM Mentor WHERE Email = ?
        UNION
        SELECT 'Kullanici' AS KullaniciTuru, KullaniciID AS ID, CONCAT(Isim, ' ', Soyisim) AS IsimSoyisim, Sifre, 1 AS Onaylandi
        FROM Kullanici WHERE Email = ?
        UNION
        SELECT 'Yonetici' AS KullaniciTuru, YoneticiID AS ID, CONCAT(Ad, ' ', Soyad) AS IsimSoyisim, Sifre, 1 AS Onaylandi
        FROM yonetici WHERE Mail = ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $email, $email, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    // Kullanıcı bulunduysa
    if ($result->num_rows == 1) {
        $kullanici = $result->fetch_assoc();
        if (password_verify($sifre, $kullanici['Sifre']) && $kullanici['KullaniciTuru'] == 'Mentor' && (int) $kullanici['Onaylandi'] === 0) {
            $loginHata = "Hesabın henüz yönetici onayı bekliyor. Onaylandığında giriş yapabileceksin.";
        } elseif (password_verify($sifre, $kullanici['Sifre'])) {
            // Oturum değişkenlerini ayarla
            $_SESSION['ID'] = $kullanici['ID'];
            $_SESSION['IsimSoyisim'] = $kullanici['IsimSoyisim'];

            // giris tablosuna her başarılı girişte kayıt/güncelleme yap.
            // Email PRIMARY KEY olduğu için bu tablo kullanıcı başına tek
            // satır tutuyor (bir log tablosu değil) - o yüzden aynı email
            // tekrar giriş yaptığında satırı güncelliyoruz, çoğaltmıyoruz.
            $girisStmt = $conn->prepare(
                "INSERT INTO giris (Email, Sifre) VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE Sifre = VALUES(Sifre)"
            );
            $girisStmt->bind_param("ss", $email, $kullanici['Sifre']);
            $girisStmt->execute();
            $girisStmt->close();

            // Kullanıcı türüne göre yönlendirme
            if ($kullanici['KullaniciTuru'] == 'Mentor') {
                $_SESSION['mentor_id'] = $kullanici['ID'];
                header("Location: mentorun-profili.php");
                exit();
            } elseif ($kullanici['KullaniciTuru'] == 'Kullanici') {
                // Düzeltme: burada yanlışlıkla tanımsız $mentor_id değişkeni
                // kullanılıyordu; kullanicinin-profili.php doğrudan
                // $_SESSION['kullanici_id'] bekliyor, o yüzden doğru anahtar bu.
                $_SESSION['kullanici_id'] = $kullanici['ID'];
                header("Location: kullanicinin-profili.php");
                exit();
            } elseif ($kullanici['KullaniciTuru'] == 'Yonetici') {
                $_SESSION['yonetici_id'] = $kullanici['ID'];
                $_SESSION['yonetici_ad'] = $kullanici['IsimSoyisim'];
                header("Location: yonetici-panel.php");
                exit();
            }
        } else {
            $loginHata = "Email veya şifre hatalı.";
        }
    } else {
        $loginHata = "Email veya şifre hatalı.";
    }

    $stmt->close();
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
      .col-lg-6{
        text-align: left;
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
              <li><a href="index.php" class="nav-link active">Ana Sayfa</a></li>
              <li><a href="anket.php">Anket</a></li>
              <li><a href="mentor-listesi.php">Mentorlar</a></li>
              <li><a href="about.php">Hakkımızda</a></li>
              <!-- <li class="has-children">
                <a href="job-listings.php" class="active">Job Listings</a>
                <ul class="dropdown">
                  <li><a href="job-single.php">Job Single</a></li>
                  <li><a href="post-job.php" class="active">Post a Job</a></li>
                </ul> 
              </li> -->
              <!-- <li class="has-children">
                <a href="services.php">Pages</a>
                <ul class="dropdown">
                  <li><a href="services.php">Services</a></li>
                  <li><a href="service-single.php">Service Single</a></li>
                  <li><a href="blog-single.php">Blog Single</a></li>
                  <li><a href="portfolio.php">Portfolio</a></li>
                  <li><a href="portfolio-single.php">Portfolio Single</a></li>
                  <li><a href="testimonials.php">Testimonials</a></li>
                  <li><a href="faq.php">Frequently Ask Questions</a></li>
                  <li><a href="gallery.php">Gallery</a></li>
                </ul>
              </li> -->
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
            <div class="ml-auto">
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
            <h1 class="text-white font-weight-bold">Giriş Yap</h1>
            <div class="custom-breadcrumbs">
              <a href="#">Ana Sayfa</a> <span class="mx-2 slash">/</span>
              <span class="text-white"><strong>Giriş Yap</strong></span>
            </div>
          </div>
        </div>
      </div>
    </section>

    
    <section class="site-section">
      <div class="container">

        <!-- <div class="row align-items-center mb-5">
          <div class="col-lg-8 mb-4 mb-lg-0">
            <div class="d-flex align-items-center">
              <div>
                <h2>Giriş Yap</h2>
              </div>
            </div>
          </div> -->
          <!-- <div class="col-lg-4">
            <div class="row">
              <div class="col-6">
                <a href="#" class="btn btn-block btn-light btn-md"><span class="icon-open_in_new mr-2"></span>Preview</a>
              </div>
              <div class="col-6">
                <a href="#" class="btn btn-block btn-primary btn-md">Save Job</a>
              </div>
            </div>
          </div> -->
        </div>
        <center>
<div class="col-lg-6">
    <h2 class="mb-4"><center>Giriş Yap</center></h2>
    <?php if (!empty($loginHata)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($loginHata); ?></div>
    <?php endif; ?>
    <!-- Tek Form -->
    <form method="POST" action="post-job.php" class="p-4 border rounded">
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" class="form-control" placeholder="Email adresinizi giriniz" required>
        </div>
        <div class="form-group">
            <label for="sifre">Şifre</label>
            <input type="password" id="sifre" name="sifre" class="form-control" placeholder="Şifrenizi giriniz" required>
        </div>
        <button type="submit" class="btn px-4 btn-primary text-white">Giriş Yap</button>
    </form>
    <p class="mt-3"><a href="sifremi-unuttum.php">Şifremi Unuttum</a></p>
</div>
</center>

        <!-- <div class="row align-items-center mb-5">
          
          <div class="col-lg-4 ml-auto">
            <div class="row">
              <div class="col-6">
                <a href="#" class="btn btn-block btn-light btn-md"><span class="icon-open_in_new mr-2"></span>Preview</a>
              </div>
              <div class="col-6">
                <a href="#" class="btn btn-block btn-primary btn-md">Save Job</a>
              </div>
            </div>
          </div>
        </div> -->
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
            <h3>Bizimle Ulaş</h3>
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
              <!-- Link back to Colorlib can't be removed. Template is licensed under CC BY 3.0. -->
              Telif hakkı &copy;<script>document.write(new Date().getFullYear());</script> Bütün hakkılar saklıdır <a href="https://colorlib.com" target="_blank" ></a>
            <!-- Link back to Colorlib can't be removed. Template is licensed under CC BY 3.0. --></small></p>
          </div>
        </div>
      </div>
    </footer>
  
  </div>

    <!-- SCRIPTS -->
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
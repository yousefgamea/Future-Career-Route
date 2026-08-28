<?php
session_start();
include("baglanti.php"); // Veritabanı bağlantısını dahil et

$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Formdan gelen verileri al
    $isimSoyisim = trim($_POST['isimSoyisim']);
    $email = trim($_POST['email']);
    $telefon = trim($_POST['telefon']);
    $dogumTarihi = $_POST['dogumTarihi'];
    $sinif = $_POST['sinif'];
    $alan = $_POST['alan'];
    $sifre = $_POST['sifre'];
    $sifre_tekrar = $_POST['sifre_tekrar'];
    $guvenlikSorusu = trim($_POST['guvenlikSorusu'] ?? '');
    $guvenlikCevabi = trim($_POST['guvenlikCevabi'] ?? '');

    if (empty($isimSoyisim) || empty($email) || empty($telefon) || empty($dogumTarihi)) {
        $errors['genel'] = "Lütfen tüm zorunlu alanları doldurun.";
    } elseif (empty($guvenlikSorusu) || empty($guvenlikCevabi)) {
        $errors['genel'] = "Şifremi unuttum akışı için güvenlik sorusu ve cevabı zorunludur.";
    } elseif (strlen($sifre) < 6) {
        $errors['sifre'] = "Şifre en az 6 karakter olmalıdır.";
    } elseif ($sifre !== $sifre_tekrar) {
        $errors['sifre_tekrar'] = "Şifreler aynı değil.";
    }

    if (empty($errors)) {
        // Şifreyi hashle
        $sifre_hash = password_hash($sifre, PASSWORD_DEFAULT);

        // Güvenlik sorusu cevabını da şifre gibi hash'liyoruz (küçük harfe
        // çevirip boşlukları kırparak - kullanıcı "İstanbul" ya da
        // "istanbul " yazsa bile şifremi unuttum akışında eşleşsin diye).
        $guvenlikCevabiHash = password_hash(mb_strtolower($guvenlikCevabi, 'UTF-8'), PASSWORD_DEFAULT);

        // Veritabanındaki Kullanici tablosunda isim ve soyisim ayrı sütunlar
        // (Isim, Soyisim) - formdaki tek "Ad - Soyad" alanını ilk boşluktan
        // ikiye bölüyoruz.
        $adParcalari = explode(' ', $isimSoyisim, 2);
        $isim = $adParcalari[0];
        $soyisim = $adParcalari[1] ?? '';

        // SQL sorgusu (prepared statement ile - SQL injection'a karşı güvenli)
        $sql = "INSERT INTO Kullanici (Isim, Soyisim, Email, TelefonNumarasi, DogumTarihi, Sinif, Alan, Sifre, GuvenlikSorusu, GuvenlikCevabi)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssssss", $isim, $soyisim, $email, $telefon, $dogumTarihi, $sinif, $alan, $sifre_hash, $guvenlikSorusu, $guvenlikCevabiHash);

        if ($stmt->execute()) {
            $_SESSION['isimSoyisim'] = $isimSoyisim;
            $_SESSION['email'] = $email;
            $_SESSION['telefon'] = $telefon;
            $_SESSION['kullanici_id'] = $conn->insert_id;

            $stmt->close();
            $conn->close();

            header("Location: kullanicinin-profili.php");
            exit();
        } else {
            $errors['genel'] = "Kayıt sırasında bir hata oluştu, lütfen tekrar deneyin.";
        }
        $stmt->close();
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
              <!-- <li class="has-children">
                <a href="job-listings.php">Job Listings</a>
                <ul class="dropdown">
                  <li><a href="job-single.php">Job Single</a></li>
                  <li><a href="post-job.php">Post a Job</a></li>
                </ul>
              </li> -->
              <!--<li class="has-children">
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
            <h1 class="text-white font-weight-bold">Kayıt Ol</h1>
            <div class="custom-breadcrumbs">
              <a href="#">Ana Sayfa</a> <span class="mx-2 slash">/</span>
              <span class="text-white"><strong>Kayıt Ol</strong></span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="site-section">
      <div class="container">
        <div class="row">
          <div class="col-lg-6 mb-5">
            <h2 class="mb-4">Kayıt Ol</h2>
            <?php foreach ($errors as $hata): ?>
              <div class="alert alert-danger"><?php echo htmlspecialchars($hata); ?></div>
            <?php endforeach; ?>
            <form action="login.php" method="POST" class="p-4 border rounded">

              <div class="row form-group">
                <div class="col-md-12 mb-3 mb-md-0">
                  <label class="text-black" for="fname">Ad - Soyad</label>
                  <input type="text" name="isimSoyisim" id="isimSoyisim" class="form-control" placeholder="Ad - Soyad" required>
                </div>
              </div>
              <div class="row form-group">
                <div class="col-md-12 mb-3 mb-md-0">
                  <label class="text-black" for="fname">Email</label>
                  <input type="email" name="email" id="email" class="form-control" placeholder="Email adres" required>
                </div>
              </div>
              <div class="row form-group">
                <div class="col-md-12 mb-3 mb-md-0">
                  <label class="text-black" for="fname">Telefon numarası</label>
                  <input type="text" name="telefon" id="telefon" class="form-control" placeholder="Telefon numarası" required>
                </div>
              </div>
              <div class="row form-group">
                <div class="col-md-12 mb-3 mb-md-0">
                  <label class="text-black" for="fname">Doğum günü</label>
                  <input type="date" name="dogumTarihi"  id="dogumTarihi" class="form-control" placeholder="Doğum günü" required>
                </div>
              </div>
              <select class="form-select"  name="sinif" id="sinif" aria-label="Default select example" required>
                <option selected>Sınıf</option>
                <option value="10">10</option>
                <option value="11">11</option>
                <option value="12">12</option>
                <option value="Mezun">Mezun</option>
                <option value="Diger">Diğer</option>
              </select>
              <select class="form-select"  name="alan"  id="alan" aria-label="Default select example" required>
                <option selected>Alan</option>
                <option value="1">Sayısal</option>
                <option value="2">Sözel</option>
                <option value="3">Eşit ağırlık</option>
                <option value="4">Dil</option>
              </select>
              <div class="row form-group">
                <div class="col-md-12 mb-3 mb-md-0">
                  <label class="text-black" for="fname">Şifre</label>
                  <input type="password" name="sifre" id="sifre"  class="form-control" placeholder="Şifre" required>
                </div>
              </div>
              <div class="row form-group mb-4">
                <div class="col-md-12 mb-3 mb-md-0">
                  <label class="text-black" for="fname">Şifre(tekrar)</label>
                  <input type="password" name="sifre_tekrar" id="sifre_tekrar"class="form-control" placeholder="Şifre(tekrar)" required>
                </div>
              </div>

              <div class="row form-group">
                <div class="col-md-12 mb-3 mb-md-0">
                  <label class="text-black" for="guvenlikSorusu">Güvenlik Sorusu</label>
                  <select name="guvenlikSorusu" id="guvenlikSorusu" class="form-control" required>
                    <option value="">Bir soru seçin</option>
                    <option value="İlkokulda okuduğunuz okulun adı nedir?">İlkokulda okuduğunuz okulun adı nedir?</option>
                    <option value="En sevdiğiniz öğretmeninizin adı nedir?">En sevdiğiniz öğretmeninizin adı nedir?</option>
                    <option value="Doğduğunuz şehir neresidir?">Doğduğunuz şehir neresidir?</option>
                    <option value="En sevdiğiniz kitabın adı nedir?">En sevdiğiniz kitabın adı nedir?</option>
                    <option value="İlk evcil hayvanınızın adı nedir?">İlk evcil hayvanınızın adı nedir?</option>
                  </select>
                </div>
              </div>
              <div class="row form-group mb-4">
                <div class="col-md-12 mb-3 mb-md-0">
                  <label class="text-black" for="guvenlikCevabi">Güvenlik Sorusu Cevabı</label>
                  <input type="text" name="guvenlikCevabi" id="guvenlikCevabi" class="form-control" placeholder="Cevabınız" required>
                </div>
              </div>

              <div class="row form-group">
                <div class="col-md-12">
                <button type="submit" class="btn px-4 btn-primary text-white">Kayıt Ol</button>

                </div>
              </div>

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



<?php
session_start();
include 'baglanti.php';

// Giriş yapılmamışsa bu sayfayı görüntülemeyi engelle
if (empty($_SESSION['mentor_id'])) {
    header("Location: post-job.php");
    exit();
}

// Mentor bilgilerini çek
$mentor_id = $_SESSION['mentor_id'];
$sql = "SELECT * FROM Mentor WHERE MentorID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $mentor_id);
$stmt->execute();
$result = $stmt->get_result();
$mentor = $result->fetch_assoc();

if (!$mentor) {
    echo "Mentor bilgileri alınamadı.";
    exit();
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
        .profile-header {
  background-color: #007bff;
  color: white;
  padding: 20px;
  border-radius: 8px;
}

.profile-card {
  background-color: #f8f9fa;
  border: 1px solid #ddd;
  border-radius: 8px;
  padding: 20px;
  margin-bottom: 20px;
}

.badges img {
  width: 50px;
  margin-right: 10px;
}

.badge-icon {
  border-radius: 50%;
  border: 2px solid #007bff;
  padding: 5px;
}

.rounded-circle {
  border-radius: 50%;
  border: 3px solid #007bff;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.col-md-7{

position: relative;
right: 31px;
top: 20px;
}

.col-md-9{
            width: 650px;
            height: auto;
            position: relative;
            left: 180px;
            bottom: 397px;
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
              <li class="d-lg-none"><a href="post-job.php">Giriş Yap</a></li>
              <li class="d-lg-none"><a href="kayit-ol-secigi.php">Kayıt Ol</a></li>
            </ul>
          </nav>
          
          <div class="right-cta-menu text-right d-flex aligin-items-center col-6">
            <div class="ml-auto">
              <a href="logout.php" class="btn btn-outline-white border-width-2 d-none d-lg-inline-block"><span class="mr-2 icon-lock_outline"></span>Çıkış Yap</a> 
            
            </div>
            <a href="#" class="site-menu-toggle js-menu-toggle d-inline-block d-xl-none mt-lg-2 ml-3" onclick="OnePageNavigation()"><span class="icon-menu h3 m-0 p-0 mt-2"></span></a>
          </div>

        </div>
      </div>
    </header>

    <!-- HOME -->
    <section class="section-hero overlay inner-page bg-image" style="background-image: url('images/hero_1.jpg');" id="home-section">
      <!-- <div class="container">
        <div class="row">
          <div class="col-md-7">
            <h1 class="text-white font-weight-bold">Anket</h1>
            <div class="custom-breadcrumbs">
              <a href="#">Ana Sayfa</a> <span class="mx-2 slash">/</span>
              <span class="text-white"><strong>Anket</strong></span>
            </div>
          </div>
        </div>
      </div> -->
    </section>

    <div class="container mt-5">
        <!-- Mentorun Temel Bilgileri -->
        <div class="profile-header text-center">
            <div id="mentor-profile">
                <!-- Resim buraya dinamik olarak yüklenecek -->
              </div>
              
              <script>
                // Resmin URL'si
                const imageUrl = "images/person_1.jpg"; // Resmi proje klasörüne kaydettiyseniz doğru yolu kontrol edin.
              
                // HTML elementine resim ekleme
                const profileDiv = document.getElementById("mentor-profile");
                profileDiv.innerHTML = `
                 <img src="<?php echo !empty($mentor['FotoURL']) ? htmlspecialchars($mentor['FotoURL']) : 'images/person_1.jpg'; ?>"
     alt="Mentor Profili" class="rounded-circle" style="width: 120px; height: 120px;">

                `;
              </script>
              
              
              <h1><?php echo htmlspecialchars($mentor['IsimSoyisim']); ?></h1>
          <p>Yazılım Mühendisi | Veri Bilimci | Mentor</p>
          <p>"Geleceği şekillendirmek isteyenlere rehberlik ediyorum."</p>
        </div>
      
        <!-- Eğitim ve Deneyim -->
        <div class="row mt-4">
          <div class="col-md-6">
            <div class="profile-card">
              <h5>Eğitim</h5>
              <ul>
                <li>Boğaziçi Üniversitesi - Bilgisayar Mühendisliği</li>
                <li>Stanford Üniversitesi - Veri Bilimi (Yüksek Lisans)</li>
              </ul>
            </div>
          </div>
          <div class="col-md-6">
            <div class="profile-card">
              <h5>Deneyim</h5>
              <ul>
                <li>Google - Yazılım Mühendisi (2015-2020)</li>
                <li>Microsoft - Veri Bilimcisi (2020-2023)</li>
              </ul>
            </div>
          </div>
        </div>
      
        <!-- Mentorluk İstatistikleri -->
        <div class="row mt-4">
          <div class="col-md-4">
            <div class="profile-card text-center">
              <h5>Toplam Menti</h5>
              <p>35</p>
            </div>
          </div>
          <div class="col-md-4">
            <div class="profile-card text-center">
              <h5>Toplam Saat</h5>
              <p>150 Saat</p>
            </div>
          </div>
          <div class="col-md-4">
            <div class="profile-card text-center">
              <h5>Değerlendirme</h5>
              <p>4.8/5</p>
            </div>
          </div>
        </div>
      
        <!-- Rozetler -->
        <!-- <div class="profile-card mt-4">
          <h5>Rozetler</h5>
          <div class="badges">
            <img src="https://via.placeholder.com/50" alt="Rozet" class="badge-icon" title="Yıldız Mentor">
            <img src="https://via.placeholder.com/50" alt="Rozet" class="badge-icon" title="En Aktif Mentor">
          </div>
        </div> -->
      
        <!-- Randevu ve İletişim -->
        <div class="row mt-4">
          <!-- <div class="col-md-6">
            <div class="profile-card text-center">
              <h5>Randevu Al</h5>
              <button class="btn btn-primary">Randevu Planla</button>
            </div>
          </div> -->
          <div class="col-md-6">
            <div class="profile-card text-center">
              <h5>İletişim</h5>
              <p>Email: <?php echo htmlspecialchars($mentor['Email']); ?></p>
              <a href="#" class="btn btn-outline-secondary">LinkedIn Profili</a>
            </div>
          </div>
        </div>

        <div class="form-group">
          <label for="company-website-tw d-block">Video Ekle</label> <br>
          <label class="btn btn-primary btn-md btn-file">
              Dosyaya Gözat<input type="file" hidden >
          </label>
        </div>

        <div class="col-md-7">
          <div class="col-md-6 col-lg-4 mb-5">
              <a href="blog-single.php"><img src="images/sq_img_1.jpg" alt="Image" class="img-fluid rounded mb-4"></a>
              <h3><a href="blog-single.php" class="text-black">7 Factors for Choosing Between Two Jobs</a></h3>
              <div>Ayşe Demir</div>
            </div>
          </div>

          <div class="col-md-9">
            <div class="col-md-6 col-lg-4 mb-5">
              <a href="blog-single.php"><img src="images/sq_img_4.jpg" alt="Image" class="img-fluid rounded mb-4"></a>
              <h3><a href="blog-single.php" class="text-black">The Right Way to Quit a Job You Started</a></h3>
              <div>Ayşe Demir</div>
            </div>
          </div>
      
        <!-- Menti Geri Bildirimleri -->
        <div class="profile-card mt-4">
          <h5>Menti Geri Bildirimleri</h5>
          <blockquote>
            <p>"Ayşe Hanım sayesinde kariyerimde büyük bir adım attım!"</p>
            <footer>— Mehmet Yılmaz</footer>
          </blockquote>
          <blockquote>
            <p>"Mentorluk süreci inanılmaz faydalıydı. Teşekkürler!"</p>
            <footer>— Zeynep Kaya</footer>
          </blockquote>
        </div>
      </div>
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
              <li><a href="#">Kariyer</a></li>
              <li><a href="blog.php">Programlar</a></li>
            </ul>
          </div>
          <div class="col-6 col-md-3 mb-4 mb-md-0">
            <h3>Destek</h3>
            <ul class="list-unstyled">
              <li><a href="contact.php">Destek</a></li>
              <li><a href="#">Privacy</a></li>
              <li><a href="#">Hizmet Şartları</a></li>
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
              Telif hakkı &copy;<script>document.write(new Date().getFullYear());</script> Bütün haklar saklıdır <a href="https://colorlib.com" target="_blank" ></a>
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

    <script src="js/rozet.js"></script>
   
   
     
  </body>
</html>
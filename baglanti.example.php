<?php
// Bu dosya bir ŞABLONDUR. Gerçek bağlantı bilgilerin "baglanti.php" içinde
// olmalı (o dosya .gitignore'da olduğu için GitHub'a hiç yüklenmiyor - her
// bilgisayarda farklı olabilecek veritabanı bilgilerin bu yüzden repoda
// görünmez). Projeyi başka bir bilgisayarda çalıştırmak için:
//   1. Bu dosyayı "baglanti.php" olarak kopyala
//   2. Aşağıdaki bilgileri kendi ortamına göre doldur
$servername = "localhost"; // Sunucu adı
$username = "root";        // Veritabanı kullanıcı adı
$password = "";            // Veritabanı şifresi (Boşsa "")
$dbname = "f_c_r"; // Veritabanı adı

// Bağlantıyı oluştur
$conn = new mysqli($servername, $username, $password, $dbname);

// Bağlantıyı kontrol et
if ($conn->connect_error) {
    die("Bağlantı hatası: " . $conn->connect_error);
}

// Türkçe karakterlerin (ş, ğ, ı, ç, ö, ü) veritabanında bozuk
// görünmesini önlemek için karakter setini utf8mb4 olarak ayarla.
$conn->set_charset("utf8mb4");
?>

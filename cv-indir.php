<?php
// Mentor CV'sini veritabanından (LONGBLOB) okuyup dosya olarak indirir.
// Erişim kontrolü: sadece giriş yapmış kullanıcılar (öğrenci veya mentor)
// CV indirebilir; anonim ziyaretçiler için engellendi.
session_start();
include 'baglanti.php';

if (empty($_SESSION['kullanici_id']) && empty($_SESSION['mentor_id'])) {
    header("Location: post-job.php");
    exit();
}

$mentorId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($mentorId <= 0) {
    http_response_code(400);
    echo "Geçersiz mentor ID.";
    exit();
}

$stmt = $conn->prepare("SELECT Isim, Soyisim, CV FROM Mentor WHERE MentorID = ?");
$stmt->bind_param("i", $mentorId);
$stmt->execute();
$result = $stmt->get_result();
$mentor = $result->fetch_assoc();
$stmt->close();

if (!$mentor || empty($mentor['CV'])) {
    http_response_code(404);
    echo "CV bulunamadı.";
    exit();
}

$cvData = $mentor['CV'];

// Dosya türünü ilk baytlardan tahmin et (uzantı ayrı bir sütunda
// saklanmadığı için basit bir imza kontrolü yapıyoruz).
$ext = 'pdf';
if (substr($cvData, 0, 4) === "\x25\x50\x44\x46") { // "%PDF"
    $ext = 'pdf';
    $mime = 'application/pdf';
} elseif (substr($cvData, 0, 4) === "\x50\x4B\x03\x04") { // ZIP tabanlı (docx)
    $ext = 'docx';
    $mime = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
} elseif (substr($cvData, 0, 2) === "\xD0\xCF") { // eski .doc
    $ext = 'doc';
    $mime = 'application/msword';
} else {
    $mime = 'application/octet-stream';
}

$dosyaAdi = 'CV_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $mentor['Isim'] . '_' . $mentor['Soyisim']) . '.' . $ext;

header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . $dosyaAdi . '"');
header('Content-Length: ' . strlen($cvData));
echo $cvData;
exit();

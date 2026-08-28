// Kullanıcı puanlarına göre rozetleri belirle
const userPoints = 1250;
const badgeContainer = document.querySelector(".badges");

// Rozet ve puan eşleşmeleri
// const badges = [
//   { name: "Bronz", minPoints: 0, img: "https://via.placeholder.com/50" },
//   { name: "Gümüş", minPoints: 500, img: "https://via.placeholder.com/50" },
//   { name: "Altın", minPoints: 1000, img: "https://via.placeholder.com/50" },
// ];
if (minPoints >= 1000) {
          badgeImage = 'images/gold-medal.png'; // Altın rozet
          badgeText = 'Tebrikler! Altın Rozet Kazandınız!';
        } else if (minPoints >= 500) {
          badgeImage = 'images/silver-medal.png'; // Gümüş rozet
          badgeText = 'Harika! Gümüş Rozet Kazandınız!';
        } else if (minPoints >= 200) {
          badgeImage = 'images/bronze-medal.png'; // Bronz rozet
          badgeText = 'İyi iş! Bronz Rozet Kazandınız!';
        } else {
          badgeText = 'Puanınız bir rozet için yeterli değil. Daha çok çalışın!';
        }
      

// Rozetleri kullanıcı puanına göre ekle
badges.forEach((badge) => {
  if (userPoints >= badge.minPoints) {
    const badgeElement = document.createElement("div");
    badgeElement.className = "badge";
    badgeElement.innerHTML = `
      <img src="${badge.img}" alt="${badge.name}">
      <p>${badge.name}</p>
    `;
    badgeContainer.appendChild(badgeElement);
  }
});

// function determineBadge() {
//     const points = parseInt(document.getElementById('points').value);
//     const resultDiv = document.getElementById('badge-result');
  
//     resultDiv.innerHTML = ''; // Sonuçları temizle
  
//     if (isNaN(points) || points < 0 || points > 1000) {
//       resultDiv.innerHTML = '<p>Lütfen 0-1000 arasında geçerli bir puan girin!</p>';
//       return;
//     }
  
//     let badgeImage = '';
//     let badgeText = '';
  
//     if (points >= 800) {
//       badgeImage = 'images/gold-medal.png'; // Altın rozet
//       badgeText = 'Tebrikler! Altın Rozet Kazandınız!';
//     } else if (points >= 500) {
//       badgeImage = 'silver-medal.png'; // Gümüş rozet
//       badgeText = 'Harika! Gümüş Rozet Kazandınız!';
//     } else if (points >= 200) {
//       badgeImage = 'bronze-medal.png'; // Bronz rozet
//       badgeText = 'İyi iş! Bronz Rozet Kazandınız!';
//     } else {
//       badgeText = 'Puanınız bir rozet için yeterli değil. Daha çok çalışın!';
//     }
  
//     if (badgeImage) {
//       const imgElement = document.createElement('img');
//       imgElement.src = badgeImage;
//       imgElement.alt = badgeText;
//       resultDiv.appendChild(imgElement);
//     }
  
//     const textElement = document.createElement('p');
//     textElement.textContent = badgeText;
//     resultDiv.appendChild(textElement);
//   }
  
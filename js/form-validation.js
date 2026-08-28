function validateForm() {
    let isValid = true;
    
    // Input field validation
    const requiredFields = {
      'isimSoyisim': 'Ad-Soyad',
      'email': 'Email',
      'telefon': 'Telefon',
      'dogumTarihi': 'Doğum tarihi',
      'dersAdi': 'Ders adı',
      'sifre': 'Şifre',
      'sifre_tekrar': 'Şifre tekrar'
    };
  
    for (let fieldId in requiredFields) {
      const field = document.getElementById(fieldId);
      const feedbackDiv = document.createElement('div');
      feedbackDiv.className = 'validation-feedback';
      
      // Remove existing feedback
      const existingFeedback = field.parentNode.querySelector('.validation-feedback');
      if (existingFeedback) {
        existingFeedback.remove();
      }
      
      if (!field.value.trim()) {
        feedbackDiv.innerHTML = `<i class="icon-close text-danger"></i> ${requiredFields[fieldId]} alanı boş bırakılamaz`;
        field.parentNode.appendChild(feedbackDiv);
        isValid = false;
      } else {
        feedbackDiv.innerHTML = '<i class="icon-check text-success"></i>';
        field.parentNode.appendChild(feedbackDiv);
      }
    }
  
    // File extension validation
    const fileValidations = {
      'foto': {
        extensions: ['.jpg', '.jpeg', '.png'],
        message: 'Lütfen geçerli bir resim dosyası yükleyin (jpg, jpeg, png)'
      },
      'cv': {
        extensions: ['.pdf', '.doc', '.docx'],
        message: 'Lütfen geçerli bir CV dosyası yükleyin (pdf, doc, docx)'
      },
      'video': {
        extensions: ['.mp4', '.mov', '.avi'],
        message: 'Lütfen geçerli bir video dosyası yükleyin (mp4, mov, avi)'
      }
    };
  
    for (let fileId in fileValidations) {
      const fileInput = document.querySelector(`input[name="${fileId}"]`);
      const feedbackDiv = document.createElement('div');
      feedbackDiv.className = 'validation-feedback';
      
      // Remove existing feedback
      const existingFeedback = fileInput.parentNode.querySelector('.validation-feedback');
      if (existingFeedback) {
        existingFeedback.remove();
      }
      
      if (fileInput.files.length > 0) {
        const fileName = fileInput.files[0].name.toLowerCase();
        const isValidExtension = fileValidations[fileId].extensions.some(ext => 
          fileName.endsWith(ext)
        );
        
        if (!isValidExtension) {
          feedbackDiv.innerHTML = `<i class="icon-close text-danger"></i> ${fileValidations[fileId].message}`;
          fileInput.parentNode.appendChild(feedbackDiv);
          isValid = false;
        } else {
          feedbackDiv.innerHTML = '<i class="icon-check text-success"></i>';
          fileInput.parentNode.appendChild(feedbackDiv);
        }
      } else {
        feedbackDiv.innerHTML = `<i class="icon-close text-danger"></i> Dosya seçilmedi`;
        fileInput.parentNode.appendChild(feedbackDiv);
        isValid = false;
      }
    }
  
    return isValid;
  }
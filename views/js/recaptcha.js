var cbRecaptcha = function() {

   $('#js-recaptcha-wrap').appendTo('#content form .form-fields');
   setTimeout(function() {
          grecaptcha.render('js-recaptcha');
      }, 1);

};


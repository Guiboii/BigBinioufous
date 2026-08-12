// Aperçu immédiat de la photo choisie sur /desk/profile et admin/user/show
// (templates/desk/_user_form_fields.html.twig), avant même la soumission du
// formulaire (le fichier n'est réellement enregistré qu'à ce moment-là) :
// confirme visuellement le bon fichier sans avoir à valider pour le voir.
document.querySelectorAll('.profile-avatar-frame').forEach(function (frame) {
  var input = frame.querySelector('input[type="file"]');
  if (!input) {
    return;
  }

  input.addEventListener('change', function () {
    var file = input.files && input.files[0];
    if (!file) {
      return;
    }

    var img = frame.querySelector('img');
    if (!img) {
      var icon = frame.querySelector('i.ri-user-3-fill');
      if (icon) {
        icon.remove();
      }
      img = document.createElement('img');
      img.alt = '';
      frame.insertBefore(img, frame.firstChild);
    }
    img.src = URL.createObjectURL(file);
  });
});

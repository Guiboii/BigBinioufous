// Instant preview of the chosen picture, before the form is even submitted (the file is only actually saved at that point): confirms visually it's the right file without having to submit to see it.
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

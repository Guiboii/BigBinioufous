// Bascule l'écran (waveform) en lecteur vidéo YouTube intégré quand on
// clique sur le badge YouTube d'un morceau, plutôt qu'ouvrir un nouvel
// onglet (retour utilisatrice, 2026-08-21 : "un player en mode écran plasma
// au lieu du son pour les liens ytb"). Ouvert à tout le monde (contrairement
// au lecteur audio, réservé aux binioufous/admins, cf. music/index.html.twig)
// et pour n'importe quel morceau ayant un lien YouTube, avec ou sans fichier
// audio associé.
//
// Communication avec Player.js via CustomEvent sur document plutôt qu'un
// import direct : les deux scripts tournent en parallèle sans se connaître
// (wavesurfer est une variable de module interne à Player.js, pas exportée),
// chacun coupe l'autre quand il démarre.
document.addEventListener('DOMContentLoaded', function () {
  var playlist = document.getElementById('playlist');
  var waveform = document.getElementById('waveform');
  var embed = document.getElementById('youtube-embed');
  var playerWrap = document.querySelector('.player-wrap');
  var nowPlaying = document.getElementById('now-playing');

  if (!playlist || !waveform || !embed || !playerWrap) {
    return;
  }

  var titlePrefix = embed.dataset.embedTitlePrefix || '';
  var closeLabel = embed.dataset.closeLabel || '';

  function hideVideo() {
    embed.classList.add('d-none');
    embed.textContent = '';
    waveform.classList.remove('d-none');
    playerWrap.classList.remove('video-mode');
  }

  // DOM construit via createElement/textContent plutôt qu'innerHTML : le
  // titre du morceau (data-youtube-title) vient d'un champ libre saisi par
  // un·e binioufous/admin (SetlistController), pas une source qu'on veut
  // interpoler brute dans du HTML.
  function showVideo(videoId, trackTitle) {
    // Prévient Player.js : un morceau audio en cours de lecture doit
    // s'arrêter, la waveform n'a plus de sens pendant que la vidéo occupe
    // le même espace à l'écran.
    document.dispatchEvent(new CustomEvent('music:show-video'));

    embed.textContent = '';

    var closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.className = 'youtube-embed-close';
    closeBtn.setAttribute('aria-label', closeLabel);
    closeBtn.addEventListener('click', hideVideo);
    var closeIcon = document.createElement('span');
    closeIcon.setAttribute('aria-hidden', 'true');
    closeIcon.textContent = '×';
    closeBtn.appendChild(closeIcon);

    var iframe = document.createElement('iframe');
    iframe.src =
      'https://www.youtube-nocookie.com/embed/' + encodeURIComponent(videoId) + '?autoplay=1';
    iframe.title = titlePrefix + trackTitle;
    iframe.allow = 'autoplay; encrypted-media; picture-in-picture';
    iframe.allowFullscreen = true;

    embed.appendChild(closeBtn);
    embed.appendChild(iframe);

    waveform.classList.add('d-none');
    embed.classList.remove('d-none');
    playerWrap.classList.add('video-mode');

    if (nowPlaying) {
      nowPlaying.textContent = titlePrefix + trackTitle;
    }
  }

  // Symétrique : un morceau lancé au lecteur audio (Player.js) ferme la
  // vidéo en cours, la waveform doit reprendre sa place.
  document.addEventListener('music:audio-playing', hideVideo);

  playlist.addEventListener('click', function (e) {
    var badge = e.target.closest('[data-youtube-id]');
    if (!badge) {
      return;
    }
    e.preventDefault();
    showVideo(badge.dataset.youtubeId, badge.dataset.youtubeTitle || '');
  });
});

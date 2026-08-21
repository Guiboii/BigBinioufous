// Ouvre un grand lecteur vidéo YouTube en overlay par-dessus la scène 3D
// quand on clique sur le badge YouTube d'un morceau, plutôt qu'un nouvel
// onglet (retour utilisatrice, 2026-08-21 : d'abord "un player en mode écran
// plasma au lieu du son pour les liens ytb", puis précisé "c'est juste
// l'écran, quand c'est une vidéo ytb qui doit apparaître par-dessus" - le
// petit écran audio inline, lui, reste posé sur le meuble 3D, cf.
// music/index.html.twig et Player.js, complètement indépendant de cet
// overlay). Ouvert à tout le monde (contrairement au lecteur audio, réservé
// aux binioufous/admins) et pour n'importe quel morceau ayant un lien
// YouTube, avec ou sans fichier audio associé.
//
// Communication avec Player.js/music.js via CustomEvent sur document plutôt
// qu'un import direct : les scripts tournent en parallèle sans se connaître.
document.addEventListener('DOMContentLoaded', function () {
  var playlist = document.getElementById('playlist');
  var embed = document.getElementById('youtube-embed');
  var nowPlaying = document.getElementById('now-playing');

  if (!playlist || !embed) {
    return;
  }

  var titlePrefix = embed.dataset.embedTitlePrefix || '';

  // Pas de bouton fermer propre à la vidéo : #videoOverlay (music.js) porte
  // déjà le sien (backdrop/bouton/Échap).
  function hideVideo() {
    embed.textContent = '';
  }

  // DOM construit via createElement/textContent plutôt qu'innerHTML : le
  // titre du morceau (data-youtube-title) vient d'un champ libre saisi par
  // un·e binioufous/admin (SetlistController), pas une source qu'on veut
  // interpoler brute dans du HTML.
  function showVideo(videoId, trackTitle) {
    // Prévient Player.js : l'audio en cours doit s'arrêter. Prévient aussi
    // music.js : ouvre le grand écran en overlay (#videoOverlay).
    document.dispatchEvent(new CustomEvent('music:show-video'));

    embed.textContent = '';

    var iframe = document.createElement('iframe');
    iframe.src =
      'https://www.youtube-nocookie.com/embed/' + encodeURIComponent(videoId) + '?autoplay=1';
    iframe.title = titlePrefix + trackTitle;
    iframe.allow = 'autoplay; encrypted-media; picture-in-picture';
    iframe.allowFullscreen = true;

    embed.appendChild(iframe);

    if (nowPlaying) {
      nowPlaying.textContent = titlePrefix + trackTitle;
    }
  }

  // Un morceau lancé au petit lecteur audio (Player.js) ferme la vidéo en
  // cours : les deux ne jouent jamais en même temps.
  document.addEventListener('music:audio-playing', hideVideo);

  // Fermer le grand écran (bouton fermer/backdrop/Échap, cf. music.js) doit
  // couper la vidéo plutôt que la laisser tourner cachée.
  document.addEventListener('music:close-video', function () {
    hideVideo();
    if (nowPlaying) {
      nowPlaying.textContent = '';
    }
  });

  playlist.addEventListener('click', function (e) {
    var badge = e.target.closest('[data-youtube-id]');
    if (!badge) {
      return;
    }
    e.preventDefault();
    showVideo(badge.dataset.youtubeId, badge.dataset.youtubeTitle || '');
  });
});

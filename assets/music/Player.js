// Create a WaveSurfer instance
var wavesurfer;

// Init on DOM ready
document.addEventListener('DOMContentLoaded', function () {
  wavesurfer = WaveSurfer.create({
    container: '#waveform',
    waveColor: '#e2e2e2',
    progressColor: '#ffffff',
    cursorColor: '#ffffff',
    barWidth: 1,
    height: 91,
    //backend: 'MediaElement',
    plugins: [WaveSurfer.regions.create()],
  });
});

document.querySelector('#slider').oninput = function () {
  wavesurfer.zoom(Number(this.value));
};

// Bind controls
//
// #stopTrack/#playPause/#loopRegion sont maintenant de vrais <button>
// (avant : <div> avec juste un handler click, ni focusables ni activables
// au clavier, cf. music/index.html.twig). Le handler écoute directement sur
// le bouton plutôt que sur l'ancien <span id="stop"> interne : un clic
// clavier (Entrée/Espace) déclenche l'événement "click" avec le bouton
// lui-même comme cible, pas un de ses enfants.
document.addEventListener('DOMContentLoaded', function () {
  var playPause = document.querySelector('#playPause');
  playPause.addEventListener('click', function () {
    wavesurfer.playPause();
  });

  var stopTrack = document.querySelector('#stopTrack');
  stopTrack.addEventListener('click', function () {
    wavesurfer.stop();
  });

  // Toggle play/pause icon + nom accessible (le bouton n'a pas de texte
  // visible, seulement une icône : sans aria-label à jour, un lecteur
  // d'écran annoncerait toujours "Lecture" même une fois en pause).
  wavesurfer.on('play', function () {
    document.querySelector('#play').style.display = 'none';
    document.querySelector('#pause').style.display = '';
    playPause.setAttribute('aria-label', playPause.dataset.pauseLabel);
  });
  wavesurfer.on('pause', function () {
    document.querySelector('#play').style.display = '';
    document.querySelector('#pause').style.display = 'none';
    playPause.setAttribute('aria-label', playPause.dataset.playLabel);
  });

  var loopRegion = document.querySelector('#loopRegion');
  loopRegion.addEventListener('click', function () {
    if (hasClass(loopRegion, 'looping')) {
      loopRegion.classList.remove('looping');
      loopRegion.setAttribute('aria-pressed', 'false');
      wavesurfer.clearRegions();
      //wavesurfer.play();
    } else {
      wavesurfer.clearRegions();
      loopRegion.classList.add('looping');
      loopRegion.setAttribute('aria-pressed', 'true');
      wavesurfer.addRegion({
        id: 'loop',
        start: 5,
        end: 25,
        loop: false,
        color: 'hsla(163, 53%, 26%, 0.4)',
      });
      wavesurfer.regions.list['loop'].playLoop();
      //var region = wavesurfer.regions.list['loopMe'];
      //region.playLoop();
    }
  });

  // The playlist links. Recalculés à chaque usage (pas de NodeList mise en
  // cache) : la modale "Gérer la setlist" (assets/music/setlist-manage.js)
  // rafraîchit #playlist en innerHTML après un ajout/édition/suppression,
  // sans recharger toute la page, ce qui remplace les <a> existants par de
  // nouveaux nœuds DOM. Un cache pris une seule fois au chargement
  // pointerait alors vers des éléments disparus.
  var playlist = document.getElementById('playlist');
  var currentTrack = 0;
  var nowPlaying = document.querySelector('#now-playing');

  function getLinks() {
    return playlist ? playlist.querySelectorAll('a') : [];
  }

  // Load a track by index and highlight the corresponding link
  // aria-current="true" en plus de la classe .active : la classe seule ne
  // porte l'info que visuellement (couleur de fond), un lecteur d'écran ne
  // peut pas la détecter. nowPlaying (aria-live) annonce le changement pour
  // qui ne regarde pas la playlist à ce moment-là (clic ou piste suivante
  // automatique).
  //
  // autoplay (2e argument, true par défaut) : faux uniquement pour le
  // chargement initial de la page (retour utilisatrice, 2026-08-13 : "la
  // musique ne devrait pas se jouer dès qu'on arrive sur l'écran"). Un clic
  // sur un morceau ou l'enchaînement automatique en fin de piste (event
  // "finish" plus bas) restent une vraie lecture, donc autoplay=true.
  var pendingAutoplay = true;
  var setCurrentSong = function (index, autoplay) {
    var links = getLinks();
    if (!links.length || !links[index]) {
      return;
    }
    if (links[currentTrack]) {
      links[currentTrack].classList.remove('active');
      links[currentTrack].removeAttribute('aria-current');
    }
    currentTrack = index;
    links[currentTrack].classList.add('active');
    links[currentTrack].setAttribute('aria-current', 'true');
    if (nowPlaying) {
      nowPlaying.textContent = links[currentTrack].dataset.trackTitle || '';
    }
    pendingAutoplay = false !== autoplay;
    wavesurfer.load(links[currentTrack].href);
  };

  // Délégation sur le conteneur plutôt qu'un listener par <a> : reste valide
  // même après un rafraîchissement en innerHTML de #playlist (cf. commentaire
  // sur getLinks() ci-dessus), sans quoi les liens recréés n'auraient jamais
  // de listener (celui-ci n'est posé qu'une fois, au chargement de la page).
  if (playlist) {
    playlist.addEventListener('click', function (e) {
      var link = e.target.closest('a');
      if (!link) {
        return;
      }
      e.preventDefault();
      var index = Array.prototype.indexOf.call(getLinks(), link);
      if (-1 !== index) {
        setCurrentSong(index);
      }
    });
  }

  // Play on audio load, sauf pour le chargement initial (cf. pendingAutoplay
  // ci-dessus).
  wavesurfer.on('ready', function () {
    if (pendingAutoplay) {
      wavesurfer.play();
    }
  });

  wavesurfer.on('error', function (e) {
    console.warn(e);
  });

  // Go to the next track on finish
  wavesurfer.on('finish', function () {
    var links = getLinks();
    if (links.length) {
      setCurrentSong((currentTrack + 1) % links.length);
    }
  });

  // Load the first track (rien à charger si la playlist est vide : sans
  // cette garde, ça plante au chargement). autoplay=false : juste préparer
  // le lecteur, pas déclencher la lecture toute seule à l'arrivée sur la
  // page.
  if (getLinks().length) {
    setCurrentSong(0, false);
  }
});

function hasClass(elem, className) {
  return new RegExp(' ' + className + ' ').test(' ' + elem.className + ' ');
}

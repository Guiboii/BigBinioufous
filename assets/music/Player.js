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
    // MediaElement plutôt que le backend webaudio par défaut : webaudio
    // télécharge et décode le fichier entier avant de déclencher "ready"
    // (donc avant que play() ait un effet), ~13,5s mesurés en local sur un
    // mp3 de 22 Mo (assets/music/, cf. CLAUDE.md). MediaElement s'appuie sur
    // <audio> natif, qui streame et joue dès que les premières données
    // arrivent (retour utilisatrice, 2026-08-21 : "giga délai" avant que le
    // son se lance) : ~300ms mesurés sur le même fichier.
    backend: 'MediaElement',
    plugins: [WaveSurfer.regions.create()],
  });

  // Symétrique de l'event "music:audio-playing" plus bas : une vidéo YouTube
  // ouverte en grand (assets/music/youtube-embed.js) doit couper l'audio en
  // cours.
  document.addEventListener('music:show-video', function () {
    wavesurfer.pause();
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
  // Témoin "écran plasma" (habillage visuel, cf. music/index.html.twig) :
  // pastille rouge allumée seulement pendant une lecture réelle, pas juste
  // "un morceau est chargé".
  var powerLed = document.querySelector('#powerLed');
  wavesurfer.on('play', function () {
    document.querySelector('#play').style.display = 'none';
    document.querySelector('#pause').style.display = '';
    playPause.setAttribute('aria-label', playPause.dataset.pauseLabel);
    if (powerLed) {
      powerLed.classList.add('is-playing');
    }
    // Prévient assets/music/youtube-embed.js : une vidéo YouTube ouverte
    // occupe le même espace à l'écran que la waveform, elle doit se fermer
    // si l'audio (re)démarre.
    document.dispatchEvent(new CustomEvent('music:audio-playing'));
  });
  wavesurfer.on('pause', function () {
    document.querySelector('#play').style.display = '';
    document.querySelector('#pause').style.display = 'none';
    playPause.setAttribute('aria-label', playPause.dataset.playLabel);
    if (powerLed) {
      powerLed.classList.remove('is-playing');
    }
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
  var trackCounter = document.querySelector('#trackCounter');
  var screenTicker = document.querySelector('#screenTicker');
  var waveW = document.querySelector('.waveW');

  // a.list-group-item seulement : un item de playlist peut aussi contenir un
  // lien YouTube (.badge, cf. music/index.html.twig), pas destiné à
  // wavesurfer. Un simple querySelectorAll('a') le comptait comme une piste
  // (index décalés, wavesurfer.load() appelé avec une URL YouTube) et le
  // rendait aussi impossible à ouvrir normalement (cf. délégation de clic
  // ci-dessous, qui appelait preventDefault() sur son clic aussi).
  function getLinks() {
    return playlist ? playlist.querySelectorAll('a.list-group-item') : [];
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
    var title = links[currentTrack].dataset.trackTitle || '';
    if (nowPlaying) {
      nowPlaying.textContent = title;
    }
    if (screenTicker) {
      screenTicker.textContent = title;
    }
    if (trackCounter) {
      trackCounter.textContent =
        String(currentTrack + 1).padStart(2, '0') + ' / ' + String(links.length).padStart(2, '0');
    }
    // Sursaut bref (cf. @keyframes bb-screen-flicker, music.css), retiré
    // après coup pour pouvoir se redéclencher au morceau suivant (une classe
    // laissée en place ne rejoue pas son animation).
    if (waveW) {
      waveW.classList.remove('screen-flicker');
      void waveW.offsetWidth; // force le reflow, sinon le navigateur fusionne le remove+add et l'animation ne repart pas
      waveW.classList.add('screen-flicker');
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
      var link = e.target.closest('a.list-group-item');
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

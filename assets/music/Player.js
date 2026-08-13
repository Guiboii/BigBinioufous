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

  // The playlist links
  var links = document.querySelectorAll('#playlist a');
  var currentTrack = 0;
  var nowPlaying = document.querySelector('#now-playing');

  // Load a track by index and highlight the corresponding link
  // aria-current="true" en plus de la classe .active : la classe seule ne
  // porte l'info que visuellement (couleur de fond), un lecteur d'écran ne
  // peut pas la détecter. nowPlaying (aria-live) annonce le changement pour
  // qui ne regarde pas la playlist à ce moment-là (clic ou piste suivante
  // automatique).
  var setCurrentSong = function (index) {
    links[currentTrack].classList.remove('active');
    links[currentTrack].removeAttribute('aria-current');
    currentTrack = index;
    links[currentTrack].classList.add('active');
    links[currentTrack].setAttribute('aria-current', 'true');
    if (nowPlaying) {
      nowPlaying.textContent = links[currentTrack].dataset.trackTitle || '';
    }
    wavesurfer.load(links[currentTrack].href);
  };

  // Load the track on click
  Array.prototype.forEach.call(links, function (link, index) {
    link.addEventListener('click', function (e) {
      e.preventDefault();
      setCurrentSong(index);
    });
  });

  // Play on audio load
  wavesurfer.on('ready', function () {
    wavesurfer.play();
  });

  wavesurfer.on('error', function (e) {
    console.warn(e);
  });

  // Go to the next track on finish
  wavesurfer.on('finish', function () {
    if (links.length) {
      setCurrentSong((currentTrack + 1) % links.length);
    }
  });

  // Load the first track (rien à charger si la playlist est vide : sans
  // cette garde, links[0] est undefined et ça plante au chargement)
  if (links.length) {
    setCurrentSong(currentTrack);
  }
});

function hasClass(elem, className) {
  return new RegExp(' ' + className + ' ').test(' ' + elem.className + ' ');
}

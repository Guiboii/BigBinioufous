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
    // MediaElement rather than the default webaudio backend: webaudio downloads and decodes the whole file before firing "ready" (~13.5s measured locally on a 22 Mo mp3). MediaElement streams via native <audio> and starts playing as soon as the first data arrives (~300ms on the same file).
    backend: 'MediaElement',
    plugins: [WaveSurfer.regions.create()],
  });

  // Mirror of the "music:audio-playing" event below: an opened YouTube video overlay must stop the current audio.
  document.addEventListener('music:show-video', function () {
    wavesurfer.pause();
  });
});

document.querySelector('#slider').oninput = function () {
  wavesurfer.zoom(Number(this.value));
};

// Bind controls: #stopTrack/#playPause/#loopRegion are real <button> elements (not focusable/keyboard-activatable <div>s), so the handler listens on the button itself and a keyboard activation (Enter/Space) fires "click" with the button as target.
document.addEventListener('DOMContentLoaded', function () {
  var playPause = document.querySelector('#playPause');
  playPause.addEventListener('click', function () {
    wavesurfer.playPause();
  });

  var stopTrack = document.querySelector('#stopTrack');
  stopTrack.addEventListener('click', function () {
    wavesurfer.stop();
  });

  // Toggles the play/pause icon and accessible name: the button has no visible text, so aria-label must stay in sync or a screen reader would always announce "Play". The power LED lights up only during actual playback, not just "a track is loaded".
  var powerLed = document.querySelector('#powerLed');
  wavesurfer.on('play', function () {
    document.querySelector('#play').style.display = 'none';
    document.querySelector('#pause').style.display = '';
    playPause.setAttribute('aria-label', playPause.dataset.pauseLabel);
    if (powerLed) {
      powerLed.classList.add('is-playing');
    }
    // Notifies youtube-embed.js: an open YouTube video occupies the same screen space as the waveform and must close if audio (re)starts.
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

  // The playlist links, recalculated on every use rather than cached: setlist-manage.js refreshes #playlist's innerHTML in place, replacing the existing <a> elements, so a NodeList cached once at load would point to stale nodes.
  var playlist = document.getElementById('playlist');
  var currentTrack = 0;
  var nowPlaying = document.querySelector('#now-playing');
  var trackCounter = document.querySelector('#trackCounter');
  var screenTicker = document.querySelector('#screenTicker');
  var waveW = document.querySelector('.waveW');

  // Only a.list-group-item: a playlist item can also contain a YouTube badge link not meant for wavesurfer. A plain querySelectorAll('a') would count it as a track (offsetting indexes, loading a YouTube URL as audio) and also break its own click handling.
  function getLinks() {
    return playlist ? playlist.querySelectorAll('a.list-group-item') : [];
  }

  // Loads a track by index and highlights the corresponding link. aria-current="true" is set alongside the .active class since the class alone is only a visual cue; nowPlaying (aria-live) announces the change for anyone not looking at the playlist.
  //
  // autoplay (2nd argument, true by default) is false only for the page's initial load: music shouldn't start playing the moment the screen appears. A click on a track or the automatic next-track on finish are real playback intents, so they default to autoplay=true.
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
    // Brief flicker effect, removed then re-added so it can retrigger on the next track: a class left in place never replays its animation.
    if (waveW) {
      waveW.classList.remove('screen-flicker');
      void waveW.offsetWidth; // force a reflow, otherwise the browser coalesces remove+add and the animation never restarts
      waveW.classList.add('screen-flicker');
    }
    pendingAutoplay = false !== autoplay;
    wavesurfer.load(links[currentTrack].href);
  };

  // Delegated on the container rather than one listener per <a>: stays valid after an innerHTML refresh of #playlist, since re-created links would otherwise never get a listener (this one is bound only once, at page load).
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

  // Play on audio load, except for the initial page load (see pendingAutoplay above).
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

  // Loads the first track (guarded, since an empty playlist has nothing to load). autoplay=false: just prepare the player, don't start playback on page arrival.
  if (getLinks().length) {
    setCurrentSong(0, false);
  }
});

function hasClass(elem, className) {
  return new RegExp(' ' + className + ' ').test(' ' + elem.className + ' ');
}

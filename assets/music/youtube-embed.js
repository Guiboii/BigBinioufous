// Opens a large YouTube video overlay over the 3D scene when a song's YouTube badge is clicked, rather than a new tab. Open to everyone (unlike the audio player, reserved to binioufous/admins), for any song with a YouTube link, with or without an audio file.
//
// Communicates with Player.js/music.js via CustomEvent on document rather than a direct import: the scripts run in parallel without knowing about each other.
document.addEventListener('DOMContentLoaded', function () {
  var playlist = document.getElementById('playlist');
  var embed = document.getElementById('youtube-embed');
  var nowPlaying = document.getElementById('now-playing');

  if (!playlist || !embed) {
    return;
  }

  var titlePrefix = embed.dataset.embedTitlePrefix || '';

  // No dedicated close button for the video itself: #videoOverlay (music.js) already provides one (backdrop/button/Escape).
  function hideVideo() {
    embed.textContent = '';
  }

  // DOM built via createElement/textContent rather than innerHTML: the track title comes from a free-text field, not a source to interpolate raw into HTML.
  function showVideo(videoId, trackTitle) {
    // Notifies Player.js to stop any current audio, and music.js to open the overlay screen.
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

  // Starting a track on the audio player closes the running video: the two never play at once.
  document.addEventListener('music:audio-playing', hideVideo);

  // Closing the overlay screen must stop the video rather than leaving it running hidden.
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

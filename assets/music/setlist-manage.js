// The "Manage setlist" modal's forms (add/edit/delete/reorder), submitted via fetch rather than a full navigation, to keep the modal open and avoid restarting the whole 3D scene on every small action. On success, only the modal content and the public #playlist are refreshed by re-fetching /music and swapping in the fresh HTML; the "member area" voice list isn't touched here and stays stale until a real reload, an accepted tradeoff for the rare case of editing and listening at once.
var setlistDialog = document.getElementById('setlistManageDialog');

if (setlistDialog) {
  setlistDialog.addEventListener('submit', function (e) {
    var form = e.target.closest('form');
    if (!form) {
      return;
    }

    e.preventDefault();

    fetch(form.action, {
      method: form.method || 'POST',
      body: new FormData(form),
    })
      .then(function () {
        return fetch(window.location.href);
      })
      .then(function (response) {
        return response.text();
      })
      .then(function (html) {
        var freshDoc = new DOMParser().parseFromString(html, 'text/html');

        var freshDialogBody = freshDoc.querySelector('#setlistManageDialog .dialog-body');
        var currentDialogBody = setlistDialog.querySelector('.dialog-body');
        if (freshDialogBody && currentDialogBody) {
          currentDialogBody.innerHTML = freshDialogBody.innerHTML;
        }

        var freshPlaylist = freshDoc.getElementById('playlist');
        var currentPlaylist = document.getElementById('playlist');
        if (freshPlaylist && currentPlaylist) {
          currentPlaylist.innerHTML = freshPlaylist.innerHTML;
        }
      });
  });
}

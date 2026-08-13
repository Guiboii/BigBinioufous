// Formulaires de la modale "Gérer la setlist" (ajout/édition/suppression/
// ordre, cf. templates/music/index.html.twig) : soumis en fetch plutôt
// qu'en navigation complète (retour utilisatrice, 2026-08-13 : "si tu peux
// faire en sorte que ça reload pas tt la page à chaque clic"), pour garder
// la modale ouverte et éviter de relancer toute la scène 3D à chaque petite
// action. Après succès, seuls le contenu de la modale et la playlist
// publique (#playlist) sont rafraîchis en re-fetchant /music et en extrayant
// le HTML à jour : la liste des voix dans "Espace membres" n'est pas
// retouchée ici (reste à jour seulement au prochain vrai rechargement),
// compromis assumé pour rester simple, cas rare d'édition ET écoute
// simultanées par la même personne.
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

# Plan de tests Playwright, fonctionnalité par fonctionnalité

Rappel de la règle de `doc/tests.md` : Playwright **seulement** pour ce que PHPUnit ne voit pas (JavaScript, glisser-déposer, rendu, scène 3D, mobile). Droits d'accès, redirections, formulaires classiques, réponses JSON : PHPUnit (déjà 19 fichiers dans `tests/Controller`).

Chaque fonctionnalité ci-dessous liste donc uniquement les scénarios qui dépendent du navigateur.

Priorités :

- **P1** : a déjà cassé une fois (régression connue) ou cœur de l'usage quotidien.
- **P2** : JS métier, cassable par une mise à jour de dépendance.
- **P3** : confort, rendu, difficile à tester de façon stable (scène 3D au pixel).

## 0. Prérequis (avant d'écrire les tests)

- [ ] **Comptes de test fixes** (pas encore nécessaire : tous les tests P1 passent en admin) : les fixtures n'ont que 2 comptes à email connu (admins). Ajouter des comptes stables pour l'e2e : un·e binioufous validé·e, un compte simple validé, un compte en attente de validation (emails fixes, mot de passe `password`).
- [x] **Helper partagé** `tests/e2e/support/helpers.js` (`login`, `openFreshFolder`, `uploadFiles`, `fixture`) + session admin enregistrée une fois par `auth.setup.js` (`storageState`) : un test fichiers passe de ~17 s à ~2 s. `folder-drop.spec.js` garde encore son propre `login()`.
- [x] **Fichiers de test** `tests/e2e/fixtures/` : un petit mp3 (quelques Ko), un PDF, une image, un fichier non autorisé (`.zip`).
- [ ] **Projet mobile** dans `playwright.config.js` (ex. `devices['Pixel 7']`), lancé uniquement sur les specs taguées `@mobile`.

## 1. Transverse (toutes pages)

| Scénario                                             | Vérification                                                          | Prio | Statut                     |
| ---------------------------------------------------- | --------------------------------------------------------------------- | ---- | -------------------------- |
| Clic sur le burger au-dessus d'une scène 3D          | Le clic n'atteint pas la scène (#44)                                  | P1   | ✅ `click-through.spec.js` |
| Menu mobile ouvert puis `Échap`                      | Le menu se ferme, le focus revient au burger (`app.js`)               | P2   |                            |
| Navigation au clavier (Tab) sur chaque page publique | Un indicateur `:focus-visible` est visible sur chaque élément atteint | P3   |                            |

## 2. Connexion `/join` (`login/join.js`)

| Scénario                                | Vérification                                                | Prio | Statut                          |
| --------------------------------------- | ----------------------------------------------------------- | ---- | ------------------------------- |
| Bouton "connexion" accessible (hors 3D) | La popup s'ouvre, focus dans le 1er champ, `Échap` la ferme | P1   | ✅ `join.spec.js`               |
| Identifiants valides                    | Arrivée sur `/desk`                                         | P1   | ✅ `auth.setup.js`              |
| Identifiants faux                       | Message d'erreur visible, popup toujours ouverte            | P2   | ✅ `join.spec.js` (bug corrigé) |
| Clic 3D sur la soucoupe / le tapis      | Redirection attendue (`/join` ↔ inscription / accueil)      | P3   |                                 |

## 3. Profil `/desk/profile`

| Scénario                       | Vérification                                                                                              | Prio      | Statut |
| ------------------------------ | --------------------------------------------------------------------------------------------------------- | --------- | ------ |
| Instrument "Autre" sélectionné | Le champ "lequel ?" apparaît et devient actif ; masqué et `disabled` sinon (`instrument-other-toggle.js`) | P2        |        |
| Choix d'un avatar              | Aperçu affiché avant envoi du formulaire (`avatar-preview.js`)                                            | P3        |        |
| Compte non validé              | Bandeau "en cours de vérification" visible                                                                | (PHPUnit) |        |

## 4. Gestionnaire de fichiers `/desk/files/{space}`

Le plus gros bloc, et celui qui a le plus cassé.

| Scénario                                            | Vérification                                                                                                 | Prio | Statut                                            |
| --------------------------------------------------- | ------------------------------------------------------------------------------------------------------------ | ---- | ------------------------------------------------- |
| Dépôt d'un dossier dans un dossier homonyme         | Arrive dans le dossier affiché (#36)                                                                         | P1   | ✅ `folder-drop.spec.js`                          |
| Dépôt d'un fichier simple sur la zone               | Le document apparaît dans la liste sans erreur                                                               | P1   | ✅ `files.spec.js`                                |
| Dépôt d'un fichier refusé (type ou taille)          | Le message précis s'affiche (`invalid_mimetype`, `file_too_large`), pas un "échec" générique                 | P1   | ✅ `files.spec.js` (bug corrigé ; taille simulée) |
| Dépôt mp3 + PDF + image dans l'espace `music`       | Les 3 sont acceptés (régression du 2026-08-13)                                                               | P1   | ✅ `files.spec.js`                                |
| Glisser un document sur un dossier                  | Le document est déplacé (`drag-move.js`)                                                                     | P1   | ✅ `files.spec.js`                                |
| Glisser un dossier sur lui-même / un de ses enfants | Refusé, rien ne bouge                                                                                        | P2   |                                                   |
| Multi-sélection puis "Déplacer"                     | Seuls les éléments cochés sont déplacés ; 2e action groupée sans ids résiduels de la 1re (`bulk-actions.js`) | P1   | 🟡 `files.spec.js` (ids résiduels pas testés)     |
| Multi-sélection puis "Supprimer"                    | Éléments en corbeille, puis restauration depuis la corbeille                                                 | P2   |                                                   |
| Clic sur une image / un PDF / un mp3                | Modale d'aperçu avec le bon lecteur ; un `.docx` reste un lien normal (`file-preview.js`)                    | P2   |                                                   |
| Création d'un dossier                               | La carte "Nouveau dossier" est visible et fonctionne                                                         | P3   | ✅ indirect (tous les tests fichiers)             |
| Recherche et tri                                    | (PHPUnit : rendu serveur)                                                                                    |      |                                                   |

## 5. Musique `/music` (`music.js`, `Player.js`, `setlist-manage.js`, `youtube-embed.js`)

| Scénario                                                                                        | Vérification                                                                                                               | Prio | Statut             |
| ----------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------- | ---- | ------------------ |
| Arrivée sur la page                                                                             | **Aucune lecture automatique** (`pendingAutoplay`)                                                                         | P1   | ✅ `music.spec.js` |
| Clic sur un morceau de la playlist                                                              | Lecture lancée, morceau marqué actif                                                                                       | P1   | ✅ `music.spec.js` |
| Modale "Gérer la setlist" : ajout d'un morceau                                                  | Morceau ajouté dans la modale **et** dans `#playlist`, **sans rechargement** (marqueur posé sur `window` toujours présent) | P1   | ✅ `music.spec.js` |
| Idem : monter / descendre / supprimer                                                           | Ordre mis à jour en place, modale toujours ouverte                                                                         | P1   | ✅ `music.spec.js` |
| Après un ajout, clic sur un morceau                                                             | Le lecteur fonctionne toujours (délégation d'événements après `innerHTML`)                                                 | P1   | ✅ `music.spec.js` |
| Rond jaune "Envoyer"                                                                            | Ouvre la même modale                                                                                                       | P2   | ✅ `music.spec.js` |
| Badge YouTube (visiteur non connecté)                                                           | Overlay vidéo ouvert, fermable au clavier                                                                                  | P2   |                    |
| Position du lecteur sur le meuble à 1280×800, 1835×901, 2200×700, 1920×1080 + redimensionnement | `left/top/width/height` du lecteur à quelques px des valeurs de référence                                                  | P3   |                    |
| Mobile (<700px)                                                                                 | Pas de canvas 3D, lecteur utilisable                                                                                       | P2   |                    |
| Espace membres, longue liste de voix                                                            | La liste défile (pas rognée)                                                                                               | P3   |                    |

## 6. Agenda `/schedule` (`schedule.js`)

| Scénario                                                   | Vérification                                                    | Prio | Statut |
| ---------------------------------------------------------- | --------------------------------------------------------------- | ---- | ------ |
| Arrivée sans ancre                                         | Le mois courant est affiché                                     | P2   |        |
| Arrivée sur `/schedule#month-09`                           | Le mois de septembre est ouvert directement                     | P2   |        |
| Mois suivant / précédent, puis bouton Retour du navigateur | Cohérence entre l'ancre et le mois affiché                      | P2   |        |
| Mobile (<700px)                                            | Pas de 3D, contenu en flux normal                               | P2   |        |
| Cadrage de l'overlay sur la scène                          | À traiter après passage en "vraie projection" (cf. `CLAUDE.md`) | P3   |        |

## 7. Histoire `/story` (`story.js`, `minisite.js`)

| Scénario                                 | Vérification                                                              | Prio | Statut                                                   |
| ---------------------------------------- | ------------------------------------------------------------------------- | ---- | -------------------------------------------------------- |
| Clic sur la mascotte dansante (1280×800) | **Pas** de redirection vers un espace fichiers (régression du 2026-09-12) | P1   | ⚠️ `story.spec.js` en `fixme` (bug ouvert, cf. plus bas) |
| Clic sur la soucoupe                     | Redirection vers `/join`                                                  | P2   |                                                          |
| Iframe `#miniSite`                       | Chargée, sans navbar (mode iframe)                                        | P2   |                                                          |
| Mobile (<700px)                          | Redirection vers le minisite, avec sa petite navbar                       | P2   |                                                          |
| Formulaire de contact du minisite        | Envoi en `fetch`, résultat affiché sans quitter la page                   | P2   |                                                          |

Note : pour les clics 3D, viser des coordonnées fixes à une taille de fenêtre fixe et vérifier la **destination** (`page.waitForURL`), pas le pixel.

## 8. Contact `/contact` (`contact.js`)

| Scénario                                    | Vérification                                                                           | Prio | Statut |
| ------------------------------------------- | -------------------------------------------------------------------------------------- | ---- | ------ |
| Envoi valide (attendre le délai anti-robot) | Message de succès affiché, pas de navigation                                           | P2   |        |
| Envoi trop rapide / honeypot rempli         | Message d'erreur affiché proprement                                                    | P2   |        |
| Widgets HelloAsso                           | Non testé (service externe, à bloquer via `page.route` pour ne pas dépendre du réseau) |      |        |

## 9. Administration

| Scénario                                            | Vérification                                                                                                     | Prio | Statut |
| --------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------- | ---- | ------ |
| `/admin/valid` : "Tout sélectionner"                | Toutes les cases cochées, compteur à jour (`registration-bulk.js`)                                               | P2   |        |
| Validation groupée                                  | (PHPUnit)                                                                                                        |      |        |
| Édition d'une section Histoire / d'une note         | L'éditeur Markdown se charge, un bouton de la barre d'outils (gras) modifie le texte, la valeur est bien soumise | P2   |        |
| Compta : devis/facture, ajouter / retirer une ligne | Les lignes apparaissent, indices corrects, le formulaire s'enregistre (`document-lines.js`)                      | P2   |        |
| Compta : choix d'un client                          | Adresse et contact pré-remplis, toujours modifiables (`client-autofill.js`)                                      | P2   |        |

## 10. Accessibilité automatisée (optionnel)

- `@axe-core/playwright` sur chaque page publique + `/desk` + `/desk/files/music` : 0 violation `serious`/`critical`.
- Nouvelle dépendance de dev : à valider avant ajout.

## Ordre de mise en œuvre conseillé

1. Prérequis (section 0).
2. ✅ Tous les **P1** : `files.spec.js`, `music.spec.js`, `join.spec.js`, `story.spec.js` (une spec par fonctionnalité plutôt que par scénario).
3. Les **P2**, une spec par fonctionnalité.
4. Les **P3** si le temps d'exécution en CI reste raisonnable (objectif : sous 2 minutes au total).

## Bugs trouvés par ces tests

- ✅ **Dépôt refusé sans message** (`quick-upload.js`) : `var status` dans un script classique, c'est `window.status`, qui transforme tout en texte. Plantage `status.appendChild is not a function`, aucun message affiché. Variable renommée.
- ✅ **Échec de connexion invisible** (`join.js`) : le message d'erreur était dans la popup, fermée après le rechargement. Elle se rouvre maintenant seule. Le focus allait aussi sur « fermer » au lieu du 1er champ.
- ⚠️ **Clic sur la mascotte de `/story` (ouvert)** : à (95, 590) en 1280×800, le rayon ne touche jamais la mascotte (modèle animé, probablement testé dans sa pose de repos par three r128). Le 1er objet touché est la boîte invisible `trieurAccounting`, ce qui envoie vers `/desk/files/accounting` (puis `/join` pour un visiteur). Le correctif du 2026-09-12 ne couvrait que le cas où la mascotte est touchée. Test en `fixme` en attendant.

## Durée des tests musique (~30 s chacun)

Mesuré le 2026-10-14, sans corriger :

- **Cause :** dès que les modèles 3D de `/music` sont chargés, le navigateur headless (rendu WebGL logiciel, pas de GPU) est saturé. Ouvrir la modale et remplir un champ prend environ 8 s, contre moins d'1 s pour charger la page.
- **Bloquer les `.gltf` ne marche pas :** `#uploadNew` n'est positionné qu'une fois le meuble 3D chargé, donc il n'apparaît jamais.
- **Pistes si ça devient gênant :** un mode "sans 3D" pour les tests (comme le mode mobile, qui n'initialise pas la scène), ou un viewport plus petit.

# Documentation BigBinioufous

Docs courtes, une par partie du projet, pour se remettre dans le code vite. Chacune liste surtout les **gotchas / trucs pas évidents** plutôt que de réexpliquer ce que le code montre déjà.

- [role.md](role.md) : les rôles utilisateurs (`ROLE_ADMIN`, `ROLE_COMPTA`...), qui a accès à quoi, comment un rôle est attribué.
- [entities.md](entities.md) : le modèle de données Doctrine (`User`, `Role`, `Instrument`, `Artist`, `Track`, `PasswordUpdate`) et leurs relations.
- [controllers.md](controllers.md) : toutes les routes, par contrôleur, + où le contrôle d'accès est (et n'est pas) vérifié.
- [forms.md](forms.md) : les types de formulaires, ce qui est dupliqué (`AccountType`/`EditUserType`), le pattern d'upload de fichier.
- [security.md](security.md) : authentification, `access_control`, comment les rôles sont obtenus.
- [style.md](style.md) : frontend : **les deux systèmes d'icônes** (Font Awesome vs SVG bootstrap-icons collés à la main), palette de couleurs, entries Webpack Encore.
- [i18n.md](i18n.md) : traduction (fr/en/br), sélecteur de langue, comment ajouter une clé, limite du check CI sur le breton.

Vue d'ensemble stack/installation/structure : voir le [README.md](../README.md) à la racine.

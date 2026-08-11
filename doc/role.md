# Rôles utilisateurs

Résumé des rôles définis dans l'application (voir `src/DataFixtures/AppFixtures.php`, `src/Entity/User.php`, `config/packages/security.yaml`).

| Rôle | Description (fixtures) | Accès / droits observés dans le code |
|---|---|---|
| `ROLE_ADMIN` | Administrator | Accès à `/admin/*` (`security.yaml`). Peut valider les inscriptions (`AdminController::validUser`), attribuer les rôles Admin et Comptable (`addAdminRole`, `addAccountantRole`), voit toutes les listes d'utilisateurs (admins, comptables, binioufous, membres, simples) sur le desk, accède au menu "Songs" et "Admin". |
| `ROLE_COMPTA` | Accountant (comptable) | Accès à une page dédiée `accountant` depuis le desk. Attribué via `AdminController::addAccountantRole`. |
| `ROLE_BINIOUFOUS` | Binioufous (membres de l'association/groupe) | Voit la liste des Binioufous sur le desk, accès au menu "Songs". C'est l'un des deux souhaits (`wish`) possibles à l'inscription avec `ROLE_MEMBER`. |
| `ROLE_MEMBER` | Member | Accès aux mailing-lists souscrites et aux chansons favorites sur le desk. Deuxième "souhait" possible à l'inscription. |
| `ROLE_SIMPLE` | Simple | Accès basique : mailing-lists souscrites uniquement. Rôle par défaut pour les "simples utilisateurs" (souhait `Simple`). |
| `ROLE_USER` | User | Rôle de base attribué automatiquement à **tout** utilisateur authentifié (`User::getRoles()` l'ajoute toujours). Donne accès à `/desk` (`security.yaml`). |

## Fonctionnement général

- Un utilisateur choisit un **souhait** (`wish`) à l'inscription : `Administrator`, `Binioufous`, `Member` ou `Simple`.
- Tant que le compte n'est pas validé (`validation = false`), un message d'attente s'affiche sur le desk.
- Un administrateur valide l'inscription (`/admin/{wish}/{slug}/valid`), ce qui attribue le rôle correspondant au souhait (`Role::findOneByDescription($wish)`).
- `ROLE_ADMIN` et `ROLE_COMPTA` ne sont pas choisis à l'inscription : ils sont accordés manuellement par un admin via `/admin/setadmin/{slug}` et `/admin/setaccountant/{slug}`.
- `ROLE_USER` est implicite et cumulé avec tous les autres rôles.

## Points d'attention relevés

- ~~Incohérence de casse/orthographe entre les rôles stockés et ceux testés dans les templates~~ : corrigé dans `templates/desk/index.html.twig` (`ROLE_BINOUFOUS` → `ROLE_BINIOUFOUS`, `ROLE_SIMPLE` → `ROLE_Simple`).
- ~~`ROLE_Simple` était le seul rôle stocké en casse mixte, tous les autres étant en MAJUSCULES (`ROLE_ADMIN`, `ROLE_COMPTA`...)~~ : corrigé le 2026-08-11, renommé en `ROLE_SIMPLE` dans `AppFixtures.php` et `templates/desk/index.html.twig` (les deux seuls endroits où la chaîne apparaissait). Fonctionnellement neutre (comparaison de chaîne exacte des deux côtés), pur alignement de convention.

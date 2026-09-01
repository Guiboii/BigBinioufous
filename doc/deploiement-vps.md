# Déploiement VPS

Guide pour paramétrer le VPS à la main (setup one-shot, cf. ROADMAP.md Phase 3), avant que le pipeline CI (`.github/workflows/pipeline.yml`, job `deploy`) puisse déployer automatiquement à chaque push sur `master`.

## 0. Choisir le VPS

- OS recommandé : **Debian 12** ou **Ubuntu 24.04 LTS** (paquets à jour, support long terme, correspond à l'environnement de dev).
- Taille : le site est léger (pas de build Node en prod, cf. AssetMapper), un VPS d'entrée de gamme (1 vCPU / 1-2 Go RAM) suffit largement pour une petite assos.

## 1. Première connexion et sécurisation de base

L'hébergeur donne un accès `root` avec mot de passe par mail/interface. À faire immédiatement :

```bash
ssh root@IP_DU_VPS
apt update && apt upgrade -y
```

**Créer un utilisateur de déploiement dédié**, jamais `root` directement (principe de moindre privilège) :

```bash
adduser binioufous-deploy
usermod -aG sudo binioufous-deploy
```

Choisis un mot de passe fort pour ce compte et note-le dans un gestionnaire de mots de passe, jamais en clair dans un fichier du repo ou dans ce doc.

## 2. Clé SSH (à faire depuis ta machine, pas sur le VPS)

Génère une paire de clés **dédiée à ce VPS** (ne réutilise pas ta clé GitHub personnelle) :

```bash
ssh-keygen -t ed25519 -C "binioufous-vps-deploy" -f ~/.ssh/binioufous_vps
```

Copie la clé publique sur le VPS :

```bash
ssh-copy-id -i ~/.ssh/binioufous_vps.pub binioufous-deploy@IP_DU_VPS
```

Vérifie que la connexion par clé fonctionne avant de couper le mot de passe :

```bash
ssh -i ~/.ssh/binioufous_vps binioufous-deploy@IP_DU_VPS
```

Puis, sur le VPS (`/etc/ssh/sshd_config`), désactive l'authentification par mot de passe et le login root en SSH :

```
PermitRootLogin no
PasswordAuthentication no
```

```bash
sudo systemctl restart sshd
```

## 3. Pare-feu

```bash
sudo apt install ufw -y
sudo ufw allow OpenSSH
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

## 4. Dépendances serveur

Adapter le numéro de version PHP à ce que fournit la distrib (VPS OVH actuel : Ubuntu 25.04, PHP `8.4`). `composer.json` demande `^8.1`, toute version 8.1+ convient. Node n'est **pas** nécessaire en prod (cf. AssetMapper, section Frontend de `CLAUDE.md`).

```bash
sudo apt install -y php8.4-fpm php8.4-mysql php8.4-mbstring php8.4-xml php8.4-curl php8.4-zip php8.4-intl php8.4-gd \
  mysql-server nginx composer git unzip
```

**Piège rencontré le 2026-08-31** : `php8.4-mysql` (extension PDO MySQL) oublié au premier passage : l'appli renvoie un 500 `could not find driver` et `doctrine:*` échoue avec le même message. À installer explicitement, puis `sudo systemctl restart php8.4-fpm`.

## 5. Base de données

MySQL 8.4 côté VPS OVH. `sudo mysql` fonctionne sans mot de passe (auth_socket root).

```bash
sudo mysql_secure_installation   # facultatif mais recommandé
sudo mysql
```

```sql
CREATE DATABASE binioufous CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'binioufous'@'127.0.0.1' IDENTIFIED BY 'MOT_DE_PASSE';
GRANT ALL PRIVILEGES ON binioufous.* TO 'binioufous'@'127.0.0.1';
FLUSH PRIVILEGES;
```

- Host `127.0.0.1` (pas `localhost`) pour coller au `DATABASE_URL` (connexion TCP).
- Génère le mot de passe avec **`openssl rand -hex 24`** (hexa pur) et non `base64` : une valeur base64 peut contenir `:`/`#`/`/`/`+` qui cassent le parsing de l'URL `DATABASE_URL` (le `#` surtout, qui tronque tout ce qui suit). Bug rencontré le 2026-08-31.

## 6. Cloner le repo

Le repo GitHub est **public** : `git clone`/`git pull` en HTTPS, aucune clé de déploiement nécessaire (ne pas suivre les vieilles instructions "Deploy keys").

```bash
sudo mkdir -p /var/www/binioufous
sudo chown binioufous-deploy:binioufous-deploy /var/www/binioufous
cd /var/www/binioufous
git clone https://github.com/Guiboii/BigBinioufous.git .
```

`master` est la seule branche de prod (l'ancienne branche vitrine `prod_vitrine`, qui coupait l'espace membre, a été fusionnée puis supprimée).

## 7. Fichiers `.env`

Ce projet **gitignore `.env`** (non standard : d'habitude `.env` est versionné avec les défauts de dev). Sur un clone neuf il n'existe donc pas, et `config/bootstrap.php` refuse de démarrer sans lui (`Unable to read the ".env" environment file`, 500). Il faut créer **deux** fichiers à la main (les deux gitignorés) :

`.env` (socle, sans secret) :
```env
APP_ENV=prod
APP_SECRET=change_me
```

`.env.prod.local` (secrets réels, chargé après et écrase `.env`) :
```env
DATABASE_URL="mysql://binioufous:MOT_DE_PASSE@127.0.0.1:3306/binioufous?serverVersion=8.4.0"
APP_SECRET=GENERE_AVEC_openssl_rand_hex_16
MAILER_DSN=smtp://contact%40binioufous.fr:MOT_DE_PASSE_MAILBOX@ssl0.ovh.net:465
CONTACT_EMAIL=contact@binioufous.fr
```

`MAILER_DSN` : boîte OVH (offre MX Plan liée au domaine). Identifiant SMTP = l'adresse complète, le `@` s'encode en `%40` ; si le mot de passe contient des caractères spéciaux (`@ : / # ? % &`), les url-encoder aussi. Sans `MAILER_DSN` valide, l'appli démarre quand même (résolu seulement à l'envoi) mais aucun mail (contact, inscription, validation) ne part.

## 8. Premier déploiement manuel

**Il n'y a aucun fichier de migration dans le repo** (`migrations/` ne contient qu'un `.gitignore` vide). Le schéma se crée donc directement depuis les entités, pas via `doctrine:migrations` :

```bash
cd /var/www/binioufous
composer install --no-dev --no-progress --prefer-dist --optimize-autoloader
php bin/console doctrine:schema:create
php bin/console doctrine:schema:validate      # doit dire "in sync"
php bin/console importmap:install             # peuple assets/vendor/ (gitignoré), sinon 500 "jquery vendor asset is missing"
php bin/console asset-map:compile
php bin/console cache:clear
```

Puis insérer les 3 rôles (les fixtures sont `require-dev`, indisponibles avec `--no-dev`) :

```bash
php bin/console dbal:run-sql "INSERT INTO role (title, description) VALUES ('ROLE_ADMIN','Administrator'),('ROLE_COMPTA','Accountant'),('ROLE_BINIOUFOUS','Binioufous')"
```

## 8bis. Contenu vitrine (Histoire + planning)

Les pages `/story` et `/schedule` lisent leur contenu en base (`story_section`, `event`), vide sur un clone neuf. Une commande dédiée le sème, **idempotente** (ne réinsère pas ce qui existe déjà, n'écrase jamais ce qui a été édité via `/admin`) :

```bash
php bin/console app:seed-content
```

À lancer **une seule fois** après le premier déploiement. Volontairement **pas** dans le job CI `deploy` : le rejouer à chaque push re-créerait une section supprimée depuis `/admin`.

## 8ter. Pool PHP-FPM dédié

Pour que l'appli tourne sous `binioufous-deploy` (même compte que les `git pull` de la CI, pas de souci de permissions sur `var/`), un pool FPM séparé plutôt que le pool `www-data` global partagé avec les autres vhosts du VPS :

```ini
# /etc/php/8.4/fpm/pool.d/binioufous.conf
[binioufous]
user = binioufous-deploy
group = binioufous-deploy
listen = /run/php/php8.4-fpm-binioufous.sock
listen.owner = www-data
listen.group = www-data
pm = dynamic
pm.max_children = 10
pm.start_servers = 2
pm.min_spare_servers = 1
pm.max_spare_servers = 3
php_admin_value[upload_max_filesize] = 200M
php_admin_value[post_max_size] = 210M
```

```bash
sudo systemctl restart php8.4-fpm
```

Les valeurs `upload_max_filesize`/`post_max_size` reprennent le besoin de l'espace musique (cf. `CLAUDE.md`, gros fichiers audio/vidéo).

## 9. Vhost Nginx

```nginx
server {
    listen 80;
    server_name binioufous.fr www.binioufous.fr;
    root /var/www/binioufous/public;

    location / {
        try_files $uri /index.php$is_args$args;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/run/php/php8.4-fpm-binioufous.sock;   # socket du pool dédié (section 8ter)
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $document_root;
        internal;
    }

    location ~ \.php$ {
        return 404;
    }

    # Empêche l'exécution de PHP dans les fichiers uploadés (équivalent Nginx du
    # public/uploads/.htaccess pensé pour Apache, cf. CLAUDE.md phase sécurité).
    location ^~ /uploads/ {
        location ~ \.php$ {
            deny all;
        }
    }

    error_log /var/log/nginx/binioufous_error.log;
    access_log /var/log/nginx/binioufous_access.log;
}
```

```bash
sudo ln -s /etc/nginx/sites-available/binioufous /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

## 10. HTTPS (Let's Encrypt)

```bash
sudo apt install certbot python3-certbot-nginx -y
sudo certbot --nginx -d binioufous.fr -d www.binioufous.fr
```

Une fois HTTPS actif : décommenter/ajouter `Strict-Transport-Security` dans `config/packages/nelmio_security.yaml` (volontairement pas encore posé, cf. `CLAUDE.md`, n'a d'effet qu'une fois HTTPS réellement actif).

## 11. Secrets GitHub Actions (pour le déploiement automatique)

Repo GitHub → Settings → Secrets and variables → Actions → New repository secret :

| Secret | Valeur |
|---|---|
| `VPS_HOST` | IP ou domaine du VPS |
| `VPS_USER` | `binioufous-deploy` |
| `VPS_SSH_KEY` | contenu de la clé **privée** dédiée (`~/.ssh/binioufous_ci`), pas la `.pub`. Sa `.pub` est dans `~binioufous-deploy/.ssh/authorized_keys` sur le VPS. Sert au runner GitHub à se connecter en SSH sur le VPS (rien à voir avec l'accès Git, le repo étant public). |
| `VPS_PATH` | `/var/www/binioufous` |

Une fois posés, tout push sur `master` déclenche le job `deploy` du pipeline : `git fetch`/`checkout master`/`pull`, puis `composer install --no-dev`, `importmap:install`, `asset-map:compile`, `cache:clear`.

Le job ne joue **pas** `doctrine:migrations:migrate` : le repo ne versionne aucune migration et le schéma est posé une fois via `doctrine:schema:create` (section 8). Des fichiers `migrations/Version*.php` traînant sur le disque d'un vieux clone feraient d'ailleurs échouer `migrate` (rejeu sur un schéma déjà complet, `Duplicate column name`) : les supprimer du VPS le cas échéant. Les évolutions de schéma se font à la main (`doctrine:schema:update --force --complete` après revue du `--dump-sql`).

## Checklist rapide

- [ ] Utilisateur `binioufous-deploy` créé, accès `sudo`
- [ ] Connexion SSH par clé uniquement (mot de passe + root désactivés)
- [ ] Pare-feu actif (22, 80, 443)
- [ ] PHP 8.4 (dont `php8.4-mysql`), MySQL, Nginx, Composer installés
- [ ] Pool PHP-FPM dédié `binioufous` (section 8ter), tournant sous `binioufous-deploy`
- [ ] Base de données créée (user `@127.0.0.1`, mot de passe `openssl rand -hex 24`), identifiants notés
- [ ] Repo cloné (HTTPS), `.env` **et** `.env.prod.local` créés (jamais commités)
- [ ] Fichiers `migrations/Version*.php` traînants supprimés du VPS (sinon `migrate` casse ; le job n'y touche plus mais un `migrate` manuel oui)
- [ ] Schéma créé via `doctrine:schema:create` (pas de migrations dans le repo), `importmap:install` lancé
- [ ] 3 rôles insérés, `app:seed-content` lancé une fois (Histoire + planning)
- [ ] vhost nginx pointant sur `/var/www/binioufous/public` + socket du pool dédié
- [ ] Premier déploiement manuel réussi, site accessible
- [ ] HTTPS actif (certbot)
- [ ] 4 secrets GitHub ajoutés (`VPS_HOST`, `VPS_USER`, `VPS_SSH_KEY`, `VPS_PATH`)
- [ ] `Strict-Transport-Security` activé une fois HTTPS confirmé

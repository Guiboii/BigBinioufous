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

```bash
sudo apt install -y php8.3 php8.3-fpm php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip php8.3-intl php8.3-gd \
  mysql-server nginx composer git unzip
```

`composer.json` demande PHP `^8.1`, n'importe quelle version 8.1+ des paquets Debian/Ubuntu convient (Node n'est **pas** nécessaire en prod, cf. AssetMapper, section Frontend de `CLAUDE.md`).

## 5. Base de données

```bash
sudo mysql_secure_installation
sudo mysql -u root -p
```

```sql
CREATE DATABASE binioufous CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'binioufous'@'localhost' IDENTIFIED BY 'MOT_DE_PASSE_A_GENERER';
GRANT ALL PRIVILEGES ON binioufous.* TO 'binioufous'@'localhost';
FLUSH PRIVILEGES;
```

Génère le mot de passe avec `openssl rand -base64 24` par exemple, note-le dans le même gestionnaire que le reste (il ira dans `.env.prod.local`, jamais commité).

## 6. Cloner le repo

```bash
sudo mkdir -p /var/www/binioufous
sudo chown binioufous-deploy:binioufous-deploy /var/www/binioufous
cd /var/www/binioufous
git clone git@github.com:Guiboii/BigBinioufous.git .
```

Pour que le `git clone`/`git pull` fonctionne en SSH sans mot de passe, ajoute une clé de déploiement GitHub (Settings → Deploy keys du repo, lecture seule suffit) ou utilise une clé perso déjà autorisée.

Pour l'instant, checkout la branche `prod_vitrine` (espace membre coupé, pas de mailer nécessaire) :

```bash
git checkout prod_vitrine
```

## 7. `.env.prod.local`

Jamais commité (cf. `.gitignore`). À créer à la main sur le VPS :

```bash
nano /var/www/binioufous/.env.prod.local
```

```env
APP_ENV=prod
APP_SECRET=GENERE_UNE_VALEUR_ALEATOIRE
DATABASE_URL="mysql://binioufous:MOT_DE_PASSE@127.0.0.1:3306/binioufous?serverVersion=5.7"
MAILER_DSN=null://null
```

- `APP_SECRET` : `openssl rand -hex 16`.
- `MAILER_DSN` reste sur `null://null` tant que la mailbox OVH n'est pas configurée (cf. `CLAUDE.md`, phase "Emails fonctionnels") : cohérent avec `prod_vitrine`, qui coupe justement toute fonctionnalité dépendant du mail.

## 8. Premier déploiement manuel

```bash
cd /var/www/binioufous
composer install --no-dev --no-progress --prefer-dist --optimize-autoloader
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console asset-map:compile
php bin/console cache:clear
```

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
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
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
| `VPS_SSH_KEY` | contenu de `~/.ssh/binioufous_vps` (clé **privée**, celle générée à l'étape 2, jamais la `.pub`) |
| `VPS_PATH` | `/var/www/binioufous` |

Une fois posés, tout push sur `master` **ou `prod_vitrine`** déclenche le job `deploy` du pipeline, qui déploie la branche poussée (`git fetch`/`checkout`/`pull` sur `github.ref_name`, puis `composer install --no-dev`, migrations, `asset-map:compile`, `cache:clear`).

Le clone du VPS suit donc la dernière branche déployée : tant que `prod_vitrine` est la branche de prod, c'est elle qui tourne sur le VPS. Après fusion dans `master`, un push sur `master` rebascule le VPS dessus.

## Checklist rapide

- [ ] Utilisateur `binioufous-deploy` créé, accès `sudo`
- [ ] Connexion SSH par clé uniquement (mot de passe + root désactivés)
- [ ] Pare-feu actif (22, 80, 443)
- [ ] PHP 8.1+, MySQL, Nginx, Composer installés
- [ ] Base de données créée, identifiants notés dans un gestionnaire de mots de passe
- [ ] Repo cloné, `.env.prod.local` créé (jamais commité)
- [ ] Premier déploiement manuel réussi, site accessible en HTTP
- [ ] HTTPS actif (certbot)
- [ ] 4 secrets GitHub ajoutés
- [ ] `Strict-Transport-Security` activé une fois HTTPS confirmé

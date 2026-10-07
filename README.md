# Solaccess - Application de gestion de serrures connectées

Solaccess est une application Open Source de gestion de serrures connectées. L'application se découpe en deux parties :

- le firmware des serrures qui intéragit avec le backoffice (partie suivante) afin de définir les permissions, transmettre des informations sur l'état de la serrure ...
- le backoffice permettant de récupérer les informations des serrures envoyées par le firmware en les affichant d'une manière "user friendly".

## Stack technique

- [PHP](https://php.net) version 8.4+
- [Symfony](https://symfony.com/) version 7.4
- [Composer](https://getcomposer.org) version 2.1

## Installation web local

Clonage du projet avec [Git](https://git-scm.com/ sous Windows) :

`git clone https://github.com/numelib/solaccess-backend.git`

**Configuration :**

Créer un fichier `.env.local` à la raçine du projet avec le contenu suivant :

```bash
APP_ENV="dev"

# (optionnel) Ces variables d'environement servent a initialiser la base de données du conteneur Docker et peuvent être aussi définies au lancement des commandes Docker.
# Exemple avec Bash: "DATABASE_ROOT_PASSWORD="root" docker compose build"
# Voir la documentation de votre shell pour plus d'informations.
DATABASE_ROOT_PASSWORD="root"
DATABASE_NAME="solaccess"
DATABASE_USER="solaccess"
DATABASE_PASSWORD="password"

DATABASE_URL="mysql://${DATABASE_USER}:${DATABASE_PASSWORD}@mysql:3306/${DATABASE_NAME}?serverVersion=16&charset=utf8"

# Email de l'envoyeur utilsé pour l'envoi d'alertes en cas de disfonctionnement d'une serrure OU pour envoi de logs de la librairie "symfony/monolog-bundle"
# Voir fichiers "config/packages/monolog.yaml" ou "config/packages/dev/monolog.yaml" pour plus d'informations
ADMIN_EMAIL="support@acme.com"
# Destinataire des mails envoyés par la librairie "symfony/monolog-bundle".
# Voir fichiers "config/packages/monolog.yaml" ou "config/packages/dev/monolog.yaml" pour plus d'informations
MAILER_RECIPIENT="support@acme.com"
```

**Création du super admin :**
```bash
php bin/console app:create-user
```
ROLE_ADMIN: super utilisateur avec toutes les permissions nécessaires pour en créer d'autres.

ROLE_USER: utilisateur simple avec accès à son ou ses contrôleur(s) ESP32 uniquement. Fonctionnalité non implémentée actuellement.

## Installation Docker

**A venir !**


# Solaccess - Application de gestion de serrures connectées

Solaccess est une application Open Source de gestion de serrures connectées. L'application se découpe en deux parties :

- le firmware des serrures qui intéragit avec le backoffice (partie suivante) afin de définir les permissions, transmettre des informations sur l'état de la serrure ...
- le backoffice permettant de récupérer les informations des serrures envoyées par le firmware en les affichant d'une manière "user friendly".

## Stack technique

- [PHP](https://php.net) version 8.4
- [Symfony](https://symfony.com/) version 7.4
- [Composer](https://getcomposer.org) version 2.1

## Installation

### En local

Commencez tout d'abord en clonant le projet avec [Git](https://git-scm.com/) :

`git clone https://gitlab.anybug.net:2443/dev/solaccess.git`

#### Docker

**Prérequis :**

Avoir installé les outils suivants :

- [Docker Engine](https://docs.docker.com/engine/install) en [mode "rootless"](https://docs.docker.com/engine/security/rootless)
- (recommandé) [Just](https://github.com/casey/just)

**Notes :**

Cette configuration est normalement la plus simple mais rend l'utilisation parfois plus longue et fastidieuse que la normale.

Par exemple, étant donné que la base de données est dans un conteneur, vous ne pouvez pas y accéder aussi facilement que si elle était installée directement sur la machine hôte.

Afin de remédier à cela, des commandes spéciales ont été rajoutées dans le fichier `.justfile` pour simplifier certaines manipulations.

**Configuration :**

Créez un fichier `.env.local` à la raçine du projet avec le contenu suivant :

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

**Avec [Just](https://github.com/casey/just) :**

Lancez les commandes suivantes :

```bash
# Construit le conteneur Docker de développement
just build-dev
# Lance le conteneur Docker de développement
just run-dev
# Installe les dépendances avec Composer dans le conteneur
just install-deps
# Lance les migrations Doctrine dans le conteneur
just migrate
# Lance les fixtures Doctrine dans le conteneur
just fixtures-load
```

(voir fichier `.justfile` à la raçine du projet pour plus d'informations)

Accédez ensuite à l'application à l'adresse : [http://localhost:8000](http://localhost:8000)

**Sans [Just](https://github.com/casey/just) :**

Référez-vous aux commandes définies dans le fichier `.justfile` à la racine du projet. Plus d'informations sur la syntaxe de ce fichier et son fonctionnement dans la [documentation de Just](https://just.systems/man/en)

### En production

> A faire

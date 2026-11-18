# Integration optionnelle Numelib / Teable

Ce repertoire contient exclusivement **l'adaptateur Numelib**. Ce n'est ni le coeur
de Solaccess, ni un framework universel de frontends. L'intranet Numelib et le
portail utilisateur Teable ne font **pas** partie de ce depot open source.

## Frontiere d'architecture

- **Solaccess Core** (`src/Entity/User.php`, `src/Service/Contacts/`,
  `src/Service/Credentials/`, `src/Controller/Admin/`,
  `src/Controller/Api/Hardware/`) : comptes, ESP32, serrures, contacts,
  credentials, permissions metier et administration technique.
- **Integration Numelib** (ce repertoire) : communication serveur-a-serveur avec
  le frontend Numelib, identifiants Teable `rec...`, controles des secrets,
  liaison du compte externe et representation JSON du snapshot.

Une installation Solaccess self-hosted **mono-administrateur** utilise `/admin`
et `/api/device/**` sans la moindre dependance au portail Numelib.

## Activation

Les routes de cette integration sont decouvertes via le `routing.controllers` de Symfony 7.4,
mais sont **non exploitables** sans configuration de secrets :

- `PORTAL_BRIDGE_SHARED_SECRET` : lecture utilisateur ;
- `PORTAL_BRIDGE_WRITE_SECRET` : ecriture utilisateur ;
- `PORTAL_BRIDGE_ADMIN_SECRET` et `PORTAL_BRIDGE_ADMIN_USER_IDS` : gestion
  administrative des liaisons, avec controle OTP et role admin sur le serveur
  Teable.

En l'absence de secrets, les verificateurs de la passerelle refusent l'acces.
Ne jamais communiquer ces secrets au navigateur, ni les confondre avec un
token d'API ESP32. Le serveur Teable reste responsable de verifier la session
OTP et le role Numelib avant d'emettre les headers `X-Numelib-*`.

## Compatibilite et stockage

Cette organisation ne change **aucune URL, aucun nom de route, aucune table SQL
ni aucun payload JSON**. L'entite Doctrine optionnelle
`Entity/SolaccessPortalAccountLink.php` est mappee a la table existante
`solaccess_portal_account_link` via `config/packages/doctrine.yaml`.

`Service/PortalAccountLinkManager.php` est utilise aussi par la commande CLI
`app:solaccess:portal-link`, de sorte que la logique de liaison ne soit pas
dupliquee. Les services metier Contacts/Credentials restent independants de
Numelib et reutilisables par d'autres interfaces a l'avenir.

Cette version **n'est pas** encore un bundle Composer installable/desinstallable.
Une installation autonome peut avoir la table de liaison inutilisee : le
deploiement est optionnel au niveau fonctionnel, pas au niveau du schéma.

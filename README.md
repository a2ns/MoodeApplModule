# MoodeApplModule — Module Apple Music pour moOde

Module [moOde audio player](https://github.com/moode-player/moode) permettant d'utiliser votre bibliothèque **Apple Music** directement dans moOde :

- 🎧 **Écouter vos playlists** Apple Music
- 🔎 **Rechercher** de nouveaux morceaux, albums et artistes dans le catalogue Apple Music
- 📚 Parcourir les morceaux de **votre bibliothèque**

> ⚠️ **Statut : en cours de développement.** L'UI et le backend sont fonctionnels dans les grandes lignes ; à tester sur un système moOde réel.

## Architecture

Le module s'installe dans `/var/local/www/applemusic/` et est servi par le serveur web moOde :

```
applemusic/
├── index.html          # UI web (playlists, recherche, lecture)
├── api/index.php       # Proxy JSON vers l'API Apple Music
├── lib/
│   ├── config.php      # Chargeur de configuration
│   ├── developer_token.php  # Génération du JWT Developer Token (ES256)
│   └── apple_client.php     # Client API Apple Music (catalog + bibliothèque)
├── config.json         # Configuration (Team ID, Key ID, clé .p8, storefront)
├── AuthKey_<KEYID>.p8  # (à fournir) Clé privée Apple
├── data/               # Cache token + media user token
└── update/install.sh   # Script d'installation
```

### Principe de fonctionnement

```
Navigateur (UI) ──► api/index.php ──► api.music.apple.com
      │                                     ▲
      └── MusicKit JS (lecture audio) ──────┘
```

1. **Backend PHP** : signe un *Developer Token* JWT (ES256) avec votre clé Apple `.p8`, et proxifie les appels API (playlists utilisateur, pistes, recherche catalogue).
2. **Navigateur** : [MusicKit JS v3](https://developer.apple.com/documentation/musickitjs) gère l'autorisation Apple Music (compte utilisateur) et la **lecture audio** (HLS) directement depuis le navigateur.
3. L'*Apple Music Media User Token* obtenu après autorisation est stocké côté moOde (`data/user_token.txt`) pour permettre l'accès aux playlists sans re-connexion.

> **Note sur la lecture** : MusicKit JS diffuse l'audio vers le navigateur (sortie audio du périphérique utilisé, ex. tablette/PC). Pour un rendu vers le DAC du Raspberry Pi, il faudrait associer le flux à une entrée moOde (ex. Bluetooth/RTL88), ce qui est une évolution future.

## Prérequis

### Côté Apple (compte développeur)

1. Un compte [Apple Developer Program](https://developer.apple.com/programs/) (99 $/an).
2. Dans [Certificates, Identifiers & Profiles → Keys](https://developer.apple.com/account/resources/authkeys/list) :
   - Créez une clé **Media Services / Apple Music API** → téléchargez `AuthKey_<KEYID>.p8`
   - Notez le **Key ID** et votre **Team ID** (page Membership).
3. Un abonnement **Apple Music** actif pour l'écoute complète des morceaux (sans abonnement, seuls des extraits de 30 s sont joués).

### Côté moOde

- moOde 8.x installé, accès SSH (root) et serveur web fonctionnel.
- `php-curl` et `php-openssl` installés (`sudo apt install php-curl` si besoin — généralement déjà présents).

## Installation

```bash
# Sur le Raspberry Pi moOde
git clone https://github.com/a2ns/MoodeApplModule.git
cd MoodeApplModule/applemusic
sudo bash update/install.sh
```

Puis :

1. Copiez votre clé Apple dans le dossier du module :
   ```bash
   sudo cp ~/AuthKey_XXXXXXXXXX.p8 /var/local/www/applemusic/
   sudo chown www-data:www-data /var/local/www/applemusic/AuthKey_*.p8
   ```
2. Éditez la configuration :
   ```bash
   sudo nano /var/local/www/applemusic/config.json
   ```
   ```json
   {
       "team_id": "XXXXXXXXXX",
       "key_id": "XXXXXXXXXX",
       "private_key_path": "/var/local/www/applemusic/AuthKey_XXXXXXXXXX.p8",
       "storefront": "FR",
       "data_dir": "/var/local/www/applemusic/data"
   }
   ```
3. Ouvrez **`http://<ip-moode>/applemusic/`** dans un navigateur (Chrome/Safari récent recommandé), cliquez sur **Connecter** et identifiez-vous avec votre compte Apple Music.

### Accès depuis l'UI moOde

Le module est accessible à l'URL `/applemusic/`. Vous pouvez l'ajouter comme favori dans le navigateur de votre appareil de contrôle ; une intégration directe dans le menu moOde est prévue.

## Configuration

| Champ | Description | Exemple |
|---|---|---|
| `team_id` | Apple Developer Team ID | `A1B2C3D4E5` |
| `key_id` | Key ID de la clé Media Services | `F6G7H8I9J0` |
| `private_key_path` | Chemin de la clé `.p8` sur le Pi | `/var/local/www/applemusic/AuthKey_XXXX.p8` |
| `storefront` | Code pays du catalogue Apple Music | `FR`, `BE`, `CA`, `US`… |
| `data_dir` | Dossier de cache (tokens) | `/var/local/www/applemusic/data` |

## API interne du module

`/applemusic/api/?action=…`

| Action | Méthode | Description |
|---|---|---|
| `status` | GET | État du module (configuré, autorisé) |
| `devtoken` | GET | Developer Token (JWT) pour MusicKit |
| `playlists` | GET | Playlists de la bibliothèque utilisateur |
| `playlist&id=` | GET | Pistes d'une playlist |
| `library` | GET | Morceaux de la bibliothèque |
| `search&term=` | GET | Recherche catalogue (morceaux/albums/artistes) |
| `authtoken` | POST | Enregistre le Media User Token |
| `logout` | POST | Oublie le Media User Token |

## Sécurité

- La clé `.p8` et le fichier de config sont en `chmod 640`, propriété `www-data`.
- Le Media User Token est stocké en `0640` côté serveur.
- Le Developer Token est régénéré/cache pendant 30 jours max.
- ⚠️ Ne commitez jamais votre clé `.p8` ni votre `config.json` rempli dans un dépôt public.

## Feuille de route

- [ ] Test complet sur moOde 8.x réel
- [ ] Intégration dans le menu latéral moOde
- [ ] Rendu audio vers le DAC moOde (via entrée ALSA loopback ou similaire)
- [ ] Support des radios Apple Music
- [ ] Ajout de morceaux du catalogue vers la bibliothèque utilisateur

## Licence

GPL-3.0 — voir [LICENSE](LICENSE). Conforme au projet [moOde](https://github.com/moode-player/moode) (GPL-3.0).

# MoodeApplModule — Apple Music Module for moOde

Un module [moOde audio player](https://github.com/moode-player/moode) permettant l'intégration d'Apple Music comme source de rendu audio.

> ⚠️ **Statut : en cours de développement.** Ce projet est en phase d'initialisation.

## Objectif

Fournir un plugin moOde qui permet de lire de la musique depuis Apple Music sur un appareil moOde, en suivant l'architecture des plugins officiels moOde (`moode-player/plugins`).

## Architecture

Le module suit la structure des plugins moOde :

```
MoodeApplModule/
├── README.md          # Ce fichier
├── LICENSE           # GPL-3.0 (conforme à moOde)
├── .gitignore
└── applemusic/       # Code du module Apple Music
    ├── README.md     # Documentation du module
    └── update/       # Scripts d'installation/mise à jour
        └── install.sh
```

## Documentation moOde utile

- [moOde audio player](https://github.com/moode-player/moode)
- [Plugins officiels moOde](https://github.com/moode-player/plugins)
- [Infrastructure audio moOde](https://github.com/moode-player/docs/blob/main/moode_audio_infrastructure.md)
- [Forum moOde — section Plugins](https://moodeaudio.org/forum/showthread.php?tid=1879)

## Licence

Distribué sous la licence [GNU General Public License v3](LICENSE), conformément au projet moOde.

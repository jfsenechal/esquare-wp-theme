
wp-content/themes/esquare/bin/build-css.sh
- Add --watch to rebuild automatically while you edit theme files.
## Machines du FabLab

Les machines sont un type de contenu WordPress, « Machines » dans le menu de
l'admin (`src/Machine/MachinePostType.php`, type `esquare_machine`). Notion
n'est plus utilisé pour elles.

Une machine se modifie comme une page :

| Dans l'éditeur | Sert à |
|---|---|
| Titre | nom de la machine |
| Image mise en avant | la photo ; une machine sans photo passe dans « Et aussi au FabLab » |
| Extrait | le texte court de la carte et le chapeau de la fiche |
| Contenu | caractéristiques techniques, liens utiles (blocs titre / liste) |
| Panneau « Infos pratiques » | tarif / conditions, lien de réservation (bouton « Réserver ») |
| Attributs › Ordre | position dans la liste (du plus petit au plus grand) |

- Liste : bloc `esquare/machines` (`src/Block/Machines.php`), section
  `#equipements` de la page Fablab.
- Fiche : `/fablab/equipements/<slug>/`, gabarit
  `templates/single-esquare_machine.html`, bloc `esquare/machine`
  (`src/Block/MachineDetail.php`). Une machine sans contenu n'a pas de lien
  vers sa fiche.
- `/fablab/equipements/` (URL de la liste sur l'ancien site) affiche la page Fablab.

### Import depuis l'ancien site

Le contenu de https://e-square.marche.be/fablab/equipements/ a été repris et
nettoyé dans `data/machines-legacy.json`. À lancer une fois par installation :

```bash
wp esquare machines import            # crée les machines et télécharge les photos
wp esquare machines import --dry-run  # montre ce qui serait fait
```

La commande peut être relancée sans risque : les machines déjà importées sont
laissées telles quelles (seule une photo manquante est retentée). `--update`
réécrit les machines importées à partir du fichier et écrase donc les
modifications faites dans WordPress.

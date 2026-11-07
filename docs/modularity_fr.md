# Choisir les fonctionnalités de Simbioza

<a id="choosing-simbioza-features"></a>


> Les captures d’écran présentent l’interface en anglais.

[Version croate](modularity_hr.md)

Simbioza réunit des pages, un wiki, des calendriers, des sondages et des outils de collaboration dans une application complète. Si vous n’avez besoin que de certaines fonctions, le framework modulaire HeartPhrame permet aussi une installation ciblée. Les profils suivants décrivent cette option supplémentaire.

**Simbioza** signifie **symbiose** en croate : des organismes différents vivant ensemble. L’application porte ce nom parce que ses modules ont des rôles distincts tout en pouvant fonctionner ensemble. Une page explique un événement du calendrier, un wiki relie les connaissances de l’équipe et un sondage recueille les avis. La version complète de Simbioza réunit ces outils, les tâches, les commentaires, les notifications et les intégrations dans un même environnement.

<a id="choose-a-purpose"></a>

## Choisir un usage

| Votre besoin | Profil d’installation | Modules fonctionnels |
| --- | --- | --- |
| Toutes les fonctions disponibles réunies | Simbioza complet | Tous les modules facultatifs |
| Un site simple avec des pages HTML | Site HTML | HTML Editor |
| Des événements, des réunions et CalDAV | Calendrier | Calendar |
| Un wiki avec recherche, organisé en espaces de travail | Wiki | Workspace, Workspace Search et Simbioza User |
| Des sondages avec questions, parcours conditionnels et résultats | Sondages | Survey |

Chaque installation comprend **Auth, ORM, Menu, Theme, Backup et Audit**. Ces modules assurent l’authentification et les droits d’accès, l’accès à la base de données, les menus, l’apparence, les sauvegardes et les journaux d’activité. « Autonome » signifie sans les autres modules métier, mais avec la base commune et les dépendances nécessaires.

Le programme d’installation ajoute automatiquement les dépendances nécessaires. Par exemple, Survey utilise HTML Editor pour les introductions et les explications ; le wiki a aussi besoin d’Editor, et Simbioza User ajoute les notifications. L’API, l’accessibilité et les autres intégrations facultatives n’exigent pas l’installation de fonctions métier sans rapport avec elles.

<a id="install-the-selected-tool"></a>

## Installer l’outil choisi

1. Préparez une nouvelle installation avec le [guide d’installation](installation_fr.md) adapté.
2. À l’étape des paramètres de l’application dans l’assistant, sélectionnez un **Profil d’installation** et cliquez sur **Appliquer le profil**.
3. Vérifiez les modules sélectionnés. Les dépendances nécessaires restent incluses ; ne sélectionnez des fonctions supplémentaires que si vous en avez besoin.
4. Sélectionnez les langues de l’installation et terminez la configuration. Les langues du contenu et de ses traductions sont choisies parmi les langues installées et actives.
5. Connectez-vous en tant qu’administrateur, créez votre contenu et ajoutez des liens dans les menus pour que les visiteurs puissent le trouver.

Vous obtenez une application adaptée à l’usage choisi. Les pages, calendriers et sondages d’exemple ne sont pas des données métier automatiquement fournies à chaque nouveau site ; vous créez votre contenu après l’installation.

<a id="share-a-standalone-html-website"></a>

## Partager un site HTML autonome

Un site HTML n’a pas besoin du wiki pour accueillir plusieurs auteurs ou des pages privées. En tant qu’administrateur, ouvrez **Paramètres → Éditeur HTML → Droits des pages** et ajoutez des utilisateurs ou des groupes. Le droit d’**Ajout** permet de créer et de modifier ses propres pages. Le droit de **Modification** permet de gérer toutes les pages autonomes, y compris les pages privées ; il ne donne pas accès aux paramètres d’administration.

Sur une page, ouvrez **Paramètres et droits de la page**. Choisissez une lecture publique, réservée à tous les utilisateurs connectés ou limitée aux utilisateurs et groupes sélectionnés. Ce choix protège aussi les pièces jointes, les liens directs et les lectures par API. Dans un wiki, continuez à utiliser les droits existants de l’espace de travail et de la page.

<a id="extend-an-existing-installation"></a>

## Étendre une installation existante

Vous pouvez, par exemple, ajouter un calendrier ou des sondages à un site HTML, ou des sondages, des tâches et des commentaires à un wiki. **Paramètres → Configuration et modules** indique les modules installés, ceux qui sont activés et les dépendances nécessaires.

Sur une installation FPM dédiée correctement configurée, un administrateur peut cliquer sur **Installer** à côté d’un autre module. Pour mod_php et FPM classique, la personne chargée de la maintenance exécute la commande CLI correspondante depuis la racine de l’application, avec le compte de maintenance :

```bash
php vendor/bin/hph modules add calendar --fresh
php vendor/bin/hph modules add survey --fresh
```

Ces exemples sont distincts : choisissez le module dont vous avez besoin. Le système ajoute ses dépendances nécessaires. Utilisez `task` et `comment` pour les tâches et les commentaires ; un wiki peut ensuite recevoir `confluence-import` pour importer du contenu existant. Après l’extension de l’installation, configurez les droits, les menus et les paramètres du nouveau module. L’installation d’un module ne donne pas à chaque utilisateur le droit de modifier le contenu.

Faites une sauvegarde avant de modifier la composition de l’installation. Désactiver un module facultatif et le désinstaller sont deux actions différentes : la désactivation conserve les données, tandis que la désinstallation supprime les tables et le paquet du module après la préparation d’une sauvegarde de récupération. Les six modules obligatoires ne peuvent être ni désactivés ni désinstallés.

<a id="add-workspaces-to-an-existing-html-website"></a>

### Ajouter les espaces de travail à un site HTML existant

L’ajout des espaces de travail ne déplace pas les pages HTML existantes. Elles conservent leurs adresses, leur contenu, leurs traductions, leurs pièces jointes et leurs droits autonomes. Les pages HTML autonomes et les pages des espaces de travail peuvent être utilisées ensemble, y compris par l’API.

Seul un administrateur peut déplacer une page existante vers un espace de travail :

1. Ouvrez **Paramètres → Éditeur HTML → Pages en dehors des espaces de travail**.
2. Recherchez la page et choisissez **Déplacer vers un espace de travail**.
3. Ouvrez la liste des destinations ou effectuez une recherche par nom. D’autres espaces de travail se chargent à mesure que vous faites défiler la liste.
4. Vérifiez la visibilité de la destination et confirmez ses droits, puis déplacez la page.

La page est placée au premier niveau de l’arborescence de destination. Toutes ses traductions, son historique des versions et les versions de ses pièces jointes sont déplacés avec elle. Les fichiers sont transférés vers le stockage de l’espace de travail ; le contenu HTML reste dans les mêmes enregistrements de version de la base de données, désormais rattachés au nouvel espace de travail. Les liens des pages enregistrées sont corrigés, y compris les choix de langue et les ancres. Les adresses existantes des pages et des pièces jointes restent utilisables, avec les contrôles d’accès de la destination. Les versions publiées restent publiées et les brouillons restent des brouillons.

**Les droits de la destination remplacent les droits autonomes.** Les anciens lecteurs ne sont pas automatiquement ajoutés comme exceptions. Déplacer une page publique vers un espace de travail privé peut en restreindre l’accès ; déplacer une page privée vers un espace de travail public peut rendre son contenu publié accessible à tous. Vérifiez la destination avant de confirmer. Faites d’abord une sauvegarde si vous avez besoin d’un point de retour : cet écran ne propose pas le déplacement inverse.

<a id="updates-and-backups"></a>

## Mises à jour et sauvegardes

Les nouvelles installations reçoivent des cookies de connexion indépendants, même lorsque plusieurs sites partagent le même domaine. Une restauration conserve l’identité de session locale du site cible au lieu de copier l’identité du cookie de connexion d’un autre site.

Une installation FPM dédiée avec un assistant restreint correctement configuré permet les mises à jour dans **Paramètres → Configuration et modules → Exécuter la mise à jour**. L’utilisation de FPM seule ne suffit pas : le contrôle de l’environnement doit confirmer la disponibilité des mises à jour depuis l’interface.

Pour les autres installations classiques, la personne chargée de la maintenance exécute ces commandes depuis la racine de l’application :

```bash
php update.php --check --lang=en
php update.php --lang=en
```

Les mises à jour conservent les modules sélectionnés et les données existantes. Un nouveau module facultatif devient disponible à l’installation ; il n’est pas activé automatiquement. Une sauvegarde complète comprend les données et les paramètres portables des modules activés. Conservez également une copie distincte de la configuration de l’hôte et des secrets locaux, qui ne sont pas transférés par les sauvegardes des données métier.

## Docker

Docker utilise la même sélection de fonctions. Si une image contient tous les modules, sélectionnez et activez uniquement ceux qui sont nécessaires pendant l’installation. Pour une image personnalisée plus petite, construisez-la avec les modules choisis et leurs dépendances nécessaires. L’ajout d’un module absent de l’image nécessite une nouvelle image, plutôt qu’une modification du code dans un conteneur en cours d’exécution. Les mises à jour remplacent l’image en conservant la base de données et les données d’exécution. Suivez le [guide Docker](docker_en.md).

<a id="guides-and-integration-with-other-systems"></a>

## Guides et intégration avec d’autres systèmes

Avec le wiki, ces pages se trouvent dans l’espace de travail **Guides d’utilisation**. Sans wiki, le même manuel est disponible dans **Guides**, avec une liste des chapitres et une navigation. Il n’est pas nécessaire d’installer un wiki uniquement pour lire le manuel.

L’API peut être ajoutée à chaque type d’installation. Une clé API détermine les appels autorisés sans jamais contourner les droits de son propriétaire. Les routes et les portées disponibles dépendent des modules installés et activés. Cela permet les intégrations avec d’autres systèmes et les applications HeartPhrame sur mesure, sans reconstruire l’authentification, la navigation, l’accès à la base de données et les autres fondations communes.

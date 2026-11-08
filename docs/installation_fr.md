# Choisir une installation de Simbioza

<a id="choose-a-simbioza-installation"></a>


> Les captures d’écran présentent l’interface en anglais.

[Version croate](installation_hr.md)

Ces guides installent Simbioza **0.3.46**. Voir [versions et dépendances du module](module-dependencies_en.md) pour l'inventaire actuel des packages. Le framework reste **0.0.25**. La ligne de base 0.3.x commence avec un nouveau répertoire d'application, une nouvelle configuration et une nouvelle base de données. Une mise à niveau à partir de 0.2.x ou la réutilisation de ses sauvegardes Simbioza n'est pas prise en charge.

Choisissez **un guide complet** ci-dessous. Chacun démarre sur un terminal d'administrateur, installe/vérifie les outils hôtes, télécharge la version, prépare la base de données et les autorisations, configure le serveur Web, exécute l'assistant et vérifie la première connexion. N'utilisez pas cette présentation comme une étape d'installation supplémentaire.

| Installation | Identité PHP | Préparation du package et mises à jour ultérieures de l'application | Guide complet |
|---|---|---|---|
| Apache mod_php | Compte Apache partagé | CLI, en tant que propriétaire du code | [Démarrez l'installation de mod_php](installation_mod_php_fr.md) |
| Ordinaire PHP-FPM | Compte Web système partagé dans un pool FPM | CLI, en tant que propriétaire du code | [Démarrer l'installation FPM ordinaire](installation_fpm_fr.md) |
| Sécurisé PHP-FPM (recommandé) | Comptes FPM et de déploiement dédiés, assistant restreint | Interface graphique via le travailleur, ou CLI en tant que responsable du déploiement | [Démarrer l'installation sécurisée de FPM](installation_fpm_secured_fr.md) |

Tous les trois disposent d'un **installateur initial** graphique et d'une interface graphique de configuration. Le FPM ordinaire n'est pas la même chose que le FPM sécurisé : l'ajout d'un gestionnaire FastCGI seul ne crée pas d'isolation de déploiement ni n'active les mises à jour de l'interface graphique Composer/application. Nginx nécessite FPM ; il ne peut pas exécuter le mod_php d'Apache. [Installation Docker](docker_en.md) est un route distinct et facultatif basé sur une image.

<a id="before-choosing-a-php-mode"></a>

## Avant de choisir un mode PHP

Les exemples par défaut concernent un nouvel hôte Linux ou macOS, et non un pool de production existant. Avoir un shell d'administrateur, un certificat de domaine/TLS pour un site public et un choix de base de données. SQLite ne nécessite aucun service de base de données. Chaque guide choisi répète les commandes de préparation exactes ; aucun compte Simbioza FPM/deploy existant n'est supposé.

Pour la réinstallation, l'administrateur système gère d'abord les processus et les données de l'ancien site, vérifie les identités existantes et sélectionne l'instance et le port appropriés. Les guides ne suppriment jamais une autre installation ni ne recréent un site en cours d'exécution simplement parce que le répertoire de téléchargement existe.

La configuration du module privé utilise `HPH_CONFIG_PATH` (par défaut `config/`) ; les fichiers persistants utilisent `data/`. Seul `public/` est exposé par le serveur Web.

<a id="after-installation"></a>

## Après l'installation

Configurez la [rotation des journaux](logging_en.md) pour la sortie cron, updater et FPM. Le gestionnaire de rotation `app.log` de l'application ne fait pas pivoter ces fichiers séparés.

Chaque guide complet comprend son propre module, sa langue et ses commandes de mise à jour, ses limites de sauvegarde et ses vérifications finales. Le travail de routine sur les packages/codes appartient à l'identité du responsable/déploiement, pas au compte Web ni à la racine. Les modules linguistiques peuvent être mis à jour indépendamment de la version d'une application.

Le programme de mise à jour de l'application actualise les guides de l'utilisateur fournis à partir des packages de version installés. La lecture de GitHub `main` ne remplace pas automatiquement les pages déjà stockées sur un site ; leur contenu peut appartenir à une version plus ancienne. Les pages d'installation groupées sont générées à partir de ces sources Markdown.

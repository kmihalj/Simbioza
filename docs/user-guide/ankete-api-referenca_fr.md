# API — routes, portées et erreurs

<a id="api--routes-scopes-and-errors"></a>


> Les captures d’écran présentent l’interface en anglais.

[Pour les développeurs — API](ankete-developeri_fr.md)

Le chemin de base est `/api/v1/surveys`, sans barre oblique finale. Préfixez-le avec le sous-répertoire d'installation, le cas échéant. Chaque demande nécessite une clé valide, sa portée requise et l'autorisation de domaine actuelle du propriétaire.

<div class="table-responsive">

| Suffixe de méthode et de chemin                                | Portée           |
|-------------------------------------------------------|-----------------|
| GET /, GET /{survey}                                  | surveys:read    |
| POST /, PATCH /{survey}                               | surveys:write   |
| POST /{survey}/submit-review, withdraw, copy, delete, discard-draft | surveys:write |
| POST /{survey}/publish, return-review, close, archive, restore | surveys:publish |
| GET /{survey}/results, responses                      | surveys:results |
| POST /{survey}/exports; GET /{survey}/exports/{token} | surveys:export  |

</div>

Les transitions utilisent `version`, `revision` et explicite `confirmed`. La copie accepte un nouveau titre. Ne modifiez pas la structure en place une fois les réponses obtenues : copiez le sondage.

La liste prend en charge `q`, `state`, `page` et `per_page`. Les résultats acceptent la langue et la pagination ; les gestionnaires peuvent également filtrer les jours de reporting inclusifs `from`/`to`. Les soumissions individuelles nécessitent une gestion ; l'autorisation du lecteur de cartes est insuffisante.

Les exportations acceptent `mode` (aggregate/responses), `lang` et le choix explicite de `include_identity`. Le jeton renvoyé expire et reste limité au même créateur autorisé. Il ne s'agit pas d'un lien public ni d'une sauvegarde.

<div class="table-responsive">

| Statut | Action |
|----|----|
| 401 / 403 | Vérifiez la clé, la portée, le compte actif et les autorisations actuelles. |
| 404 | La ressource n'est pas disponible ou un module requis est désactivé ; ne contournez pas les contrôles d’accès. |
| 409 | Relisez la version actuelle et conciliez la modification. |
| 422 | Corrigez la définition, la langue ou les filtres à l'aide du message de réponse. |

</div>

Les réponses réussies utilisent `data`, `meta` et `links`. Les identifiants de requête aident au diagnostic. N'enregistrez pas les réponses ou les clés publiquement. Il n'y a pas de soumission symbolique uniquement API agissant en tant que participant ; La connexion normale, la validation et la protection contre les répétitions s'appliquent toujours.

<a id="removal-and-recovery-through-the-api"></a>

## Suppression et récupération via l’API

`delete` déplace l'intégralité du sondage vers la corbeille et conserve les réponses. `discard-draft` rejette un brouillon de travail ; une publication existante reste inchangée. Les deux nécessitent les valeurs `version`, `revision` et booléennes `confirmed: true` actuelles. `restore` nécessite `version`, `confirmed: true` et un administrateur, en plus de la portée de clé ci-dessus. Une publication précédente revient fermée. La réponse inclut l’UUID, l'opération et le `deleted`, indiquant si le sondage est désormais dans la corbeille. HTTP 409 signifie relire l'état actuel avant de décider à nouveau.

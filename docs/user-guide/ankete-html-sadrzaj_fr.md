# Contenu HTML et sécurité

<a id="html-content-and-safety"></a>


> Les captures d’écran présentent l’interface en anglais.

[Pour les développeurs — API](ankete-developeri_fr.md)

HTML intégré passe par le même désinfectant côté serveur que l'entrée de l'éditeur. L'édition des sources ne fait pas exception à la politique de sécurité.

<div class="table-responsive">

| Contenu | Comportement |
|----|----|
| Texte ordinaire, b/strong, i/em, titres, listes, tableaux et supports sécurisés | Autorisé dans la stratégie HTML Editor. |
| script, style et gestionnaires tels que onclick/onerror | Supprimé; ne les utilisez jamais pour la logique du sondage. |
| javascript :, data : et URL exécutables similaires | Rejeté. |
| Attributs et CSS non pris en charge | Peut être retiré même s’il n’est pas dangereux. |
| Graphiques, feuilles de route, onglets, accordéons, listes déroulantes et tableaux interactifs | Leur balisage et leurs paramètres d'éditeur pris en charge sont conservés et utilisent l'affichage partagé de l'éditeur. |
| Blocs Calendrier, Tâches, Pages incluses et Espace de travail | Les références et paramètres sécurisés sont préservés. Sans un service hôte qui résout le blocage, celui-ci reste un espace réservé inerte. |
| Intégrations Iframe | Seules les intégrations acceptées par la politique partagée de l'éditeur sont conservées, y compris leurs paramètres de protection requis. |

</div>

Utilisez les structures canoniques créées par l'éditeur plutôt que le balisage exécutable arbitraire. L'application et l'enregistrement conservent le contenu pris en charge ; l'affichage du graphique ou de la feuille de route généré ne remplace pas sa source modifiable. Survey ne transforme pas les références de registre de script en scripts exécutables et n'accorde pas l'accès aux cibles des blocs dynamiques. Fournissez les conditions de question sous forme de définitions déclaratives prises en charge, et non en JavaScript.

Respectez l’autorisation de pièce jointe lors de l’intégration de systèmes. Un lien ne donne pas accès à du contenu privé. Traitez toujours les réponses des participants comme des données et non comme un exécutable HTML.

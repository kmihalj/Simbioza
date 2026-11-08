# Connexion et utilisateurs

<a id="login-and-users"></a>


> Les captures d’écran présentent l’interface en anglais.

Ce guide présente à l'administrateur tous les paramètres de connexion, attributs utilisateur, groupes, comptes d'utilisateurs, enregistrements locaux, usurpations d'identité, clés API, fournisseurs SSO et écrans de sécurité et d'accès personnels.

<div class="alert alert-info">

**Les exemples ont été créés sur une nouvelle installation SQLite.** Les comptes, groupes et clés présentés ici n'existent qu'à des fins de documentation. Les secrets et les valeurs d'attributs personnels ont été supprimés des captures d'écran. En production, utilisez vos propres noms, domaines, durées de vie et règles de moindre privilège.

</div>

<a id="contents"></a>

## Contenu

1.  [Paramètres de connexion](#login-settings)
2.  [Attributs utilisateur](#attributes)
3.  [Groupes](#groups)
4.  [Utilisateurs et comptes locaux](#users)
5.  [Connexion et enregistrement locaux](#local-login)
6.  [Usurpation d'identité](#impersonation)
7.  [Touches API](#api-keys)
8.  [Flux de travail SSO commun](#sso)
9.  [SAML](#saml)
10. [SAML proxy](#saml-proxy)
11. [CAS](#cas)
12. [OIDC](#oidc)
13. [OAuth2](#oauth2)
14. [Profil personnel : sécurité et accès](#personal-profile)
15. [Liste de contrôle de vérification et de sécurité](#verification)

<a id="login-settings"></a>

<a id="1-login-settings"></a>

## 1. Paramètres de connexion

Ouvrez **Paramètres → Utilisateurs → Paramètres de connexion** . Cette page active les méthodes d'authentification pour l'application. L'activation d'un fournisseur rend uniquement sa configuration et son menu disponibles ; chaque profil individuel doit toujours être enregistré, activé, testé et mappé correctement.

<span class="figure editor-html-media-figure editor-html-media-image d-inline-block align-top me-2 mb-2 w-100"><img src="images/fb8366ae-1f95-45c3-82a7-b4b374ba51a2.png" class="figure-img img-fluid rounded mb-1" alt="Paramètres de connexion et configuration des méthodes de connexion disponibles." /><span class="figure-caption d-block">Aperçu des paramètres de connexion</span></span>

<figure class="my-4">

<figcaption>Paramètres de connexion dans l'interface anglaise.</figcaption>
</figure>

<a id="main-switches"></a>

### Interrupteurs principaux

- **La connexion locale** permet l'authentification du nom d'utilisateur et du mot de passe stockés par Simbioza.
- **SimpleSAMLphp, CAS, OIDC et OAuth2** activent les groupes de profils SSO correspondants.
- **La connexion locale en cas de bris de verre** conserve une route d'administrateur directe distincte disponible lorsque la connexion locale normale est désactivée. Protégez ce compte avec un mot de passe unique et fort et utilisez-le uniquement à des fins de récupération.
- **La durée de connexion** est une limite d'inactivité en jours (1 à 365), renouvelée pendant que l'utilisateur connecté reste actif. Utilisez une période plus courte sur les appareils administratifs et partagés.

<div class="alert alert-warning">

**« Profil actif » n'est pas la même chose que la coche verte.** Actif signifie que le profil peut être utilisé. La coche verte et le statut *Testé et opérationnel* n'apparaissent qu'après un test réussi du fournisseur et des mappages requis valides. Un profil actif peut toujours être non opérationnel.

</div>

<a id="attributes"></a>

<a id="2-user-attributes"></a>

## 2. Attributs utilisateur

Ouvrez les **Attributs utilisateur** . Le modèle comprend normalement l’e-mail, le prénom et le nom. Ajoutez un attribut lorsque l'application doit stocker une valeur en dehors du profil de base, comme une institution, un service, un numéro d'employé ou un rôle d'annuaire externe.

<span class="figure editor-html-media-figure editor-html-media-image d-inline-block align-top me-2 mb-2 w-100"><img src="images/f1ea79f6-6612-4b72-bb14-29ff71f2a9d8.png" class="figure-img img-fluid rounded mb-1" alt="Paramètres des attributs utilisateur par défaut et personnalisés." /><span class="figure-caption d-block">Attributs utilisateur</span></span>

<figure class="my-4">

<figcaption>Attributs par défaut et personnalisés dans l'interface anglaise.</figcaption>
</figure>

<a id="adding-an-attribute"></a>

### Ajout d'un attribut

1.  Entrez un nom de machine stable en minuscules sans espaces, tel que `affiliation` .
2.  Fournissez des étiquettes croates et anglaises distinctes.
3.  Choisissez le type de données et configurez **Required** , **Registration** , **Profile** et **Active** indépendamment.
4.  Enregistrez-le. Le nouveau champ devient disponible dans les profils utilisateur et dans chaque table de mappage de profil SSO.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/6e88b4f0-77b9-4da0-8e7a-e84f74f86e7e.png" class="img-fluid rounded border" alt="Exemple d'attribut personnalisé bilingue" /> <span class="figure-caption d-block">Exemple d'attribut personnalisé bilingue</span> </span>
<figcaption>Le champ du modèle d'affiliation est étiqueté Ustanova / Institution, reste disponible pour les profils et les mappages SSO et est exclu de l'auto-inscription.</figcaption>
</figure>

Le **Mapping** connecte un champ de modèle Simbioza au nom d'attribut exact renvoyé par le fournisseur. Les noms d'attribut sont exacts : `mail` , `givenName` et `given_name` sont des champs différents. Testez d’abord, inspectez les noms renvoyés, puis entrez ensuite les mappages. Un mappage SSO n’ajoute pas automatiquement le champ au formulaire d’inscription public.

<div class="alert alert-info">

**Quels champs sont inclus dans l'auto-inscription ?** L'administrateur choisit cela par attribut. **Inscription** contrôle si un champ apparaît dans le formulaire public, tandis que **Obligatoire** rejette une valeur vide dans les contextes où ce champ participe. Lorsque les deux paramètres sont activés, le champ est obligatoire lors de l'auto-inscription. Un attribut obligatoire doit également avoir un mappage SSO valide pour qu'un test de profil de fournisseur devienne opérationnel. Dans cet exemple, `affiliation` stocke l'institution fournie par un fournisseur et apparaît dans le profil, elle est donc intentionnellement exclue de l'enregistrement.

</div>

<a id="groups"></a>

<a id="3-groups"></a>

## 3. Groupes

Les groupes collectent des utilisateurs et disposent d'autorisations. Le groupe Administrateur est un groupe système protégé et ne doit pas être utilisé comme un groupe professionnel normal. Créez des groupes de moindre privilège distincts pour les départements, les projets ou les rôles.

<span class="figure editor-html-media-figure editor-html-media-image d-inline-block align-top me-2 mb-2 w-100"><img src="assets/group-list_en.jpg" class="figure-img img-fluid rounded mb-1" alt="Liste des groupes de systèmes et d'exemples avec des actions de gestion." /><span class="figure-caption d-block">Groupes</span></span>

Groupes système et exemples dans l'interface anglaise.\

1.  Choisissez **Ajouter un groupe** (le bouton plus), saisissez un **Nom de groupe** reconnaissable et cochez **Actif**.
2.  Choisissez **Enregistrer les groupes**. Cet écran ne contient pas de description ni d'autorisations de contenu.
3.  Ajoutez ou supprimez l'appartenance à un groupe dans les détails de chaque utilisateur.
4.  Accordez au groupe les autorisations requises dans les paramètres d'un espace de travail, d'une page, d'un calendrier ou d'un sondage. L’adhésion SSO peut également suivre des règles basées sur un attribut mappé.

**Résultat :** le groupe existe et peut être sélectionné lors de l'attribution des autorisations. Le créer à lui seul n’ouvre pas le contenu à ses membres.

Pour désactiver temporairement un groupe non système, décochez **Actif** et enregistrez. Pour le supprimer définitivement, choisissez **Supprimer le groupe**. Sur la page de confirmation, vérifiez son nom et les conséquences affichées pour les appartenances et les règles de mappage, puis confirmez la suppression ou annulez. Les comptes utilisateurs sont conservés. Un nom vide ne supprime plus un groupe. Avant de confirmer, vérifiez où le groupe donne accès au contenu. Un nouveau groupe de même nom possède une autre identité et ne récupère ni les anciennes appartenances ni les autorisations. Les groupes système ne peuvent pas être supprimés.

<figure class="my-4">
<img src="assets/group-deletion_en.jpg" class="img-fluid rounded border" alt="Vérifiez le nom et les conséquences avant de confirmer. Annuler revient à la liste sans suppression." />
<figcaption>Vérifiez le nom et les conséquences avant de confirmer. Annuler revient à la liste sans suppression.</figcaption>
</figure>

<a id="users"></a>

<a id="4-users-and-local-accounts"></a>

## 4. Utilisateurs et comptes locaux

Ouvrez **Paramètres → Utilisateurs → Utilisateurs** pour rechercher des comptes, inspecter leur état et gérer l'adhésion et l'accès.

<span class="figure editor-html-media-figure editor-html-media-image d-inline-block align-top me-2 mb-2 w-100"><img src="images/a00666a6-9521-4f7d-bd23-8c116dfc53a8.png" class="figure-img img-fluid rounded mb-1" alt="Liste réactive des comptes utilisateurs avec actions de gestion." /><span class="figure-caption d-block">Tableau utilisateur réactif</span></span>

<figure class="my-4">
<span>Colonnes de table utilisateur équilibrées dans l'interface anglaise.</span>
</figure>

<a id="creating-a-local-user"></a>

### Création d'un utilisateur local

1.  Sélectionnez **Nouvel utilisateur** .
2.  Entrez un identifiant de connexion unique, un nom d'affichage et une adresse e-mail.
3.  Choisissez la source d'authentification locale et attribuez un mot de passe temporaire fort.
4.  Exiger un changement de mot de passe lors de la première connexion.
5.  Attribuez uniquement les groupes requis ; l'accès administrateur ne doit pas être la valeur par défaut.

<span class="figure editor-html-media-figure editor-html-media-image d-inline-block align-top me-2 mb-2 w-100"><img src="images/8eb23938-b2cd-4e3f-b7f2-2f19aa91a301.png" class="figure-img img-fluid rounded mb-1" alt="Formulaire de création d'un compte utilisateur local." /><span class="figure-caption d-block">Créer un formulaire d'utilisateur local</span></span>

<figure class="my-4">
<span>Exemple de formulaire de compte local sans mot de passe affiché.</span>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/80bea9cb-0777-45c4-96ce-3ee97e33ceab.png" class="img-fluid rounded border" alt="Nouvel utilisateur dans la liste" /> <span class="figure-caption d-block">Nouvel utilisateur dans la liste</span> </span>
<figcaption>L'utilisateur de la documentation après la création.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/8caff857-27dd-4bcd-9492-82641074e399.png" class="img-fluid rounded border" alt="Détails de l'utilisateur" /> <span class="figure-caption d-block">Détails de l'utilisateur</span> </span>
<figcaption>État du compte, fournisseurs liés et adhésions aux groupes.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/46024a32-147e-49bb-9097-4a925d195440.png" class="img-fluid rounded border" alt="Changement obligatoire du mot de passe de première connexion" /> <span class="figure-caption d-block">Changement obligatoire du mot de passe de première connexion</span> </span>
<figcaption>Un mot de passe temporaire oblige l'utilisateur à en choisir un nouveau avant de continuer.</figcaption>
</figure>

Sur la page de détails, vérifiez l'activité du compte, la source d'authentification, les identités externes liées et les appartenances aux groupes. La désactivation est généralement plus sûre que la suppression car elle préserve la piste d'audit. Ne remplacez jamais manuellement l’identifiant de sujet stable d’un fournisseur externe.

<a id="local-login"></a>

<a id="5-local-login-and-registration"></a>

## 5. Connexion et inscription locales

**Connexion locale** contrôle l'auto-inscription. Gardez-le désactivé pour les systèmes fermés et créez des comptes de manière administrative ou via SSO de confiance. Si elle est activée, l'inscription crée un compte ordinaire actif et connecte l'utilisateur. Elle n'envoie pas d'e-mail de vérification et ne prouve pas la propriété de l'adresse saisie. Pour un système fermé, laissez l'enregistrement désactivé et utilisez des comptes créés par l'administrateur ou un fournisseur SSO de confiance. Publiez un avis de confidentialité approprié avant d’accepter les inscriptions. Le formulaire public inclut uniquement les attributs utilisateur dont l'option **Inscription** est activée ; le champ *Institution* uniquement SSO n'est donc pas affiché ici.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/d88655d6-11ff-40c9-aef9-82a7e31d919b.png" class="img-fluid rounded border" alt="Inscription désactivée" /> <span class="figure-caption d-block">Inscription désactivée</span> </span>
<figcaption>État initial recommandé pour une application fermée.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/71149ada-dd68-4f4f-8db6-7705ba261edd.png" class="img-fluid rounded border" alt="Inscription activée" /> <span class="figure-caption d-block">Inscription activée</span> </span>
<figcaption>Inscription activée uniquement pour un exemple contrôlé.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/d18a4b43-a1b2-4078-a810-b18a02cc0793.png" class="img-fluid rounded border" alt="Lien d'inscription" /> <span class="figure-caption d-block">Lien d'inscription</span> </span>
<figcaption>L'écran de connexion affiche l'enregistrement uniquement après son activation.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/4f5652ca-62a4-4b3a-857c-6fccf29b2e76.png" class="img-fluid rounded border" alt="Formulaire d'inscription" /> <span class="figure-caption d-block">Formulaire d'inscription</span> </span>
<figcaption>Formulaire public avec les champs d'inscription sélectionnés ; l'attribut Institution utilisé pour le mappage SSO est intentionnellement masqué.</figcaption>
</figure>

Après le test, restaurez l’état de production prévu. Les mots de passe doivent être uniques, suffisamment longs et stockés dans un gestionnaire de mots de passe. Les administrateurs ne doivent jamais demander aux utilisateurs leur mot de passe actuel.

<a id="impersonation"></a>

<a id="6-impersonation"></a>

## 6. Usurpation d'identité

**Usurper l'identité d'un utilisateur** ouvre temporairement l'application dans le contexte de l'utilisateur sélectionné. Utilisez-le pour obtenir des autorisations et afficher des diagnostics, et non pour effectuer des actions commerciales au nom de l'utilisateur.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/08b8a426-17a9-4fcd-b69c-1d9f877eb742.png" class="img-fluid rounded border" alt="Écran d'usurpation d'identité" /> <span class="figure-caption d-block">Écran d'usurpation d'identité</span> </span>
<figcaption>Sélection d'un compte pour les diagnostics de support contrôlés. <span>Sélectionnez uniquement le compte nécessaire pour le dossier de support.</span></figcaption>
</figure>

<figure class="my-4">
<span>L'interface indique clairement la session active avec emprunt d'identité.</span>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/a0e76efd-bc62-4268-b508-d7de6e12fce8.png" class="img-fluid rounded border" alt="Retour à l'administrateur" /> <span class="figure-caption d-block">Retour à l'administrateur</span> </span>
<figcaption>Terminez la tâche en revenant à votre compte administrateur.</figcaption>
</figure>

<div class="alert alert-warning">

L’usurpation d’identité ne teste pas l’authentification réelle SSO, MFA ou la stratégie du fournisseur. Utilisez le test de profil ou un compte de test dédié à cet effet.

</div>

<a id="api-keys"></a>

<a id="7-api-keys"></a>

## 7. Touches API

Ces écrans sont disponibles lorsque le modul’API est installé et activé. Une clé API agit en tant que propriétaire. Les étendues peuvent uniquement réduire les autorisations existantes du propriétaire ; ils ne peuvent pas accorder d'autorisations dont le propriétaire ne dispose pas. Utilisez une clé distincte par intégration, une étiquette descriptive, la durée de vie pratique la plus courte et le plus petit ensemble de portées.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/33c88c2c-3956-4eaf-840d-5f236fb052a1.png" class="img-fluid rounded border" alt="Administration des clés API" /> <span class="figure-caption d-block">Administration des clés API</span> </span>
<figcaption>Administration des clés API dans l'interface anglaise.</figcaption>
</figure>

<a id="direct-key-issuance"></a>

### Émission directe de la clé

1.  Sélectionnez le propriétaire et uniquement les étendues requises.
2.  Définissez une date d'expiration et, si nécessaire, une adresse IP autorisée ou une restriction réseau.
3.  Créez la clé et copiez immédiatement le secret pour sécuriser le stockage.
4.  Le secret n'est révélé qu'une seule fois. En cas de perte, révoquez la clé et attribuez-en une nouvelle.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/89146be8-0fe9-4bdd-a406-d92bc09e00bc.png" class="img-fluid rounded border" alt="Clé API de moindre privilège" /> <span class="figure-caption d-block">Clé API de moindre privilège</span> </span>
<figcaption>Une clé intentionnellement restreinte avant la création.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/4f972d0e-66b0-4085-8234-5e3b53fdb175.png" class="img-fluid rounded border" alt="Secret API unique" /> <span class="figure-caption d-block">Secret API unique</span> </span>
<figcaption>Le secret a été supprimé de la capture d'écran.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/39bcd07d-03f4-4f31-8085-0b34a0604698.png" class="img-fluid rounded border" alt="Clés API actives" /> <span class="figure-caption d-block">Clés API actives</span> </span>
<figcaption>Vérifiez et révoquez les clés actives de cette liste.</figcaption>
</figure>

<a id="user-request-and-administrator-approval"></a>

### Demande de l'utilisateur et approbation de l'administrateur

Un utilisateur peut demander une clé à partir du profil personnel et expliquer son objectif. L'administrateur examine le propriétaire, les étendues, la durée de vie et la justification, puis approuve ou rejette la demande. Après approbation, l'utilisateur peut révéler le secret une fois dans son profil personnel. Lorsque les notifications sont activées, une notification dans l'application pointe également vers cet écran. Ni la notification ni un e-mail ne doivent contenir le secret.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/fcdeaf36-1804-4d2f-8639-78774d1f3580.png" class="img-fluid rounded border" alt="Formulaire de demande de clé API" /> <span class="figure-caption d-block">Formulaire de demande de clé API</span> </span>
<figcaption>L'utilisateur demande une clé restreinte et indique son objectif.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/e20ced95-af39-44d6-ad45-775b198caabb.png" class="img-fluid rounded border" alt="Demande envoyée" /> <span class="figure-caption d-block">Demande envoyée</span> </span>
<figcaption>La demande attend l'examen de l'administrateur.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/7f412246-9d37-4f65-a008-49c42fd15f68.png" class="img-fluid rounded border" alt="En attente de demand’API" /> <span class="figure-caption d-block">En attente de demand’API</span> </span>
<figcaption>L'administrateur vérifie la demande avant son approbation.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/1b731910-dbde-43d7-8e72-416157977d5a.png" class="img-fluid rounded border" alt="Demand’API approuvée" /> <span class="figure-caption d-block">Demand’API approuvée</span> </span>
<figcaption>L'approbation crée la clé sans révéler le secret à l'administrateur.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/36020798-5696-4756-a707-3a463d582f49.png" class="img-fluid rounded border" alt="Notification de clé API" /> <span class="figure-caption d-block">Notification de clé API</span> </span>
<figcaption>L'utilisateur est informé de la décision.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/be701281-d85e-40a8-ada2-2668a2c212c0.png" class="img-fluid rounded border" alt="Notification de demande approuvée" /> <span class="figure-caption d-block">Notification de demande approuvée</span> </span>
<figcaption>La notification conduit à une récupération unique du secret.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/140963e5-a521-4a24-83df-d8a549fb49da.png" class="img-fluid rounded border" alt="Récupération unique du secret API" /> <span class="figure-caption d-block">Récupération unique du secret API</span> </span>
<figcaption>Le secret est supprimé et ne peut plus être affiché après la fermeture.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/0c3b8928-1587-4071-b847-2d66589c6c03.png" class="img-fluid rounded border" alt="Demand’API terminée" /> <span class="figure-caption d-block">Demand’API terminée</span> </span>
<figcaption>La demande complétée reste auditable sans exposer le secret.</figcaption>
</figure>

<a id="sso"></a>

<a id="8-common-sso-workflow"></a>

## 8. Flux de travail commun SSO

1.  Enregistrez l'application auprès du fournisseur et entrez uniquement les points de terminaison, l'ID client et le secret nécessaires.
2.  Enregistrez le profil actif. Les secrets existants ne sont jamais affichés lors de l'édition ; laisser le champ secret vide conserve la valeur côté serveur.
3.  Sélectionnez **Tester le profil** avant de deviner les mappages. Effectuez une véritable connexion pour collecter les attributs réels du fournisseur.
4.  Mappez les champs d'objet, de connexion, de nom d'affichage et d'utilisateur à partir des noms renvoyés.
5.  Testez à nouveau. Seul **Testé et opérationnel** accompagné d'une coche verte confirme un profil complet.

<a id="allowed-domains"></a>

### Domaines autorisés

La liste restreint la connexion aux domaines organisationnels. Saisissez un domaine par ligne ou une liste séparée par des virgules. `example.org` autorise ce domaine et ses sous-domaines ; un suffixe tel que `hr` autorise les identifiants dont le domaine se termine par `.hr` . Une liste vide n'impose aucune restriction de domaine.

**L'attribut source du domaine** doit être l'attribut réel du fournisseur contenant une valeur telle que `user@example.org` ou un domaine. Il est requis uniquement lorsque la liste des domaines autorisés est renseignée. N'utilisez pas de nom d'affichage ou une autre valeur modifiable.

<a id="public-callback-addresses"></a>

### Adresses de rappel publiques

Le fournisseur doit renvoyer le navigateur à l'URL publique de la même installation. Incluez un sous-répertoire lorsqu'un sous-répertoire est utilisé : pour une application sur `https://simbioza.example.org/simbioza` , entrez cette valeur complète comme URL de base publique. Il ne s'agit pas de l'adresse interne PHP-FPM, du conteneur ou de la base de données. Enregistrez les URL de rappel exactes affichées par le profil.

<a id="saml"></a>

## 9. SAML

Un profil SAML dépend d'un **SimpleSAMLphp** installé séparément et correctement configuré. Son chargeur automatique, sa source d'authentification SP, ses certificats, ses métadonnées et ses URL de retour doivent déjà fonctionner. Le chemin d'accès à `vendor/autoload.php` et le nom de la source d'authentification doivent correspondre à cette installation.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/d33dc6ed-ec34-4a5f-b54b-94354364bd31.png" class="img-fluid rounded border" alt="Configuration initiale SAML" /> <span class="figure-caption d-block">Configuration initiale SAML</span> </span>
<figcaption>Le profil est enregistré, mais les mappages ne sont pas encore confirmés.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/fc09510d-0f1e-44ed-88b5-22d64f4d151f.png" class="img-fluid rounded border" alt="Premier test SAML" /> <span class="figure-caption d-block">Premier test SAML</span> </span>
<figcaption>Les travaux et attributs de connectivité ont été collectés ; les valeurs sont expurgées.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/21676ed7-d1a6-4008-8ab6-8885b087e737.png" class="img-fluid rounded border" alt="Lancement de la connexion AAI" /> <span class="figure-caption d-block">Lancement de la connexion AAI</span> </span>
<figcaption>L'écran de connexion en anglais Simbioza démarre le flux AAI@EduHr SSO configuré sans exposer les informations d'identification.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/a92bb0e8-0687-4f28-88c7-e02fa8f36b42.png" class="img-fluid rounded border" alt="Mappages SAML" /> <span class="figure-caption d-block">Mappages SAML</span> </span>
<figcaption>Mappages requis saisis à partir des attributs renvoyés réels.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/31d0a1e9-cc0a-479b-afbc-9aadcd604748.png" class="img-fluid rounded border" alt="Profil opérationnel SAML" /> <span class="figure-caption d-block">Profil opérationnel SAML</span> </span>
<figcaption>Le test répété confirme un profil SAML opérationnel.</figcaption>
</figure>

<a id="saml-proxy"></a>

<a id="10-saml-proxy-and-child-profiles"></a>

## 10. SAML profils proxy et enfants

Une instance de proxy représente une source d'authentification SimpleSAMLphp qui peut exposer plusieurs fournisseurs en amont. L'onglet **Par défaut** définit le chemin de chargement automatique partagé, la source d'authentification et le mappage de base. Les onglets enfants supplémentaires héritent des paramètres de connexion partagés tout en conservant leurs propres mappages d'attributs.

**Un proxy n'a pas de champs d'attribut Domaines autorisés ou Source de domaine.** Ceci est délibéré : les fournisseurs en amont peuvent renvoyer des identifiants et des domaines différents. Appliquez des restrictions au niveau du proxy ou du fournisseur en amont, et non via une règle de domaine Simbioza partagée.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/4f8e5ae8-57d5-4e8a-878c-ac5e78821030.png" class="img-fluid rounded border" alt="Sélection du fournisseur de proxy" /> <span class="figure-caption d-block">Sélection du fournisseur de proxy</span> </span>
<figcaption>La véritable connexion proxy propose AAI@EduHR, Facebook et Google, expliquant les trois profils d'enfants.</figcaption>
</figure>

<a id="default--aaieduhr"></a>

### Par défaut / AAI@EduHR

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/faa2088e-6d03-4385-852f-0bab107c5ae1.png" class="img-fluid rounded border" alt="Premier test de proxy par défaut" /> <span class="figure-caption d-block">Premier test de proxy par défaut</span> </span>
<figcaption>Attributs collectés avant le mappage, avec valeurs supprimées.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/3cf0d969-0b78-4ba6-ae53-89391e530d7d.png" class="img-fluid rounded border" alt="Mappages de proxy par défaut" /> <span class="figure-caption d-block">Mappages de proxy par défaut</span> </span>
<figcaption>Mappages pour la source AAI par défaut.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/d731b742-31b9-447a-9bf7-b481b3417240.png" class="img-fluid rounded border" alt="Profil proxy opérationnel par défaut" /> <span class="figure-caption d-block">Profil proxy opérationnel par défaut</span> </span>
<figcaption>Onglet proxy par défaut après le test répété réussi.</figcaption>
</figure>

### Facebook

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/a44f01cb-48ba-4b0d-bdb4-f7257ede99c5.png" class="img-fluid rounded border" alt="Premier test de proxy Facebook" /> <span class="figure-caption d-block">Premier test de proxy Facebook</span> </span>
<figcaption>Attributs Facebook collectés avant le mappage, avec valeurs supprimées.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/b8477a91-99c7-474b-bdd0-62f141c956e0.png" class="img-fluid rounded border" alt="Cartographies d'enfants sur Facebook" /> <span class="figure-caption d-block">Cartographies d'enfants sur Facebook</span> </span>
<figcaption>Identifiant Facebook stable, champs de connexion et utilisateur mappés indépendamment.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/cd8a17cd-616a-4826-8caa-85ace9446401.png" class="img-fluid rounded border" alt="Profil enfant Facebook opérationnel" /> <span class="figure-caption d-block">Profil enfant Facebook opérationnel</span> </span>
<figcaption>Onglet enfant Facebook après une cartographie et un test réussis.</figcaption>
</figure>

### Google

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/c0338f0d-59e3-4a6b-ab0b-fdd3a1ee8f0c.png" class="img-fluid rounded border" alt="Premier test de proxy Google" /> <span class="figure-caption d-block">Premier test de proxy Google</span> </span>
<figcaption>Attributs Google collectés avant le mappage, avec valeurs supprimées.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/da95a7fc-0699-4fb3-b8f3-874cdfd0f370.png" class="img-fluid rounded border" alt="Mappages enfants Google" /> <span class="figure-caption d-block">Mappages enfants Google</span> </span>
<figcaption>L'UID de Google est le sujet, l'e-mail de Google est la connexion et les champs de nom sont mappés dans le modèle.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/4290218a-cbf8-4cd3-b989-0c4c2700a349.png" class="img-fluid rounded border" alt="Profil enfant Google opérationnel" /> <span class="figure-caption d-block">Profil enfant Google opérationnel</span> </span>
<figcaption>Onglet enfant Google après le test répété réussi.</figcaption>
</figure>

<a id="cas"></a>

## 11. CAS

Un profil CAS a besoin de l'URL de base du serveur et de ses points de terminaison de connexion, de validation et de déconnexion. Si une chaîne d'autorité de certification privée est utilisée, fournissez un chemin de certificat lisible. Ne désactivez jamais la validation TLS pour contourner une configuration de certificat incorrecte.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/aa4de493-9953-4a64-a76c-548b7aa92c9d.png" class="img-fluid rounded border" alt="Configuration initiale CAS" /> <span class="figure-caption d-block">Configuration initiale CAS</span> </span>
<figcaption>La connexion CAS est préparée mais les mappages ne sont pas terminés.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/417fd550-238d-4b9b-b008-280738935d04.png" class="img-fluid rounded border" alt="Premier test CAS" /> <span class="figure-caption d-block">Premier test CAS</span> </span>
<figcaption>L'authentification CAS renvoie les attributs ; les valeurs sont expurgées.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/1e2d14d5-7702-4326-8bca-73f266673fa9.png" class="img-fluid rounded border" alt="Mappages CAS" /> <span class="figure-caption d-block">Mappages CAS</span> </span>
<figcaption>Champs stables d’objet, de connexion, de nom d’affichage et de modèle mappés à partir du test.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/c4341d65-56fa-4c42-82cf-28db9b05e563.png" class="img-fluid rounded border" alt="Profil opérationnel CAS" /> <span class="figure-caption d-block">Profil opérationnel CAS</span> </span>
<figcaption>Le test répété confirme le profil CAS.</figcaption>
</figure>

<a id="oidc"></a>

## 12. OIDC

OIDC utilise un ID client et un secret, une autorisation, un jeton, des informations utilisateur et éventuellement des points de terminaison de fin de session. La portée doit inclure `openid` , suivi uniquement des données requises telles que `profile` et `email` . **L'URL de base de redirection publique** est l'adresse de base publique complète de cette installation, y compris un sous-répertoire le cas échéant.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/a8ba39ed-751e-4586-9199-a5cdf7b80c8c.png" class="img-fluid rounded border" alt="Configuration initiale OIDC" /> <span class="figure-caption d-block">Configuration initiale OIDC</span> </span>
<figcaption>Points de terminaison OIDC et URL de base publique avant le mappage.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/7dd1a0ab-0d45-48f9-a529-7fb5016ba951.png" class="img-fluid rounded border" alt="Premier test OIDC" /> <span class="figure-caption d-block">Premier test OIDC</span> </span>
<figcaption>OIDC revendications collectées avant le mappage, avec les valeurs supprimées.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/ff56adb1-ea0d-4d0a-bf0e-02b358a71c05.png" class="img-fluid rounded border" alt="Mappage et domaine OIDC" /> <span class="figure-caption d-block">Mappage et domaine OIDC</span> </span>
<figcaption>Objet, identifiant, nom d'affichage, champs utilisateur et source de domaine mappés à partir de revendications réelles.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/0603f7ff-a5e7-4f13-a6e6-b7b144b992a5.png" class="img-fluid rounded border" alt="Profil opérationnel OIDC" /> <span class="figure-caption d-block">Profil opérationnel OIDC</span> </span>
<figcaption>Le test répété confirme OIDC et affiche la coche verte.</figcaption>
</figure>

Utilisez un paramètre de connexion forcée tel que `max_age=0` uniquement lorsque le fournisseur le prend en charge et que **Forcer la connexion** est activé. Sinon, laissez-le vide pour conserver une session SSO existante valide.

<a id="oauth2"></a>

## 13. OAuth2

OAuth2 utilise les points de terminaison d'autorisation, de jeton et d'informations utilisateur ainsi que les étendues requises pour les données utilisateur. Si le fournisseur prend en charge OpenID Connect, préférez OIDC car il standardise la validation des identités et des jetons. La déconnexion OAuth2 peut nécessiter un point de terminaison dédié, un nom de paramètre de redirection ou un indice ; entrez uniquement les valeurs requises par la documentation du fournisseur.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/c8024292-d7ab-4926-82fc-3ae3135df40e.png" class="img-fluid rounded border" alt="Configuration initiale OAuth2" /> <span class="figure-caption d-block">Configuration initiale OAuth2</span> </span>
<figcaption>Profil OAuth2 avant le mappage des attributs.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/5a358208-3572-4641-970a-278bf2b22082.png" class="img-fluid rounded border" alt="Premier test OAuth2" /> <span class="figure-caption d-block">Premier test OAuth2</span> </span>
<figcaption>Attributs Userinfo collectés via une connexion réelle, avec les valeurs supprimées.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/0df4572c-6616-4522-939e-f825f70504d2.png" class="img-fluid rounded border" alt="Mappage et domaine OAuth2" /> <span class="figure-caption d-block">Mappage et domaine OAuth2</span> </span>
<figcaption>Mappages et restriction de domaine saisis à partir du résultat du test.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/d3faabdb-f174-4a6b-9f00-45f56e11eb3b.png" class="img-fluid rounded border" alt="Profil opérationnel OAuth2" /> <span class="figure-caption d-block">Profil opérationnel OAuth2</span> </span>
<figcaption>Le test répété confirme le profil OAuth2.</figcaption>
</figure>

<a id="personal-profile"></a>

<a id="14-personal-profile-security-and-access"></a>

## 14. Profil personnel : sécurité et accès

Ouvrez **Profil personnel** dans le menu utilisateur. La section principale affiche l'identité et les attributs personnalisés. **Sécurité et accès** affiche l'appartenance au groupe, l'état du mot de passe local, les connexions externes liées et les demandes de clé API.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/52960239-b877-4349-bd2a-016f5837e8f7.png" class="img-fluid rounded border" alt="Profil personnel" /> <span class="figure-caption d-block">Profil personnel</span> </span>
<figcaption>Données personnelles et attributs personnalisés dans l'interface anglaise.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/12c871d9-5263-4a65-93f0-43ed0980d549.png" class="img-fluid rounded border" alt="Sécurité et accès étendus" /> <span class="figure-caption d-block">Sécurité et accès étendus</span> </span>
<figcaption>Groupes, connexion locale, fournisseurs liés et requêtes API en un seul endroit.</figcaption>
</figure>

Liez un compte externe uniquement pendant que vous contrôlez les deux identités. Supprimez les liens inconnus ou inutilisés, mais confirmez d'abord qu'une autre méthode de connexion reste disponible. Modifiez les mots de passe locaux via ce formulaire sécurisé, jamais par e-mail ou par message de l'administrateur.

<a id="verification"></a>

<a id="15-verification-and-security-checklist"></a>

## 15. Liste de contrôle de vérification et de sécurité

- Gardez au moins une connexion d'administrateur contrôlée disponible ; conserver le compte bris de glace en dehors d'une utilisation quotidienne normale.
- Pour chaque profil SSO, vérifiez les URL de rappel exactes, les certificats TLS, les étendues minimales et la rotation des secrets.
- N'acceptez pas une case à cocher active comme preuve. Exiger l'état opérationnel vert après le test répété.
- Le sujet doit être un identifiant de fournisseur immuable. L'identifiant et l'adresse e-mail peuvent changer et ne constituent pas toujours des identifiants principaux appropriés.
- Testez les restrictions de domaine avec un compte test autorisé et refusé.
- Examinez régulièrement les administrateurs, les adhésions aux groupes, les connexions externes liées, les clés API actives et les demandes en attente.
- Ne mettez jamais de mots de passe, de secrets clients, de jetons, de secrets API ou d'attributs personnels bruts dans des captures d'écran, de la documentation, des tickets ou du contrôle de source.
- Après le test, supprimez ou désactivez les comptes de documentation et les clés qui ne sont plus nécessaires.

<div class="alert alert-success">

Après avoir enregistré, testez une connexion normale et un compte refusé. Confirmez que chaque utilisateur voit uniquement le contenu et les actions attribués à ses groupes.

</div>

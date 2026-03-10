# Documentation Technique API v1

Ce document decrit de maniere technique le contrat reel de l'API exposee dans `routes/api.php`, base sur l'implementation actuelle (controllers, middleware, FormRequest, models et migrations).

## 1. Vue d'ensemble

- Prefixe global: `/api/v1`
- Nature des endpoints: lecture uniquement (`GET`)
- Protection: middleware `firebase.auth` puis `throttle:api`
- Format de reponse nominal: enveloppe JSON uniforme (`success`, `message`, `data`, `meta`)
- Binding de route: Eloquent implicite sur `{pharmacy}`, `{medicament}`, `{hopital}`, `{examen}` par `id`

Base URL locale exemple:

```text
http://127.0.0.1:8000/api/v1
```

Headers recommandes:

```http
Authorization: Bearer <firebase_id_token>
Accept: application/json
```

## 2. Securite et Authentification

### 2.1 Middleware `firebase.auth`

Le middleware `AuthenticateFirebaseToken` execute les etapes suivantes:

1. Extrait le bearer token via `Request::bearerToken()`.
2. Verifie la signature JWT Firebase (algorithme RS256) via les certificats publics Google.
3. Valide les claims critiques: `aud`, `iss`, `sub`, `exp`, `iat`.
4. Cree ou met a jour l'utilisateur local (`users`) par `firebase_uid`.
5. Refuse l'acces si `users.is_active = false`.
6. Injecte l'utilisateur dans le resolver de requete (`$request->user()`).

### 2.2 Prerequis de configuration

Variables necessaires:

- `FIREBASE_PROJECT_ID`
- `FIREBASE_CERTIFICATES_URL` (optionnelle, valeur par defaut Google)

Sans `FIREBASE_PROJECT_ID`, la verification echoue (401 indirect via exception de verification).

### 2.3 Codes d'erreur auth

- `401 Unauthorized`
  - Token absent
  - Token invalide/expire
  - Claim `sub` absent
- `403 Forbidden`
  - Utilisateur connu mais inactif (`is_active = false`)

Exemple format `401`:

```json
{
  "success": false,
  "message": "Token Firebase invalide.",
  "errors": {
    "authorization": ["Authentification requise."]
  }
}
```

## 3. Convention de Reponse

### 3.1 Succes simple

```json
{
  "success": true,
  "message": "Details de la pharmacie charges avec succes.",
  "data": {
    "id": 15,
    "name": "Pharmacie Centrale"
  },
  "meta": {}
}
```

### 3.2 Succes pagine

```json
{
  "success": true,
  "message": "Liste des pharmacies chargee avec succes.",
  "data": [],
  "meta": {
    "pagination": {
      "current_page": 1,
      "per_page": 15,
      "total": 0,
      "last_page": 1
    }
  }
}
```

### 3.3 Erreurs de validation

Les `FormRequest` retournent le format standard Laravel en `422` (champ `message` + objet `errors`).

```json
{
  "message": "The q field is required.",
  "errors": {
    "q": ["Le parametre q est obligatoire."]
  }
}
```

Important:

- Les messages sont personnalises en francais dans chaque `FormRequest`.
- Le shape n'est pas l'enveloppe `success/data/meta` pour les `422` Laravel natifs.

## 4. Parametres Communs

### 4.1 `per_page`

- Type: integer
- Intervalle: `1..100`
- Defaut applicatif: `15`
- Utilisation: pagination de toutes les listes

### 4.2 `city`

- Type: string
- Max: 120
- Comportement: filtrage `LIKE %city%`

## 5. Catalogue des Endpoints

Tous les endpoints ci-dessous sont sous `/api/v1` et exigent un bearer token valide.

## 5.1 Pharmacies

### GET `/pharmacies`

Objectif:

- Liste paginee des pharmacies.
- Filtrage optionnel par ville.

Query params:

- `city` optionnel (`string`, max 120)
- `per_page` optionnel (`integer`, 1..100)

Algorithme:

- Base query: `Pharmacy::query()`
- Si `city` present: `where city like %city%`
- Tri: `orderBy(name)`
- Pagination: `paginate(per_page)`

### GET `/pharmacies/de-garde`

Objectif:

- Retourner les pharmacies de garde pour une date/ville.

Query params:

- `date` optionnel (`Y-m-d`)
- `ville` optionnel (`string`, max 120, defaut `Ouagadougou`)
- `per_page` optionnel (`integer`, 1..100)

Algorithme metier:

1. Date de reference = debut de jour.
2. Recherche d'un planning actif dans `groupes_garde`:
   - `actif = true`
   - `debut_garde <= reference`
   - `fin_garde > reference`
   - `ville = <ville>`
   - plus recent `debut_garde` en priorite
3. Si aucun planning: retour succes avec `data=[]` et `meta.garde` null.
4. Sinon: pharmacies filtrees par `city=<ville>` et `groupe=<nom_du_groupe>`.

Meta specifique:

- `meta.pagination` standard
- `meta.garde`:
  - `ville`
  - `date_reference`
  - `groupe`
  - `debut_garde`
  - `fin_garde`

### GET `/pharmacies/par-medicament`

Objectif:

- Retourner les pharmacies qui proposent un medicament recherche par nom avec stock disponible.

Query params:

- `q` requis (`string`, min 2, max 120)
- `city` optionnel (`string`, max 120)
- `min_stock` optionnel (`integer`, min 1, defaut 1)
- `per_page` optionnel (`integer`, 1..100)

Regles metier:

- Filtre pharmacie optionnel sur `city like %city%`
- `whereHas(medicaments)` avec:
  - `medicaments.name like %q%`
  - `medicament_pharmacy.is_available = true`
  - `medicament_pharmacy.stock_quantity >= min_stock`
- Charge uniquement les medicaments correspondant au critere dans la relation `medicaments` retournee.

Exemple:

```text
/pharmacies/par-medicament?q=amoxicilline&min_stock=5&city=Ouagadougou&per_page=20
```

### GET `/pharmacies/on-duty`

Objectif:

- Liste paginee des pharmacies marquees `is_on_duty = true`.

Query params:

- `per_page` optionnel

### GET `/pharmacies/nearby`

Objectif:

- Liste paginee des pharmacies geographiquement proches.

Query params:

- `lat` requis (numeric, -90..90)
- `lng` requis (numeric, -180..180)
- `radius_km` optionnel (numeric, 0.1..100, defaut 20)
- `per_page` optionnel

Detail geospatial:

- Formule utilisee: Haversine SQL approximative sur rayon terrestre `6371 km`.
- Calcul de `distance_km` en `selectRaw`.
- Conditions:
  - `latitude IS NOT NULL`
  - `longitude IS NOT NULL`
  - `having distance_km <= radius_km`
- Tri: plus proche en premier.

### GET `/pharmacies/{pharmacy}`

Objectif:

- Retour detail d'une pharmacie.

Path param:

- `{pharmacy}`: id numerique (route model binding implicite)

Codes possibles:

- `200` si trouve
- `404` si id inexistant

## 5.2 Medicaments

### GET `/medicaments`

Objectif:

- Liste paginee des medicaments actifs.

Regle metier:

- `where is_active = true`

### GET `/medicaments/search`

Objectif:

- Recherche par nom de medicament.

Query params:

- `q` requis (`string`, min 2, max 120)
- `per_page` optionnel

Regle metier:

- `where is_active = true`
- `where name like %q%`

### GET `/medicaments/{medicament}`

Objectif:

- Detail d'un medicament (actif ou non, car acces direct par id).

### GET `/medicaments/{medicament}/pharmacies`

Objectif:

- Retourner les pharmacies qui proposent ce medicament.

Regle metier sur pivot `medicament_pharmacy`:

- filtre `is_available = true`
- tri `pharmacies.name`

Donnees pivot incluses dans la relation:

- `stock_quantity`
- `is_available`
- `price`

## 5.3 Hopitaux

### GET `/hopitaux`

Objectif:

- Liste paginee des hopitaux.

Query params:

- `city` optionnel
- `per_page` optionnel

### GET `/hopitaux/par-examen`

Objectif:

- Retourner les hopitaux proposant un examen cible.

Query params:

- `examen` requis (`string`, min 2, max 120)
- `per_page` optionnel

Regles metier:

- `whereHas(examens)` avec:
  - `examens.name like %examen%`
  - `examen_hopital.is_available = true`
- Charge aussi la relation `examens` deja filtree de la meme maniere.

### GET `/hopitaux/search`

Objectif:

- Recherche d'hopitaux par nom ou ville.

Query params:

- `q` requis
- `per_page` optionnel

Predicate exact:

- `where name like %q%`
- `orWhere city like %q%`

Note technique:

- Le `orWhere` est au niveau racine (pas de groupement additionnel), ce qui est acceptable ici car il n'y a pas d'autre contrainte sur la query.

### GET `/hopitaux/nearby`

Objectif:

- Recherche geospatiale sur hopitaux.

Query params:

- `lat` requis
- `lng` requis
- `radius_km` optionnel (defaut 30)
- `per_page` optionnel

Implementation:

- Meme formule et logique de distance que `/pharmacies/nearby`.

### GET `/hopitaux/{hopital}`

Objectif:

- Detail hopital + examens lies.

Comportement:

- `load('examens')` avec tri `examens.name`.
- Les examens inactifs ou indisponibles sur pivot peuvent apparaitre ici (pas de filtre `is_available`).

## 5.4 Examens

### GET `/examens`

Objectif:

- Liste paginee des examens actifs.

Regle metier:

- `where is_active = true`

### GET `/examens/search`

Objectif:

- Recherche examens par nom ou categorie.

Query params:

- `q` requis (`min 2`, `max 120`)
- `per_page` optionnel

Predicate:

- `is_active = true`
- `(name like %q% OR category like %q%)`

### GET `/examens/{examen}`

Objectif:

- Detail examen par id.

### GET `/examens/{examenNom}/hopitaux`

Objectif:

- Rechercher des hopitaux a partir d'un nom d'examen dans le path.

Path param:

- `{examenNom}` (string, controle manuel min 2 apres `trim`)

Validation specifique:

- Si longueur `< 2`, retourne `422` custom:

```json
{
  "success": false,
  "message": "Le nom de l'examen doit contenir au moins 2 caracteres.",
  "errors": {
    "examenNom": ["Le parametre examenNom est invalide."]
  }
}
```

Regles metier:

- `whereHas(examens)` avec `name like` + `examen_hopital.is_available = true`
- eager loading `examens` avec le meme filtre

## 6. Validation: Contrat Precis

Regles FormRequest partagees:

- `per_page`: `nullable|integer|min:1|max:100`
- `city`: `nullable|string|max:120`
- `q`: `required|string|min:2|max:120`
- `lat`: `required|numeric|between:-90,90`
- `lng`: `required|numeric|between:-180,180`
- `radius_km`: `nullable|numeric|min:0.1|max:100`
- `date`: `nullable|date_format:Y-m-d`
- `ville`: `nullable|string|max:120`
- `examen`: `required|string|min:2|max:120`

## 7. Modele de Donnees Expose

## 7.1 `pharmacies`

Champs principaux:

- `id` bigint
- `name` string
- `address` string
- `city` string (index)
- `groupe` string nullable (index)
- `phone` string(30) nullable
- `latitude` decimal(10,7) nullable
- `longitude` decimal(10,7) nullable
- `is_on_duty` boolean default false (index)
- `opens_at` time nullable
- `closes_at` time nullable

## 7.2 `medicaments`

- `id`, `name` (index), `dosage`, `form`, `description`, `is_active` (index)

## 7.3 `hopitaux`

- `id`, `name` (index), `categorie` (index), `address`, `city` (index), `phone`
- `latitude`, `longitude`, `emergency_available` (index)

## 7.4 `examens`

- `id`, `name` (index), `category` (index), `description`, `is_active` (index)

## 7.5 `groupes_garde`

- `nom`, `ville`, `description`
- `debut_garde`, `fin_garde`, `actif`, `notes`

Utilisation API:

- Resolution des pharmacies de garde via matching `pharmacies.groupe = groupes_garde.nom` + filtre ville/date.

## 7.6 Pivots

`medicament_pharmacy`:

- FK `medicament_id`, `pharmacy_id`
- `stock_quantity`, `is_available`, `price`
- unique `(medicament_id, pharmacy_id)`

`examen_hopital`:

- FK `examen_id`, `hopital_id`
- `is_available`, `preparation_notes`
- unique `(examen_id, hopital_id)`

## 8. Matrice des Erreurs HTTP

- `200`: succes (meme avec resultat vide)
- `401`: auth Firebase absente/invalide
- `403`: utilisateur inactif
- `404`: ressource inexistante en binding implicite
- `422`: validation query/path invalide
- `429`: rate-limit middleware `throttle:api`
- `500`: erreur interne (ex: probleme de config Firebase, DB, etc.)

## 9. Exemples d'Appels

## 9.1 cURL - pharmacies de garde

```bash
curl -X GET "http://127.0.0.1:8000/api/v1/pharmacies/de-garde?date=2026-03-08&ville=Ouagadougou&per_page=20" \
  -H "Authorization: Bearer <firebase_id_token>" \
  -H "Accept: application/json"
```

## 9.2 cURL - hopitaux proches

```bash
curl -X GET "http://127.0.0.1:8000/api/v1/hopitaux/nearby?lat=12.3700&lng=-1.5200&radius_km=25&per_page=10" \
  -H "Authorization: Bearer <firebase_id_token>" \
  -H "Accept: application/json"
```

## 10. Considerations Production

- Toujours envoyer `Accept: application/json` pour homogeniser les erreurs.
- Prevoir cache applicatif sur endpoints geospatiaux a fort trafic.
- Verifier les index DB utilises (`city`, `is_on_duty`, pivots) en fonction des volumes reels.
- Monitorer les erreurs `401` pour detecter desecarts d'horloge client (claim `iat`) ou tokens expires.
- `distance_km` est calculee a la vollee: optimiser via bounding-box si volume geospatial eleve.

## 11. Limites Fonctionnelles Actuelles

- Aucun endpoint CRUD d'ecriture n'est expose dans `v1`.
- Pas de versioning de schema de reponse autre que prefixe `/v1`.
- Les `show` ne filtrent pas toujours les entites inactives (ex: `medicaments/{id}`, `examens/{id}`).
- Le role utilisateur existe en base (`users.role`) mais n'est pas encore exploite en autorisation fine.

## 12. Checklist Integration Frontend

- Gerer explicitement `401`, `403`, `422`, `429`.
- Renvoyer automatiquement vers re-auth Firebase en cas de `401`.
- Pour listes, toujours lire `meta.pagination`.
- Pour `/pharmacies/de-garde`, exploiter `meta.garde` pour afficher le contexte planning.
- Encoder correctement les segments path pour `/examens/{examenNom}/hopitaux` (espaces, slashs, accents).

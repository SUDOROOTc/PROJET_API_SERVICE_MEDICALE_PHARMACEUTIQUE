# Contrat API - Equipe Frontend (Flutter)

## 1) Base URL et authentification

- Base URL locale: `http://127.0.0.1:8000/api/v1`
- Toutes les routes `v1` sont protegees.
- Header requis:

```http
Authorization: Bearer <firebase_id_token>
Accept: application/json
```

## 2) Format de reponse standard (succes)

Toutes les reponses des controllers utilisent ce format:

```json
{
  "success": true,
  "message": "Succes",
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

Notes:
- Pour un endpoint `show`, `data` est un objet.
- Pour les endpoints listes/recherche, `data` est un tableau.
- `meta.pagination` est present sur les listes paginees.

## 3) Format de reponse erreur

### 401 (token absent/invalide)

```json
{
  "success": false,
  "message": "Token Bearer manquant.",
  "errors": {
    "authorization": ["Authentification requise."]
  }
}
```

### 403 (compte local inactif)

```json
{
  "success": false,
  "message": "Compte utilisateur inactif.",
  "errors": {
    "authorization": ["Ce compte est desactive."]
  }
}
```

### 422 (validation)
Actuellement, les `FormRequest` retournent le format Laravel standard:

```json
{
  "message": "Le parametre lat doit etre compris entre -90 et 90.",
  "errors": {
    "lat": ["Le parametre lat doit etre compris entre -90 et 90."]
  }
}
```

## 4) Endpoints disponibles

### Pharmacies
- `GET /pharmacies?per_page=15&city=lome`
- `GET /pharmacies/on-duty?per_page=15`
- `GET /pharmacies/nearby?lat=6.13&lng=1.22&radius_km=20&per_page=15`
- `GET /pharmacies/{id}`

### Medicaments
- `GET /medicaments?per_page=15`
- `GET /medicaments/search?q=paracetamol&per_page=15`
- `GET /medicaments/{id}`
- `GET /medicaments/{id}/pharmacies?per_page=15`

### Hopitaux
- `GET /hopitaux?per_page=15&city=lome`
- `GET /hopitaux/search?q=chu&per_page=15`
- `GET /hopitaux/nearby?lat=6.13&lng=1.22&radius_km=30&per_page=15`
- `GET /hopitaux/{id}`

### Examens
- `GET /examens?per_page=15`
- `GET /examens/search?q=scanner&per_page=15`
- `GET /examens/{id}`
- `GET /examens/{id}/hopitaux?per_page=15`

## 4.1) Messages de reponse exacts (FR)

### Pharmacies
- `GET /pharmacies` -> `Liste des pharmacies chargee avec succes.`
- `GET /pharmacies/on-duty` -> `Liste des pharmacies de garde chargee avec succes.`
- `GET /pharmacies/nearby` -> `Liste des pharmacies proches chargee avec succes.`
- `GET /pharmacies/{id}` -> `Details de la pharmacie charges avec succes.`

### Medicaments
- `GET /medicaments` -> `Liste des medicaments chargee avec succes.`
- `GET /medicaments/search` -> `Recherche de medicaments effectuee avec succes.`
- `GET /medicaments/{id}` -> `Details du medicament charges avec succes.`
- `GET /medicaments/{id}/pharmacies` -> `Pharmacies proposant ce medicament chargees avec succes.`

### Hopitaux
- `GET /hopitaux` -> `Liste des hopitaux chargee avec succes.`
- `GET /hopitaux/search` -> `Recherche d'hopitaux effectuee avec succes.`
- `GET /hopitaux/nearby` -> `Liste des hopitaux proches chargee avec succes.`
- `GET /hopitaux/{id}` -> `Details de l'hopital charges avec succes.`

### Examens
- `GET /examens` -> `Liste des examens chargee avec succes.`
- `GET /examens/search` -> `Recherche d'examens effectuee avec succes.`
- `GET /examens/{id}` -> `Details de l'examen charges avec succes.`
- `GET /examens/{id}/hopitaux` -> `Hopitaux proposant cet examen charges avec succes.`

### Erreurs d'authentification
- `401` sans bearer token -> `Token Bearer manquant.`
- `401` token invalide/expire -> `Token Firebase invalide.`
- `401` uid absent du token -> `Identifiant utilisateur manquant dans le token.`
- `403` utilisateur inactif -> `Compte utilisateur inactif.`

## 5) Champs retournes par ressource

### Pharmacy
```json
{
  "id": 1,
  "name": "Pharmacie Centrale",
  "address": "Avenue X",
  "city": "Lome",
  "phone": "+22890000000",
  "latitude": "6.1319000",
  "longitude": "1.2228000",
  "is_on_duty": true,
  "opens_at": "08:00:00",
  "closes_at": "22:00:00",
  "created_at": "...",
  "updated_at": "...",
  "distance_km": 1.42
}
```

`distance_km` apparait seulement sur les endpoints `nearby`.

### Medicament
```json
{
  "id": 1,
  "name": "Paracetamol",
  "dosage": "500mg",
  "form": "comprime",
  "description": null,
  "is_active": true,
  "created_at": "...",
  "updated_at": "..."
}
```

### Hopital
```json
{
  "id": 1,
  "name": "CHU Campus",
  "address": "Boulevard Y",
  "city": "Lome",
  "phone": "+22891000000",
  "latitude": "6.1450000",
  "longitude": "1.2100000",
  "emergency_available": true,
  "created_at": "...",
  "updated_at": "...",
  "distance_km": 2.10,
  "examens": []
}
```

### Examen
```json
{
  "id": 1,
  "name": "Scanner",
  "category": "Imagerie",
  "description": null,
  "is_active": true,
  "created_at": "...",
  "updated_at": "..."
}
```

## 6) Particularites des relations (pivot)

- `GET /medicaments/{id}/pharmacies` retourne les pharmacies avec un objet `pivot`:

```json
{
  "pivot": {
    "medicament_id": 1,
    "pharmacy_id": 2,
    "stock_quantity": 30,
    "is_available": true,
    "price": "2500.00",
    "created_at": "...",
    "updated_at": "..."
  }
}
```

- `GET /examens/{id}/hopitaux` retourne les hopitaux avec `pivot`:

```json
{
  "pivot": {
    "examen_id": 1,
    "hopital_id": 3,
    "is_available": true,
    "preparation_notes": "A jeun 8h",
    "created_at": "...",
    "updated_at": "..."
  }
}
```

## 7) Regles frontend recommandees

- Toujours parser `success`, `message`, `data`, `meta`.
- Toujours gerer `401`, `403`, `422`.
- Gerer les listes vides (`data: []`) sans erreur UI.
- Conserver `per_page` cote frontend pour pagination infinie/chargee.
- Pour les ecrans map/proximite, utiliser `distance_km` si present.

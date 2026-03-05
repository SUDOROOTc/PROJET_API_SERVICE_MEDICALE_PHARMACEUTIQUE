# MVP Day 1 Blueprint

## 1) Domain Model

### Core entities
- pharmacies
- medicaments
- hopitaux
- examens

### Relation tables
- medicament_pharmacy (disponibilité d'un médicament en pharmacie)
- examen_hopital (examens proposés par un hôpital)

## 2) Data Rules

### pharmacies
- `name`: required, indexed
- `address`: required
- `city`: required, indexed
- `phone`: nullable
- `latitude`, `longitude`: nullable, indexed pair
- `is_on_duty`: boolean, indexed
- `opens_at`, `closes_at`: nullable

### medicaments
- `name`: required, indexed
- `dosage`, `form`: nullable
- `description`: nullable
- `is_active`: boolean, indexed

### hopitaux
- `name`: required, indexed
- `address`: required
- `city`: required, indexed
- `phone`: nullable
- `latitude`, `longitude`: nullable, indexed pair
- `emergency_available`: boolean, indexed

### examens
- `name`: required, indexed
- `category`: nullable, indexed
- `description`: nullable
- `is_active`: boolean, indexed

### medicament_pharmacy
- `medicament_id`, `pharmacy_id`: unique pair
- `stock_quantity`: int, default 0
- `is_available`: boolean, indexed
- `price`: decimal nullable

### examen_hopital
- `examen_id`, `hopital_id`: unique pair
- `is_available`: boolean, indexed
- `preparation_notes`: nullable

## 3) API Contract (V1)

Base URL: `/api/v1`

### Pharmacies
- `GET /pharmacies`
- `GET /pharmacies/{id}`
- `GET /pharmacies/on-duty`
- `GET /pharmacies/nearby?lat=..&lng=..&radius_km=..`

### Medicaments
- `GET /medicaments`
- `GET /medicaments/{id}`
- `GET /medicaments/search?q=..`
- `GET /medicaments/{id}/pharmacies`
- `GET /pharmacies/{pharmacyId}/medicaments/{medicamentId}/availability`

### Hopitaux
- `GET /hopitaux`
- `GET /hopitaux/{id}`
- `GET /hopitaux/search?q=..`
- `GET /hopitaux/nearby?lat=..&lng=..&radius_km=..`

### Examens
- `GET /examens`
- `GET /examens/{id}`
- `GET /examens/search?q=..`
- `GET /examens/{id}/hopitaux`

## 4) Response standards

Success format:
```json
{
  "success": true,
  "message": "...",
  "data": {},
  "meta": {}
}
```

Error format:
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "field": ["error"]
  }
}
```

## 5) Day 2 readiness checklist
- Domain migrations created
- Eloquent models created with many-to-many relations
- API contract frozen for V1 endpoints
- Search and geolocation endpoints defined for implementation

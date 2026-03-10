# Connecter ton backend Laravel a Flutter et utiliser chaque endpoint

Ce document explique clairement comment appeler ton API depuis Flutter.
Il est base sur les routes reelles de `routes/api.php`.

## 1. Base URL et securite

Toutes les routes sont sous:

```text
/api/v1
```

Exemples de base URL:

- Android emulator: `http://10.0.2.2:8000/api/v1`
- iOS simulator: `http://127.0.0.1:8000/api/v1`
- Telephone physique: `http://IP_DE_TON_PC:8000/api/v1`

Toutes les routes exigent un token Firebase valide (middleware `firebase.auth`).

Headers a envoyer:

```http
Accept: application/json
Authorization: Bearer <firebase_id_token>
```

## 2. Client Flutter de base

Ajoute la dependance:

```bash
flutter pub add http
```

Client minimal:

```dart
import 'dart:convert';
import 'package:http/http.dart' as http;

class ApiClient {
  ApiClient({required this.baseUrl, required this.firebaseToken});

  final String baseUrl; // ex: http://10.0.2.2:8000/api/v1
  final String firebaseToken;

  Map<String, String> get _headers => {
        'Accept': 'application/json',
        'Authorization': 'Bearer $firebaseToken',
      };

  Future<Map<String, dynamic>> getJson(String path, {Map<String, dynamic>? query}) async {
    final uri = Uri.parse('$baseUrl$path').replace(
      queryParameters: query?.map((k, v) => MapEntry(k, v.toString())),
    );
    final res = await http.get(uri, headers: _headers);

    if (res.statusCode >= 200 && res.statusCode < 300) {
      return jsonDecode(res.body) as Map<String, dynamic>;
    }

    throw Exception('HTTP ${res.statusCode}: ${res.body}');
  }
}
```

## 3. Lire la reponse API correctement

Format succes standard:

```json
{
  "success": true,
  "message": "...",
  "data": [],
  "meta": {}
}
```

En Flutter:

```dart
final json = await api.getJson('/pharmacies', query: {'per_page': 15});
final data = json['data'];
final message = json['message'];
final meta = json['meta'];
```

## 4. Utilisation claire de chaque endpoint

Chaque exemple ci-dessous utilise `api.getJson(...)`.

## 4.1 Pharmacies

### GET `/pharmacies`

But: lister les pharmacies (pagination + filtre ville).

Params query:

- `per_page` (optionnel, 1..100)
- `city` (optionnel)

Exemple URL:

```text
/pharmacies?per_page=15&city=Ouagadougou
```

Flutter:

```dart
final res = await api.getJson('/pharmacies', query: {
  'per_page': 15,
  'city': 'Ouagadougou',
});
final pharmacies = res['data'] as List<dynamic>;
```

### GET `/pharmacies/de-garde`

But: pharmacies de garde selon date et ville.

Params query:

- `date` (optionnel, format `YYYY-MM-DD`)
- `ville` (optionnel, defaut `Ouagadougou`)
- `per_page` (optionnel)

Exemple URL:

```text
/pharmacies/de-garde?date=2026-03-09&ville=Ouagadougou&per_page=10
```

Flutter:

```dart
final res = await api.getJson('/pharmacies/de-garde', query: {
  'date': '2026-03-09',
  'ville': 'Ouagadougou',
  'per_page': 10,
});
final data = res['data'] as List<dynamic>;
final gardeMeta = res['meta']?['garde'];
```

### GET `/pharmacies/par-medicament`

But: trouver les pharmacies ayant un medicament en stock.

Params query:

- `q` (obligatoire, nom medicament)
- `min_stock` (optionnel, defaut 1)
- `city` (optionnel)
- `per_page` (optionnel)

Exemple URL:

```text
/pharmacies/par-medicament?q=amoxicilline&min_stock=5&city=Ouagadougou
```

Flutter:

```dart
final res = await api.getJson('/pharmacies/par-medicament', query: {
  'q': 'amoxicilline',
  'min_stock': 5,
  'city': 'Ouagadougou',
});
final pharmacies = res['data'] as List<dynamic>;
```

### GET `/pharmacies/on-duty`

But: lister les pharmacies marquees `is_on_duty = true`.

Params query:

- `per_page` (optionnel)

Flutter:

```dart
final res = await api.getJson('/pharmacies/on-duty', query: {'per_page': 15});
final data = res['data'] as List<dynamic>;
```

### GET `/pharmacies/nearby`

But: lister les pharmacies les plus proches.

Params query:

- `lat` (obligatoire)
- `lng` (obligatoire)
- `radius_km` (optionnel, defaut 20)
- `per_page` (optionnel)

Exemple URL:

```text
/pharmacies/nearby?lat=12.37&lng=-1.52&radius_km=10
```

Flutter:

```dart
final res = await api.getJson('/pharmacies/nearby', query: {
  'lat': 12.37,
  'lng': -1.52,
  'radius_km': 10,
  'per_page': 20,
});
final data = res['data'] as List<dynamic>; // contient distance_km
```

### GET `/pharmacies/{id}`

But: details d une pharmacie.

Flutter:

```dart
final res = await api.getJson('/pharmacies/1');
final pharmacie = res['data'];
```

## 4.2 Medicaments

### GET `/medicaments`

But: lister les medicaments actifs.

```dart
final res = await api.getJson('/medicaments', query: {'per_page': 15});
final data = res['data'] as List<dynamic>;
```

### GET `/medicaments/search`

But: recherche par nom.

Params query:

- `q` (obligatoire, min 2 caracteres)
- `per_page` (optionnel)

```dart
final res = await api.getJson('/medicaments/search', query: {
  'q': 'paracetamol',
  'per_page': 15,
});
final data = res['data'] as List<dynamic>;
```

### GET `/medicaments/{id}`

But: details d un medicament.

```dart
final res = await api.getJson('/medicaments/2');
final medicament = res['data'];
```

### GET `/medicaments/{id}/pharmacies`

But: pharmacies qui proposent ce medicament.

```dart
final res = await api.getJson('/medicaments/2/pharmacies');
final pharmacies = res['data'] as List<dynamic>;
```

Note: chaque pharmacie peut inclure un `pivot` (stock, disponibilite, prix).

## 4.3 Hopitaux

### GET `/hopitaux`

But: lister les hopitaux (pagination + filtre ville).

```dart
final res = await api.getJson('/hopitaux', query: {
  'per_page': 15,
  'city': 'Ouagadougou',
});
final data = res['data'] as List<dynamic>;
```

### GET `/hopitaux/par-examen`

But: hopitaux qui proposent un examen.

Params query:

- `examen` (obligatoire, min 2 caracteres)
- `city` (optionnel)
- `per_page` (optionnel)

```dart
final res = await api.getJson('/hopitaux/par-examen', query: {
  'examen': 'scanner',
  'city': 'Ouagadougou',
  'per_page': 15,
});
final data = res['data'] as List<dynamic>;
```

### GET `/hopitaux/search`

But: recherche d hopitaux par texte.

```dart
final res = await api.getJson('/hopitaux/search', query: {
  'q': 'chu',
  'per_page': 15,
});
final data = res['data'] as List<dynamic>;
```

### GET `/hopitaux/nearby`

But: hopitaux proches d une position.

```dart
final res = await api.getJson('/hopitaux/nearby', query: {
  'lat': 12.37,
  'lng': -1.52,
  'radius_km': 25,
  'per_page': 15,
});
final data = res['data'] as List<dynamic>; // distance_km possible
```

### GET `/hopitaux/{id}`

But: details d un hopital.

```dart
final res = await api.getJson('/hopitaux/1');
final hopital = res['data'];
```

## 4.4 Examens

### GET `/examens`

But: lister les examens actifs.

```dart
final res = await api.getJson('/examens', query: {'per_page': 15});
final data = res['data'] as List<dynamic>;
```

### GET `/examens/search`

But: recherche d examens par nom.

```dart
final res = await api.getJson('/examens/search', query: {
  'q': 'scanner',
  'per_page': 15,
});
final data = res['data'] as List<dynamic>;
```

### GET `/examens/{id}`

But: details d un examen.

```dart
final res = await api.getJson('/examens/1');
final examen = res['data'];
```

### GET `/examens/{examenNom}/hopitaux`

But: hopitaux qui proposent un examen (route basee sur le nom dans ton code actuel).

Exemple URL:

```text
/examens/scanner/hopitaux?per_page=15
```

Flutter:

```dart
final res = await api.getJson('/examens/scanner/hopitaux', query: {
  'per_page': 15,
});
final hopitaux = res['data'] as List<dynamic>;
```

## 5. Gestion des erreurs cote Flutter

Codes frequents:

- `401`: token absent/invalide/expire
- `403`: utilisateur inactif
- `422`: erreur de validation (parametre manquant ou invalide)
- `404`: ressource inexistante

Pattern simple:

```dart
try {
  final res = await api.getJson('/medicaments/search', query: {'q': 'pa'});
  // utiliser res['data']
} catch (e) {
  // afficher un message utilisateur + log debug
}
```

## 6. Checklist rapide si ca ne marche pas

1. Backend lance avec `php artisan serve --host=0.0.0.0 --port=8000`.
2. URL correcte selon emulator/appareil.
3. Header `Authorization: Bearer <firebase_id_token>` present.
4. Parametres obligatoires (`q`, `lat`, `lng`, etc.) bien envoyes.
5. Test de l endpoint d abord dans Postman.
6. Verification de `storage/logs/laravel.log`.

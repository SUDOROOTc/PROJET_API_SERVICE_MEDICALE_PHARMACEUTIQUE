## Plan d’implémentation

1. Créer un seeder dédié `PharmacyMarkdownSeeder`.
2. Lire `docs/liste_pharmacie.md` ligne par ligne.
3. Détecter le contexte courant:
- ville (`## **...**`)
- groupe (`### **Groupe N**`)
4. Parser chaque ligne de tableau pharmacie en 4 champs:
- nom
- téléphone
- situation géographique
- quartier/ville
5. Mapper vers la table `pharmacies`:
- `name` = nom
- `phone` = téléphone (nettoyé)
- `address` = situation géographique
- `city` = quartier/ville
- `groupe` = groupe détecté
- `latitude` = `null`
- `longitude` = `null`
- `is_on_duty` = `false` (par défaut)
6. Gérer les 17 lignes sans adresse:
- fallback `address` = `"Adresse non renseignee - {city}"`
  (pour respecter le `NOT NULL` actuel)
7. Empêcher les doublons à l’import:
- stratégie `updateOrCreate` sur `name + city` (ou `name + city + groupe`).
8. Exécuter le seeder et produire un mini rapport:
- insérés
- mis à jour
- ignorés/erreurs

## Résultat attendu

- Les 309 pharmacies sont en base avec leur groupe.
- API `/pharmacies` et `/pharmacies/on-duty` deviennent exploitables immédiatement.
- Les endpoints `nearby` fonctionneront seulement après ajout des lat/lng.

## Petite décision à valider avant exécution

- Clé anti-doublon:
1. `name + city` (plus strict, évite doublons métier)
2. `name + city + groupe` (autorise même nom dans groupes différents)

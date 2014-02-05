# Table Lookup

Le plugin docalist-core implémente une action ajax qui permet de consulter et 
d'interroger les tables d'autorité définies.

Le point d'entrée est disponible à l'url suivante :

```
http://wordpress/wp-admin/admin-ajax.php?action=docalist-table-lookup
```

Cette action accepte plusieurs paramètres qui permettent de récupérer le contenu d'une table et de faire des recherches sur un ou plusieurs champs.

Les réponses sont retournées sous la forme d'un tableau au format JSON et sont mises en cache par le navigateur.
  
## Paramètres

### action

- Nom de l'action à exécuter : `docalist-table-lookup`.
- Obligatoire.


### table

- Nom de la table d'autorité à consulter. 
- Obligatoire. 
- Une exception est générée si la table indiquée n'existe pas.
- Exemple : `countries`, `languages`.


### what

- Liste des champs à retourner : une chaîne contenant les noms des champs de la
  table, séparés par une virgule, que la requête doit retourner.
- Optionnel.
- Valeur par défaut : `code,label`. 
- Exemples : `ROWID,code` 

### where
- Critères de recherche, sous la forme d'une chaîne correspondant à la clause
  `where` de la requête SQL exécutée. 
- Exemples : `code="FRA"`, `label LIKE "Fr%"`, `code IN ("FRA","ESP")`

Dans les tables, tous les champs sont en double : un champ contenant les données réelles, un champ de même nom, avec le préfixe "_", contenant une version minusculisée et désaccentuée des données du champ.

Cela permet d'effectuer des recherches exactes ou des recherches insensibles à la 
casse. Par exemple, une clause `where` de la forme `label LIKE "viet%"` ne retournera
aucun résultat car l'entrée stockée dans le champ label contient la chaîne `Viêt Nam`.

Par contre, la même requête exécutée sur le champ `_label` (`_label LIKE "viet%"`) 
retournera bien les résultats attendus.

Vous pouvez utiliser une valeur comme `ROWID,code,_code,label,_label` dans le
paramètre what de la requête pour voir exactement ce qui est stocké dans la table.
  
Exemple avec `table=countries`, `what=ROWID,code,_code,label,_label` et `where=code="VNM"` :
  
```json
[
    {
        "rowid": "242",
        "code": "VNM",
        "_code": "vnm",
        "label": "Viêt Nam",
        "_label": "viet nam"
    }
]
```

### order

- Ordre de tri des réponses
- Correspond à la clause `ORDER BY` de la requête SQL exécutée.
- Optionnel
- Valeur par défaut : pas de valeur par défaut, les réponses sont retournées dans l'ordre de la
  table.
- Exemple : `_label ASC` (tri ascendant par libellé, insensible à la casse des caractères).

### limit

- Nombre maximum de valeurs à retourner.
- Correspond à la clause `LIMIT` de la requête SQL exécutée.
- Optionnel.
- Valeur par défaut : pas de limite.

### offset

- Offset de la première réponse à retourner.
- Correspond à la clause `OFFSET` de la requête SQL exécutée.
- Optionnel
- Valeur par défaut : `0`


## Format des réponses

Les réponses obtenues sont retournées au format JSON, sous la forme d'un tableau contenant un objet pour chacune des réponses obtenues (`Content-Type: application/json; charset=UTF-8`).

Chaque objet contient des propriétés qui correspondent exactement aux champs indiqués dans le paramètre `what` de la requête.

Exemple pour `/wordpress/wp-admin/admin-ajax.php?action=docalist-table-lookup&table=countries&what=code,label&limit=2` 

```json
[
    {
        "code": "ABW",
        "label": "Aruba"
    },
    {
        "code": "AFG",
        "label": "Afghanistan"
    }
]
```

En cas d'erreur (paramètre manquant, option incorrecte, table inexistante, etc.) une exception est générée, ce qui aura pour effet de retourner une réponse au format html.

## Mise en cache par le navigateur

Les réponses générées utilisent le protocole `HTTP/1.1` et génèrent les entêtes `Connection: Keep-Alive` et `Keep-Alive: timeout=5, max=100` pour que la connection reste active entre deux requêtes.

Les réponses générées seront mises en cache par le navigateur pendant 10 minutes (actuellement, cette limite est fixée en dur). Pour cela, l'entête `Cache-Control: max-age=600, public, s-maxage=600` est généré dans la réponse.

Exemple complet d'entêtes http retournés :

```
HTTP/1.1 200 OK
Date: Wed, 05 Feb 2014 10:11:11 GMT
Server: Apache
Cache-Control: max-age=600, public, s-maxage=600
Keep-Alive: timeout=5, max=100
Connection: Keep-Alive
Transfer-Encoding: chunked
Content-Type: application/json; charset=UTF-8
```

Pendant le développement, la mise en cache peut être gênante. Pour être certain d'avoir une réponse à jour, plusieurs options sont possibles :

- vider le cache du navigateur,
- ouvrir la requête dans une nouvelle fenêtre et faire un shift-F5 pour forcer un rafraîchissement du cache,
- attendre au moins 10 minutes.

## Mode debug

En mode debug (`WP_DEBUG` à `true`), les réponses sont générées en format "PRETTY_PRINT", ce qui permet, pendant le développement, une lecture plus facile des résultats :

```json
[
    {
        "code": "ABW",
        "label": "Aruba"
    },
    {
        "code": "AFG",
        "label": "Afghanistan"
    }
]
```

Lorsque le mode debug est désactivé (`WP_DEBUG` non définie ou valant `false`), les réponses générées sont optimisées en taille :

```json
[{"rowid":"242","code":"VNM","_code":"vnm","label":"Viêt Nam","_label":"viet nam"}]
```

Remarque : en mode production, assurez-vous que le mode debug est désactivé.
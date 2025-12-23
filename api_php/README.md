# API PHP pour MV3 Pro Mobile

Ces fichiers API doivent être uploadés sur votre serveur Dolibarr dans le dossier :
`/custom/mv3pro_portail/api_mobile/`

## Installation

1. **Créer le dossier api_mobile** :
```bash
mkdir -p /var/www/dolibarr/htdocs/custom/mv3pro_portail/api_mobile
```

2. **Uploader tous les fichiers** :
```
api_mobile/
├── config.php
├── auth/
│   ├── login.php
│   ├── logout.php
│   └── verify.php
├── reports/
│   ├── create.php
│   └── list.php
├── regie/
│   ├── create.php
│   └── list.php
├── sens_pose/
│   ├── create.php
│   └── list.php
├── dashboard/
│   └── stats.php
├── weather/
│   └── current.php
├── photos/
│   └── upload.php
├── clients/
│   └── list.php
├── projects/
│   └── list.php
└── materials/
    └── list.php
```

3. **Configurer config.php** :
Éditez `config.php` et modifiez :
- `DOLIBARR_DB_HOST` : Hôte de votre base de données
- `DOLIBARR_DB_NAME` : Nom de votre base Dolibarr
- `DOLIBARR_DB_USER` : Utilisateur MySQL
- `DOLIBARR_DB_PASS` : Mot de passe MySQL
- `JWT_SECRET` : Clé secrète aléatoire pour JWT

4. **Créer les tables nécessaires** :
```sql
-- Table pour les photos des rapports
CREATE TABLE IF NOT EXISTS llx_mv3_rapport_photos (
    rowid INT AUTO_INCREMENT PRIMARY KEY,
    rapport_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    file_size INT DEFAULT 0,
    uploaded_at DATETIME NOT NULL,
    FOREIGN KEY (rapport_id) REFERENCES llx_mv3_rapport(rowid) ON DELETE CASCADE
);
```

5. **Vérifier les permissions** :
```bash
chmod 755 api_mobile/
chmod 644 api_mobile/*.php
chmod 755 api_mobile/*/
chmod 644 api_mobile/*/*.php
```

6. **Configurer l'URL dans la PWA** :
Dans le fichier `.env` de la PWA, définir :
```
VITE_API_URL=https://crm.mv-3pro.ch/custom/mv3pro_portail/api_mobile
```

## Test de l'API

Test de login :
```bash
curl -X POST https://crm.mv-3pro.ch/custom/mv3pro_portail/api_mobile/auth/login.php \
  -H "Content-Type: application/json" \
  -d '{"email":"votre@email.com","password":"votre_mot_de_passe"}'
```

Si tout fonctionne, vous recevrez :
```json
{
  "user": {...},
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```

## Configuration OpenWeather

Pour la météo, obtenez une clé API gratuite sur :
https://openweathermap.org/api

Puis modifiez `weather/current.php` :
```php
$apiKey = 'VOTRE_CLE_API_OPENWEATHER';
```

## Sécurité

- ✅ CORS configuré
- ✅ JWT avec expiration
- ✅ Authentification sur toutes les routes
- ✅ Protection contre les injections SQL (PDO prepared statements)
- ✅ Validation des données

## Support

En cas de problème, vérifiez :
1. Les permissions des fichiers
2. La configuration de la base de données dans config.php
3. Les logs Apache/Nginx
4. Les logs d'erreur PHP

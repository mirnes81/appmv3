# MODE DEBUG - Guide d'utilisation

## Changements effectués

Le mode debug reste maintenant **OUVERT** et ne navigue plus automatiquement vers le dashboard.

### Avant
- Affichait le debug pendant 1 seconde
- Fermait automatiquement et allait au dashboard
- Impossible de lire les infos

### Maintenant
- Le debug reste OUVERT indéfiniment
- Vous pouvez lire TOUTES les infos tranquillement
- En cas de succès, un bouton vert apparaît pour continuer manuellement

## Comment utiliser

### 1. Activer le mode debug
Sur la page de login, cliquez sur le bouton en haut:
```
🔍 Debug OFF → 🔍 Debug ON
```

### 2. Se connecter
Remplissez vos identifiants et cliquez sur "Se connecter"

### 3. Lire les infos
Une fois la requête terminée, un grand panneau s'affiche avec:

#### Section REQUEST (rouge)
```json
{
  "URL": "/custom/mv3pro_portail/mobile_app/api/auth.php?action=login",
  "Method": "POST",
  "Headers": {
    "Content-Type": "application/json"
  },
  "Body": {
    "email": "info@mv-3pro.ch",
    "password": "[10 chars] inf..."
  }
}
```

#### Section RESPONSE (vert si 200, rouge si erreur)
```json
{
  "Status": 200,
  "Headers": {
    "content-type": "application/json",
    "access-control-allow-origin": "*"
  },
  "Body": {
    "success": true,
    "token": "abc123...",
    "user": {
      "rowid": "1",
      "email": "info@mv-3pro.ch",
      "nom": "John",
      "prenom": "Doe"
    }
  }
}
```

### 4. En cas de SUCCÈS
Un bouton vert apparaît en bas:
```
✓ LOGIN REUSSI - Continuer vers le Dashboard
```

Cliquez dessus QUAND vous avez fini de lire les infos de debug.

### 5. En cas d'ÉCHEC
Le message d'erreur s'affiche en rouge en haut + le panneau de debug montre la réponse complète avec le status code.

## Ce que vous devez vérifier

### Si Status = 200 et success = true
✅ Le login fonctionne! Le problème était ailleurs.

Vérifiez:
- Le token est-il bien généré?
- Les infos user sont-elles complètes?
- Le bouton vert apparaît-il?

### Si Status = 401 ou 403
❌ Le login échoue côté API

Dans la console serveur, regardez:
```bash
tail -f /var/log/apache2/error.log | grep "MV3 AUTH"
```

Vous verrez:
- `[MV3 AUTH] USER_NOT_FOUND` → L'email n'existe pas
- `[MV3 AUTH] PASSWORD_FAIL` → Le mot de passe est incorrect
- `[MV3 AUTH] ACCOUNT_INACTIVE` → Le compte est désactivé

### Si Status = 500
🔥 Erreur serveur PHP

Regardez la section "Body" dans RESPONSE. Elle devrait contenir le message d'erreur PHP complet.

### Si Status = 0 ou erreur réseau
🌐 Problème de connexion

Vérifiez:
- L'URL est-elle correcte dans REQUEST?
- Le serveur est-il accessible?
- Y a-t-il des erreurs CORS dans les headers?

## Console navigateur (F12)

En plus du panneau visuel, ouvrez la console (F12) pour voir les logs détaillés:

```javascript
[DEBUG] Starting login request { email: "info@mv-3pro.ch", passwordLength: 10, url: "..." }
[DEBUG] Response received { status: 200, headers: {...}, bodyLength: 523, bodyPreview: "..." }
[DEBUG] Login SUCCESS { success: true, token: "...", user: {...} }
```

Ou en cas d'erreur:
```javascript
[DEBUG] Login FAILED { success: false, message: "Mot de passe incorrect." }
```

## Désactiver le mode debug

Cliquez à nouveau sur le bouton en haut:
```
🔍 Debug ON → 🔍 Debug OFF
```

Le login redeviendra normal (navigation automatique vers le dashboard en cas de succès).

## Captures d'écran à m'envoyer

Pour que je puisse vous aider, envoyez-moi:

1. **Capture du panneau DEBUG complet** (REQUEST + RESPONSE)
2. **Copie des logs serveur** `[MV3 AUTH]` dans error.log
3. **Console navigateur** (F12) si possible

Avec ces 3 infos, je saurai EXACTEMENT où est le problème!

---

Version mise à jour le 2026-01-09

# FIX: Boucle de redirection après login (mode debug)

Date: 2026-01-09

---

## 🐛 Problème identifié

**Symptôme:**
- Toutes les étapes du mode debug passent au vert ✅
- L'étape 4 "Redirection Dashboard" s'exécute
- MAIS l'utilisateur est immédiatement renvoyé sur la page de login
- Boucle infinie: login → dashboard → login → dashboard...

**Cause racine:**

Le mode debug ne mettait **JAMAIS à jour le contexte d'authentification** (`AuthContext`).

Quand on arrive sur `/dashboard`:
```typescript
// Dans ProtectedRoute.tsx
const { isAuthenticated } = useAuth();
// isAuthenticated = !!user
// user = null (jamais mis à jour)
// donc isAuthenticated = false
if (!isAuthenticated) {
  return <Navigate to="/login" replace />;  // ← BOUCLE!
}
```

## ✅ Solution appliquée

**AVANT:**
```typescript
navigate('/dashboard', { replace: true });
```

**APRÈS:**
```typescript
// Force un reload complet pour que AuthContext recharge l'utilisateur
window.location.href = '/#/dashboard';
```

**Pourquoi ça fonctionne:**
1. Reload complet de la page
2. Le `useEffect` du `AuthContext` se déclenche
3. Lit le token depuis `localStorage`
4. Appelle `/api/v1/me.php`
5. Met à jour `setUser(userData)`
6. `isAuthenticated` devient `true`
7. Le `ProtectedRoute` laisse passer

---

Date: 2026-01-09
Fichier: `/new_dolibarr/mv3pro_portail/pwa/src/pages/Login.tsx`
Status: ✅ CORRIGÉ

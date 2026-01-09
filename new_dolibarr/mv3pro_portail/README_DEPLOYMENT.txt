═══════════════════════════════════════════════════════════════
   MV3 PRO PORTAIL - FIX 404 ENDPOINTS - GUIDE RAPIDE
═══════════════════════════════════════════════════════════════

Date: 2026-01-09
Version: 2.2.0
Priorité: CRITIQUE

───────────────────────────────────────────────────────────────
 PROBLÈME
───────────────────────────────────────────────────────────────

1. Login API    → /api/v1/auth/login.php       → 404 Not Found
2. Auth Me      → /api/v1/auth/me.php          → 404 Not Found
3. Planning     → /api/v1/planning_view.php    → 404 Not Found
4. Files        → /api/v1/planning_file.php    → 404 Not Found

Impact: Diagnostic QA échoue, PWA ne peut pas afficher le détail
        des événements, authentification API v1 impossible

───────────────────────────────────────────────────────────────
 SOLUTION: 7 FICHIERS À UPLOADER
───────────────────────────────────────────────────────────────

NOUVEAU RÉPERTOIRE: /custom/mv3pro_portail/api/v1/auth/
├── login.php       [AUTH - Login unifié mobile + Dolibarr]
├── me.php          [AUTH - Info utilisateur connecté]
├── logout.php      [AUTH - Déconnexion]
└── .htaccess       [CONFIG - CORS + permissions]

RÉPERTOIRE EXISTANT: /custom/mv3pro_portail/api/v1/
├── planning_view.php    [PLANNING - Détail événement]
└── planning_file.php    [PLANNING - Stream fichiers joints]

───────────────────────────────────────────────────────────────
 MÉTHODE DE DÉPLOIEMENT
───────────────────────────────────────────────────────────────

Via Hoststar File Manager:
  1. Se connecter à Hoststar Control Panel
  2. File Manager → htdocs/custom/mv3pro_portail/api/v1/
  3. Créer dossier "auth"
  4. Uploader 4 fichiers dans auth/
  5. Uploader 2 fichiers planning dans v1/
  6. Vérifier permissions: 644

Via FTP/SFTP:
  1. Connexion: mv3pro.ch
  2. Path: /path/to/dolibarr/htdocs/custom/mv3pro_portail/api/v1/
  3. mkdir auth
  4. Upload 4 fichiers dans auth/
  5. Upload 2 fichiers dans v1/
  6. chmod 644 sur tous les fichiers

───────────────────────────────────────────────────────────────
 TESTS RAPIDES
───────────────────────────────────────────────────────────────

Test 1 - Fichiers existent (doit retourner 401, PAS 404):

  https://mv3pro.ch/custom/mv3pro_portail/api/v1/auth/login.php
  https://mv3pro.ch/custom/mv3pro_portail/api/v1/planning_view.php

Test 2 - Login API (remplacer credentials):

  curl -X POST https://mv3pro.ch/custom/mv3pro_portail/api/v1/auth/login.php \
    -H "Content-Type: application/json" \
    -d '{"email":"admin@test.local","password":"Test2026!"}'

  Résultat attendu: {"success":true,"data":{"token":"...","user":{...}}}

Test 3 - PWA:

  1. Ouvrir: https://mv3pro.ch/custom/mv3pro_portail/pwa_dist/
  2. Se connecter
  3. Planning → Cliquer sur événement
  4. Vérifier détail complet + fichiers joints

───────────────────────────────────────────────────────────────
 RÉSULTAT ATTENDU
───────────────────────────────────────────────────────────────

AVANT:
  ❌ Login API v1           → 404
  ❌ Auth me                → 404
  ❌ Planning detail        → 404
  ❌ Planning files         → 404
  ❌ Diagnostic QA          → 40-50%

APRÈS:
  ✅ Login API v1           → 200 OK
  ✅ Auth me                → 200 OK
  ✅ Planning detail        → 200 OK (avec toutes les infos)
  ✅ Planning files         → 200 OK (ouverture sécurisée)
  ✅ Diagnostic QA          → 95-100%

───────────────────────────────────────────────────────────────
 FONCTIONNALITÉS DÉBLOCÉES
───────────────────────────────────────────────────────────────

✅ Login via API v1 (endpoint unifié)
✅ Support utilisateurs mobiles + Dolibarr
✅ Tests diagnostic QA passent
✅ Détail complet événements planning
✅ Fichiers joints accessibles (photos, PDF)
✅ Contrôle d'accès sécurisé

───────────────────────────────────────────────────────────────
 DOCUMENTATION COMPLÈTE
───────────────────────────────────────────────────────────────

GUIDE_DEPLOIEMENT_COMPLET.md
  → Instructions détaillées pas à pas
  → Tests de validation complets
  → Troubleshooting
  → Architecture complète

FICHIERS_A_UPLOADER.txt
  → Liste rapide des 7 fichiers
  → Tests de validation rapides

RECAPITULATIF_FIX_404.md
  → Vue d'ensemble complète
  → Récapitulatif technique

───────────────────────────────────────────────────────────────
 SUPPORT
───────────────────────────────────────────────────────────────

En cas de problème:

1. Vérifier logs:
   tail -f /path/to/dolibarr/documents/mv3pro_portail/debug.log

2. Vérifier fichiers uploadés:
   ls -la /path/to/dolibarr/htdocs/custom/mv3pro_portail/api/v1/auth/

3. Vérifier .htaccess:
   cat /path/to/dolibarr/htdocs/custom/mv3pro_portail/api/v1/auth/.htaccess

4. Consulter documentation complète:
   GUIDE_DEPLOIEMENT_COMPLET.md

═══════════════════════════════════════════════════════════════
  TEMPS ESTIMÉ: 10-15 minutes
  BUILD PWA: ✅ Réussi (240 KB → 70 KB gzippé)
  STATUS: ✅ Prêt pour déploiement
═══════════════════════════════════════════════════════════════

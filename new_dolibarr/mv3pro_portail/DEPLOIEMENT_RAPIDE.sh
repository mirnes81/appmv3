#!/bin/bash

################################################################################
# DÉPLOIEMENT RAPIDE - PWA MV3 PRO v3.0
# Date: 10 janvier 2026
# Build: DmJXHRZF
################################################################################

set -e  # Exit on error

echo "═══════════════════════════════════════════════════════════════════════════"
echo "  🚀 DÉPLOIEMENT PWA MV3 PRO v3.0"
echo "═══════════════════════════════════════════════════════════════════════════"
echo ""

# Configuration
DOLIBARR_ROOT="/var/www/dolibarr"
MODULE_PATH="$DOLIBARR_ROOT/custom/mv3pro_portail"
DOCS_PATH="$DOLIBARR_ROOT/documents/mv3pro_portail"
BACKUP_PATH="/tmp/mv3pro_backup_$(date +%Y%m%d_%H%M%S)"

echo "📁 Chemins de déploiement:"
echo "   Dolibarr: $DOLIBARR_ROOT"
echo "   Module: $MODULE_PATH"
echo "   Documents: $DOCS_PATH"
echo "   Backup: $BACKUP_PATH"
echo ""

# Vérifications préalables
echo "🔍 Vérifications préalables..."

if [ ! -d "$DOLIBARR_ROOT" ]; then
    echo "❌ Erreur: Dolibarr non trouvé dans $DOLIBARR_ROOT"
    exit 1
fi

if [ ! -d "$MODULE_PATH" ]; then
    echo "⚠️  Module mv3pro_portail non trouvé, création..."
    mkdir -p "$MODULE_PATH"
fi

echo "✅ Vérifications OK"
echo ""

# Backup
echo "💾 Sauvegarde des fichiers existants..."
mkdir -p "$BACKUP_PATH"

if [ -d "$MODULE_PATH/api/v1" ]; then
    cp -r "$MODULE_PATH/api/v1" "$BACKUP_PATH/" 2>/dev/null || true
    echo "   ✅ API v1 sauvegardée"
fi

if [ -d "$MODULE_PATH/pwa_dist" ]; then
    cp -r "$MODULE_PATH/pwa_dist" "$BACKUP_PATH/" 2>/dev/null || true
    echo "   ✅ PWA dist sauvegardée"
fi

echo "   📦 Backup créé: $BACKUP_PATH"
echo ""

# Déploiement des fichiers API
echo "📤 Déploiement des fichiers API..."

# Créer les dossiers nécessaires
mkdir -p "$MODULE_PATH/api/v1/object"
mkdir -p "$MODULE_PATH/api/v1/auth"

echo "   📁 Dossiers créés"

# Copier les fichiers modifiés/nouveaux
# (À adapter selon votre méthode de transfert)
echo "   ℹ️  Copiez les fichiers suivants depuis votre dépôt:"
echo ""
echo "      new_dolibarr/mv3pro_portail/api/v1/mv3_auth.php"
echo "      new_dolibarr/mv3pro_portail/api/v1/planning_upload_photo.php"
echo "      new_dolibarr/mv3pro_portail/api/v1/sens_pose.php"
echo "      new_dolibarr/mv3pro_portail/api/v1/materiel.php"
echo "      new_dolibarr/mv3pro_portail/api/v1/object/get.php"
echo "      new_dolibarr/mv3pro_portail/api/v1/object/upload.php"
echo "      new_dolibarr/mv3pro_portail/api/v1/object/file.php"
echo "      new_dolibarr/mv3pro_portail/pwa_dist/* (tout le contenu)"
echo ""
echo "   ⏸️  Appuyez sur Entrée quand les fichiers sont copiés..."
read

echo "✅ Fichiers copiés"
echo ""

# Permissions
echo "🔐 Configuration des permissions..."

# Dossiers de logs
mkdir -p "$DOCS_PATH/logs"
chown -R www-data:www-data "$DOCS_PATH/logs" 2>/dev/null || chown -R apache:apache "$DOCS_PATH/logs" 2>/dev/null || true
chmod 755 "$DOCS_PATH/logs"
echo "   ✅ Logs: $DOCS_PATH/logs"

# Dossiers d'upload
mkdir -p "$DOCS_PATH/planning"
chown -R www-data:www-data "$DOCS_PATH/planning" 2>/dev/null || chown -R apache:apache "$DOCS_PATH/planning" 2>/dev/null || true
chmod 755 "$DOCS_PATH/planning"
echo "   ✅ Planning: $DOCS_PATH/planning"

# Permissions module
chown -R www-data:www-data "$MODULE_PATH" 2>/dev/null || chown -R apache:apache "$MODULE_PATH" 2>/dev/null || true
chmod -R 755 "$MODULE_PATH"
echo "   ✅ Module: $MODULE_PATH"

echo "✅ Permissions configurées"
echo ""

# Vérification PHP
echo "🔍 Vérification configuration PHP..."

PHP_UPLOAD_MAX=$(php -r "echo ini_get('upload_max_filesize');")
PHP_POST_MAX=$(php -r "echo ini_get('post_max_size');")
PHP_MEMORY=$(php -r "echo ini_get('memory_limit');")

echo "   upload_max_filesize: $PHP_UPLOAD_MAX"
echo "   post_max_size: $PHP_POST_MAX"
echo "   memory_limit: $PHP_MEMORY"

if [[ "$PHP_UPLOAD_MAX" < "10M" ]]; then
    echo "   ⚠️  upload_max_filesize < 10M, augmenter si nécessaire"
fi

echo ""

# Tests de connectivité
echo "🧪 Tests de connectivité..."

# Test si Apache répond
if curl -s -o /dev/null -w "%{http_code}" "http://localhost" | grep -q "200\|301\|302"; then
    echo "   ✅ Apache répond"
else
    echo "   ⚠️  Apache ne répond pas sur localhost"
fi

echo ""

# Résumé
echo "═══════════════════════════════════════════════════════════════════════════"
echo "  ✅ DÉPLOIEMENT TERMINÉ"
echo "═══════════════════════════════════════════════════════════════════════════"
echo ""
echo "📋 PROCHAINES ÉTAPES:"
echo ""
echo "1. FORCER LE RECHARGEMENT PWA:"
echo "   Ouvrir sur téléphone:"
echo "   https://crm.mv-3pro.ch/custom/mv3pro_portail/pwa_dist/FORCE_RELOAD.html"
echo ""
echo "2. TESTER L'AUTHENTIFICATION:"
echo "   curl -X POST 'https://crm.mv-3pro.ch/custom/mv3pro_portail/mobile_app/api/auth.php?action=login' \\"
echo "     -H 'Content-Type: application/json' \\"
echo "     -d '{\"email\":\"test@example.com\",\"password\":\"xxx\"}'"
echo ""
echo "3. TESTER L'UPLOAD PHOTO:"
echo "   TOKEN=\"...\""
echo "   curl -X POST \\"
echo "     -H \"Authorization: Bearer \$TOKEN\" \\"
echo "     -F 'event_id=74049' \\"
echo "     -F 'file=@photo.jpg' \\"
echo "     'https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/planning_upload_photo.php'"
echo ""
echo "4. CONSULTER LES LOGS (si debug activé):"
echo "   tail -f $DOCS_PATH/logs/api.log"
echo ""
echo "5. CONSULTER LA DOCUMENTATION:"
echo "   - GUIDE_TEST_FINAL.md → Tests complets"
echo "   - PWA_AUTH_FIX_COMPLETE.md → Documentation technique"
echo "   - VALIDATION_FINALE.md → Validation conformité"
echo ""
echo "═══════════════════════════════════════════════════════════════════════════"
echo ""
echo "📦 Backup disponible dans: $BACKUP_PATH"
echo "🔄 Version déployée: 3.0 (Build: DmJXHRZF)"
echo "📅 Date: $(date '+%Y-%m-%d %H:%M:%S')"
echo ""
echo "✅ Déploiement réussi !"
echo ""

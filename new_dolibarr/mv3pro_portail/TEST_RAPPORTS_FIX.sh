#!/bin/bash

# Script de test pour valider la correction des rapports
# Usage: ./TEST_RAPPORTS_FIX.sh [TOKEN]

BASE_URL="https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1"
TOKEN="${1:-YOUR_TOKEN_HERE}"

if [ "$TOKEN" = "YOUR_TOKEN_HERE" ]; then
    echo "❌ Erreur: Vous devez fournir un token d'authentification"
    echo "Usage: $0 YOUR_AUTH_TOKEN"
    exit 1
fi

echo "========================================="
echo "🧪 TEST 1: Diagnostic (/rapports_debug.php)"
echo "========================================="
curl -s -H "X-Auth-Token: $TOKEN" "$BASE_URL/rapports_debug.php" | jq '.debug_info.user_info, .comparison, .recommendation'

echo ""
echo "========================================="
echo "🧪 TEST 2: Liste des rapports (/rapports.php)"
echo "========================================="
curl -s -H "X-Auth-Token: $TOKEN" "$BASE_URL/rapports.php" | jq '{success, total: .data.total, items_count: (.data.items | length), first_rapport: .data.items[0]}'

echo ""
echo "========================================="
echo "🧪 TEST 3: Liste des utilisateurs (/users.php)"
echo "========================================="
curl -s -H "X-Auth-Token: $TOKEN" "$BASE_URL/users.php" | jq '{success, count: .data.count, users: .data.users | map({id, name, admin})}'

echo ""
echo "========================================="
echo "✅ Tests terminés"
echo "========================================="
echo ""
echo "Points à vérifier:"
echo "1. debug_info.user_info.dolibarr_user_id doit être > 0"
echo "2. comparison.new_system doit montrer des rapports > 0"
echo "3. Liste rapports doit afficher des items (pas vide)"
echo "4. Liste users doit être accessible (si admin) ou 403 (si employé)"

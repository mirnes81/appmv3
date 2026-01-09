#!/bin/bash

# ================================================================
# Script de test des endpoints API MV3 PRO Portail
# Usage: ./TEST_API_ENDPOINTS.sh YOUR_TOKEN_HERE
# ================================================================

# Couleurs pour l'affichage
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
BASE_URL="https://crm.mv-3pro.ch/custom/mv3pro_portail"
API_URL="${BASE_URL}/api/v1"
AUTH_URL="${BASE_URL}/mobile_app/api/auth.php"

# Token d'authentification (passé en argument ou prompt)
if [ -z "$1" ]; then
    echo -e "${YELLOW}⚠️  Aucun token fourni${NC}"
    echo ""
    echo "1. Ouvrez : ${BASE_URL}/pwa_dist/"
    echo "2. Connectez-vous"
    echo "3. F12 → Application → Local Storage → Copiez 'mv3pro_token'"
    echo ""
    read -p "Collez votre token ici : " TOKEN
else
    TOKEN="$1"
fi

if [ -z "$TOKEN" ]; then
    echo -e "${RED}❌ Token manquant. Abandon.${NC}"
    exit 1
fi

echo ""
echo "=========================================="
echo "🧪 TEST API MV3 PRO - Production"
echo "=========================================="
echo "Base URL: $API_URL"
echo "Token: ${TOKEN:0:20}..."
echo ""

# Fonction de test
test_endpoint() {
    local name="$1"
    local url="$2"
    local method="${3:-GET}"

    echo -e "${YELLOW}Testing:${NC} $name"
    echo "URL: $url"

    response=$(curl -s -w "\nHTTP_CODE:%{http_code}" \
        -X "$method" \
        -H "X-Auth-Token: $TOKEN" \
        -H "Authorization: Bearer $TOKEN" \
        -H "X-MV3-Debug: 1" \
        "$url")

    http_code=$(echo "$response" | grep "HTTP_CODE:" | cut -d: -f2)
    body=$(echo "$response" | sed '/HTTP_CODE:/d')

    if [ "$http_code" = "200" ]; then
        echo -e "${GREEN}✅ OK ($http_code)${NC}"
        echo "$body" | jq '.' 2>/dev/null || echo "$body"
    elif [ "$http_code" = "401" ]; then
        echo -e "${RED}❌ UNAUTHORIZED ($http_code)${NC}"
        echo "$body"
    elif [ "$http_code" = "500" ]; then
        echo -e "${RED}❌ SERVER ERROR ($http_code)${NC}"
        echo "$body"
    elif [ "$http_code" = "501" ]; then
        echo -e "${YELLOW}⚠️  NOT IMPLEMENTED ($http_code)${NC}"
        echo "$body"
    else
        echo -e "${RED}❌ ERROR ($http_code)${NC}"
        echo "$body"
    fi

    echo ""
}

# Tests des endpoints
echo "=========================================="
echo "📋 Endpoints critiques"
echo "=========================================="
echo ""

test_endpoint "1. Me (user info)" "${API_URL}/me.php"
test_endpoint "2. Planning (today)" "${API_URL}/planning.php"
test_endpoint "3. Rapports (list)" "${API_URL}/rapports.php?limit=10"

echo "=========================================="
echo "📋 Endpoints secondaires"
echo "=========================================="
echo ""

test_endpoint "4. Matériel (list)" "${API_URL}/materiel_list.php"
test_endpoint "5. Notifications (list)" "${API_URL}/notifications_list.php"
test_endpoint "6. Régie (list)" "${API_URL}/regie_list.php"
test_endpoint "7. Sens de pose (list)" "${API_URL}/sens_pose_list.php"

echo "=========================================="
echo "📋 Test login (sans token)"
echo "=========================================="
echo ""

echo -e "${YELLOW}Test login public endpoint${NC}"
echo "URL: ${AUTH_URL}?action=login"
echo ""
echo "Ce test devrait retourner 400 (credentials manquantes) au lieu de 500"
echo ""

login_response=$(curl -s -w "\nHTTP_CODE:%{http_code}" \
    -X POST \
    -H "Content-Type: application/json" \
    -d '{"email":"test@example.com","password":"wrongpass"}' \
    "${AUTH_URL}?action=login")

login_code=$(echo "$login_response" | grep "HTTP_CODE:" | cut -d: -f2)
login_body=$(echo "$login_response" | sed '/HTTP_CODE:/d')

if [ "$login_code" = "400" ] || [ "$login_code" = "401" ]; then
    echo -e "${GREEN}✅ OK ($login_code) - Erreur attendue${NC}"
elif [ "$login_code" = "200" ]; then
    echo -e "${YELLOW}⚠️  OK mais credentials acceptées ? Vérifier${NC}"
else
    echo -e "${RED}❌ ERROR ($login_code)${NC}"
fi

echo "$login_body" | jq '.' 2>/dev/null || echo "$login_body"
echo ""

echo "=========================================="
echo "✅ Tests terminés"
echo "=========================================="
echo ""
echo "Si vous voyez des 500/510, consultez les logs serveur :"
echo "  - Apache : /var/log/apache2/error.log"
echo "  - PHP : /var/log/php/error.log"
echo "  - Dolibarr : documents/dolibarr.log"
echo ""
echo "Activez le mode debug dans la PWA :"
echo "  - Ouvrez : ${BASE_URL}/pwa_dist/#/debug"
echo "  - Activez 'Mode Debug'"
echo "  - Consultez F12 → Console"

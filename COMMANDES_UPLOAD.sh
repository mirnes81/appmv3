#!/bin/bash

###############################################################################
# Script d'upload du système de diagnostic MV3 PRO
# Date : 2026-01-09
###############################################################################

# CONFIGURATION À ADAPTER
SERVER_USER="votre_user"
SERVER_HOST="votre_serveur"
SERVER_PATH="/path/to/dolibarr/htdocs"

# Couleurs pour l'affichage
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo "============================================================"
echo "Upload du système de diagnostic MV3 PRO"
echo "============================================================"
echo ""

# Vérifier que nous sommes dans le bon répertoire
if [ ! -f "new_dolibarr/mv3pro_portail/api/v1/debug.php" ]; then
    echo -e "${RED}Erreur : Fichier debug.php non trouvé${NC}"
    echo "Assurez-vous d'exécuter ce script depuis la racine du projet"
    exit 1
fi

echo -e "${YELLOW}Configuration actuelle :${NC}"
echo "  Serveur : $SERVER_USER@$SERVER_HOST"
echo "  Chemin  : $SERVER_PATH"
echo ""
read -p "Voulez-vous continuer ? (o/n) " -n 1 -r
echo ""
if [[ ! $REPLY =~ ^[Oo]$ ]]; then
    echo "Annulé"
    exit 0
fi

echo ""
echo "============================================================"
echo "1. Upload du endpoint debug.php"
echo "============================================================"

scp new_dolibarr/mv3pro_portail/api/v1/debug.php \
    $SERVER_USER@$SERVER_HOST:$SERVER_PATH/custom/mv3pro_portail/api/v1/debug.php

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ debug.php uploadé avec succès${NC}"
else
    echo -e "${RED}✗ Erreur lors de l'upload de debug.php${NC}"
    exit 1
fi

echo ""
echo "============================================================"
echo "2. Upload de la PWA (pwa_dist/)"
echo "============================================================"

rsync -av --progress new_dolibarr/mv3pro_portail/pwa_dist/ \
    $SERVER_USER@$SERVER_HOST:$SERVER_PATH/custom/mv3pro_portail/pwa_dist/

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ PWA uploadée avec succès${NC}"
else
    echo -e "${RED}✗ Erreur lors de l'upload de la PWA${NC}"
    exit 1
fi

echo ""
echo "============================================================"
echo "3. Activation du mode développement"
echo "============================================================"

ssh $SERVER_USER@$SERVER_HOST "touch /tmp/mv3pro_debug.flag"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Mode dev activé${NC}"
else
    echo -e "${RED}✗ Erreur lors de l'activation du mode dev${NC}"
    exit 1
fi

echo ""
echo "============================================================"
echo "4. Test de l'installation"
echo "============================================================"

echo "Test de debug.php..."
RESPONSE=$(curl -s -w "\n%{http_code}" https://crm.mv-3pro.ch/custom/mv3pro_portail/api/v1/debug.php)
HTTP_CODE=$(echo "$RESPONSE" | tail -n 1)

if [ "$HTTP_CODE" == "200" ]; then
    echo -e "${GREEN}✓ debug.php fonctionne (HTTP 200)${NC}"
else
    echo -e "${RED}✗ debug.php ne répond pas correctement (HTTP $HTTP_CODE)${NC}"
fi

echo ""
echo "============================================================"
echo "Installation terminée !"
echo "============================================================"
echo ""
echo "Prochaines étapes :"
echo ""
echo "1. Ouvrez votre navigateur :"
echo "   https://crm.mv-3pro.ch/custom/mv3pro_portail/pwa_dist/"
echo ""
echo "2. Connectez-vous avec vos credentials"
echo ""
echo "3. Allez sur la page Debug :"
echo "   https://crm.mv-3pro.ch/custom/mv3pro_portail/pwa_dist/#/debug"
echo ""
echo "4. Cliquez sur 'Diagnostic Complet'"
echo ""
echo "5. Exportez le rapport JSON"
echo ""
echo -e "${YELLOW}Important :${NC}"
echo "N'oubliez pas de désactiver le mode dev après utilisation :"
echo "  ssh $SERVER_USER@$SERVER_HOST 'rm /tmp/mv3pro_debug.flag'"
echo ""

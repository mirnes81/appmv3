#!/bin/bash

################################################################################
# SCRIPT D'INSTALLATION API MOBILE MV3 PRO
# Pour Dolibarr sur crm.mv-3pro.ch
################################################################################

echo "================================================"
echo "  Installation API Mobile MV3 Pro"
echo "================================================"
echo ""

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
DOLIBARR_PATH="/var/www/dolibarr/htdocs/custom/mv3pro_portail"
API_PATH="$DOLIBARR_PATH/api_mobile"

# Vérifications préalables
echo -e "${YELLOW}Vérification des prérequis...${NC}"

# Vérifier si le script est exécuté avec les permissions appropriées
if [ ! -d "$DOLIBARR_PATH" ]; then
    echo -e "${RED}❌ Le dossier Dolibarr n'existe pas: $DOLIBARR_PATH${NC}"
    echo "   Êtes-vous sur le bon serveur ?"
    exit 1
fi

echo -e "${GREEN}✅ Dossier Dolibarr trouvé${NC}"

# Créer le dossier api_mobile
echo ""
echo -e "${YELLOW}Création du dossier api_mobile...${NC}"
mkdir -p "$API_PATH"

if [ -d "$API_PATH" ]; then
    echo -e "${GREEN}✅ Dossier créé: $API_PATH${NC}"
else
    echo -e "${RED}❌ Impossible de créer le dossier${NC}"
    exit 1
fi

# Copier les fichiers
echo ""
echo -e "${YELLOW}Copie des fichiers API...${NC}"
cp -r api_mobile/* "$API_PATH/"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Fichiers copiés${NC}"
else
    echo -e "${RED}❌ Erreur lors de la copie des fichiers${NC}"
    exit 1
fi

# Définir les permissions
echo ""
echo -e "${YELLOW}Configuration des permissions...${NC}"
chmod 755 "$API_PATH"
chmod 644 "$API_PATH"/*.php
chmod 755 "$API_PATH"/auth/ "$API_PATH"/reports/ "$API_PATH"/dashboard/ "$API_PATH"/weather/
chmod 644 "$API_PATH"/auth/*.php "$API_PATH"/reports/*.php "$API_PATH"/dashboard/*.php "$API_PATH"/weather/*.php

echo -e "${GREEN}✅ Permissions configurées${NC}"

# Configuration
echo ""
echo "================================================"
echo -e "${YELLOW}CONFIGURATION NÉCESSAIRE${NC}"
echo "================================================"
echo ""
echo "Il vous reste à configurer la base de données:"
echo ""
echo "1. Éditez le fichier config.php:"
echo "   nano $API_PATH/config.php"
echo ""
echo "2. Modifiez les paramètres de connexion MySQL:"
echo "   - DOLIBARR_DB_HOST"
echo "   - DOLIBARR_DB_NAME"
echo "   - DOLIBARR_DB_USER"
echo "   - DOLIBARR_DB_PASS"
echo "   - JWT_SECRET (générez avec: openssl rand -base64 32)"
echo ""
echo "3. Testez l'API avec:"
echo "   curl -X POST https://crm.mv-3pro.ch/custom/mv3pro_portail/api_mobile/auth/login.php \\"
echo "     -H 'Content-Type: application/json' \\"
echo "     -d '{\"email\":\"votre@email.com\",\"password\":\"votre_mdp\"}'"
echo ""
echo "================================================"
echo -e "${GREEN}✅ Installation terminée !${NC}"
echo "================================================"
echo ""
echo "Consultez GUIDE_INSTALLATION_API.md pour plus de détails."

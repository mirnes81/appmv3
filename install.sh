#!/bin/bash

################################################################################
# Script d'installation PWA MV3 PRO - 100% Dolibarr
# Supabase a été supprimé
################################################################################

echo "================================================================================
PWA MV3 PRO - Installation automatique
================================================================================
"

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Variables
DOLIBARR_API_DIR="/var/www/html/dolibarr/custom/mv3pro_portail/api"
PWA_DIR="/var/www/html/app.mv-3pro.ch/public_html/pro"
UPLOADS_DIR="/var/www/dolibarr_documents/mv3pro_portail"

echo -e "${YELLOW}Configuration :${NC}"
echo "  - API Dolibarr : $DOLIBARR_API_DIR"
echo "  - PWA : $PWA_DIR"
echo "  - Uploads : $UPLOADS_DIR"
echo ""

################################################################################
# ÉTAPE 1 : API DOLIBARR
################################################################################

echo -e "${GREEN}[1/5] Installation API Dolibarr...${NC}"

if [ ! -f "dolibarr_api_complet.tar.gz" ]; then
  echo -e "${RED}Erreur : dolibarr_api_complet.tar.gz introuvable${NC}"
  exit 1
fi

if [ ! -d "$DOLIBARR_API_DIR" ]; then
  echo "  Création dossier $DOLIBARR_API_DIR"
  mkdir -p "$DOLIBARR_API_DIR"
fi

echo "  Extraction archive..."
tar -xzf dolibarr_api_complet.tar.gz -C "$DOLIBARR_API_DIR"

echo "  Configuration permissions..."
chmod 644 $DOLIBARR_API_DIR/*.php
chown www-data:www-data $DOLIBARR_API_DIR/*.php

echo -e "${GREEN}  ✓ API Dolibarr installée${NC}"
echo ""

################################################################################
# ÉTAPE 2 : PROXY API
################################################################################

echo -e "${GREEN}[2/5] Installation Proxy API...${NC}"

if [ ! -f "pwa_proxy.tar.gz" ]; then
  echo -e "${RED}Erreur : pwa_proxy.tar.gz introuvable${NC}"
  exit 1
fi

PROXY_DIR="$PWA_DIR/api"

if [ ! -d "$PROXY_DIR" ]; then
  echo "  Création dossier $PROXY_DIR"
  mkdir -p "$PROXY_DIR"
fi

echo "  Extraction archive..."
tar -xzf pwa_proxy.tar.gz -C "$PROXY_DIR"

echo "  Configuration permissions..."
chmod 644 $PROXY_DIR/index.php
chmod 644 $PROXY_DIR/.htaccess

echo -e "${GREEN}  ✓ Proxy installé${NC}"
echo ""

################################################################################
# ÉTAPE 3 : PWA FRONTEND
################################################################################

echo -e "${GREEN}[3/5] Installation PWA Frontend...${NC}"

if [ ! -f "pwa_frontend.tar.gz" ]; then
  echo -e "${RED}Erreur : pwa_frontend.tar.gz introuvable${NC}"
  exit 1
fi

echo "  Extraction archive..."
tar -xzf pwa_frontend.tar.gz -C "$PWA_DIR"

echo "  Configuration permissions..."
chmod 644 $PWA_DIR/index.html
chmod 644 $PWA_DIR/assets/*

echo -e "${GREEN}  ✓ PWA installée${NC}"
echo ""

################################################################################
# ÉTAPE 4 : DOSSIERS UPLOADS
################################################################################

echo -e "${GREEN}[4/5] Création dossiers uploads...${NC}"

mkdir -p "$UPLOADS_DIR/rapports"
mkdir -p "$UPLOADS_DIR/pdf"

chmod 755 "$UPLOADS_DIR/rapports"
chmod 755 "$UPLOADS_DIR/pdf"

chown -R www-data:www-data "$UPLOADS_DIR"

echo -e "${GREEN}  ✓ Dossiers créés${NC}"
echo ""

################################################################################
# ÉTAPE 5 : VÉRIFICATION BASE DE DONNÉES
################################################################################

echo -e "${GREEN}[5/5] Vérification base de données...${NC}"

if command -v mysql &> /dev/null; then
  echo "  Vérification colonnes GPS/météo..."

  DB_USER="root"
  DB_NAME="dolibarr"

  read -s -p "  Mot de passe MySQL root : " DB_PASS
  echo ""

  # Vérifier colonnes GPS
  GPS_COLS=$(mysql -u$DB_USER -p$DB_PASS $DB_NAME -N -e "SHOW COLUMNS FROM llx_mv3_rapport LIKE 'gps_%'" 2>/dev/null | wc -l)

  if [ $GPS_COLS -lt 3 ]; then
    echo -e "${YELLOW}  Colonnes GPS manquantes, application du script...${NC}"

    if [ -f "new_dolibarr/mv3pro_portail/sql/llx_mv3_rapport_add_features.sql" ]; then
      mysql -u$DB_USER -p$DB_PASS $DB_NAME < new_dolibarr/mv3pro_portail/sql/llx_mv3_rapport_add_features.sql
      echo -e "${GREEN}  ✓ Colonnes ajoutées${NC}"
    else
      echo -e "${RED}  Erreur : Script SQL introuvable${NC}"
    fi
  else
    echo -e "${GREEN}  ✓ Colonnes GPS déjà présentes${NC}"
  fi
else
  echo -e "${YELLOW}  MySQL CLI non trouvé, vérifiez manuellement :${NC}"
  echo "  mysql -u root -p dolibarr < new_dolibarr/mv3pro_portail/sql/llx_mv3_rapport_add_features.sql"
fi

echo ""

################################################################################
# TESTS
################################################################################

echo -e "${GREEN}================================================================================
Tests
================================================================================${NC}"

echo ""
echo "Test 1 : Proxy API"
echo "  curl https://app.mv-3pro.ch/pro/api/auth_me.php"
echo ""

echo "Test 2 : Login"
echo '  curl -X POST "https://app.mv-3pro.ch/pro/api/auth_login.php" \'
echo '    -H "Content-Type: application/json" \'
echo '    -d '\''{"login":"admin","password":"VOTRE_MDP"}'\'''
echo ""

echo "Test 3 : PWA"
echo "  Ouvrez : https://app.mv-3pro.ch/pro/"
echo ""

################################################################################
# FIN
################################################################################

echo -e "${GREEN}================================================================================
Installation terminée !
================================================================================${NC}"

echo ""
echo "Fichiers installés :"
echo "  - API Dolibarr : $DOLIBARR_API_DIR"
echo "  - Proxy : $PROXY_DIR"
echo "  - PWA : $PWA_DIR"
echo "  - Uploads : $UPLOADS_DIR"
echo ""
echo "Prochaines étapes :"
echo "  1. Testez le proxy (voir commandes ci-dessus)"
echo "  2. Ouvrez la PWA : https://app.mv-3pro.ch/pro/"
echo "  3. Connectez-vous avec vos identifiants Dolibarr"
echo ""
echo "Documentation :"
echo "  - RECAPITULATIF_DOLIBARR_ONLY.md"
echo "  - GUIDE_INSTALLATION_DOLIBARR.md"
echo "  - README_DEPLOIEMENT_FINAL.txt"
echo ""
echo -e "${GREEN}✅ Supabase a été supprimé - L'app fonctionne 100% avec Dolibarr${NC}"
echo ""

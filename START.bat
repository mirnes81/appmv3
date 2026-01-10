@echo off
echo.
echo ═══════════════════════════════════════════════════════════════
echo   🚀 MV-3 PRO - Démarrage Serveur Demo
echo ═══════════════════════════════════════════════════════════════
echo.
echo   📦 Installation dépendances...
echo.

call npm install > nul 2>&1

echo   ✅ Dépendances OK
echo.
echo   🌐 Démarrage serveur...
echo.

call npm run dev

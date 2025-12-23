INSTALLATION PWA MV3 PRO
========================

Ce dossier contient l'application PWA complète prête à être déployée.

URL CIBLE: https://app.mv-3pro.ch/pro/

INSTRUCTIONS DE DEPLOIEMENT:
----------------------------

1. Uploader tout le contenu de ce dossier vers:
   /var/www/html/pro/

   Commandes:
   scp -r pro/* user@app.mv-3pro.ch:/var/www/html/pro/

   OU avec rsync:
   rsync -avz pro/ user@app.mv-3pro.ch:/var/www/html/pro/

2. Configuration Apache (.htaccess inclus):
   - Le fichier .htaccess est déjà configuré
   - S'assurer que mod_rewrite est activé
   - S'assurer que mod_headers est activé

3. Configuration Nginx (si utilisé):

   location /pro {
       alias /var/www/html/pro;
       try_files $uri $uri/ /pro/index.html;

       location ~ ^/pro/sw.js {
           add_header Cache-Control "no-cache";
       }

       location ~ ^/pro/manifest.json {
           add_header Cache-Control "public, max-age=86400";
       }
   }

4. Permissions:
   chmod -R 755 /var/www/html/pro
   chown -R www-data:www-data /var/www/html/pro

5. Test:
   https://app.mv-3pro.ch/pro/

CONTENU DU DOSSIER:
------------------
- index.html          : Point d'entrée
- manifest.json       : Manifest PWA
- sw.js              : Service Worker
- assets/            : JS et CSS
- *.png              : Images/icônes
- .htaccess          : Configuration Apache

NOTES:
------
- Tous les chemins sont configurés pour /pro/
- La PWA fonctionnera uniquement sur HTTPS
- Service Worker gérera le cache automatiquement

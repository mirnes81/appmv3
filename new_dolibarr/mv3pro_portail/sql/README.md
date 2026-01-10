# SQL - Aucune table custom nécessaire

## ℹ️ Information

Ce module **ne nécessite aucune table personnalisée** dans la base de données.

Il utilise uniquement les tables standard de Dolibarr :
- `llx_actioncomm` : Événements du planning
- `llx_actioncomm_extrafields` : Champs personnalisés (si nécessaire)
- `llx_user` : Utilisateurs
- `llx_const` : Configuration du module

## 🚀 Installation

Aucun script SQL à exécuter. Le module fonctionne directement après activation.

## 📝 Notes

Si vous aviez des tables custom auparavant (llx_mv3_rapport, llx_mv3_regie, etc.), elles ne sont plus utilisées par cette version minimale.

Vous pouvez les conserver en base de données ou les supprimer si vous en êtes certain.

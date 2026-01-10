<?php
/**
 * ObjectHelper - Classe helper pour gérer les objets Dolibarr de manière générique
 *
 * Utilise UNIQUEMENT les classes natives Dolibarr (pas de SQL custom)
 * Supporte: actioncomm (RDV), task (tâches), etc.
 *
 * @author  MV3 Pro
 * @version 1.0
 */

class ObjectHelper
{
    private $db;
    private $user;

    /**
     * Configuration des types d'objets supportés
     */
    private static $objectConfig = [
        'actioncomm' => [
            'class' => 'ActionComm',
            'file' => 'comm/action/class/actioncomm.class.php',
            'table' => 'actioncomm',
            'module_dir' => 'actions',
            'doc_subdir' => 'action',
            'name_field' => 'label',
            'supports_extrafields' => true,
        ],
        'task' => [
            'class' => 'Task',
            'file' => 'projet/class/task.class.php',
            'table' => 'projet_task',
            'module_dir' => 'project',
            'doc_subdir' => 'task',
            'name_field' => 'label',
            'supports_extrafields' => true,
        ],
        'project' => [
            'class' => 'Project',
            'file' => 'projet/class/project.class.php',
            'table' => 'projet',
            'module_dir' => 'project',
            'doc_subdir' => 'project',
            'name_field' => 'title',
            'supports_extrafields' => true,
        ],
    ];

    public function __construct($db, $user)
    {
        $this->db = $db;
        $this->user = $user;
    }

    /**
     * Récupère un objet Dolibarr par type et ID
     *
     * @param string $type Type d'objet (actioncomm, task, etc.)
     * @param int $id ID de l'objet
     * @return array|false Array avec l'objet et ses métadonnées, ou false si erreur
     */
    public function getObject($type, $id)
    {
        global $conf;

        // Vérifier que le type est supporté
        if (!isset(self::$objectConfig[$type])) {
            $this->error = "Type d'objet non supporté: $type";
            return false;
        }

        $config = self::$objectConfig[$type];

        // Charger la classe Dolibarr
        require_once DOL_DOCUMENT_ROOT . '/' . $config['file'];

        $className = $config['class'];
        $object = new $className($this->db);

        // Charger l'objet
        $result = $object->fetch($id);
        if ($result <= 0) {
            $this->error = "Objet non trouvé: $type #$id";
            return false;
        }

        // Vérifier les droits
        if (!$this->checkReadPermission($type, $object)) {
            $this->error = "Pas de permission pour lire cet objet";
            return false;
        }

        // Préparer les données de base
        $data = [
            'id' => $object->id,
            'ref' => $object->ref ?? null,
            'label' => $object->{$config['name_field']} ?? '',
            'type' => $type,
            'object' => $object,
        ];

        // Ajouter les extrafields si supportés
        if ($config['supports_extrafields']) {
            $data['extrafields'] = $this->getExtrafields($type, $id);
        }

        // Ajouter les fichiers (via ECM natif)
        $data['files'] = $this->getFiles($type, $id);

        return $data;
    }

    /**
     * Récupère les extrafields d'un objet
     *
     * @param string $type Type d'objet
     * @param int $id ID de l'objet
     * @return array Extrafields
     */
    private function getExtrafields($type, $id)
    {
        require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';

        $config = self::$objectConfig[$type];
        $extrafields = new ExtraFields($this->db);

        // Charger la définition des extrafields
        $extrafields->fetch_name_optionals_label($config['table']);

        // Charger les valeurs
        $tableName = $config['table'] . '_extrafields';
        $sql = "SELECT * FROM " . MAIN_DB_PREFIX . $tableName . " WHERE fk_object = " . (int)$id;
        $resql = $this->db->query($sql);

        $result = [];
        if ($resql) {
            if ($obj = $this->db->fetch_object($resql)) {
                foreach ($extrafields->attributes[$config['table']]['label'] as $key => $label) {
                    $result[$key] = [
                        'label' => $label,
                        'value' => $obj->$key ?? null,
                        'type' => $extrafields->attributes[$config['table']]['type'][$key] ?? 'text',
                    ];
                }
            }
        }

        return $result;
    }

    /**
     * Récupère les fichiers d'un objet via l'API ECM native Dolibarr
     *
     * @param string $type Type d'objet
     * @param int $id ID de l'objet
     * @return array Liste des fichiers
     */
    private function getFiles($type, $id)
    {
        global $conf;

        require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
        require_once DOL_DOCUMENT_ROOT . '/core/class/link.class.php';

        $config = self::$objectConfig[$type];

        // Construire le chemin de stockage standard Dolibarr
        $upload_dir = $conf->$config['module_dir']->dir_output . '/' . $id;

        $files = [];

        if (is_dir($upload_dir)) {
            // Utiliser dol_dir_list (fonction native Dolibarr)
            $filearray = dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$', 'date', SORT_DESC);

            if (is_array($filearray)) {
                foreach ($filearray as $key => $file) {
                    $files[] = [
                        'name' => $file['name'],
                        'path' => $file['relativename'],
                        'fullpath' => $file['fullname'],
                        'size' => $file['size'],
                        'date' => $file['date'],
                        'type' => $this->getFileType($file['name']),
                        'is_image' => $this->isImage($file['name']),
                        'url' => $this->getFileUrl($type, $id, $file['relativename']),
                    ];
                }
            }
        }

        return $files;
    }

    /**
     * Upload un fichier pour un objet (via ECM natif)
     *
     * @param string $type Type d'objet
     * @param int $id ID de l'objet
     * @param array $file Fichier uploadé ($_FILES['file'])
     * @return array|false Infos du fichier uploadé ou false
     */
    public function uploadFile($type, $id, $file)
    {
        global $conf;

        // Vérifier que le type est supporté
        if (!isset(self::$objectConfig[$type])) {
            $this->error = "Type d'objet non supporté: $type";
            return false;
        }

        $config = self::$objectConfig[$type];

        // Charger l'objet pour vérifier les droits
        require_once DOL_DOCUMENT_ROOT . '/' . $config['file'];
        $className = $config['class'];
        $object = new $className($this->db);

        $result = $object->fetch($id);
        if ($result <= 0) {
            $this->error = "Objet non trouvé";
            return false;
        }

        // Vérifier les droits d'écriture
        if (!$this->checkWritePermission($type, $object)) {
            $this->error = "Pas de permission pour uploader";
            return false;
        }

        // Construire le répertoire de destination
        $upload_dir = $conf->$config['module_dir']->dir_output . '/' . $id;

        // Créer le répertoire s'il n'existe pas
        if (!is_dir($upload_dir)) {
            require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
            dol_mkdir($upload_dir);
        }

        // Utiliser la fonction native Dolibarr pour uploader
        require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

        $destfile = $upload_dir . '/' . $file['name'];

        // Déplacer le fichier
        $result = dol_move_uploaded_file($file['tmp_name'], $destfile, 1, 0, $file['error']);

        if ($result > 0) {
            // Ajouter dans ECM
            require_once DOL_DOCUMENT_ROOT . '/ecm/class/ecmfiles.class.php';
            $ecmfile = new EcmFiles($this->db);

            $ecmfile->label = $file['name'];
            $ecmfile->filename = $file['name'];
            $ecmfile->filepath = $config['module_dir'] . '/' . $id;
            $ecmfile->fullpath_orig = $destfile;
            $ecmfile->src_object_type = $type;
            $ecmfile->src_object_id = $id;
            $ecmfile->gen_or_uploaded = 'uploaded';
            $ecmfile->description = '';

            $ecmfile->create($this->user);

            return [
                'success' => true,
                'filename' => $file['name'],
                'size' => filesize($destfile),
                'url' => $this->getFileUrl($type, $id, $file['name']),
            ];
        } else {
            $this->error = "Erreur lors de l'upload du fichier";
            return false;
        }
    }

    /**
     * Supprime un fichier (via ECM natif)
     *
     * @param string $type Type d'objet
     * @param int $id ID de l'objet
     * @param string $filename Nom du fichier
     * @return bool
     */
    public function deleteFile($type, $id, $filename)
    {
        global $conf;

        // Vérifier que le type est supporté
        if (!isset(self::$objectConfig[$type])) {
            $this->error = "Type d'objet non supporté";
            return false;
        }

        $config = self::$objectConfig[$type];

        // Charger l'objet pour vérifier les droits
        require_once DOL_DOCUMENT_ROOT . '/' . $config['file'];
        $className = $config['class'];
        $object = new $className($this->db);

        $result = $object->fetch($id);
        if ($result <= 0) {
            $this->error = "Objet non trouvé";
            return false;
        }

        // Vérifier les droits (admin ou propriétaire du fichier)
        if (!$this->checkDeletePermission($type, $object)) {
            $this->error = "Pas de permission pour supprimer";
            return false;
        }

        // Construire le chemin
        $upload_dir = $conf->$config['module_dir']->dir_output . '/' . $id;
        $filepath = $upload_dir . '/' . $filename;

        // Supprimer le fichier physique
        require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
        $result = dol_delete_file($filepath, 1);

        if ($result) {
            // Supprimer de ECM
            require_once DOL_DOCUMENT_ROOT . '/ecm/class/ecmfiles.class.php';
            $sql = "DELETE FROM " . MAIN_DB_PREFIX . "ecm_files
                    WHERE src_object_type = '" . $this->db->escape($type) . "'
                    AND src_object_id = " . (int)$id . "
                    AND filename = '" . $this->db->escape($filename) . "'";
            $this->db->query($sql);

            return true;
        }

        $this->error = "Erreur lors de la suppression";
        return false;
    }

    /**
     * Vérifie les permissions de lecture
     */
    private function checkReadPermission($type, $object)
    {
        global $user;

        switch ($type) {
            case 'actioncomm':
                return $user->rights->agenda->myactions->read || $user->rights->agenda->allactions->read;
            case 'task':
                return $user->rights->projet->lire;
            case 'project':
                return $user->rights->projet->lire;
            default:
                return false;
        }
    }

    /**
     * Vérifie les permissions d'écriture
     */
    private function checkWritePermission($type, $object)
    {
        global $user;

        switch ($type) {
            case 'actioncomm':
                return $user->rights->agenda->myactions->create || $user->rights->agenda->allactions->create;
            case 'task':
                return $user->rights->projet->creer;
            case 'project':
                return $user->rights->projet->creer;
            default:
                return false;
        }
    }

    /**
     * Vérifie les permissions de suppression
     */
    private function checkDeletePermission($type, $object)
    {
        global $user;

        // Admin peut toujours supprimer
        if ($user->admin) {
            return true;
        }

        switch ($type) {
            case 'actioncomm':
                return $user->rights->agenda->myactions->delete || $user->rights->agenda->allactions->delete;
            case 'task':
                return $user->rights->projet->supprimer;
            case 'project':
                return $user->rights->projet->supprimer;
            default:
                return false;
        }
    }

    /**
     * Détermine le type de fichier
     */
    private function getFileType($filename)
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        $imageExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
        $docExts = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt'];

        if (in_array($ext, $imageExts)) {
            return 'image';
        } elseif (in_array($ext, $docExts)) {
            return 'document';
        } else {
            return 'other';
        }
    }

    /**
     * Vérifie si c'est une image
     */
    private function isImage($filename)
    {
        return $this->getFileType($filename) === 'image';
    }

    /**
     * Construit l'URL d'un fichier
     */
    private function getFileUrl($type, $id, $filename)
    {
        global $dolibarr_main_url_root;

        return $dolibarr_main_url_root . '/custom/mv3pro_portail/api/v1/object/file.php?type=' . $type . '&id=' . $id . '&filename=' . urlencode($filename);
    }

    /**
     * Liste les types d'objets supportés
     */
    public static function getSupportedTypes()
    {
        return array_keys(self::$objectConfig);
    }

    /**
     * Récupère la config d'un type
     */
    public static function getTypeConfig($type)
    {
        return self::$objectConfig[$type] ?? null;
    }
}

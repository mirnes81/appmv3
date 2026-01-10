<?php
/**
 * DIAGNOSTIC COMPLET - Fichiers Planning
 *
 * Ce script analyse POURQUOI les fichiers ne s'affichent pas
 */

require_once __DIR__.'/_bootstrap.php';

$auth = require_auth();
require_method('GET');

$id = (int)get_param('id', 0);
require_param($id, 'id');

$diagnostic = [
    'event_id' => $id,
    'timestamp' => date('Y-m-d H:i:s'),
    'steps' => []
];

// ===== ÉTAPE 1: Vérifier la structure de la table ECM =====
$diagnostic['steps'][] = [
    'name' => 'Structure table llx_ecm_files',
    'status' => 'checking'
];

$sql_structure = "SHOW COLUMNS FROM ".MAIN_DB_PREFIX."ecm_files";
$res_structure = $db->query($sql_structure);

$columns = [];
if ($res_structure) {
    while ($col = $db->fetch_object($res_structure)) {
        $columns[] = $col->Field;
    }
    $db->free($res_structure);
}

$diagnostic['steps'][0]['status'] = 'success';
$diagnostic['steps'][0]['data'] = [
    'columns_found' => $columns,
    'has_filesize' => in_array('filesize', $columns),
    'has_label' => in_array('label', $columns),
    'has_filename' => in_array('filename', $columns),
    'has_filepath' => in_array('filepath', $columns)
];

// ===== ÉTAPE 2: Chercher les fichiers dans ECM (avec colonnes existantes) =====
$diagnostic['steps'][] = [
    'name' => 'Recherche dans llx_ecm_files',
    'status' => 'checking'
];

$select_fields = ['ecm.rowid'];
if (in_array('label', $columns)) $select_fields[] = 'ecm.label';
if (in_array('filename', $columns)) $select_fields[] = 'ecm.filename';
if (in_array('filepath', $columns)) $select_fields[] = 'ecm.filepath';
if (in_array('fullpath_orig', $columns)) $select_fields[] = 'ecm.fullpath_orig';
if (in_array('src_object_type', $columns)) $select_fields[] = 'ecm.src_object_type';
if (in_array('src_object_id', $columns)) $select_fields[] = 'ecm.src_object_id';
if (in_array('date_c', $columns)) $select_fields[] = 'ecm.date_c';
if (in_array('position', $columns)) $select_fields[] = 'ecm.position';

$sql_ecm = "SELECT ".implode(', ', $select_fields)."
FROM ".MAIN_DB_PREFIX."ecm_files as ecm
WHERE ecm.src_object_type = 'actioncomm'
AND ecm.src_object_id = ".$id;

$res_ecm = $db->query($sql_ecm);
$ecm_files = [];

if ($res_ecm) {
    while ($file = $db->fetch_object($res_ecm)) {
        $file_data = [
            'rowid' => $file->rowid ?? null,
            'label' => $file->label ?? 'N/A',
            'filename' => $file->filename ?? 'N/A',
            'filepath' => $file->filepath ?? 'N/A',
            'fullpath_orig' => $file->fullpath_orig ?? 'N/A',
        ];

        // Construire le chemin complet
        if (isset($file->filepath) && isset($file->filename)) {
            $relative_path = $file->filepath.'/'.$file->filename;
            $full_path = DOL_DATA_ROOT.'/'.$relative_path;
            $file_data['full_path'] = $full_path;
            $file_data['file_exists_on_disk'] = file_exists($full_path);

            if (file_exists($full_path)) {
                $file_data['real_filesize'] = filesize($full_path);
                $file_data['mime_type'] = mime_content_type($full_path);
            }
        }

        $ecm_files[] = $file_data;
    }
    $db->free($res_ecm);
}

$diagnostic['steps'][1]['status'] = 'success';
$diagnostic['steps'][1]['data'] = [
    'sql_query' => $sql_ecm,
    'files_found_in_ecm' => count($ecm_files),
    'files' => $ecm_files
];

// ===== ÉTAPE 3: Scanner le dossier filesystem =====
$diagnostic['steps'][] = [
    'name' => 'Scan dossier filesystem',
    'status' => 'checking'
];

$upload_dir = DOL_DATA_ROOT.'/actioncomm/'.$id;
$filesystem_files = [];

$diagnostic['steps'][2]['data'] = [
    'dol_data_root' => DOL_DATA_ROOT,
    'upload_dir' => $upload_dir,
    'dir_exists' => is_dir($upload_dir)
];

if (is_dir($upload_dir)) {
    $files = scandir($upload_dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;

        $filepath = $upload_dir.'/'.$file;
        if (is_file($filepath)) {
            $filesystem_files[] = [
                'filename' => $file,
                'filesize' => filesize($filepath),
                'mime_type' => mime_content_type($filepath),
                'full_path' => $filepath
            ];
        }
    }
}

$diagnostic['steps'][2]['data']['files_found_in_filesystem'] = count($filesystem_files);
$diagnostic['steps'][2]['data']['files'] = $filesystem_files;
$diagnostic['steps'][2]['status'] = 'success';

// ===== ÉTAPE 4: Vérifier l'événement lui-même =====
$diagnostic['steps'][] = [
    'name' => 'Vérification événement actioncomm',
    'status' => 'checking'
];

$sql_event = "SELECT id, label, code, datep, datep2, fk_user_action, fk_soc, fk_project
FROM ".MAIN_DB_PREFIX."actioncomm
WHERE id = ".$id;

$res_event = $db->query($sql_event);
$event_data = null;

if ($res_event && $db->num_rows($res_event) > 0) {
    $event_data = $db->fetch_object($res_event);
    $db->free($res_event);
}

$diagnostic['steps'][3]['status'] = $event_data ? 'success' : 'error';
$diagnostic['steps'][3]['data'] = [
    'event_found' => $event_data ? true : false,
    'event' => $event_data ? [
        'id' => $event_data->id,
        'label' => $event_data->label,
        'code' => $event_data->code,
        'date_debut' => $event_data->datep,
        'date_fin' => $event_data->datep2
    ] : null
];

// ===== ÉTAPE 5: Vérifier les permissions du dossier =====
$diagnostic['steps'][] = [
    'name' => 'Permissions dossier',
    'status' => 'checking'
];

$perms_data = [];
if (is_dir($upload_dir)) {
    $perms = fileperms($upload_dir);
    $perms_data = [
        'permissions_octal' => substr(sprintf('%o', $perms), -4),
        'is_readable' => is_readable($upload_dir),
        'is_writable' => is_writable($upload_dir),
        'owner' => function_exists('posix_getpwuid') ? posix_getpwuid(fileowner($upload_dir)) : 'N/A'
    ];
}

$diagnostic['steps'][4]['status'] = 'success';
$diagnostic['steps'][4]['data'] = $perms_data;

// ===== DIAGNOSTIC FINAL =====
$diagnostic['conclusion'] = [];

if (count($ecm_files) === 0 && count($filesystem_files) === 0) {
    $diagnostic['conclusion'][] = [
        'severity' => 'error',
        'message' => 'Aucun fichier trouvé ni dans ECM ni sur le disque',
        'solution' => 'Le fichier n\'a jamais été uploadé ou a été supprimé'
    ];
} elseif (count($ecm_files) > 0 && count($filesystem_files) === 0) {
    $diagnostic['conclusion'][] = [
        'severity' => 'error',
        'message' => 'Fichier référencé dans ECM mais absent du disque',
        'solution' => 'Les fichiers physiques ont été supprimés. Nettoyer la table ECM ou restaurer les fichiers.'
    ];
} elseif (count($ecm_files) === 0 && count($filesystem_files) > 0) {
    $diagnostic['conclusion'][] = [
        'severity' => 'warning',
        'message' => 'Fichiers présents sur disque mais non référencés dans ECM',
        'solution' => 'Ajouter les entrées manquantes dans llx_ecm_files'
    ];
} else {
    // Vérifier la correspondance
    $ecm_filenames = array_column($ecm_files, 'filename');
    $fs_filenames = array_column($filesystem_files, 'filename');

    $missing_on_disk = array_diff($ecm_filenames, $fs_filenames);
    $missing_in_ecm = array_diff($fs_filenames, $ecm_filenames);

    if (empty($missing_on_disk) && empty($missing_in_ecm)) {
        $diagnostic['conclusion'][] = [
            'severity' => 'success',
            'message' => 'Tous les fichiers sont correctement synchronisés',
            'count' => count($ecm_files)
        ];
    } else {
        if (!empty($missing_on_disk)) {
            $diagnostic['conclusion'][] = [
                'severity' => 'error',
                'message' => 'Fichiers dans ECM mais absents du disque',
                'files' => array_values($missing_on_disk)
            ];
        }
        if (!empty($missing_in_ecm)) {
            $diagnostic['conclusion'][] = [
                'severity' => 'warning',
                'message' => 'Fichiers sur disque mais absents de ECM',
                'files' => array_values($missing_in_ecm)
            ];
        }
    }
}

// ===== AFFICHAGE HTML =====
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnostic Fichiers Planning #<?php echo $id; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f5f5f5;
            padding: 20px;
            line-height: 1.6;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .header h1 { font-size: 28px; margin-bottom: 10px; }
        .header p { opacity: 0.9; }

        .conclusion {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .conclusion h2 {
            font-size: 20px;
            margin-bottom: 15px;
            color: #333;
        }
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 4px solid;
        }
        .alert-success {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
        .alert-warning {
            background: #fff3cd;
            border-color: #ffc107;
            color: #856404;
        }
        .alert-error {
            background: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }
        .alert strong { display: block; margin-bottom: 5px; font-size: 16px; }
        .alert p { margin-top: 5px; font-size: 14px; }

        .step {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .step-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        .step-number {
            background: #667eea;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 15px;
            font-size: 18px;
        }
        .step-title {
            font-size: 20px;
            font-weight: 600;
            color: #333;
        }

        .data-section {
            margin-top: 15px;
        }
        .data-item {
            display: flex;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .data-item:last-child { border-bottom: none; }
        .data-label {
            font-weight: 600;
            width: 250px;
            color: #666;
            flex-shrink: 0;
        }
        .data-value {
            color: #333;
            word-break: break-all;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-success { background: #d4edda; color: #28a745; }
        .badge-error { background: #f8d7da; color: #dc3545; }

        .file-list {
            margin-top: 15px;
        }
        .file-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        .file-item strong {
            display: block;
            margin-bottom: 8px;
            color: #667eea;
        }

        pre {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            font-size: 13px;
            margin-top: 10px;
        }

        .json-data {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            max-height: 400px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Diagnostic Fichiers Planning</h1>
            <p>Événement #<?php echo $id; ?> - <?php echo date('d/m/Y H:i:s'); ?></p>
        </div>

        <div class="conclusion">
            <h2>📊 Conclusion</h2>
            <?php foreach ($diagnostic['conclusion'] as $item): ?>
                <div class="alert alert-<?php echo $item['severity']; ?>">
                    <strong><?php echo htmlspecialchars($item['message']); ?></strong>
                    <?php if (isset($item['solution'])): ?>
                        <p><strong>Solution:</strong> <?php echo htmlspecialchars($item['solution']); ?></p>
                    <?php endif; ?>
                    <?php if (isset($item['files'])): ?>
                        <p><strong>Fichiers concernés:</strong> <?php echo implode(', ', $item['files']); ?></p>
                    <?php endif; ?>
                    <?php if (isset($item['count'])): ?>
                        <p><strong>Nombre de fichiers:</strong> <?php echo $item['count']; ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <?php foreach ($diagnostic['steps'] as $index => $step): ?>
            <div class="step">
                <div class="step-header">
                    <div class="step-number"><?php echo $index + 1; ?></div>
                    <div class="step-title"><?php echo htmlspecialchars($step['name']); ?></div>
                </div>

                <?php if ($step['name'] === 'Structure table llx_ecm_files'): ?>
                    <div class="data-section">
                        <div class="data-item">
                            <div class="data-label">Colonnes trouvées:</div>
                            <div class="data-value"><?php echo count($step['data']['columns_found']); ?></div>
                        </div>
                        <div class="data-item">
                            <div class="data-label">Colonnes disponibles:</div>
                            <div class="data-value"><?php echo implode(', ', $step['data']['columns_found']); ?></div>
                        </div>
                        <div class="data-item">
                            <div class="data-label">Colonne 'filesize':</div>
                            <div class="data-value">
                                <span class="badge badge-<?php echo $step['data']['has_filesize'] ? 'success' : 'error'; ?>">
                                    <?php echo $step['data']['has_filesize'] ? 'Existe' : 'Manquante'; ?>
                                </span>
                            </div>
                        </div>
                    </div>

                <?php elseif ($step['name'] === 'Recherche dans llx_ecm_files'): ?>
                    <div class="data-section">
                        <div class="data-item">
                            <div class="data-label">Fichiers trouvés dans ECM:</div>
                            <div class="data-value">
                                <strong><?php echo $step['data']['files_found_in_ecm']; ?></strong>
                            </div>
                        </div>
                    </div>
                    <pre><?php echo htmlspecialchars($step['data']['sql_query']); ?></pre>

                    <?php if (!empty($step['data']['files'])): ?>
                        <div class="file-list">
                            <?php foreach ($step['data']['files'] as $file): ?>
                                <div class="file-item">
                                    <strong><?php echo htmlspecialchars($file['filename'] ?? $file['label']); ?></strong>
                                    <div class="data-item">
                                        <div class="data-label">Chemin relatif:</div>
                                        <div class="data-value"><?php echo htmlspecialchars($file['filepath'] ?? 'N/A'); ?></div>
                                    </div>
                                    <?php if (isset($file['full_path'])): ?>
                                        <div class="data-item">
                                            <div class="data-label">Chemin complet:</div>
                                            <div class="data-value"><?php echo htmlspecialchars($file['full_path']); ?></div>
                                        </div>
                                        <div class="data-item">
                                            <div class="data-label">Existe sur disque:</div>
                                            <div class="data-value">
                                                <span class="badge badge-<?php echo $file['file_exists_on_disk'] ? 'success' : 'error'; ?>">
                                                    <?php echo $file['file_exists_on_disk'] ? 'OUI' : 'NON'; ?>
                                                </span>
                                            </div>
                                        </div>
                                        <?php if (isset($file['real_filesize'])): ?>
                                            <div class="data-item">
                                                <div class="data-label">Taille:</div>
                                                <div class="data-value"><?php echo number_format($file['real_filesize'] / 1024, 2); ?> KB</div>
                                            </div>
                                            <div class="data-item">
                                                <div class="data-label">Type MIME:</div>
                                                <div class="data-value"><?php echo htmlspecialchars($file['mime_type']); ?></div>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                <?php elseif ($step['name'] === 'Scan dossier filesystem'): ?>
                    <div class="data-section">
                        <div class="data-item">
                            <div class="data-label">DOL_DATA_ROOT:</div>
                            <div class="data-value"><?php echo htmlspecialchars($step['data']['dol_data_root']); ?></div>
                        </div>
                        <div class="data-item">
                            <div class="data-label">Dossier upload:</div>
                            <div class="data-value"><?php echo htmlspecialchars($step['data']['upload_dir']); ?></div>
                        </div>
                        <div class="data-item">
                            <div class="data-label">Dossier existe:</div>
                            <div class="data-value">
                                <span class="badge badge-<?php echo $step['data']['dir_exists'] ? 'success' : 'error'; ?>">
                                    <?php echo $step['data']['dir_exists'] ? 'OUI' : 'NON'; ?>
                                </span>
                            </div>
                        </div>
                        <div class="data-item">
                            <div class="data-label">Fichiers trouvés:</div>
                            <div class="data-value"><strong><?php echo $step['data']['files_found_in_filesystem']; ?></strong></div>
                        </div>
                    </div>

                    <?php if (!empty($step['data']['files'])): ?>
                        <div class="file-list">
                            <?php foreach ($step['data']['files'] as $file): ?>
                                <div class="file-item">
                                    <strong><?php echo htmlspecialchars($file['filename']); ?></strong>
                                    <div class="data-item">
                                        <div class="data-label">Taille:</div>
                                        <div class="data-value"><?php echo number_format($file['filesize'] / 1024, 2); ?> KB</div>
                                    </div>
                                    <div class="data-item">
                                        <div class="data-label">Type:</div>
                                        <div class="data-value"><?php echo htmlspecialchars($file['mime_type']); ?></div>
                                    </div>
                                    <div class="data-item">
                                        <div class="data-label">Chemin:</div>
                                        <div class="data-value"><?php echo htmlspecialchars($file['full_path']); ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                <?php elseif ($step['name'] === 'Vérification événement actioncomm'): ?>
                    <div class="data-section">
                        <div class="data-item">
                            <div class="data-label">Événement trouvé:</div>
                            <div class="data-value">
                                <span class="badge badge-<?php echo $step['data']['event_found'] ? 'success' : 'error'; ?>">
                                    <?php echo $step['data']['event_found'] ? 'OUI' : 'NON'; ?>
                                </span>
                            </div>
                        </div>
                        <?php if ($step['data']['event']): ?>
                            <div class="data-item">
                                <div class="data-label">Label:</div>
                                <div class="data-value"><?php echo htmlspecialchars($step['data']['event']['label']); ?></div>
                            </div>
                            <div class="data-item">
                                <div class="data-label">Date début:</div>
                                <div class="data-value"><?php echo htmlspecialchars($step['data']['event']['date_debut']); ?></div>
                            </div>
                        <?php endif; ?>
                    </div>

                <?php elseif ($step['name'] === 'Permissions dossier'): ?>
                    <?php if (!empty($step['data'])): ?>
                        <div class="data-section">
                            <div class="data-item">
                                <div class="data-label">Permissions (octal):</div>
                                <div class="data-value"><?php echo htmlspecialchars($step['data']['permissions_octal'] ?? 'N/A'); ?></div>
                            </div>
                            <div class="data-item">
                                <div class="data-label">Lisible:</div>
                                <div class="data-value">
                                    <span class="badge badge-<?php echo $step['data']['is_readable'] ? 'success' : 'error'; ?>">
                                        <?php echo $step['data']['is_readable'] ? 'OUI' : 'NON'; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="data-item">
                                <div class="data-label">Modifiable:</div>
                                <div class="data-value">
                                    <span class="badge badge-<?php echo $step['data']['is_writable'] ? 'success' : 'error'; ?>">
                                        <?php echo $step['data']['is_writable'] ? 'OUI' : 'NON'; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <p style="color: #999;">Dossier inexistant, impossible de vérifier les permissions</p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <div class="step">
            <div class="step-header">
                <div class="step-title">📄 Données complètes (JSON)</div>
            </div>
            <div class="json-data">
                <?php echo json_encode($diagnostic, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>
            </div>
        </div>
    </div>
</body>
</html>

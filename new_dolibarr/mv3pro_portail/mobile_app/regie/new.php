<?php
/**
 * Nouveau bon de régie - Version mobile
 */

require_once '../includes/session.php';
require_once '../../regie/class/regie.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

global $db, $user, $conf;

checkMobileSession();

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $regie = new Regie($db);
    $regie->fk_project = GETPOST('fk_project', 'int');
    $regie->date_regie = dol_now();
    $regie->location_text = GETPOST('location_text', 'alpha');
    $regie->type_regie = GETPOST('type_regie', 'alpha');
    $regie->note_public = GETPOST('note_public', 'restricthtml');
    $regie->fk_user_author = $user->id;

    $project = new Project($db);
    $result = $project->fetch($regie->fk_project);

    if ($result > 0) {
        if ($project->socid > 0) {
            $regie->fk_soc = $project->socid;
        } else {
            $regie->fk_soc = 0;
        }

        $id = $regie->create($user);

        if ($id > 0) {
            header('Location: view.php?id='.$id);
            exit;
        } else {
            $error_message = "Erreur lors de la création du bon de régie: " . $regie->error;
        }
    } else {
        $error_message = "Erreur: Projet introuvable.";
    }
}

$sql = "SELECT p.rowid, p.ref, p.title, p.fk_soc, s.nom as client_name";
$sql .= " FROM ".MAIN_DB_PREFIX."projet as p";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = p.fk_soc";
$sql .= " WHERE p.entity IN (0, ".$conf->entity.")";
$sql .= " AND p.fk_statut = 1";
$sql .= " ORDER BY p.ref DESC LIMIT 100";

$result = $db->query($sql);
$projects = array();
if ($result) {
    while ($obj = $db->fetch_object($result)) {
        $projects[] = $obj;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouveau bon de régie</title>
    <link rel="stylesheet" href="../css/mobile_app.css">
    <style>
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }
        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
        }
        .form-textarea {
            min-height: 100px;
            resize: vertical;
        }
        .btn-submit {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <div class="page-header">
            <a href="list.php" class="back-btn">←</a>
            <h1>Nouveau bon de régie</h1>
        </div>

        <div class="content-area" style="padding: 20px;">
            <?php if ($error_message): ?>
                <div style="background: #ff7675; color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <strong>⚠️ Erreur:</strong> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div style="background: #55efc4; color: #00b894; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <strong>✅ Succès:</strong> <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Projet *</label>
                    <select name="fk_project" class="form-select" required>
                        <option value="">Sélectionner un projet...</option>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?php echo $project->rowid; ?>">
                                <?php echo $project->ref.' - '.$project->title.($project->client_name ? ' ('.$project->client_name.')' : ''); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Lieu précis</label>
                    <input type="text" name="location_text" class="form-input"
                           placeholder="Ex: Appartement 3.2, SDB">
                </div>

                <div class="form-group">
                    <label class="form-label">Type de régie</label>
                    <select name="type_regie" class="form-select">
                        <option value="">Sélectionner...</option>
                        <?php
                        $sql = "SELECT code, label FROM ".MAIN_DB_PREFIX."mv3_regie_type WHERE active = 1 ORDER BY position";
                        $resql = $db->query($sql);
                        if ($resql) {
                            while ($obj = $db->fetch_object($resql)) {
                                echo '<option value="'.$obj->code.'">'.$obj->label.'</option>';
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Observations</label>
                    <textarea name="note_public" class="form-textarea"
                              placeholder="Détails des travaux..."></textarea>
                </div>

                <button type="submit" class="btn-submit">Créer le bon de régie</button>
            </form>
        </div>

        <?php include '../includes/bottom_nav.php'; ?>
    </div>
</body>
</html>

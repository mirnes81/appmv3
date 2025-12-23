<?php
/**
 * Modifier un bon de régie - Version mobile
 */

require_once '../includes/session.php';
require_once '../../regie/class/regie.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

global $db, $user, $conf;

checkMobileSession();

$id = GETPOST('id', 'int');
$error_message = '';
$success_message = '';

$regie = new Regie($db);
$result = $regie->fetch($id);

if ($result <= 0) {
    header('Location: list.php');
    exit;
}

if ($regie->status != 0) {
    header('Location: view.php?id='.$id);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $regie->location_text = GETPOST('location_text', 'alpha');
    $regie->type_regie = GETPOST('type_regie', 'alpha');
    $regie->note_public = GETPOST('note_public', 'restricthtml');

    $result = $regie->update($user);

    if ($result > 0) {
        header('Location: view.php?id='.$id);
        exit;
    } else {
        $error_message = "Erreur lors de la modification: " . $regie->error;
    }
}

$project = new Project($db);
$project->fetch($regie->fk_project);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier <?php echo $regie->ref; ?></title>
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
        .project-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <div class="page-header">
            <a href="view.php?id=<?php echo $id; ?>" class="back-btn">←</a>
            <h1>Modifier <?php echo $regie->ref; ?></h1>
        </div>

        <div class="content-area" style="padding: 20px;">
            <?php if ($error_message): ?>
                <div style="background: #ff7675; color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <strong>⚠️ Erreur:</strong> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <div class="project-info">
                <strong>Projet:</strong> <?php echo $project->ref.' - '.$project->title; ?>
            </div>

            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Lieu précis</label>
                    <input type="text" name="location_text" class="form-input"
                           value="<?php echo dol_escape_htmltag($regie->location_text); ?>"
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
                                $selected = ($obj->code == $regie->type_regie) ? 'selected' : '';
                                echo '<option value="'.$obj->code.'" '.$selected.'>'.$obj->label.'</option>';
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Observations</label>
                    <textarea name="note_public" class="form-textarea"
                              placeholder="Détails des travaux..."><?php echo dol_escape_htmltag($regie->note_public); ?></textarea>
                </div>

                <button type="submit" class="btn-submit">Enregistrer les modifications</button>
            </form>
        </div>

        <?php include '../includes/bottom_nav.php'; ?>
    </div>
</body>
</html>

<?php
/**
 * Envoi email fiche sens de pose avec messages prédéfinis
 */

$res = 0;
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";

if (!$res) {
    die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';

$id = GETPOST('id', 'int');
$action = GETPOST('action', 'alpha');

if (!$id) {
    header('Location: list.php');
    exit;
}

$sql = "SELECT sp.*, s.nom as client_societe, s.email as client_email,
               p.ref as projet_ref, p.title as projet_title
        FROM ".MAIN_DB_PREFIX."mv3_sens_pose sp
        LEFT JOIN ".MAIN_DB_PREFIX."societe s ON s.rowid = sp.fk_client
        LEFT JOIN ".MAIN_DB_PREFIX."projet p ON p.rowid = sp.fk_projet
        WHERE sp.rowid = ".(int)$id;

$resql = $db->query($sql);
if (!$resql || $db->num_rows($resql) == 0) {
    header('Location: list.php');
    exit;
}

$fiche = $db->fetch_object($resql);

$messages_predefinis = [
    'validation' => [
        'sujet' => 'Validation Fiche Sens de Pose - '.$fiche->ref,
        'message' => "Bonjour,

Veuillez trouver ci-joint la fiche de sens de pose pour votre chantier.

Merci de bien vouloir la valider et nous la retourner signée.

Cordialement,
L'équipe MV3 PRO"
    ],
    'information' => [
        'sujet' => 'Information Chantier - Fiche Sens de Pose '.$fiche->ref,
        'message' => "Bonjour,

Vous trouverez en pièce jointe la fiche technique détaillant le sens de pose et les spécifications des carrelages pour votre chantier.

N'hésitez pas à nous contacter pour toute question.

Cordialement,
L'équipe MV3 PRO"
    ],
    'rappel' => [
        'sujet' => 'Rappel - Validation Fiche Sens de Pose '.$fiche->ref,
        'message' => "Bonjour,

Nous revenons vers vous concernant la fiche de sens de pose ci-jointe.

Pourriez-vous nous confirmer sa validation dans les meilleurs délais ?

Merci d'avance,
L'équipe MV3 PRO"
    ],
    'confirmation' => [
        'sujet' => 'Confirmation - Fiche Sens de Pose '.$fiche->ref,
        'message' => "Bonjour,

Suite à notre échange, vous trouverez ci-joint la fiche de sens de pose mise à jour.

Tout est maintenant conforme à vos demandes.

Cordialement,
L'équipe MV3 PRO"
    ]
];

$success = '';
$error = '';

if ($action == 'send') {
    $email_to = GETPOST('email_to', 'email');
    $email_cc = GETPOST('email_cc', 'email');
    $sujet = GETPOST('sujet', 'restricthtml');
    $message = GETPOST('message', 'restricthtml');

    if (!$email_to) {
        $error = "L'adresse email du destinataire est requise";
    } elseif (!$sujet || !$message) {
        $error = "Le sujet et le message sont requis";
    } else {
        $pdf_url = dol_buildpath('/custom/mv3pro_portail/sens_pose/pdf.php?id='.$id, 2);
        $pdf_content = file_get_contents($pdf_url);

        if ($pdf_content === false) {
            $error = "Erreur lors de la génération du PDF";
        } else {
            $pdf_filename = 'sens_pose_'.$fiche->ref.'.pdf';
            $temp_file = DOL_DATA_ROOT.'/admin/temp/'.$pdf_filename;

            if (!is_dir(DOL_DATA_ROOT.'/admin/temp')) {
                mkdir(DOL_DATA_ROOT.'/admin/temp', 0755, true);
            }

            file_put_contents($temp_file, $pdf_content);

            $from = $conf->global->MAIN_MAIL_EMAIL_FROM;
            $from_name = $conf->global->MAIN_INFO_SOCIETE_NOM;

            $mail = new CMailFile(
                $sujet,
                $email_to,
                $from,
                $message,
                array($temp_file),
                array('application/pdf'),
                array($pdf_filename),
                $email_cc,
                '',
                0,
                -1,
                '',
                '',
                '',
                '',
                'mail'
            );

            $result = $mail->sendfile();

            if ($result) {
                $sql_update = "UPDATE ".MAIN_DB_PREFIX."mv3_sens_pose
                              SET statut = 'envoye', date_envoi = NOW()
                              WHERE rowid = ".(int)$id;
                $db->query($sql_update);

                $success = "Email envoyé avec succès à ".$email_to;

                unlink($temp_file);
            } else {
                $error = "Erreur lors de l'envoi de l'email : ".$mail->error;
            }
        }
    }
}

llxHeader('', 'Envoi Email');
?>

<style>
.email-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 24px;
}
.email-header {
    background: linear-gradient(135deg, #0891b2 0%, #06b6d4 100%);
    color: white;
    padding: 32px;
    border-radius: 16px;
    margin-bottom: 32px;
}
.email-title {
    font-size: 28px;
    font-weight: 800;
    margin-bottom: 8px;
}
.email-subtitle {
    font-size: 16px;
    opacity: 0.95;
}
.email-card {
    background: white;
    border-radius: 16px;
    padding: 32px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    margin-bottom: 24px;
}
.card-title {
    font-size: 20px;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 12px;
}
.form-group {
    margin-bottom: 24px;
}
.form-label {
    display: block;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 8px;
    font-size: 15px;
}
.form-input, .form-textarea, .form-select {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-size: 16px;
    transition: all 0.2s;
    font-family: inherit;
}
.form-input:focus, .form-textarea:focus, .form-select:focus {
    outline: none;
    border-color: #0891b2;
    box-shadow: 0 0 0 4px rgba(8,145,178,0.1);
}
.form-textarea {
    min-height: 180px;
    resize: vertical;
}
.btn {
    padding: 14px 28px;
    border: none;
    border-radius: 10px;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-block;
    text-decoration: none;
}
.btn-primary {
    background: #0891b2;
    color: white;
}
.btn-primary:hover {
    background: #0e7490;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(8,145,178,0.3);
}
.btn-secondary {
    background: #64748b;
    color: white;
}
.btn-secondary:hover {
    background: #475569;
}
.alert {
    padding: 16px;
    border-radius: 10px;
    margin-bottom: 24px;
    font-weight: 600;
}
.alert-success {
    background: #d1fae5;
    color: #065f46;
    border: 2px solid #10b981;
}
.alert-error {
    background: #fee2e2;
    color: #991b1b;
    border: 2px solid #ef4444;
}
.template-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}
.template-btn {
    padding: 16px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    background: white;
    cursor: pointer;
    transition: all 0.2s;
    text-align: left;
}
.template-btn:hover {
    border-color: #0891b2;
    background: #f0f9ff;
    transform: translateY(-2px);
}
.template-btn.active {
    border-color: #0891b2;
    background: #e0f2fe;
}
.template-name {
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 4px;
}
.template-preview {
    font-size: 13px;
    color: #64748b;
}
.info-box {
    background: #f0f9ff;
    border: 2px solid #0891b2;
    border-radius: 10px;
    padding: 16px;
    margin-bottom: 24px;
}
.info-box-title {
    font-weight: 700;
    color: #0891b2;
    margin-bottom: 8px;
}
.info-box-content {
    color: #0e7490;
    line-height: 1.6;
}
</style>

<div class="email-container">
    <div class="email-header">
        <div class="email-title">Envoyer par Email</div>
        <div class="email-subtitle">Fiche sens de pose : <?php echo htmlspecialchars($fiche->ref); ?></div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
        <a href="view.php?id=<?php echo $id; ?>" class="btn btn-primary">Retour à la fiche</a>
    <?php else: ?>

    <div class="email-card">
        <h2 class="card-title">Informations Client</h2>
        <div class="info-box">
            <div class="info-box-title">Destinataire</div>
            <div class="info-box-content">
                <strong><?php echo htmlspecialchars($fiche->client_name); ?></strong><br>
                <?php if ($fiche->client_email): ?>
                    Email : <?php echo htmlspecialchars($fiche->client_email); ?>
                <?php else: ?>
                    <span style="color: #ef4444;">Aucun email configuré pour ce client</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="post" action="" id="emailForm" accept-charset="UTF-8">
        <input type="hidden" name="token" value="<?php echo newToken(); ?>">
        <input type="hidden" name="action" value="send">

        <div class="email-card">
            <h2 class="card-title">Messages Prédéfinis</h2>
            <div class="template-grid">
                <?php foreach ($messages_predefinis as $key => $template): ?>
                    <button type="button" class="template-btn" onclick="useTemplate('<?php echo $key; ?>')">
                        <div class="template-name"><?php echo ucfirst($key); ?></div>
                        <div class="template-preview"><?php echo substr($template['sujet'], 0, 50).'...'; ?></div>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="email-card">
            <h2 class="card-title">Destinataires</h2>

            <div class="form-group">
                <label class="form-label">À (requis)</label>
                <input type="email" name="email_to" class="form-input"
                       value="<?php echo htmlspecialchars($fiche->client_email ?: ''); ?>"
                       placeholder="email@exemple.com" required>
            </div>

            <div class="form-group">
                <label class="form-label">CC (optionnel)</label>
                <input type="email" name="email_cc" class="form-input"
                       placeholder="copie@exemple.com">
            </div>
        </div>

        <div class="email-card">
            <h2 class="card-title">Message</h2>

            <div class="form-group">
                <label class="form-label">Sujet</label>
                <input type="text" name="sujet" id="sujet" class="form-input"
                       placeholder="Objet de l'email" required>
            </div>

            <div class="form-group">
                <label class="form-label">Message</label>
                <textarea name="message" id="message" class="form-textarea"
                          placeholder="Votre message..." required></textarea>
            </div>

            <div class="info-box">
                <div class="info-box-title">Pièce jointe</div>
                <div class="info-box-content">
                    Le PDF de la fiche sens de pose sera automatiquement joint à l'email
                </div>
            </div>
        </div>

        <div style="display: flex; gap: 16px;">
            <button type="submit" class="btn btn-primary">
                Envoyer l'email
            </button>
            <a href="view.php?id=<?php echo $id; ?>" class="btn btn-secondary">
                Annuler
            </a>
        </div>
    </form>

    <?php endif; ?>
</div>

<script>
const templates = <?php echo json_encode($messages_predefinis); ?>;

function useTemplate(key) {
    const template = templates[key];
    if (template) {
        document.getElementById('sujet').value = template.sujet;
        document.getElementById('message').value = template.message;

        document.querySelectorAll('.template-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        event.target.closest('.template-btn').classList.add('active');
    }
}
</script>

<?php
llxFooter();
?>

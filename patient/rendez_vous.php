<?php
// ============================================================
//  Prise de Rendez-vous
//  Fichier : patient/rendez_vous.php
// ============================================================
$pageTitle = "Prendre un Rendez-vous";
require_once '../includes/fonctions.php';
require_once '../includes/connexion.php';
requireRole('patient');

// Récupérer l'ID du patient
$stmt = $pdo->prepare("SELECT id FROM patients WHERE utilisateur_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$patient = $stmt->fetch();
$patient_id = $patient['id'];

// Liste des médecins disponibles
$stmt = $pdo->query("
    SELECT me.id, me.specialite, CONCAT(u.prenom, ' ', u.nom) AS nom_complet
    FROM medecins me
    JOIN utilisateurs u ON me.utilisateur_id = u.id
    WHERE u.statut = 'actif'
    ORDER BY me.specialite, u.nom
");
$medecins = $stmt->fetchAll();

$erreurs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $medecin_id = (int)($_POST['medecin_id'] ?? 0);
    $date_rdv   = clean($_POST['date_rdv'] ?? '');
    $heure_rdv  = clean($_POST['heure_rdv'] ?? '');
    $motif      = clean($_POST['motif'] ?? '');

    // Validations
    if (!$medecin_id)        $erreurs[] = "Veuillez sélectionner un médecin.";
    if (empty($date_rdv))    $erreurs[] = "Veuillez choisir une date.";
    if (empty($heure_rdv))   $erreurs[] = "Veuillez choisir une heure.";

    // Vérifier que la date n'est pas dans le passé
    if (!empty($date_rdv) && strtotime($date_rdv) < strtotime(date('Y-m-d'))) {
        $erreurs[] = "La date ne peut pas être dans le passé.";
    }

    // Vérifier que le créneau n'est pas déjà pris
    if (empty($erreurs)) {
        $stmt = $pdo->prepare("
            SELECT id FROM rendez_vous
            WHERE medecin_id = ? AND date_rdv = ? AND heure_rdv = ?
            AND statut NOT IN ('annule')
        ");
        $stmt->execute([$medecin_id, $date_rdv, $heure_rdv]);
        if ($stmt->fetch()) {
            $erreurs[] = "Ce créneau est déjà pris. Veuillez choisir un autre horaire.";
        }
    }

    // Enregistrement
    if (empty($erreurs)) {
        $stmt = $pdo->prepare("
            INSERT INTO rendez_vous (patient_id, medecin_id, date_rdv, heure_rdv, motif, statut)
            VALUES (?, ?, ?, ?, ?, 'en_attente')
        ");
        $stmt->execute([$patient_id, $medecin_id, $date_rdv, $heure_rdv, $motif]);

        // Notification au patient
        $stmt = $pdo->prepare("
            INSERT INTO notifications (utilisateur_id, message, type)
            VALUES (?, ?, 'rappel_rdv')
        ");
        $msg = "Votre rendez-vous du " . date('d/m/Y', strtotime($date_rdv)) . " à " . $heure_rdv . " a été enregistré avec succès.";
        $stmt->execute([$_SESSION['user_id'], $msg]);

        setFlash('success', 'Rendez-vous pris avec succès ! En attente de confirmation du médecin.');
        header("Location: mes_rdv.php");
        exit();
    }
}

require_once '../includes/header.php';
?>

<div class="row">
    <!-- SIDEBAR -->
    <div class="col-md-3 col-lg-2 px-0">
        <div class="sidebar d-flex flex-column" style="min-height:85vh;">
            <div class="text-center text-white py-4 px-3 border-bottom border-secondary">
                <i class="bi bi-person-circle" style="font-size:3rem;"></i>
                <p class="mb-0 fw-bold mt-2"><?= htmlspecialchars($_SESSION['prenom'].' '.$_SESSION['nom']) ?></p>
                <small class="opacity-75">Patient</small>
            </div>
            <nav class="nav flex-column mt-3">
                <a href="dashboard.php" class="nav-link">
                    <i class="bi bi-speedometer2"></i> Tableau de bord
                </a>
                <a href="rendez_vous.php" class="nav-link active">
                    <i class="bi bi-calendar-plus"></i> Prendre RDV
                </a>
                <a href="mes_rdv.php" class="nav-link">
                    <i class="bi bi-calendar-check"></i> Mes Rendez-vous
                </a>
                <a href="historique.php" class="nav-link">
                    <i class="bi bi-folder2-open"></i> Dossier Médical
                </a>
                <a href="notifications.php" class="nav-link">
                    <i class="bi bi-bell"></i> Notifications
                </a>
                <a href="profil.php" class="nav-link">
                    <i class="bi bi-person-gear"></i> Mon Profil
                </a>
                <hr class="border-secondary mx-3">
                <a href="../auth/logout.php" class="nav-link text-danger">
                    <i class="bi bi-box-arrow-right"></i> Déconnexion
                </a>
            </nav>
        </div>
    </div>

    <!-- CONTENU -->
    <div class="col-md-9 col-lg-10 py-3 px-4">

        <?php afficherFlash(); ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold text-primary mb-0">
                    <i class="bi bi-calendar-plus me-2"></i>Prendre un Rendez-vous
                </h4>
                <small class="text-muted">Choisissez un médecin, une date et un horaire</small>
            </div>
            <a href="mes_rdv.php" class="btn btn-outline-primary">
                <i class="bi bi-calendar-check me-2"></i>Mes RDV
            </a>
        </div>

        <div class="row g-4">
            <!-- FORMULAIRE -->
            <div class="col-md-7">
                <div class="card">
                    <div class="card-header bg-white fw-bold text-primary border-bottom">
                        <i class="bi bi-pencil-square me-2"></i>Formulaire de Rendez-vous
                    </div>
                    <div class="card-body p-4">

                        <?php if (!empty($erreurs)): ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <ul class="mb-0 mt-1">
                                    <?php foreach ($erreurs as $e): ?>
                                        <li><?= $e ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">

                            <!-- Sélection du médecin -->
                            <div class="mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-person-badge me-1 text-primary"></i>
                                    Médecin <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" name="medecin_id" id="medecin_id"
                                        onchange="chargerDisponibilites(this.value)" required>
                                    <option value="">-- Sélectionner un médecin --</option>
                                    <?php
                                    $specialite_courante = '';
                                    foreach ($medecins as $med):
                                        if ($med['specialite'] !== $specialite_courante):
                                            if ($specialite_courante !== '') echo '</optgroup>';
                                            echo '<optgroup label="' . htmlspecialchars($med['specialite']) . '">';
                                            $specialite_courante = $med['specialite'];
                                        endif;
                                    ?>
                                        <option value="<?= $med['id'] ?>"
                                            <?= (isset($_POST['medecin_id']) && $_POST['medecin_id'] == $med['id']) ? 'selected' : '' ?>>
                                            Dr. <?= htmlspecialchars($med['nom_complet']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                    <?php if ($specialite_courante !== '') echo '</optgroup>'; ?>
                                </select>
                            </div>

                            <!-- Date -->
                            <div class="mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-calendar3 me-1 text-primary"></i>
                                    Date souhaitée <span class="text-danger">*</span>
                                </label>
                                <input type="date" class="form-control" name="date_rdv" id="date_rdv"
                                       min="<?= date('Y-m-d') ?>"
                                       value="<?= isset($_POST['date_rdv']) ? clean($_POST['date_rdv']) : '' ?>"
                                       onchange="chargerCreneaux()" required>
                            </div>

                            <!-- Heure -->
                            <div class="mb-3">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-clock me-1 text-primary"></i>
                                    Heure souhaitée <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" name="heure_rdv" id="heure_rdv" required>
                                    <option value="">-- Choisir d'abord un médecin et une date --</option>
                                </select>
                                <div id="creneaux_info" class="form-text text-muted"></div>
                            </div>

                            <!-- Motif -->
                            <div class="mb-4">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-chat-text me-1 text-primary"></i>
                                    Motif de la consultation
                                </label>
                                <textarea class="form-control" name="motif" rows="3"
                                          placeholder="Décrivez brièvement la raison de votre visite..."><?= isset($_POST['motif']) ? clean($_POST['motif']) : '' ?></textarea>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-calendar-check me-2"></i>Confirmer le Rendez-vous
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- INFOS & CONSEILS -->
            <div class="col-md-5">
                <!-- Récapitulatif médecin sélectionné -->
                <div class="card mb-3" id="card_medecin" style="display:none!important;">
                    <div class="card-header bg-white fw-bold text-primary border-bottom">
                        <i class="bi bi-person-lines-fill me-2"></i>Médecin sélectionné
                    </div>
                    <div class="card-body text-center py-4">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center
                                    justify-content-center mx-auto mb-3"
                             style="width:60px;height:60px;font-size:1.8rem;">
                            <i class="bi bi-person-badge"></i>
                        </div>
                        <h6 class="fw-bold mb-1" id="info_nom_medecin">—</h6>
                        <small class="text-muted" id="info_specialite">—</small>
                        <hr>
                        <div id="info_disponibilites" class="text-start small text-muted"></div>
                    </div>
                </div>

                <!-- Conseils -->
                <div class="card">
                    <div class="card-header bg-white fw-bold text-primary border-bottom">
                        <i class="bi bi-info-circle me-2"></i>Informations utiles
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <i class="bi bi-check-circle text-success me-2"></i>
                                Arrivez 10 min avant votre rendez-vous
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-check-circle text-success me-2"></i>
                                Apportez votre carte d'étudiant ou badge
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-check-circle text-success me-2"></i>
                                Annulation possible jusqu'à 24h avant
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-info-circle text-primary me-2"></i>
                                Le médecin confirmera votre RDV
                            </li>
                            <li>
                                <i class="bi bi-bell text-warning me-2"></i>
                                Vous recevrez une notification de confirmation
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Données des médecins (injectées depuis PHP)
const medecins = <?= json_encode($medecins) ?>;

// Créneaux horaires standards
const tousCreneaux = [
    "08:00","08:30","09:00","09:30","10:00","10:30",
    "11:00","11:30","14:00","14:30","15:00","15:30",
    "16:00","16:30","17:00"
];

function chargerDisponibilites(medecinId) {
    const select  = document.getElementById('heure_rdv');
    const cardMed = document.getElementById('card_medecin');
    const infoNom = document.getElementById('info_nom_medecin');
    const infoSpe = document.getElementById('info_specialite');

    select.innerHTML = '<option value="">-- Choisir une date d\'abord --</option>';

    if (!medecinId) {
        cardMed.style.display = 'none';
        return;
    }

    const med = medecins.find(m => m.id == medecinId);
    if (med) {
        infoNom.textContent = 'Dr. ' + med.nom_complet;
        infoSpe.textContent = med.specialite;
        cardMed.style.removeProperty('display');
    }

    chargerCreneaux();
}

function chargerCreneaux() {
    const medecinId = document.getElementById('medecin_id').value;
    const dateRdv   = document.getElementById('date_rdv').value;
    const select    = document.getElementById('heure_rdv');
    const info      = document.getElementById('creneaux_info');

    select.innerHTML = '<option value="">-- Chargement... --</option>';

    if (!medecinId || !dateRdv) {
        select.innerHTML = '<option value="">-- Choisir un médecin et une date --</option>';
        return;
    }

    // Appel AJAX pour obtenir les créneaux déjà pris
    fetch(`get_creneaux.php?medecin_id=${medecinId}&date=${dateRdv}`)
        .then(r => r.json())
        .then(pris => {
            select.innerHTML = '<option value="">-- Sélectionner un créneau --</option>';
            let disponibles = 0;

            tousCreneaux.forEach(h => {
                const opt = document.createElement('option');
                opt.value = h + ':00';
                opt.textContent = h;
                if (pris.includes(h + ':00')) {
                    opt.disabled = true;
                    opt.textContent += ' (indisponible)';
                    opt.style.color = '#999';
                } else {
                    disponibles++;
                }
                select.appendChild(opt);
            });

            info.textContent = disponibles + ' créneau(x) disponible(s)';
        })
        .catch(() => {
            select.innerHTML = '<option value="">Erreur de chargement</option>';
        });
}
</script>

<?php require_once '../includes/footer.php'; ?>

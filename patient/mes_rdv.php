<?php
// ============================================================
//  Mes Rendez-vous
//  Fichier : patient/mes_rdv.php
// ============================================================
$pageTitle = "Mes Rendez-vous";
require_once '../includes/fonctions.php';
require_once '../includes/connexion.php';
requireRole('patient');

$stmt = $pdo->prepare("SELECT id FROM patients WHERE utilisateur_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$patient    = $stmt->fetch();
$patient_id = $patient['id'];

// Annulation d'un RDV
if (isset($_GET['annuler']) && is_numeric($_GET['annuler'])) {
    $rdv_id = (int)$_GET['annuler'];
    // Vérifier que ce RDV appartient bien au patient
    $stmt = $pdo->prepare("
        SELECT id, date_rdv FROM rendez_vous
        WHERE id = ? AND patient_id = ? AND statut IN ('en_attente','confirme')
    ");
    $stmt->execute([$rdv_id, $patient_id]);
    $rdv = $stmt->fetch();

    if ($rdv) {
        $stmt = $pdo->prepare("UPDATE rendez_vous SET statut = 'annule' WHERE id = ?");
        $stmt->execute([$rdv_id]);

        // Notification
        $stmt = $pdo->prepare("INSERT INTO notifications (utilisateur_id, message, type) VALUES (?, ?, 'annulation')");
        $stmt->execute([$_SESSION['user_id'], "Votre rendez-vous du " . date('d/m/Y', strtotime($rdv['date_rdv'])) . " a été annulé."]);

        setFlash('warning', 'Rendez-vous annulé avec succès.');
    }
    header("Location: mes_rdv.php");
    exit();
}

// Filtre par statut
$filtre = clean($_GET['statut'] ?? 'tous');
$where  = "WHERE r.patient_id = ?";
$params = [$patient_id];
if ($filtre !== 'tous') {
    $where  .= " AND r.statut = ?";
    $params[] = $filtre;
}

$stmt = $pdo->prepare("
    SELECT r.*, CONCAT(u.prenom, ' ', u.nom) AS medecin_nom, me.specialite
    FROM rendez_vous r
    JOIN medecins me ON r.medecin_id = me.id
    JOIN utilisateurs u ON me.utilisateur_id = u.id
    $where
    ORDER BY r.date_rdv DESC, r.heure_rdv DESC
");
$stmt->execute($params);
$rdvs = $stmt->fetchAll();

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
                <a href="dashboard.php" class="nav-link"><i class="bi bi-speedometer2"></i> Tableau de bord</a>
                <a href="rendez_vous.php" class="nav-link"><i class="bi bi-calendar-plus"></i> Prendre RDV</a>
                <a href="mes_rdv.php" class="nav-link active"><i class="bi bi-calendar-check"></i> Mes Rendez-vous</a>
                <a href="historique.php" class="nav-link"><i class="bi bi-folder2-open"></i> Dossier Médical</a>
                <a href="notifications.php" class="nav-link"><i class="bi bi-bell"></i> Notifications</a>
                <a href="profil.php" class="nav-link"><i class="bi bi-person-gear"></i> Mon Profil</a>
                <hr class="border-secondary mx-3">
                <a href="../auth/logout.php" class="nav-link text-danger"><i class="bi bi-box-arrow-right"></i> Déconnexion</a>
            </nav>
        </div>
    </div>

    <!-- CONTENU -->
    <div class="col-md-9 col-lg-10 py-3 px-4">

        <?php afficherFlash(); ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold text-primary mb-0">
                    <i class="bi bi-calendar-check me-2"></i>Mes Rendez-vous
                </h4>
                <small class="text-muted"><?= count($rdvs) ?> rendez-vous trouvé(s)</small>
            </div>
            <a href="rendez_vous.php" class="btn btn-primary">
                <i class="bi bi-calendar-plus me-2"></i>Nouveau RDV
            </a>
        </div>

        <!-- Filtres -->
        <div class="card mb-4">
            <div class="card-body py-2">
                <div class="d-flex gap-2 flex-wrap align-items-center">
                    <span class="fw-semibold text-muted me-2">Filtrer :</span>
                    <?php
                    $filtres = [
                        'tous'       => ['label' => 'Tous',        'class' => 'secondary'],
                        'en_attente' => ['label' => 'En attente',  'class' => 'warning'],
                        'confirme'   => ['label' => 'Confirmés',   'class' => 'success'],
                        'effectue'   => ['label' => 'Effectués',   'class' => 'info'],
                        'annule'     => ['label' => 'Annulés',     'class' => 'danger'],
                    ];
                    foreach ($filtres as $val => $opt):
                        $active = ($filtre === $val) ? 'btn-'.$opt['class'] : 'btn-outline-'.$opt['class'];
                    ?>
                        <a href="?statut=<?= $val ?>" class="btn btn-sm <?= $active ?>">
                            <?= $opt['label'] ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Liste des RDV -->
        <?php if (!empty($rdvs)): ?>
            <div class="row g-3">
                <?php foreach ($rdvs as $rdv):
                    $badgeClass = [
                        'en_attente' => 'warning',
                        'confirme'   => 'success',
                        'annule'     => 'danger',
                        'effectue'   => 'info',
                    ];
                    $statut = $rdv['statut'];
                    $label  = ucfirst(str_replace('_', ' ', $statut));
                    $isPast = strtotime($rdv['date_rdv']) < strtotime(date('Y-m-d'));
                ?>
                <div class="col-md-6">
                    <div class="card h-100 border-start border-4 border-<?= $badgeClass[$statut] ?? 'secondary' ?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle bg-primary text-white d-flex
                                                align-items-center justify-content-center me-3"
                                         style="width:42px;height:42px;font-size:1.2rem; flex-shrink:0;">
                                        <i class="bi bi-person-badge"></i>
                                    </div>
                                    <div>
                                        <p class="mb-0 fw-bold">Dr. <?= htmlspecialchars($rdv['medecin_nom']) ?></p>
                                        <small class="text-muted"><?= htmlspecialchars($rdv['specialite']) ?></small>
                                    </div>
                                </div>
                                <span class="badge bg-<?= $badgeClass[$statut] ?? 'secondary' ?>"><?= $label ?></span>
                            </div>

                            <div class="row g-2 mt-1">
                                <div class="col-6">
                                    <small class="text-muted d-block">
                                        <i class="bi bi-calendar3 me-1"></i>
                                        <?= date('d/m/Y', strtotime($rdv['date_rdv'])) ?>
                                    </small>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">
                                        <i class="bi bi-clock me-1"></i>
                                        <?= date('H:i', strtotime($rdv['heure_rdv'])) ?>
                                    </small>
                                </div>
                            </div>

                            <?php if (!empty($rdv['motif'])): ?>
                                <p class="text-muted small mt-2 mb-0">
                                    <i class="bi bi-chat-text me-1"></i>
                                    <?= htmlspecialchars($rdv['motif']) ?>
                                </p>
                            <?php endif; ?>

                            <!-- Bouton annuler -->
                            <?php if (in_array($statut, ['en_attente','confirme']) && !$isPast): ?>
                                <div class="mt-3">
                                    <a href="?annuler=<?= $rdv['id'] ?>"
                                       class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('Confirmer l\'annulation de ce rendez-vous ?')">
                                        <i class="bi bi-x-circle me-1"></i>Annuler
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center text-muted py-5">
                <i class="bi bi-calendar-x" style="font-size:4rem;opacity:0.2;"></i>
                <p class="mt-3 fs-5">Aucun rendez-vous trouvé</p>
                <a href="rendez_vous.php" class="btn btn-primary">
                    <i class="bi bi-calendar-plus me-2"></i>Prendre un rendez-vous
                </a>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

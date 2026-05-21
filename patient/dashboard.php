<?php
// ============================================================
//  Tableau de bord Patient
//  Fichier : patient/dashboard.php
// ============================================================
$pageTitle = "Mon Tableau de Bord";
require_once '../includes/fonctions.php';
require_once '../includes/connexion.php';
requireRole('patient');

// Récupérer l'ID du patient
$stmt = $pdo->prepare("SELECT id FROM patients WHERE utilisateur_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$patient = $stmt->fetch();
$patient_id = $patient['id'];

// Prochain rendez-vous
$stmt = $pdo->prepare("
    SELECT r.*, CONCAT(u.prenom, ' ', u.nom) AS medecin_nom, m.specialite
    FROM rendez_vous r
    JOIN medecins me ON r.medecin_id = me.id
    JOIN utilisateurs u ON me.utilisateur_id = u.id
    JOIN medecins m ON r.medecin_id = m.id
    WHERE r.patient_id = ? AND r.date_rdv >= CURDATE()
      AND r.statut IN ('en_attente','confirme')
    ORDER BY r.date_rdv ASC, r.heure_rdv ASC
    LIMIT 1
");
$stmt->execute([$patient_id]);
$prochain_rdv = $stmt->fetch();

// Nombre total de rendez-vous
$stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM rendez_vous WHERE patient_id = ?");
$stmt->execute([$patient_id]);
$total_rdv = $stmt->fetch()['total'];

// Nombre de consultations effectuées
$stmt = $pdo->prepare("
    SELECT COUNT(*) AS total FROM rendez_vous
    WHERE patient_id = ? AND statut = 'effectue'
");
$stmt->execute([$patient_id]);
$total_consultations = $stmt->fetch()['total'];

// Notifications non lues
$stmt = $pdo->prepare("
    SELECT COUNT(*) AS total FROM notifications
    WHERE utilisateur_id = ? AND statut = 'non_lu'
");
$stmt->execute([$_SESSION['user_id']]);
$notifs_non_lues = $stmt->fetch()['total'];

// Derniers rendez-vous (5 derniers)
$stmt = $pdo->prepare("
    SELECT r.*, CONCAT(u.prenom, ' ', u.nom) AS medecin_nom, me.specialite
    FROM rendez_vous r
    JOIN medecins me ON r.medecin_id = me.id
    JOIN utilisateurs u ON me.utilisateur_id = u.id
    WHERE r.patient_id = ?
    ORDER BY r.date_rdv DESC, r.heure_rdv DESC
    LIMIT 5
");
$stmt->execute([$patient_id]);
$derniers_rdv = $stmt->fetchAll();

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
                <a href="dashboard.php" class="nav-link active">
                    <i class="bi bi-speedometer2"></i> Tableau de bord
                </a>
                <a href="rendez_vous.php" class="nav-link">
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
                    <?php if ($notifs_non_lues > 0): ?>
                        <span class="badge bg-danger ms-1"><?= $notifs_non_lues ?></span>
                    <?php endif; ?>
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

    <!-- CONTENU PRINCIPAL -->
    <div class="col-md-9 col-lg-10 py-3 px-4">

        <?php afficherFlash(); ?>

        <!-- Titre -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold text-primary mb-0">
                    <i class="bi bi-speedometer2 me-2"></i>Tableau de Bord
                </h4>
                <small class="text-muted">
                    Bonjour, <?= htmlspecialchars($_SESSION['prenom']) ?> ! 
                    <?= date('l d F Y') ?>
                </small>
            </div>
            <a href="rendez_vous.php" class="btn btn-primary">
                <i class="bi bi-calendar-plus me-2"></i>Nouveau RDV
            </a>
        </div>

        <!-- CARTES STATISTIQUES -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card stat-card h-100" style="background: linear-gradient(135deg,#1A5276,#2E86C1);">
                    <div class="stat-icon"><i class="bi bi-calendar-check"></i></div>
                    <div class="stat-number"><?= $total_rdv ?></div>
                    <div>Total Rendez-vous</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card h-100" style="background: linear-gradient(135deg,#1E8449,#27AE60);">
                    <div class="stat-icon"><i class="bi bi-clipboard2-pulse"></i></div>
                    <div class="stat-number"><?= $total_consultations ?></div>
                    <div>Consultations Effectuées</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card h-100" style="background: linear-gradient(135deg,#7D3C98,#A569BD);">
                    <div class="stat-icon"><i class="bi bi-bell"></i></div>
                    <div class="stat-number"><?= $notifs_non_lues ?></div>
                    <div>Notifications non lues</div>
                </div>
            </div>
        </div>

        <!-- PROCHAIN RENDEZ-VOUS -->
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header fw-bold text-primary bg-white border-bottom">
                        <i class="bi bi-calendar-event me-2"></i>Prochain Rendez-vous
                    </div>
                    <div class="card-body">
                        <?php if ($prochain_rdv): ?>
                            <div class="d-flex align-items-center mb-3">
                                <div class="rounded-circle bg-primary text-white d-flex align-items-center
                                            justify-content-center me-3"
                                     style="width:50px;height:50px;font-size:1.5rem;">
                                    <i class="bi bi-person-badge"></i>
                                </div>
                                <div>
                                    <p class="mb-0 fw-bold">Dr. <?= htmlspecialchars($prochain_rdv['medecin_nom']) ?></p>
                                    <small class="text-muted"><?= htmlspecialchars($prochain_rdv['specialite']) ?></small>
                                </div>
                            </div>
                            <p class="mb-1">
                                <i class="bi bi-calendar3 text-primary me-2"></i>
                                <strong><?= date('d/m/Y', strtotime($prochain_rdv['date_rdv'])) ?></strong>
                            </p>
                            <p class="mb-1">
                                <i class="bi bi-clock text-primary me-2"></i>
                                <strong><?= date('H:i', strtotime($prochain_rdv['heure_rdv'])) ?></strong>
                            </p>
                            <p class="mb-2">
                                <i class="bi bi-tag text-primary me-2"></i>
                                <?php
                                $badgeClass = [
                                    'en_attente' => 'warning',
                                    'confirme'   => 'success',
                                    'annule'     => 'danger',
                                    'effectue'   => 'info',
                                ];
                                $statut = $prochain_rdv['statut'];
                                $label  = ucfirst(str_replace('_', ' ', $statut));
                                ?>
                                <span class="badge bg-<?= $badgeClass[$statut] ?? 'secondary' ?>">
                                    <?= $label ?>
                                </span>
                            </p>
                            <?php if ($prochain_rdv['motif']): ?>
                                <p class="text-muted small mb-0">
                                    <i class="bi bi-chat-text me-1"></i>
                                    <?= htmlspecialchars($prochain_rdv['motif']) ?>
                                </p>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="text-center text-muted py-4">
                                <i class="bi bi-calendar-x" style="font-size:3rem;opacity:0.3;"></i>
                                <p class="mt-2">Aucun rendez-vous à venir</p>
                                <a href="rendez_vous.php" class="btn btn-sm btn-primary">
                                    Prendre un RDV
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- ACCÈS RAPIDE -->
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header fw-bold text-primary bg-white border-bottom">
                        <i class="bi bi-lightning me-2"></i>Accès Rapide
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-6">
                                <a href="rendez_vous.php" class="text-decoration-none">
                                    <div class="card text-center p-3 border h-100"
                                         style="border-radius:12px; background:#EAF2F8;">
                                        <i class="bi bi-calendar-plus text-primary" style="font-size:2rem;"></i>
                                        <small class="fw-semibold text-dark mt-2 d-block">Nouveau RDV</small>
                                    </div>
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="mes_rdv.php" class="text-decoration-none">
                                    <div class="card text-center p-3 border h-100"
                                         style="border-radius:12px; background:#E9F7EF;">
                                        <i class="bi bi-calendar-check text-success" style="font-size:2rem;"></i>
                                        <small class="fw-semibold text-dark mt-2 d-block">Mes RDV</small>
                                    </div>
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="historique.php" class="text-decoration-none">
                                    <div class="card text-center p-3 border h-100"
                                         style="border-radius:12px; background:#F4ECF7;">
                                        <i class="bi bi-folder2-open text-purple" style="font-size:2rem; color:#7D3C98;"></i>
                                        <small class="fw-semibold text-dark mt-2 d-block">Dossier Médical</small>
                                    </div>
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="profil.php" class="text-decoration-none">
                                    <div class="card text-center p-3 border h-100"
                                         style="border-radius:12px; background:#FEF9E7;">
                                        <i class="bi bi-person-gear text-warning" style="font-size:2rem;"></i>
                                        <small class="fw-semibold text-dark mt-2 d-block">Mon Profil</small>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- DERNIERS RENDEZ-VOUS -->
        <div class="card">
            <div class="card-header fw-bold text-primary bg-white border-bottom d-flex justify-content-between align-items-center">
                <span><i class="bi bi-clock-history me-2"></i>Derniers Rendez-vous</span>
                <a href="mes_rdv.php" class="btn btn-sm btn-outline-primary">Voir tout</a>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($derniers_rdv)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Heure</th>
                                    <th>Médecin</th>
                                    <th>Spécialité</th>
                                    <th>Motif</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($derniers_rdv as $rdv): ?>
                                    <?php
                                    $badgeClass = [
                                        'en_attente' => 'warning',
                                        'confirme'   => 'success',
                                        'annule'     => 'danger',
                                        'effectue'   => 'info',
                                    ];
                                    $statut = $rdv['statut'];
                                    $label  = ucfirst(str_replace('_', ' ', $statut));
                                    ?>
                                    <tr>
                                        <td><?= date('d/m/Y', strtotime($rdv['date_rdv'])) ?></td>
                                        <td><?= date('H:i', strtotime($rdv['heure_rdv'])) ?></td>
                                        <td>Dr. <?= htmlspecialchars($rdv['medecin_nom']) ?></td>
                                        <td><?= htmlspecialchars($rdv['specialite']) ?></td>
                                        <td class="text-muted small"><?= htmlspecialchars($rdv['motif'] ?? '—') ?></td>
                                        <td>
                                            <span class="badge bg-<?= $badgeClass[$statut] ?? 'secondary' ?>">
                                                <?= $label ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-calendar-x" style="font-size:3rem;opacity:0.3;"></i>
                        <p class="mt-2">Aucun rendez-vous pour le moment</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div><!-- fin col -->
</div><!-- fin row -->

<?php require_once '../includes/footer.php'; ?>

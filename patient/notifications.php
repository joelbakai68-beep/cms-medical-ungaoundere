<?php
// ============================================================
//  Notifications
//  Fichier : patient/notifications.php
// ============================================================
$pageTitle = "Mes Notifications";
require_once '../includes/fonctions.php';
require_once '../includes/connexion.php';
requireRole('patient');

// Marquer toutes comme lues
if (isset($_GET['tout_lire'])) {
    $stmt = $pdo->prepare("UPDATE notifications SET statut = 'lu' WHERE utilisateur_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    header("Location: notifications.php");
    exit();
}

// Marquer une notification comme lue
if (isset($_GET['lire']) && is_numeric($_GET['lire'])) {
    $stmt = $pdo->prepare("UPDATE notifications SET statut = 'lu' WHERE id = ? AND utilisateur_id = ?");
    $stmt->execute([(int)$_GET['lire'], $_SESSION['user_id']]);
    header("Location: notifications.php");
    exit();
}

// Récupérer toutes les notifications
$stmt = $pdo->prepare("
    SELECT * FROM notifications
    WHERE utilisateur_id = ?
    ORDER BY date_envoi DESC
");
$stmt->execute([$_SESSION['user_id']]);
$notifications = $stmt->fetchAll();

$non_lues = array_filter($notifications, function($n) { return $n['statut'] === 'non_lu'; });

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
                <a href="mes_rdv.php" class="nav-link"><i class="bi bi-calendar-check"></i> Mes Rendez-vous</a>
                <a href="historique.php" class="nav-link"><i class="bi bi-folder2-open"></i> Dossier Médical</a>
                <a href="notifications.php" class="nav-link active">
                    <i class="bi bi-bell"></i> Notifications
                    <?php if (count($non_lues) > 0): ?>
                        <span class="badge bg-danger ms-1"><?= count($non_lues) ?></span>
                    <?php endif; ?>
                </a>
                <a href="profil.php" class="nav-link"><i class="bi bi-person-gear"></i> Mon Profil</a>
                <hr class="border-secondary mx-3">
                <a href="../auth/logout.php" class="nav-link text-danger"><i class="bi bi-box-arrow-right"></i> Déconnexion</a>
            </nav>
        </div>
    </div>

    <!-- CONTENU -->
    <div class="col-md-9 col-lg-10 py-3 px-4">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold text-primary mb-0">
                    <i class="bi bi-bell me-2"></i>Mes Notifications
                </h4>
                <small class="text-muted">
                    <?= count($non_lues) ?> non lue(s) — <?= count($notifications) ?> au total
                </small>
            </div>
            <?php if (count($non_lues) > 0): ?>
                <a href="?tout_lire=1" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-check-all me-1"></i>Tout marquer comme lu
                </a>
            <?php endif; ?>
        </div>

        <?php if (!empty($notifications)): ?>
            <div class="list-group">
                <?php
                $icones = [
                    'rappel_rdv'      => ['icon' => 'bi-calendar-event',  'color' => 'text-primary'],
                    'resultat_examen' => ['icon' => 'bi-file-medical',    'color' => 'text-warning'],
                    'annulation'      => ['icon' => 'bi-x-circle',        'color' => 'text-danger'],
                    'info'            => ['icon' => 'bi-info-circle',     'color' => 'text-info'],
                ];
                foreach ($notifications as $notif):
                    $ic    = $icones[$notif['type']] ?? ['icon' => 'bi-bell', 'color' => 'text-secondary'];
                    $nonLu = $notif['statut'] === 'non_lu';
                ?>
                <div class="list-group-item list-group-item-action d-flex align-items-start gap-3 py-3
                            <?= $nonLu ? 'bg-light border-start border-primary border-3' : '' ?>">
                    <div class="mt-1">
                        <i class="bi <?= $ic['icon'] ?> <?= $ic['color'] ?> fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <p class="mb-1 <?= $nonLu ? 'fw-bold' : 'text-muted' ?>">
                            <?= htmlspecialchars($notif['message']) ?>
                        </p>
                        <small class="text-muted">
                            <i class="bi bi-clock me-1"></i>
                            <?= date('d/m/Y à H:i', strtotime($notif['date_envoi'])) ?>
                        </small>
                    </div>
                    <?php if ($nonLu): ?>
                    <div>
                        <a href="?lire=<?= $notif['id'] ?>" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-check2"></i>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center text-muted py-5">
                <i class="bi bi-bell-slash" style="font-size:4rem;opacity:0.2;"></i>
                <p class="mt-3">Aucune notification pour le moment</p>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

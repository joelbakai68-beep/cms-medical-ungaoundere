<?php
// ============================================================
//  Tableau de bord Administrateur
//  Fichier : admin/dashboard.php
// ============================================================
$pageTitle = "Tableau de Bord Admin";
require_once '../includes/fonctions.php';
require_once '../includes/connexion.php';
requireRole('admin');

// Statistiques globales
$stats = [];

$stmt = $pdo->query("SELECT COUNT(*) AS total FROM utilisateurs WHERE role = 'patient'");
$stats['patients'] = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) AS total FROM utilisateurs WHERE role = 'medecin'");
$stats['medecins'] = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) AS total FROM rendez_vous");
$stats['rdv_total'] = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) AS total FROM rendez_vous WHERE statut = 'effectue'");
$stats['consultations'] = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) AS total FROM rendez_vous WHERE date_rdv = CURDATE()");
$stats['rdv_aujourd_hui'] = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) AS total FROM rendez_vous WHERE statut = 'en_attente'");
$stats['en_attente'] = $stmt->fetch()['total'];

// RDV par statut (pour graphique)
$stmt = $pdo->query("
    SELECT statut, COUNT(*) AS total
    FROM rendez_vous
    GROUP BY statut
");
$rdv_par_statut = $stmt->fetchAll();

// RDV des 7 derniers jours
$stmt = $pdo->query("
    SELECT DATE(date_rdv) AS jour, COUNT(*) AS total
    FROM rendez_vous
    WHERE date_rdv >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(date_rdv)
    ORDER BY jour ASC
");
$rdv_semaine = $stmt->fetchAll();

// Derniers RDV enregistrés
$stmt = $pdo->query("
    SELECT r.*, 
           CONCAT(up.prenom, ' ', up.nom) AS patient_nom,
           CONCAT(um.prenom, ' ', um.nom) AS medecin_nom,
           me.specialite
    FROM rendez_vous r
    JOIN patients p ON r.patient_id = p.id
    JOIN utilisateurs up ON p.utilisateur_id = up.id
    JOIN medecins me ON r.medecin_id = me.id
    JOIN utilisateurs um ON me.utilisateur_id = um.id
    ORDER BY r.date_creation DESC
    LIMIT 8
");
$derniers_rdv = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="row">
    <!-- SIDEBAR -->
    <div class="col-md-3 col-lg-2 px-0">
        <div class="sidebar d-flex flex-column" style="min-height:85vh;">
            <div class="text-center text-white py-4 px-3 border-bottom border-secondary">
                <i class="bi bi-shield-check" style="font-size:3rem;"></i>
                <p class="mb-0 fw-bold mt-2"><?= htmlspecialchars($_SESSION['prenom'].' '.$_SESSION['nom']) ?></p>
                <small class="opacity-75">Administrateur</small>
            </div>
            <nav class="nav flex-column mt-3">
                <a href="dashboard.php" class="nav-link active">
                    <i class="bi bi-speedometer2"></i> Tableau de bord
                </a>
                <a href="utilisateurs.php" class="nav-link">
                    <i class="bi bi-people"></i> Utilisateurs
                </a>
                <a href="medecins.php" class="nav-link">
                    <i class="bi bi-person-badge"></i> Médecins
                </a>
                <a href="rendez_vous.php" class="nav-link">
                    <i class="bi bi-calendar-week"></i> Rendez-vous
                </a>
                <a href="rapports.php" class="nav-link">
                    <i class="bi bi-bar-chart"></i> Rapports
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
                    <i class="bi bi-speedometer2 me-2"></i>Tableau de Bord
                </h4>
                <small class="text-muted">
                    Bonjour <?= htmlspecialchars($_SESSION['prenom']) ?> !
                    <?= date('d F Y') ?>
                </small>
            </div>
        </div>

        <!-- STATISTIQUES -->
        <div class="row g-3 mb-4">
            <div class="col-md-2 col-6">
                <div class="card stat-card" style="background:linear-gradient(135deg,#1A5276,#2E86C1);">
                    <div class="stat-icon"><i class="bi bi-people"></i></div>
                    <div class="stat-number"><?= $stats['patients'] ?></div>
                    <div>Patients</div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="card stat-card" style="background:linear-gradient(135deg,#117A65,#1ABC9C);">
                    <div class="stat-icon"><i class="bi bi-person-badge"></i></div>
                    <div class="stat-number"><?= $stats['medecins'] ?></div>
                    <div>Médecins</div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="card stat-card" style="background:linear-gradient(135deg,#6C3483,#A569BD);">
                    <div class="stat-icon"><i class="bi bi-calendar-check"></i></div>
                    <div class="stat-number"><?= $stats['rdv_total'] ?></div>
                    <div>Total RDV</div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="card stat-card" style="background:linear-gradient(135deg,#1E8449,#27AE60);">
                    <div class="stat-icon"><i class="bi bi-clipboard2-check"></i></div>
                    <div class="stat-number"><?= $stats['consultations'] ?></div>
                    <div>Consultations</div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="card stat-card" style="background:linear-gradient(135deg,#B7950B,#F39C12);">
                    <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
                    <div class="stat-number"><?= $stats['en_attente'] ?></div>
                    <div>En attente</div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="card stat-card" style="background:linear-gradient(135deg,#922B21,#E74C3C);">
                    <div class="stat-icon"><i class="bi bi-calendar-day"></i></div>
                    <div class="stat-number"><?= $stats['rdv_aujourd_hui'] ?></div>
                    <div>Aujourd'hui</div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <!-- Graphique RDV par statut -->
            <div class="col-md-5">
                <div class="card h-100">
                    <div class="card-header bg-white fw-bold text-primary border-bottom">
                        <i class="bi bi-pie-chart me-2"></i>RDV par Statut
                    </div>
                    <div class="card-body d-flex align-items-center justify-content-center">
                        <canvas id="chartStatut" height="220"></canvas>
                    </div>
                </div>
            </div>

            <!-- Graphique RDV semaine -->
            <div class="col-md-7">
                <div class="card h-100">
                    <div class="card-header bg-white fw-bold text-primary border-bottom">
                        <i class="bi bi-bar-chart me-2"></i>RDV des 7 derniers jours
                    </div>
                    <div class="card-body">
                        <canvas id="chartSemaine" height="180"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Derniers RDV -->
        <div class="card">
            <div class="card-header bg-white fw-bold text-primary border-bottom d-flex justify-content-between align-items-center">
                <span><i class="bi bi-clock-history me-2"></i>Derniers Rendez-vous enregistrés</span>
                <a href="rendez_vous.php" class="btn btn-sm btn-outline-primary">Voir tout</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Patient</th>
                                <th>Médecin</th>
                                <th>Spécialité</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($derniers_rdv as $rdv):
                                $badgeClass = [
                                    'en_attente' => 'warning',
                                    'confirme'   => 'success',
                                    'annule'     => 'danger',
                                    'effectue'   => 'info',
                                ];
                            ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($rdv['date_rdv'])) ?></td>
                                <td><?= htmlspecialchars($rdv['patient_nom']) ?></td>
                                <td>Dr. <?= htmlspecialchars($rdv['medecin_nom']) ?></td>
                                <td class="text-muted small"><?= htmlspecialchars($rdv['specialite']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $badgeClass[$rdv['statut']] ?? 'secondary' ?>">
                                        <?= ucfirst(str_replace('_', ' ', $rdv['statut'])) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Graphique Donut — RDV par statut
const statutData = <?= json_encode($rdv_par_statut) ?>;
const statutLabels = statutData.map(d => d.statut.replace('_', ' '));
const statutValues = statutData.map(d => parseInt(d.total));
const statutColors = ['#F39C12','#27AE60','#E74C3C','#2E86C1'];

new Chart(document.getElementById('chartStatut'), {
    type: 'doughnut',
    data: {
        labels: statutLabels,
        datasets: [{ data: statutValues, backgroundColor: statutColors, borderWidth: 2 }]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } }
    }
});

// Graphique Barres — RDV semaine
const semaineData = <?= json_encode($rdv_semaine) ?>;
const semaineLabels = semaineData.map(d => {
    const date = new Date(d.jour);
    return date.toLocaleDateString('fr-FR', { weekday: 'short', day: 'numeric' });
});
const semaineValues = semaineData.map(d => parseInt(d.total));

new Chart(document.getElementById('chartSemaine'), {
    type: 'bar',
    data: {
        labels: semaineLabels,
        datasets: [{
            label: 'Rendez-vous',
            data: semaineValues,
            backgroundColor: '#2E86C1',
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>

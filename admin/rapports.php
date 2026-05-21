<?php
// ============================================================
//  Rapports & Statistiques
//  Fichier : admin/rapports.php
// ============================================================
$pageTitle = "Rapports & Statistiques";
require_once '../includes/fonctions.php';
require_once '../includes/connexion.php';
requireRole('admin');

// Période filtre
$periode = clean($_GET['periode'] ?? '30');

// Statistiques par médecin
$stmt = $pdo->prepare("
    SELECT CONCAT(u.prenom, ' ', u.nom) AS medecin_nom, me.specialite,
           COUNT(r.id) AS total_rdv,
           SUM(CASE WHEN r.statut = 'effectue' THEN 1 ELSE 0 END) AS consultations,
           SUM(CASE WHEN r.statut = 'annule' THEN 1 ELSE 0 END) AS annulations
    FROM medecins me
    JOIN utilisateurs u ON me.utilisateur_id = u.id
    LEFT JOIN rendez_vous r ON r.medecin_id = me.id
        AND r.date_rdv >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
    GROUP BY me.id
    ORDER BY consultations DESC
");
$stmt->execute([$periode]);
$stats_medecins = $stmt->fetchAll();

// Évolution mensuelle des RDV (12 derniers mois)
$stmt = $pdo->query("
    SELECT DATE_FORMAT(date_rdv, '%Y-%m') AS mois,
           COUNT(*) AS total,
           SUM(CASE WHEN statut = 'effectue' THEN 1 ELSE 0 END) AS effectues
    FROM rendez_vous
    WHERE date_rdv >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(date_rdv, '%Y-%m')
    ORDER BY mois ASC
");
$evolution = $stmt->fetchAll();

// Top motifs de consultation
$stmt = $pdo->query("
    SELECT motif, COUNT(*) AS total
    FROM rendez_vous
    WHERE motif IS NOT NULL AND motif != ''
    GROUP BY motif
    ORDER BY total DESC
    LIMIT 5
");
$top_motifs = $stmt->fetchAll();

// Taux global
$stmt = $pdo->query("SELECT COUNT(*) AS total FROM rendez_vous");
$total_rdv = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) AS total FROM rendez_vous WHERE statut = 'effectue'");
$total_effectues = $stmt->fetch()['total'];

$taux = $total_rdv > 0 ? round(($total_effectues / $total_rdv) * 100) : 0;

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
                <a href="dashboard.php" class="nav-link"><i class="bi bi-speedometer2"></i> Tableau de bord</a>
                <a href="utilisateurs.php" class="nav-link"><i class="bi bi-people"></i> Utilisateurs</a>
                <a href="medecins.php" class="nav-link"><i class="bi bi-person-badge"></i> Médecins</a>
                <a href="rendez_vous.php" class="nav-link"><i class="bi bi-calendar-week"></i> Rendez-vous</a>
                <a href="rapports.php" class="nav-link active"><i class="bi bi-bar-chart"></i> Rapports</a>
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
                    <i class="bi bi-bar-chart me-2"></i>Rapports & Statistiques
                </h4>
                <small class="text-muted">Analyse de l'activité du CMS</small>
            </div>
            <!-- Filtre période -->
            <div class="d-flex gap-2">
                <?php foreach (['7' => '7j', '30' => '30j', '90' => '3 mois', '365' => '1 an'] as $val => $label): ?>
                    <a href="?periode=<?= $val ?>"
                       class="btn btn-sm <?= $periode == $val ? 'btn-primary' : 'btn-outline-secondary' ?>">
                        <?= $label ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Taux de consultation -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-4 text-center">
                        <h2 class="fw-bold text-primary display-4"><?= $taux ?>%</h2>
                        <p class="text-muted mb-0">Taux de consultation global</p>
                        <small class="text-muted"><?= $total_effectues ?> consultations / <?= $total_rdv ?> RDV</small>
                    </div>
                    <div class="col-md-8">
                        <div class="progress" style="height:20px;border-radius:10px;">
                            <div class="progress-bar bg-primary" style="width:<?= $taux ?>%">
                                <?= $taux ?>%
                            </div>
                        </div>
                        <div class="d-flex justify-content-between mt-2 small text-muted">
                            <span>0%</span>
                            <span>Objectif : 80%</span>
                            <span>100%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <!-- Évolution mensuelle -->
            <div class="col-md-8">
                <div class="card h-100">
                    <div class="card-header bg-white fw-bold text-primary border-bottom">
                        <i class="bi bi-graph-up me-2"></i>Évolution mensuelle des RDV (12 mois)
                    </div>
                    <div class="card-body">
                        <canvas id="chartEvolution" height="200"></canvas>
                    </div>
                </div>
            </div>

            <!-- Top motifs -->
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-header bg-white fw-bold text-primary border-bottom">
                        <i class="bi bi-list-ol me-2"></i>Top Motifs de Consultation
                    </div>
                    <div class="card-body">
                        <?php if (!empty($top_motifs)): ?>
                            <?php foreach ($top_motifs as $i => $motif): ?>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-primary"><?= $i + 1 ?></span>
                                    <small><?= htmlspecialchars(mb_strimwidth($motif['motif'], 0, 30, '...')) ?></small>
                                </div>
                                <span class="badge bg-light text-dark border"><?= $motif['total'] ?></span>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted text-center py-3">Aucune donnée disponible</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistiques par médecin -->
        <div class="card">
            <div class="card-header bg-white fw-bold text-primary border-bottom">
                <i class="bi bi-person-badge me-2"></i>
                Performance par Médecin
                <small class="text-muted fw-normal ms-2">(<?= $periode ?> derniers jours)</small>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Médecin</th>
                                <th>Spécialité</th>
                                <th class="text-center">Total RDV</th>
                                <th class="text-center">Consultations</th>
                                <th class="text-center">Annulations</th>
                                <th>Taux</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats_medecins as $s):
                                $taux_med = $s['total_rdv'] > 0
                                    ? round(($s['consultations'] / $s['total_rdv']) * 100)
                                    : 0;
                                $couleur = $taux_med >= 70 ? 'success' : ($taux_med >= 40 ? 'warning' : 'danger');
                            ?>
                            <tr>
                                <td class="fw-semibold">Dr. <?= htmlspecialchars($s['medecin_nom']) ?></td>
                                <td class="text-muted small"><?= htmlspecialchars($s['specialite']) ?></td>
                                <td class="text-center"><?= $s['total_rdv'] ?></td>
                                <td class="text-center">
                                    <span class="badge bg-success"><?= $s['consultations'] ?></span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-danger"><?= $s['annulations'] ?></span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height:8px;">
                                            <div class="progress-bar bg-<?= $couleur ?>"
                                                 style="width:<?= $taux_med ?>%"></div>
                                        </div>
                                        <small class="text-muted"><?= $taux_med ?>%</small>
                                    </div>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const evolutionData = <?= json_encode($evolution) ?>;
const labels = evolutionData.map(d => {
    const [year, month] = d.mois.split('-');
    return new Date(year, month - 1).toLocaleDateString('fr-FR', { month: 'short', year: '2-digit' });
});

new Chart(document.getElementById('chartEvolution'), {
    type: 'line',
    data: {
        labels: labels,
        datasets: [
            {
                label: 'Total RDV',
                data: evolutionData.map(d => parseInt(d.total)),
                borderColor: '#2E86C1',
                backgroundColor: 'rgba(46,134,193,0.1)',
                fill: true,
                tension: 0.4
            },
            {
                label: 'Consultations effectuées',
                data: evolutionData.map(d => parseInt(d.effectues)),
                borderColor: '#27AE60',
                backgroundColor: 'rgba(39,174,96,0.1)',
                fill: true,
                tension: 0.4
            }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top' } },
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>

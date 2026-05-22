<?php
// ============================================================
//  Supervision des Rendez-vous
//  Fichier : admin/rendez_vous.php
// ============================================================
$pageTitle = "Supervision des Rendez-vous";
require_once '../includes/fonctions.php';
require_once '../includes/connexion.php';
requireRole('admin');

$filtre    = clean($_GET['statut'] ?? 'tous');
$recherche = clean($_GET['q'] ?? '');

$where  = "WHERE 1=1";
$params = [];

if ($filtre !== 'tous') {
    $where   .= " AND r.statut = ?";
    $params[] = $filtre;
}
if (!empty($recherche)) {
    $where   .= " AND (up.nom LIKE ? OR up.prenom LIKE ? OR um.nom LIKE ?)";
    $params[] = "%$recherche%";
    $params[] = "%$recherche%";
    $params[] = "%$recherche%";
}

$stmt = $pdo->prepare("
    SELECT r.*,
           CONCAT(up.prenom, ' ', up.nom) AS patient_nom,
           CONCAT(um.prenom, ' ', um.nom) AS medecin_nom,
           me.specialite
    FROM rendez_vous r
    JOIN patients p ON r.patient_id = p.id
    JOIN utilisateurs up ON p.utilisateur_id = up.id
    JOIN medecins me ON r.medecin_id = me.id
    JOIN utilisateurs um ON me.utilisateur_id = um.id
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
                <i class="bi bi-shield-check" style="font-size:3rem;"></i>
                <p class="mb-0 fw-bold mt-2"><?= htmlspecialchars($_SESSION['prenom'].' '.$_SESSION['nom']) ?></p>
                <small class="opacity-75">Administrateur</small>
            </div>
            <nav class="nav flex-column mt-3">
                <a href="dashboard.php" class="nav-link"><i class="bi bi-speedometer2"></i> Tableau de bord</a>
                <a href="utilisateurs.php" class="nav-link"><i class="bi bi-people"></i> Utilisateurs</a>
                <a href="medecins.php" class="nav-link"><i class="bi bi-person-badge"></i> Médecins</a>
                <a href="rendez_vous.php" class="nav-link active"><i class="bi bi-calendar-week"></i> Rendez-vous</a>
                <a href="rapports.php" class="nav-link"><i class="bi bi-bar-chart"></i> Rapports</a>
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
                    <i class="bi bi-calendar-week me-2"></i>Supervision des Rendez-vous
                </h4>
                <small class="text-muted"><?= count($rdvs) ?> rendez-vous trouvé(s)</small>
            </div>
        </div>

        <!-- Filtres -->
        <div class="card mb-4">
            <div class="card-body py-2">
                <form method="GET" class="row g-2 align-items-center">
                    <div class="col-md-4">
                        <div class="input-group">
                            <input type="text" class="form-control" name="q"
                                   placeholder="Rechercher patient ou médecin..."
                                   value="<?= htmlspecialchars($recherche) ?>">
                            <button class="btn btn-primary" type="submit">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex gap-2 flex-wrap">
                            <?php
                            $filtres = [
                                'tous'       => ['label' => 'Tous',       'class' => 'secondary'],
                                'en_attente' => ['label' => 'En attente', 'class' => 'warning'],
                                'confirme'   => ['label' => 'Confirmés',  'class' => 'success'],
                                'effectue'   => ['label' => 'Effectués',  'class' => 'info'],
                                'annule'     => ['label' => 'Annulés',    'class' => 'danger'],
                            ];
                            foreach ($filtres as $val => $opt):
                                $active = ($filtre === $val) ? 'btn-'.$opt['class'] : 'btn-outline-'.$opt['class'];
                            ?>
                                <a href="?statut=<?= $val ?>&q=<?= urlencode($recherche) ?>"
                                   class="btn btn-sm <?= $active ?>"><?= $opt['label'] ?></a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tableau -->
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Heure</th>
                                <th>Patient</th>
                                <th>Médecin</th>
                                <th>Spécialité</th>
                                <th>Motif</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($rdvs)):
                                foreach ($rdvs as $rdv):
                                    $badgeClass = [
                                        'en_attente' => 'warning',
                                        'confirme'   => 'success',
                                        'annule'     => 'danger',
                                        'effectue'   => 'info',
                                    ];
                            ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($rdv['date_rdv'])) ?></td>
                                <td><?= date('H:i', strtotime($rdv['heure_rdv'])) ?></td>
                                <td class="fw-semibold"><?= htmlspecialchars($rdv['patient_nom']) ?></td>
                                <td>Dr. <?= htmlspecialchars($rdv['medecin_nom']) ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($rdv['specialite']) ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($rdv['motif'] ?? '—') ?></td>
                                <td>
                                    <span class="badge bg-<?= $badgeClass[$rdv['statut']] ?? 'secondary' ?>">
                                        <?= ucfirst(str_replace('_', ' ', $rdv['statut'])) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach;
                            else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    Aucun rendez-vous trouvé
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

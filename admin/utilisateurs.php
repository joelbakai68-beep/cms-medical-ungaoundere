<?php
// ============================================================
//  Gestion des Utilisateurs
//  Fichier : admin/utilisateurs.php
// ============================================================
$pageTitle = "Gestion des Utilisateurs";
require_once '../includes/fonctions.php';
require_once '../includes/connexion.php';
requireRole('admin');

// Activer / Désactiver un utilisateur
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $uid  = (int)$_GET['toggle'];
    $stmt = $pdo->prepare("SELECT statut FROM utilisateurs WHERE id = ?");
    $stmt->execute([$uid]);
    $u = $stmt->fetch();
    if ($u) {
        $nouveau = $u['statut'] === 'actif' ? 'inactif' : 'actif';
        $stmt = $pdo->prepare("UPDATE utilisateurs SET statut = ? WHERE id = ?");
        $stmt->execute([$nouveau, $uid]);
        setFlash('success', "Compte " . ($nouveau === 'actif' ? 'activé' : 'désactivé') . " avec succès.");
    }
    header("Location: utilisateurs.php");
    exit();
}

// Filtre et recherche
$role      = clean($_GET['role'] ?? 'tous');
$recherche = clean($_GET['q'] ?? '');

$where  = "WHERE 1=1";
$params = [];

if ($role !== 'tous') {
    $where   .= " AND role = ?";
    $params[] = $role;
}
if (!empty($recherche)) {
    $where   .= " AND (nom LIKE ? OR prenom LIKE ? OR email LIKE ?)";
    $params[] = "%$recherche%";
    $params[] = "%$recherche%";
    $params[] = "%$recherche%";
}

$stmt = $pdo->prepare("SELECT * FROM utilisateurs $where ORDER BY date_creation DESC");
$stmt->execute($params);
$utilisateurs = $stmt->fetchAll();

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
                <a href="utilisateurs.php" class="nav-link active"><i class="bi bi-people"></i> Utilisateurs</a>
                <a href="medecins.php" class="nav-link"><i class="bi bi-person-badge"></i> Médecins</a>
                <a href="rendez_vous.php" class="nav-link"><i class="bi bi-calendar-week"></i> Rendez-vous</a>
                <a href="rapports.php" class="nav-link"><i class="bi bi-bar-chart"></i> Rapports</a>
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
                    <i class="bi bi-people me-2"></i>Gestion des Utilisateurs
                </h4>
                <small class="text-muted"><?= count($utilisateurs) ?> utilisateur(s) trouvé(s)</small>
            </div>
            <a href="medecins.php?action=ajouter" class="btn btn-primary">
                <i class="bi bi-person-plus me-2"></i>Ajouter un médecin
            </a>
        </div>

        <!-- Filtres -->
        <div class="card mb-4">
            <div class="card-body py-2">
                <form method="GET" class="row g-2 align-items-center">
                    <div class="col-md-5">
                        <div class="input-group">
                            <input type="text" class="form-control" name="q"
                                   placeholder="Rechercher par nom, email..."
                                   value="<?= htmlspecialchars($recherche) ?>">
                            <button class="btn btn-primary" type="submit">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex gap-2">
                            <?php
                            $roles = ['tous' => 'Tous', 'patient' => 'Patients', 'medecin' => 'Médecins', 'admin' => 'Admins'];
                            foreach ($roles as $val => $label):
                                $active = ($role === $val) ? 'btn-primary' : 'btn-outline-secondary';
                            ?>
                                <a href="?role=<?= $val ?>&q=<?= urlencode($recherche) ?>"
                                   class="btn btn-sm <?= $active ?>"><?= $label ?></a>
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
                                <th>#</th>
                                <th>Nom complet</th>
                                <th>Email</th>
                                <th>Téléphone</th>
                                <th>Rôle</th>
                                <th>Statut</th>
                                <th>Inscription</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($utilisateurs as $u):
                                $roleBadge = [
                                    'admin'   => 'danger',
                                    'medecin' => 'primary',
                                    'patient' => 'success',
                                ];
                            ?>
                            <tr>
                                <td class="text-muted small"><?= $u['id'] ?></td>
                                <td class="fw-semibold">
                                    <?= htmlspecialchars($u['prenom'].' '.$u['nom']) ?>
                                </td>
                                <td class="small"><?= htmlspecialchars($u['email']) ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($u['telephone'] ?? '—') ?></td>
                                <td>
                                    <span class="badge bg-<?= $roleBadge[$u['role']] ?? 'secondary' ?>">
                                        <?= ucfirst($u['role']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($u['statut'] === 'actif'): ?>
                                        <span class="badge bg-success">Actif</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Inactif</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted">
                                    <?= date('d/m/Y', strtotime($u['date_creation'])) ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                        <a href="?toggle=<?= $u['id'] ?>&role=<?= $role ?>&q=<?= urlencode($recherche) ?>"
                                           class="btn btn-sm <?= $u['statut'] === 'actif' ? 'btn-outline-danger' : 'btn-outline-success' ?>"
                                           onclick="return confirm('Confirmer cette action ?')"
                                           title="<?= $u['statut'] === 'actif' ? 'Désactiver' : 'Activer' ?>">
                                            <i class="bi bi-<?= $u['statut'] === 'actif' ? 'person-x' : 'person-check' ?>"></i>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">Vous</span>
                                    <?php endif; ?>
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

<?php require_once '../includes/footer.php'; ?>

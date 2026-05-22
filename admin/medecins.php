<?php
// ============================================================
//  Gestion des Médecins
//  Fichier : admin/medecins.php
// ============================================================
$pageTitle = "Gestion des Médecins";
require_once '../includes/fonctions.php';
require_once '../includes/connexion.php';
requireRole('admin');

$action  = clean($_GET['action'] ?? '');
$erreurs = [];

// Ajouter un médecin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter'])) {
    $nom        = clean($_POST['nom'] ?? '');
    $prenom     = clean($_POST['prenom'] ?? '');
    $email      = clean($_POST['email'] ?? '');
    $telephone  = clean($_POST['telephone'] ?? '');
    $specialite = clean($_POST['specialite'] ?? '');
    $numero     = clean($_POST['numero_ordre'] ?? '');
    $mdp        = $_POST['mot_de_passe'] ?? '';

    if (empty($nom))        $erreurs[] = "Le nom est obligatoire.";
    if (empty($prenom))     $erreurs[] = "Le prénom est obligatoire.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $erreurs[] = "Email invalide.";
    if (empty($specialite)) $erreurs[] = "La spécialité est obligatoire.";
    if (strlen($mdp) < 6)   $erreurs[] = "Le mot de passe doit avoir au moins 6 caractères.";

    if (empty($erreurs)) {
        $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $erreurs[] = "Cet email est déjà utilisé.";
        }
    }

    if (empty($erreurs)) {
        $hash = password_hash($mdp, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role, telephone) VALUES (?,?,?,?,'medecin',?)");
        $stmt->execute([$nom, $prenom, $email, $hash, $telephone]);
        $user_id = $pdo->lastInsertId();

        $stmt = $pdo->prepare("INSERT INTO medecins (utilisateur_id, specialite, numero_ordre) VALUES (?,?,?)");
        $stmt->execute([$user_id, $specialite, $numero ?: null]);

        setFlash('success', "Médecin Dr. $prenom $nom ajouté avec succès !");
        header("Location: medecins.php");
        exit();
    }
    $action = 'ajouter';
}

// Liste des médecins
$stmt = $pdo->query("
    SELECT me.*, u.nom, u.prenom, u.email, u.telephone, u.statut, u.date_creation,
           COUNT(r.id) AS nb_rdv
    FROM medecins me
    JOIN utilisateurs u ON me.utilisateur_id = u.id
    LEFT JOIN rendez_vous r ON r.medecin_id = me.id
    GROUP BY me.id
    ORDER BY u.nom ASC
");
$medecins = $stmt->fetchAll();

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
                <a href="medecins.php" class="nav-link active"><i class="bi bi-person-badge"></i> Médecins</a>
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

        <?php if ($action === 'ajouter'): ?>
        <!-- FORMULAIRE AJOUT MÉDECIN -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold text-primary mb-0">
                <i class="bi bi-person-plus me-2"></i>Ajouter un Médecin
            </h4>
            <a href="medecins.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Retour
            </a>
        </div>

        <?php if (!empty($erreurs)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0"><?php foreach ($erreurs as $e): ?><li><?= $e ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body p-4">
                <form method="POST" action="">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nom <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nom"
                                   value="<?= isset($_POST['nom']) ? clean($_POST['nom']) : '' ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Prénom <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="prenom"
                                   value="<?= isset($_POST['prenom']) ? clean($_POST['prenom']) : '' ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="email"
                                   value="<?= isset($_POST['email']) ? clean($_POST['email']) : '' ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Téléphone</label>
                            <input type="tel" class="form-control" name="telephone"
                                   value="<?= isset($_POST['telephone']) ? clean($_POST['telephone']) : '' ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Spécialité <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="specialite"
                                   placeholder="Ex: Médecine Générale"
                                   value="<?= isset($_POST['specialite']) ? clean($_POST['specialite']) : '' ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Numéro d'ordre</label>
                            <input type="text" class="form-control" name="numero_ordre"
                                   placeholder="Ex: CMR-MED-002"
                                   value="<?= isset($_POST['numero_ordre']) ? clean($_POST['numero_ordre']) : '' ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Mot de passe <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" name="mot_de_passe"
                                   placeholder="Min. 6 caractères" required>
                        </div>
                        <div class="col-12 d-flex justify-content-end gap-2 mt-2">
                            <a href="medecins.php" class="btn btn-outline-secondary">Annuler</a>
                            <button type="submit" name="ajouter" class="btn btn-primary px-4">
                                <i class="bi bi-person-plus me-2"></i>Ajouter le Médecin
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <?php else: ?>
        <!-- LISTE DES MÉDECINS -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold text-primary mb-0">
                    <i class="bi bi-person-badge me-2"></i>Gestion des Médecins
                </h4>
                <small class="text-muted"><?= count($medecins) ?> médecin(s)</small>
            </div>
            <a href="?action=ajouter" class="btn btn-primary">
                <i class="bi bi-person-plus me-2"></i>Ajouter un médecin
            </a>
        </div>

        <div class="row g-3">
            <?php foreach ($medecins as $med): ?>
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-body d-flex gap-3">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center
                                    justify-content-center flex-shrink-0"
                             style="width:55px;height:55px;font-size:1.4rem;">
                            <i class="bi bi-person-badge"></i>
                        </div>
                        <div class="flex-grow-1">
                            <p class="mb-0 fw-bold">Dr. <?= htmlspecialchars($med['prenom'].' '.$med['nom']) ?></p>
                            <small class="text-primary fw-semibold"><?= htmlspecialchars($med['specialite']) ?></small>
                            <br>
                            <small class="text-muted">
                                <i class="bi bi-envelope me-1"></i><?= htmlspecialchars($med['email']) ?>
                            </small>
                            <br>
                            <small class="text-muted">
                                <i class="bi bi-calendar-check me-1"></i><?= $med['nb_rdv'] ?> RDV
                                &nbsp;|&nbsp;
                                <?php if ($med['statut'] === 'actif'): ?>
                                    <span class="text-success">● Actif</span>
                                <?php else: ?>
                                    <span class="text-danger">● Inactif</span>
                                <?php endif; ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

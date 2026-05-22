<?php
// ============================================================
//  Mon Profil
//  Fichier : patient/profil.php
// ============================================================
$pageTitle = "Mon Profil";
require_once '../includes/fonctions.php';
require_once '../includes/connexion.php';
requireRole('patient');

// Récupérer les données actuelles
$stmt = $pdo->prepare("
    SELECT p.*, u.nom, u.prenom, u.email, u.telephone
    FROM patients p
    JOIN utilisateurs u ON p.utilisateur_id = u.id
    WHERE p.utilisateur_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$patient = $stmt->fetch();

$erreurs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom            = clean($_POST['nom'] ?? '');
    $prenom         = clean($_POST['prenom'] ?? '');
    $telephone      = clean($_POST['telephone'] ?? '');
    $date_naissance = clean($_POST['date_naissance'] ?? '');
    $groupe_sanguin = clean($_POST['groupe_sanguin'] ?? '');
    $allergies      = clean($_POST['allergies'] ?? '');
    $antecedents    = clean($_POST['antecedents'] ?? '');
    $mdp_actuel     = $_POST['mdp_actuel'] ?? '';
    $nouveau_mdp    = $_POST['nouveau_mdp'] ?? '';
    $confirm_mdp    = $_POST['confirm_mdp'] ?? '';

    if (empty($nom))    $erreurs[] = "Le nom est obligatoire.";
    if (empty($prenom)) $erreurs[] = "Le prénom est obligatoire.";

    // Changement de mot de passe (optionnel)
    $changer_mdp = !empty($mdp_actuel) || !empty($nouveau_mdp);
    if ($changer_mdp) {
        if (!password_verify($mdp_actuel, $patient['mot_de_passe'] ?? '')) {
            // Récupérer le mot de passe actuel
            $stmtMdp = $pdo->prepare("SELECT mot_de_passe FROM utilisateurs WHERE id = ?");
            $stmtMdp->execute([$_SESSION['user_id']]);
            $mdpRow = $stmtMdp->fetch();
            if (!password_verify($mdp_actuel, $mdpRow['mot_de_passe'])) {
                $erreurs[] = "Le mot de passe actuel est incorrect.";
            }
        }
        if (strlen($nouveau_mdp) < 6) $erreurs[] = "Le nouveau mot de passe doit avoir au moins 6 caractères.";
        if ($nouveau_mdp !== $confirm_mdp) $erreurs[] = "Les nouveaux mots de passe ne correspondent pas.";
    }

    if (empty($erreurs)) {
        // Mettre à jour utilisateurs
        $stmt = $pdo->prepare("UPDATE utilisateurs SET nom=?, prenom=?, telephone=? WHERE id=?");
        $stmt->execute([$nom, $prenom, $telephone, $_SESSION['user_id']]);

        // Mettre à jour patients
        $stmt = $pdo->prepare("UPDATE patients SET date_naissance=?, groupe_sanguin=?, allergies=?, antecedents=? WHERE utilisateur_id=?");
        $stmt->execute([$date_naissance ?: null, $groupe_sanguin ?: null, $allergies, $antecedents, $_SESSION['user_id']]);

        // Changer le mot de passe si demandé
        if ($changer_mdp) {
            $hash = password_hash($nouveau_mdp, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE utilisateurs SET mot_de_passe=? WHERE id=?");
            $stmt->execute([$hash, $_SESSION['user_id']]);
        }

        // Mettre à jour la session
        $_SESSION['nom']    = $nom;
        $_SESSION['prenom'] = $prenom;

        // Recharger les données
        $stmt = $pdo->prepare("SELECT p.*, u.nom, u.prenom, u.email, u.telephone FROM patients p JOIN utilisateurs u ON p.utilisateur_id = u.id WHERE p.utilisateur_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $patient = $stmt->fetch();

        setFlash('success', 'Profil mis à jour avec succès !');
        header("Location: profil.php");
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
                <a href="dashboard.php" class="nav-link"><i class="bi bi-speedometer2"></i> Tableau de bord</a>
                <a href="rendez_vous.php" class="nav-link"><i class="bi bi-calendar-plus"></i> Prendre RDV</a>
                <a href="mes_rdv.php" class="nav-link"><i class="bi bi-calendar-check"></i> Mes Rendez-vous</a>
                <a href="historique.php" class="nav-link"><i class="bi bi-folder2-open"></i> Dossier Médical</a>
                <a href="notifications.php" class="nav-link"><i class="bi bi-bell"></i> Notifications</a>
                <a href="profil.php" class="nav-link active"><i class="bi bi-person-gear"></i> Mon Profil</a>
                <hr class="border-secondary mx-3">
                <a href="../auth/logout.php" class="nav-link text-danger"><i class="bi bi-box-arrow-right"></i> Déconnexion</a>
            </nav>
        </div>
    </div>

    <!-- CONTENU -->
    <div class="col-md-9 col-lg-10 py-3 px-4">

        <?php afficherFlash(); ?>

        <h4 class="fw-bold text-primary mb-4">
            <i class="bi bi-person-gear me-2"></i>Mon Profil
        </h4>

        <?php if (!empty($erreurs)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($erreurs as $e): ?>
                        <li><?= $e ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="row g-4">

                <!-- Infos personnelles -->
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header bg-white fw-bold text-primary border-bottom">
                            <i class="bi bi-person me-2"></i>Informations Personnelles
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-3">
                                <div class="col-6">
                                    <label class="form-label fw-semibold">Nom <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="nom"
                                           value="<?= htmlspecialchars($patient['nom']) ?>" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-semibold">Prénom <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="prenom"
                                           value="<?= htmlspecialchars($patient['prenom']) ?>" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Email</label>
                                    <input type="email" class="form-control bg-light"
                                           value="<?= htmlspecialchars($patient['email']) ?>" disabled>
                                    <small class="text-muted">L'email ne peut pas être modifié</small>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Téléphone</label>
                                    <input type="tel" class="form-control" name="telephone"
                                           value="<?= htmlspecialchars($patient['telephone'] ?? '') ?>">
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-semibold">Date de naissance</label>
                                    <input type="date" class="form-control" name="date_naissance"
                                           value="<?= $patient['date_naissance'] ?? '' ?>">
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-semibold">Groupe sanguin</label>
                                    <select class="form-select" name="groupe_sanguin">
                                        <option value="">-- Non précisé --</option>
                                        <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $g): ?>
                                            <option value="<?= $g ?>" <?= ($patient['groupe_sanguin'] === $g) ? 'selected' : '' ?>><?= $g ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Infos médicales -->
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header bg-white fw-bold text-primary border-bottom">
                            <i class="bi bi-heart-pulse me-2"></i>Informations Médicales
                        </div>
                        <div class="card-body p-4">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Allergies connues</label>
                                <textarea class="form-control" name="allergies" rows="2"
                                          placeholder="Ex: Pénicilline, arachides..."><?= htmlspecialchars($patient['allergies'] ?? '') ?></textarea>
                            </div>
                            <div>
                                <label class="form-label fw-semibold">Antécédents médicaux</label>
                                <textarea class="form-control" name="antecedents" rows="3"
                                          placeholder="Ex: Diabète, hypertension..."><?= htmlspecialchars($patient['antecedents'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Changement de mot de passe -->
                    <div class="card">
                        <div class="card-header bg-white fw-bold text-primary border-bottom">
                            <i class="bi bi-lock me-2"></i>Changer le Mot de Passe
                        </div>
                        <div class="card-body p-4">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Mot de passe actuel</label>
                                <input type="password" class="form-control" name="mdp_actuel"
                                       placeholder="Laisser vide si pas de changement">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Nouveau mot de passe</label>
                                <input type="password" class="form-control" name="nouveau_mdp"
                                       placeholder="Min. 6 caractères">
                            </div>
                            <div>
                                <label class="form-label fw-semibold">Confirmer</label>
                                <input type="password" class="form-control" name="confirm_mdp"
                                       placeholder="Répéter le nouveau mot de passe">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bouton sauvegarder -->
                <div class="col-12">
                    <div class="d-flex justify-content-end gap-2">
                        <a href="dashboard.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-2"></i>Retour
                        </a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-floppy me-2"></i>Enregistrer les modifications
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

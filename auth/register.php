<?php
// ============================================================
//  Page d'inscription
//  Fichier : auth/register.php
// ============================================================
$pageTitle = "Inscription";
require_once '../includes/fonctions.php';
require_once '../includes/connexion.php';

// Si déjà connecté, rediriger
if (estConnecte()) redirectDashboard();

$erreurs = [];
$succes  = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération et nettoyage des données
    $nom            = clean($_POST['nom'] ?? '');
    $prenom         = clean($_POST['prenom'] ?? '');
    $email          = clean($_POST['email'] ?? '');
    $telephone      = clean($_POST['telephone'] ?? '');
    $matricule      = clean($_POST['matricule'] ?? '');
    $date_naissance = clean($_POST['date_naissance'] ?? '');
    $sexe           = clean($_POST['sexe'] ?? '');
    $groupe_sanguin = clean($_POST['groupe_sanguin'] ?? '');
    $mdp            = $_POST['mot_de_passe'] ?? '';
    $mdp_confirm    = $_POST['mot_de_passe_confirm'] ?? '';

    // --- Validations ---
    if (empty($nom))            $erreurs[] = "Le nom est obligatoire.";
    if (empty($prenom))         $erreurs[] = "Le prénom est obligatoire.";
    if (empty($email))          $erreurs[] = "L'email est obligatoire.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $erreurs[] = "L'email n'est pas valide.";
    if (empty($date_naissance)) $erreurs[] = "La date de naissance est obligatoire.";
    if (empty($sexe))           $erreurs[] = "Le sexe est obligatoire.";
    if (strlen($mdp) < 6)       $erreurs[] = "Le mot de passe doit contenir au moins 6 caractères.";
    if ($mdp !== $mdp_confirm)  $erreurs[] = "Les mots de passe ne correspondent pas.";

    // Vérifier si l'email existe déjà
    if (empty($erreurs)) {
        $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $erreurs[] = "Cet email est déjà utilisé. Veuillez vous connecter.";
        }
    }

    // --- Enregistrement ---
    if (empty($erreurs)) {
        $mdp_hash = password_hash($mdp, PASSWORD_BCRYPT);

        // Insérer dans utilisateurs
        $stmt = $pdo->prepare("INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role, telephone)
                                VALUES (?, ?, ?, ?, 'patient', ?)");
        $stmt->execute([$nom, $prenom, $email, $mdp_hash, $telephone]);
        $user_id = $pdo->lastInsertId();

        // Insérer dans patients
        $stmt = $pdo->prepare("INSERT INTO patients (utilisateur_id, matricule, date_naissance, sexe, groupe_sanguin)
                                VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $user_id,
            $matricule ?: null,
            $date_naissance,
            $sexe,
            $groupe_sanguin ?: null
        ]);

        setFlash('success', 'Inscription réussie ! Vous pouvez maintenant vous connecter.');
        header("Location: login.php");
        exit();
    }
}

require_once '../includes/header.php';
?>

<div class="row justify-content-center py-4">
    <div class="col-md-8 col-lg-7">
        <div class="card shadow">
            <div class="card-header text-white text-center py-4" style="background-color:#1A5276; border-radius:12px 12px 0 0;">
                <i class="bi bi-person-plus-fill fs-2 d-block mb-2"></i>
                <h4 class="mb-0 fw-bold">Créer un compte Patient</h4>
                <small class="opacity-75">CMS — Université de Ngaoundéré</small>
            </div>
            <div class="card-body p-4">

                <?php if (!empty($erreurs)): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Veuillez corriger les erreurs suivantes :</strong>
                        <ul class="mb-0 mt-2">
                            <?php foreach ($erreurs as $e): ?>
                                <li><?= $e ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">

                    <!-- Informations personnelles -->
                    <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">
                        <i class="bi bi-person me-2"></i>Informations personnelles
                    </h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nom <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nom"
                                   value="<?= isset($_POST['nom']) ? clean($_POST['nom']) : '' ?>"
                                   placeholder="Ex: Nkoa" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Prénom <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="prenom"
                                   value="<?= isset($_POST['prenom']) ? clean($_POST['prenom']) : '' ?>"
                                   placeholder="Ex: Marie" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Date de naissance <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="date_naissance"
                                   value="<?= isset($_POST['date_naissance']) ? clean($_POST['date_naissance']) : '' ?>"
                                   required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Sexe <span class="text-danger">*</span></label>
                            <select class="form-select" name="sexe" required>
                                <option value="">-- Choisir --</option>
                                <option value="M" <?= (isset($_POST['sexe']) && $_POST['sexe']==='M') ? 'selected' : '' ?>>Masculin</option>
                                <option value="F" <?= (isset($_POST['sexe']) && $_POST['sexe']==='F') ? 'selected' : '' ?>>Féminin</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Matricule (optionnel)</label>
                            <input type="text" class="form-control" name="matricule"
                                   value="<?= isset($_POST['matricule']) ? clean($_POST['matricule']) : '' ?>"
                                   placeholder="Ex: ETU-2024-001">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Groupe sanguin</label>
                            <select class="form-select" name="groupe_sanguin">
                                <option value="">-- Non précisé --</option>
                                <?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $g): ?>
                                    <option value="<?= $g ?>" <?= (isset($_POST['groupe_sanguin']) && $_POST['groupe_sanguin']===$g) ? 'selected' : '' ?>><?= $g ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Contact -->
                    <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">
                        <i class="bi bi-envelope me-2"></i>Coordonnées
                    </h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Adresse e-mail <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="email"
                                   value="<?= isset($_POST['email']) ? clean($_POST['email']) : '' ?>"
                                   placeholder="votre@email.com" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Téléphone</label>
                            <input type="tel" class="form-control" name="telephone"
                                   value="<?= isset($_POST['telephone']) ? clean($_POST['telephone']) : '' ?>"
                                   placeholder="Ex: 677000000">
                        </div>
                    </div>

                    <!-- Mot de passe -->
                    <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">
                        <i class="bi bi-lock me-2"></i>Sécurité
                    </h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Mot de passe <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="mdp"
                                       name="mot_de_passe" placeholder="Min. 6 caractères" required>
                                <button class="btn btn-outline-secondary" type="button"
                                        onclick="toggleMdp('mdp','eye1')">
                                    <i class="bi bi-eye" id="eye1"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Confirmer le mot de passe <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="mdp2"
                                       name="mot_de_passe_confirm" placeholder="Répéter le mot de passe" required>
                                <button class="btn btn-outline-secondary" type="button"
                                        onclick="toggleMdp('mdp2','eye2')">
                                    <i class="bi bi-eye" id="eye2"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-person-check me-2"></i>Créer mon compte
                        </button>
                    </div>
                </form>

                <hr class="my-3">
                <p class="text-center text-muted mb-0">
                    Déjà inscrit ?
                    <a href="login.php" class="text-primary fw-semibold">Se connecter</a>
                </p>
            </div>
        </div>
    </div>
</div>

<script>
function toggleMdp(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon  = document.getElementById(iconId);
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>

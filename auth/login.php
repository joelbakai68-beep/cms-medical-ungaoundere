<?php
// ============================================================
//  Page de connexion
//  Fichier : auth/login.php
// ============================================================
$pageTitle = "Connexion";
require_once '../includes/fonctions.php';
require_once '../includes/connexion.php';

// Si déjà connecté, rediriger
if (estConnecte()) redirectDashboard();

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = clean($_POST['email'] ?? '');
    $mdp   = $_POST['mot_de_passe'] ?? '';

    if (empty($email) || empty($mdp)) {
        $erreur = "Veuillez remplir tous les champs.";
    } else {
        // Chercher l'utilisateur par email
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ? AND statut = 'actif'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($mdp, $user['mot_de_passe'])) {
            // Régénérer l'ID de session (sécurité)
            session_regenerate_id(true);

            // Enregistrer les données en session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nom']     = $user['nom'];
            $_SESSION['prenom']  = $user['prenom'];
            $_SESSION['email']   = $user['email'];
            $_SESSION['role']    = $user['role'];

            // Rediriger selon le rôle
            redirectDashboard();
        } else {
            $erreur = "Email ou mot de passe incorrect.";
        }
    }
}

require_once '../includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card card">
        <div class="card-header">
            <i class="bi bi-hospital-fill fs-2 d-block mb-2"></i>
            <h4 class="mb-0 fw-bold">Connexion</h4>
            <small class="opacity-75">CMS — Université de Ngaoundéré</small>
        </div>
        <div class="card-body p-4">

            <?php if ($erreur): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i><?= $erreur ?>
                </div>
            <?php endif; ?>

            <?php afficherFlash(); ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label for="email" class="form-label fw-semibold">
                        <i class="bi bi-envelope me-1"></i>Adresse e-mail
                    </label>
                    <input type="email" class="form-control" id="email" name="email"
                           placeholder="votre@email.com"
                           value="<?= isset($_POST['email']) ? clean($_POST['email']) : '' ?>"
                           required autofocus>
                </div>

                <div class="mb-4">
                    <label for="mot_de_passe" class="form-label fw-semibold">
                        <i class="bi bi-lock me-1"></i>Mot de passe
                    </label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="mot_de_passe"
                               name="mot_de_passe" placeholder="••••••••" required>
                        <button class="btn btn-outline-secondary" type="button"
                                onclick="toggleMdp()">
                            <i class="bi bi-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Se connecter
                    </button>
                </div>
            </form>

            <hr class="my-3">
            <p class="text-center text-muted mb-0">
                Pas encore de compte ?
                <a href="register.php" class="text-primary fw-semibold">S'inscrire</a>
            </p>
        </div>
    </div>
</div>

<script>
function toggleMdp() {
    const input = document.getElementById('mot_de_passe');
    const icon  = document.getElementById('eyeIcon');
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

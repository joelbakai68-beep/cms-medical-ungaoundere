<?php
// ============================================================
//  Page d'accueil
//  Fichier : index.php
// ============================================================
$pageTitle = "Accueil";
require_once 'includes/fonctions.php';

// Si déjà connecté, rediriger vers le dashboard
if (estConnecte()) {
    redirectDashboard();
}

require_once 'includes/header.php';
?>

<!-- HERO SECTION -->
<div class="row align-items-center py-5">
    <div class="col-md-7">
        <h1 class="fw-bold text-primary display-5">
            <i class="bi bi-hospital me-2"></i>
            Bienvenue au CMS
        </h1>
        <h2 class="text-secondary fs-4 mb-3">Université de Ngaoundéré</h2>
        <p class="lead text-muted">
            Gérez vos visites médicales en ligne. Prenez rendez-vous, consultez
            vos dossiers médicaux et suivez votre santé facilement depuis
            n'importe quel appareil.
        </p>
        <div class="mt-4 d-flex gap-3 flex-wrap">
            <a href="auth/login.php" class="btn btn-primary btn-lg px-4">
                <i class="bi bi-box-arrow-in-right me-2"></i>Se connecter
            </a>
            <a href="auth/register.php" class="btn btn-outline-primary btn-lg px-4">
                <i class="bi bi-person-plus me-2"></i>S'inscrire
            </a>
        </div>
    </div>
    <div class="col-md-5 text-center mt-4 mt-md-0">
        <i class="bi bi-hospital-fill text-primary" style="font-size: 10rem; opacity: 0.15;"></i>
    </div>
</div>

<hr class="my-5">

<!-- FONCTIONNALITÉS -->
<h3 class="text-center fw-bold text-primary mb-4">Nos Services</h3>
<div class="row g-4 mb-5">
    <div class="col-md-4">
        <div class="card h-100 text-center p-4">
            <div class="text-primary mb-3">
                <i class="bi bi-calendar-check" style="font-size: 3rem;"></i>
            </div>
            <h5 class="fw-bold">Prise de Rendez-vous</h5>
            <p class="text-muted">Planifiez vos consultations en ligne en quelques clics, à tout moment.</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100 text-center p-4">
            <div class="text-success mb-3">
                <i class="bi bi-folder2-open" style="font-size: 3rem;"></i>
            </div>
            <h5 class="fw-bold">Dossier Médical</h5>
            <p class="text-muted">Accédez à votre historique médical, résultats d'examens et prescriptions.</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100 text-center p-4">
            <div class="text-warning mb-3">
                <i class="bi bi-bell" style="font-size: 3rem;"></i>
            </div>
            <h5 class="fw-bold">Notifications</h5>
            <p class="text-muted">Recevez des rappels pour vos rendez-vous et résultats d'examens.</p>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

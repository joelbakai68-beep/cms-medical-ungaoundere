<?php
// ============================================================
//  Fonctions utilitaires communes
//  Fichier : includes/fonctions.php
// ============================================================

// Démarrer la session si pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Sécurité ---

// Nettoyer les données entrantes (anti-XSS)
function clean($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Vérifier si l'utilisateur est connecté
function estConnecte() {
    return isset($_SESSION['user_id']);
}

// Vérifier le rôle de l'utilisateur connecté
function aLeRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

// Rediriger si non connecté
function requireConnexion() {
    if (!estConnecte()) {
        header("Location: /cms_medical/auth/login.php");
        exit();
    }
}

// Rediriger si mauvais rôle
function requireRole($role) {
    requireConnexion();
    if (!aLeRole($role)) {
        header("Location: /cms_medical/auth/login.php");
        exit();
    }
}

// --- Affichage ---

// Afficher un message flash (succès, erreur, info)
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function afficherFlash() {
    $flash = getFlash();
    if ($flash) {
        $type = $flash['type']; // success, danger, warning, info
        $msg  = $flash['message'];
        echo "<div class='alert alert-{$type} alert-dismissible fade show' role='alert'>
                {$msg}
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
              </div>";
    }
}

// Rediriger vers le dashboard selon le rôle
function redirectDashboard() {
    switch ($_SESSION['role']) {
        case 'admin':
            header("Location: /cms_medical/admin/dashboard.php");
            break;
        case 'medecin':
            header("Location: /cms_medical/medecin/dashboard.php");
            break;
        case 'patient':
        default:
            header("Location: /cms_medical/patient/dashboard.php");
            break;
    }
    exit();
}

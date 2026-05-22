<?php
// ============================================================
//  Déconnexion
//  Fichier : auth/logout.php
// ============================================================
require_once '../includes/fonctions.php';

session_unset();
session_destroy();

header("Location: /cms_medical/auth/login.php");
exit();

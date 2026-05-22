<?php
// ============================================================
//  API AJAX : créneaux déjà pris pour un médecin à une date
//  Fichier : patient/get_creneaux.php
// ============================================================
require_once '../includes/fonctions.php';
require_once '../includes/connexion.php';
requireRole('patient');

header('Content-Type: application/json');

$medecin_id = (int)($_GET['medecin_id'] ?? 0);
$date       = clean($_GET['date'] ?? '');

if (!$medecin_id || empty($date)) {
    echo json_encode([]);
    exit();
}

$stmt = $pdo->prepare("
    SELECT heure_rdv FROM rendez_vous
    WHERE medecin_id = ? AND date_rdv = ?
    AND statut NOT IN ('annule')
");
$stmt->execute([$medecin_id, $date]);
$rows = $stmt->fetchAll();

$creneaux_pris = array_column($rows, 'heure_rdv');
echo json_encode($creneaux_pris);

<?php
// ============================================================
//  Utilitaire : Générer et mettre à jour les mots de passe
//  Fichier : generate_password.php (à la racine de cms_medical)
//  SUPPRIMER CE FICHIER après utilisation !
// ============================================================

$mots_de_passe = [
    'admin@cms-uni-ngaoundere.cm'        => 'Admin1234',
    'dr.mbarga@cms-uni-ngaoundere.cm'    => 'Medecin1234',
    'marie.nkoa@etudiant.uni-ngaoundere.cm' => 'Patient1234',
];

require_once 'includes/connexion.php';

foreach ($mots_de_passe as $email => $mdp) {
    $hash = password_hash($mdp, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("UPDATE utilisateurs SET mot_de_passe = ? WHERE email = ?");
    $stmt->execute([$hash, $email]);
    echo "<p>✅ Mot de passe mis à jour pour <strong>$email</strong> → <code>$mdp</code></p>";
}

echo "<hr><p style='color:red;'><strong>⚠️ Supprimez ce fichier immédiatement après utilisation !</strong></p>";
?>

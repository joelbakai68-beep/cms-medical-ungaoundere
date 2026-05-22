<?php
// ============================================================
//  Dossier Médical & Historique
//  Fichier : patient/historique.php
// ============================================================
$pageTitle = "Mon Dossier Médical";
require_once '../includes/fonctions.php';
require_once '../includes/connexion.php';
requireRole('patient');

// Récupérer les infos du patient
$stmt = $pdo->prepare("
    SELECT p.*, u.nom, u.prenom, u.email, u.telephone
    FROM patients p
    JOIN utilisateurs u ON p.utilisateur_id = u.id
    WHERE p.utilisateur_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$patient = $stmt->fetch();
$patient_id = $patient['id'];

// Historique des consultations
$stmt = $pdo->prepare("
    SELECT r.date_rdv, r.heure_rdv, r.motif,
           c.diagnostic, c.traitement, c.notes,
           c.poids, c.taille, c.tension_arterielle, c.temperature,
           c.date_consultation, c.id AS consultation_id,
           CONCAT(u.prenom, ' ', u.nom) AS medecin_nom,
           me.specialite
    FROM rendez_vous r
    JOIN consultations c ON c.rendez_vous_id = r.id
    JOIN medecins me ON r.medecin_id = me.id
    JOIN utilisateurs u ON me.utilisateur_id = u.id
    WHERE r.patient_id = ?
    ORDER BY r.date_rdv DESC
");
$stmt->execute([$patient_id]);
$consultations = $stmt->fetchAll();

// Prescriptions récentes
$stmt = $pdo->prepare("
    SELECT pr.*, c2.date_consultation, r2.date_rdv,
           CONCAT(u2.prenom, ' ', u2.nom) AS medecin_nom
    FROM prescriptions pr
    JOIN consultations c2 ON pr.consultation_id = c2.id
    JOIN rendez_vous r2 ON c2.rendez_vous_id = r2.id
    JOIN medecins me2 ON r2.medecin_id = me2.id
    JOIN utilisateurs u2 ON me2.utilisateur_id = u2.id
    WHERE r2.patient_id = ?
    ORDER BY c2.date_consultation DESC
    LIMIT 10
");
$stmt->execute([$patient_id]);
$prescriptions = $stmt->fetchAll();

// Examens médicaux
$stmt = $pdo->prepare("
    SELECT e.*, CONCAT(u.prenom, ' ', u.nom) AS medecin_nom
    FROM examens e
    JOIN medecins me ON e.medecin_id = me.id
    JOIN utilisateurs u ON me.utilisateur_id = u.id
    WHERE e.patient_id = ?
    ORDER BY e.date_examen DESC
");
$stmt->execute([$patient_id]);
$examens = $stmt->fetchAll();

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
                <a href="historique.php" class="nav-link active"><i class="bi bi-folder2-open"></i> Dossier Médical</a>
                <a href="notifications.php" class="nav-link"><i class="bi bi-bell"></i> Notifications</a>
                <a href="profil.php" class="nav-link"><i class="bi bi-person-gear"></i> Mon Profil</a>
                <hr class="border-secondary mx-3">
                <a href="../auth/logout.php" class="nav-link text-danger"><i class="bi bi-box-arrow-right"></i> Déconnexion</a>
            </nav>
        </div>
    </div>

    <!-- CONTENU -->
    <div class="col-md-9 col-lg-10 py-3 px-4">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold text-primary mb-0">
                    <i class="bi bi-folder2-open me-2"></i>Mon Dossier Médical
                </h4>
                <small class="text-muted">Historique complet de votre suivi médical</small>
            </div>
        </div>

        <!-- ONGLETS -->
        <ul class="nav nav-tabs mb-4" id="dossierTabs">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#tab_resume">
                    <i class="bi bi-person-vcard me-1"></i>Résumé Patient
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#tab_consultations">
                    <i class="bi bi-clipboard2-pulse me-1"></i>
                    Consultations
                    <span class="badge bg-primary ms-1"><?= count($consultations) ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#tab_prescriptions">
                    <i class="bi bi-capsule me-1"></i>
                    Prescriptions
                    <span class="badge bg-success ms-1"><?= count($prescriptions) ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#tab_examens">
                    <i class="bi bi-file-medical me-1"></i>
                    Examens
                    <span class="badge bg-warning text-dark ms-1"><?= count($examens) ?></span>
                </a>
            </li>
        </ul>

        <div class="tab-content">

            <!-- ===== ONGLET RÉSUMÉ ===== -->
            <div class="tab-pane fade show active" id="tab_resume">
                <div class="row g-4">
                    <!-- Infos personnelles -->
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header bg-white fw-bold text-primary border-bottom">
                                <i class="bi bi-person me-2"></i>Informations Personnelles
                            </div>
                            <div class="card-body">
                                <table class="table table-borderless mb-0">
                                    <tr>
                                        <td class="text-muted fw-semibold" style="width:45%">Nom complet</td>
                                        <td><?= htmlspecialchars($patient['prenom'].' '.$patient['nom']) ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted fw-semibold">Matricule</td>
                                        <td><?= htmlspecialchars($patient['matricule'] ?? '—') ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted fw-semibold">Date de naissance</td>
                                        <td>
                                            <?php if ($patient['date_naissance']): ?>
                                                <?= date('d/m/Y', strtotime($patient['date_naissance'])) ?>
                                                <small class="text-muted">
                                                    (<?= date_diff(date_create($patient['date_naissance']), date_create('today'))->y ?> ans)
                                                </small>
                                            <?php else: ?>—<?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted fw-semibold">Sexe</td>
                                        <td><?= $patient['sexe'] === 'M' ? 'Masculin' : 'Féminin' ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted fw-semibold">Email</td>
                                        <td><?= htmlspecialchars($patient['email']) ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted fw-semibold">Téléphone</td>
                                        <td><?= htmlspecialchars($patient['telephone'] ?? '—') ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Infos médicales -->
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header bg-white fw-bold text-primary border-bottom">
                                <i class="bi bi-heart-pulse me-2"></i>Informations Médicales
                            </div>
                            <div class="card-body">
                                <div class="mb-3 text-center">
                                    <span class="badge fs-4 px-4 py-2"
                                          style="background-color:#1A5276;">
                                        <?= $patient['groupe_sanguin'] ?? '?' ?>
                                    </span>
                                    <small class="d-block text-muted mt-1">Groupe sanguin</small>
                                </div>
                                <hr>
                                <div class="mb-2">
                                    <span class="fw-semibold text-muted">Allergies :</span>
                                    <p class="mb-0 mt-1">
                                        <?= !empty($patient['allergies'])
                                            ? htmlspecialchars($patient['allergies'])
                                            : '<span class="text-muted fst-italic">Aucune allergie connue</span>' ?>
                                    </p>
                                </div>
                                <hr>
                                <div>
                                    <span class="fw-semibold text-muted">Antécédents médicaux :</span>
                                    <p class="mb-0 mt-1">
                                        <?= !empty($patient['antecedents'])
                                            ? htmlspecialchars($patient['antecedents'])
                                            : '<span class="text-muted fst-italic">Aucun antécédent enregistré</span>' ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== ONGLET CONSULTATIONS ===== -->
            <div class="tab-pane fade" id="tab_consultations">
                <?php if (!empty($consultations)): ?>
                    <div class="accordion" id="accordionConsultations">
                        <?php foreach ($consultations as $i => $c): ?>
                        <div class="accordion-item border mb-2 rounded">
                            <h2 class="accordion-header">
                                <button class="accordion-button <?= $i > 0 ? 'collapsed' : '' ?> rounded"
                                        type="button" data-bs-toggle="collapse"
                                        data-bs-target="#cons<?= $i ?>">
                                    <div class="d-flex align-items-center w-100 me-3">
                                        <i class="bi bi-clipboard2-pulse text-primary me-3 fs-5"></i>
                                        <div>
                                            <span class="fw-bold">
                                                <?= date('d/m/Y', strtotime($c['date_rdv'])) ?>
                                            </span>
                                            <small class="text-muted ms-2">
                                                — Dr. <?= htmlspecialchars($c['medecin_nom']) ?>
                                                (<?= htmlspecialchars($c['specialite']) ?>)
                                            </small>
                                        </div>
                                    </div>
                                </button>
                            </h2>
                            <div id="cons<?= $i ?>" class="accordion-collapse collapse <?= $i === 0 ? 'show' : '' ?>">
                                <div class="accordion-body">
                                    <div class="row g-3">
                                        <!-- Signes vitaux -->
                                        <?php if ($c['poids'] || $c['taille'] || $c['tension_arterielle'] || $c['temperature']): ?>
                                        <div class="col-12">
                                            <h6 class="fw-bold text-secondary">
                                                <i class="bi bi-activity me-1"></i>Signes Vitaux
                                            </h6>
                                            <div class="row g-2">
                                                <?php if ($c['poids']): ?>
                                                <div class="col-6 col-md-3">
                                                    <div class="card text-center p-2 bg-light">
                                                        <small class="text-muted">Poids</small>
                                                        <strong><?= $c['poids'] ?> kg</strong>
                                                    </div>
                                                </div>
                                                <?php endif; ?>
                                                <?php if ($c['taille']): ?>
                                                <div class="col-6 col-md-3">
                                                    <div class="card text-center p-2 bg-light">
                                                        <small class="text-muted">Taille</small>
                                                        <strong><?= $c['taille'] ?> cm</strong>
                                                    </div>
                                                </div>
                                                <?php endif; ?>
                                                <?php if ($c['tension_arterielle']): ?>
                                                <div class="col-6 col-md-3">
                                                    <div class="card text-center p-2 bg-light">
                                                        <small class="text-muted">Tension</small>
                                                        <strong><?= htmlspecialchars($c['tension_arterielle']) ?></strong>
                                                    </div>
                                                </div>
                                                <?php endif; ?>
                                                <?php if ($c['temperature']): ?>
                                                <div class="col-6 col-md-3">
                                                    <div class="card text-center p-2 bg-light">
                                                        <small class="text-muted">Température</small>
                                                        <strong><?= $c['temperature'] ?> °C</strong>
                                                    </div>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php endif; ?>

                                        <!-- Diagnostic -->
                                        <?php if ($c['diagnostic']): ?>
                                        <div class="col-md-6">
                                            <h6 class="fw-bold text-secondary">
                                                <i class="bi bi-search me-1"></i>Diagnostic
                                            </h6>
                                            <p class="mb-0"><?= nl2br(htmlspecialchars($c['diagnostic'])) ?></p>
                                        </div>
                                        <?php endif; ?>

                                        <!-- Traitement -->
                                        <?php if ($c['traitement']): ?>
                                        <div class="col-md-6">
                                            <h6 class="fw-bold text-secondary">
                                                <i class="bi bi-capsule me-1"></i>Traitement
                                            </h6>
                                            <p class="mb-0"><?= nl2br(htmlspecialchars($c['traitement'])) ?></p>
                                        </div>
                                        <?php endif; ?>

                                        <!-- Notes -->
                                        <?php if ($c['notes']): ?>
                                        <div class="col-12">
                                            <h6 class="fw-bold text-secondary">
                                                <i class="bi bi-sticky me-1"></i>Notes du médecin
                                            </h6>
                                            <div class="alert alert-light border mb-0">
                                                <?= nl2br(htmlspecialchars($c['notes'])) ?>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-clipboard2-x" style="font-size:4rem;opacity:0.2;"></i>
                        <p class="mt-3">Aucune consultation enregistrée pour le moment</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ===== ONGLET PRESCRIPTIONS ===== -->
            <div class="tab-pane fade" id="tab_prescriptions">
                <?php if (!empty($prescriptions)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-primary">
                                <tr>
                                    <th>Médicament</th>
                                    <th>Posologie</th>
                                    <th>Durée</th>
                                    <th>Instructions</th>
                                    <th>Médecin</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($prescriptions as $pr): ?>
                                <tr>
                                    <td>
                                        <i class="bi bi-capsule text-success me-2"></i>
                                        <strong><?= htmlspecialchars($pr['medicament']) ?></strong>
                                    </td>
                                    <td><?= htmlspecialchars($pr['posologie']) ?></td>
                                    <td><?= htmlspecialchars($pr['duree'] ?? '—') ?></td>
                                    <td class="text-muted small"><?= htmlspecialchars($pr['instructions'] ?? '—') ?></td>
                                    <td>Dr. <?= htmlspecialchars($pr['medecin_nom']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($pr['date_consultation'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-capsule" style="font-size:4rem;opacity:0.2;"></i>
                        <p class="mt-3">Aucune prescription enregistrée</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ===== ONGLET EXAMENS ===== -->
            <div class="tab-pane fade" id="tab_examens">
                <?php if (!empty($examens)): ?>
                    <div class="row g-3">
                        <?php foreach ($examens as $ex): ?>
                        <div class="col-md-6">
                            <div class="card border-start border-4 border-warning">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="fw-bold mb-0">
                                            <i class="bi bi-file-medical text-warning me-2"></i>
                                            <?= htmlspecialchars($ex['type_examen']) ?>
                                        </h6>
                                        <small class="text-muted">
                                            <?= date('d/m/Y', strtotime($ex['date_examen'])) ?>
                                        </small>
                                    </div>
                                    <p class="text-muted small mb-1">
                                        <i class="bi bi-person-badge me-1"></i>
                                        Dr. <?= htmlspecialchars($ex['medecin_nom']) ?>
                                    </p>
                                    <?php if ($ex['resultat']): ?>
                                    <div class="alert alert-light border mt-2 mb-0 small">
                                        <?= nl2br(htmlspecialchars($ex['resultat'])) ?>
                                    </div>
                                    <?php else: ?>
                                    <p class="text-muted fst-italic small mt-2 mb-0">Résultat en attente</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-file-medical" style="font-size:4rem;opacity:0.2;"></i>
                        <p class="mt-3">Aucun examen enregistré</p>
                    </div>
                <?php endif; ?>
            </div>

        </div><!-- fin tab-content -->
    </div><!-- fin col -->
</div><!-- fin row -->

<?php require_once '../includes/footer.php'; ?>

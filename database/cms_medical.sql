-- ============================================================
--  BASE DE DONNÉES : cms_medical
--  Projet : Gestion des visites médicales - CMS Université de Ngaoundéré
--  Technologies : MySQL 8.x
-- ============================================================

CREATE DATABASE IF NOT EXISTS cms_medical
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE cms_medical;

-- ------------------------------------------------------------
-- TABLE : utilisateurs
-- Tous les utilisateurs du système (patients, médecins, admin)
-- ------------------------------------------------------------
CREATE TABLE utilisateurs (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    nom           VARCHAR(100) NOT NULL,
    prenom        VARCHAR(100) NOT NULL,
    email         VARCHAR(150) NOT NULL UNIQUE,
    mot_de_passe  VARCHAR(255) NOT NULL,
    role          ENUM('patient', 'medecin', 'admin') NOT NULL DEFAULT 'patient',
    telephone     VARCHAR(20),
    statut        ENUM('actif', 'inactif') NOT NULL DEFAULT 'actif',
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- TABLE : patients
-- Informations médicales spécifiques aux patients
-- ------------------------------------------------------------
CREATE TABLE patients (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id  INT NOT NULL UNIQUE,
    matricule       VARCHAR(50) UNIQUE,
    date_naissance  DATE,
    sexe            ENUM('M', 'F') NOT NULL,
    groupe_sanguin  ENUM('A+','A-','B+','B-','AB+','AB-','O+','O-'),
    allergies       TEXT,
    antecedents     TEXT,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- TABLE : medecins
-- Informations spécifiques aux médecins
-- ------------------------------------------------------------
CREATE TABLE medecins (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id  INT NOT NULL UNIQUE,
    specialite      VARCHAR(100) NOT NULL,
    numero_ordre    VARCHAR(50),
    biographie      TEXT,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- TABLE : disponibilites
-- Créneaux horaires disponibles pour chaque médecin
-- ------------------------------------------------------------
CREATE TABLE disponibilites (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    medecin_id  INT NOT NULL,
    jour        ENUM('Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi') NOT NULL,
    heure_debut TIME NOT NULL,
    heure_fin   TIME NOT NULL,
    FOREIGN KEY (medecin_id) REFERENCES medecins(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- TABLE : rendez_vous
-- Gestion des rendez-vous entre patients et médecins
-- ------------------------------------------------------------
CREATE TABLE rendez_vous (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    patient_id      INT NOT NULL,
    medecin_id      INT NOT NULL,
    date_rdv        DATE NOT NULL,
    heure_rdv       TIME NOT NULL,
    motif           TEXT,
    statut          ENUM('en_attente', 'confirme', 'annule', 'effectue') NOT NULL DEFAULT 'en_attente',
    date_creation   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id)  REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (medecin_id)  REFERENCES medecins(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- TABLE : consultations
-- Résultats enregistrés par le médecin après chaque consultation
-- ------------------------------------------------------------
CREATE TABLE consultations (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    rendez_vous_id      INT NOT NULL UNIQUE,
    diagnostic          TEXT,
    traitement          TEXT,
    notes               TEXT,
    poids               DECIMAL(5,2),
    taille              DECIMAL(5,2),
    tension_arterielle  VARCHAR(20),
    temperature         DECIMAL(4,1),
    date_consultation   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rendez_vous_id) REFERENCES rendez_vous(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- TABLE : prescriptions
-- Médicaments prescrits lors d'une consultation
-- ------------------------------------------------------------
CREATE TABLE prescriptions (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    consultation_id INT NOT NULL,
    medicament      VARCHAR(200) NOT NULL,
    posologie       VARCHAR(200) NOT NULL,
    duree           VARCHAR(100),
    instructions    TEXT,
    FOREIGN KEY (consultation_id) REFERENCES consultations(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- TABLE : examens
-- Résultats d'examens médicaux des patients
-- ------------------------------------------------------------
CREATE TABLE examens (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    patient_id   INT NOT NULL,
    medecin_id   INT NOT NULL,
    type_examen  VARCHAR(150) NOT NULL,
    resultat     TEXT,
    fichier      VARCHAR(255),
    date_examen  DATE NOT NULL,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (medecin_id) REFERENCES medecins(id) ON DELETE CASCADE
);

-- ------------------------------------------------------------
-- TABLE : notifications
-- Rappels et notifications envoyés aux utilisateurs
-- ------------------------------------------------------------
CREATE TABLE notifications (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id  INT NOT NULL,
    message         TEXT NOT NULL,
    type            ENUM('rappel_rdv', 'resultat_examen', 'annulation', 'info') NOT NULL,
    statut          ENUM('non_lu', 'lu') NOT NULL DEFAULT 'non_lu',
    date_envoi      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
);

-- ============================================================
--  DONNÉES DE TEST (comptes démo)
-- ============================================================

-- Administrateur (mot de passe : Admin@1234)
INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role, telephone) VALUES
('Administrateur', 'CMS', 'admin@cms-uni-ngaoundere.cm',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '677000001');

-- Médecin (mot de passe : Medecin@1234)
INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role, telephone) VALUES
('Mbarga', 'Paul', 'dr.mbarga@cms-uni-ngaoundere.cm',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'medecin', '677000002');

INSERT INTO medecins (utilisateur_id, specialite, numero_ordre) VALUES
(2, 'Médecine Générale', 'CMR-MED-001');

-- Patient (mot de passe : Patient@1234)
INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role, telephone) VALUES
('Nkoa', 'Marie', 'marie.nkoa@etudiant.uni-ngaoundere.cm',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'patient', '677000003');

INSERT INTO patients (utilisateur_id, matricule, date_naissance, sexe, groupe_sanguin) VALUES
(3, 'ETU-2024-001', '2002-05-15', 'F', 'O+');

-- Disponibilités du médecin
INSERT INTO disponibilites (medecin_id, jour, heure_debut, heure_fin) VALUES
(1, 'Lundi',    '08:00:00', '12:00:00'),
(1, 'Mardi',    '08:00:00', '12:00:00'),
(1, 'Mercredi', '14:00:00', '17:00:00'),
(1, 'Jeudi',    '08:00:00', '12:00:00'),
(1, 'Vendredi', '08:00:00', '11:00:00');

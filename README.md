# 🏥 CMS Médical — Université de Ngaoundéré

> Application web de suivi et gestion des visites médicales systématiques au sein du Centre Médical Universitaire (CMS) de l'Université de Ngaoundéré.

![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?logo=php)
![MySQL](https://img.shields.io/badge/MySQL-8.x-4479A1?logo=mysql)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?logo=bootstrap)
![XAMPP](https://img.shields.io/badge/XAMPP-orange?logo=apache)

---

## 📋 Description

Ce projet a été développé dans le cadre de l'unité **Ingénierie des Applications Web** à l'Université de Ngaoundéré. Il s'agit d'une application web multi-rôles permettant de digitaliser la gestion médicale du CMS.

---

## 👥 Équipe de développement

| Membre | Rôle | Branche |
|--------|------|---------|
|BAKAI JOEL: 22A160FS (Chef) | Base de données + Infrastructure | `membre1-base-infrastructure` |
|DAAYANG LANDRY: 22B442FS | Module Authentification | `membre2-authentification` |
|ZEUFALBO PAYANG: 23A164FS | Module Patient — Rendez-vous | `membre3-patient-rdv` |
|FOULPOUL BIENVENU: 22A709FS | Module Patient — Dossier Médical | `membre4-patient-dossier` |
|DJERANE MODESTE: 18A803FS | Module Médecin | `membre5-module-medecin` |
|YOSSA DEVANE: 22B286FS | Module Admin — Gestion | `membre6-admin-gestion` |
|TEMWA HABMO MAXIME: 22A578FS | Module Admin — Rapports | `membre7-admin-rapports` |
|POKAM JAIRUS: 22A346FS | Cahier des Charges + Diagrammes UML | `membre8-cahier-des-charges` |

---

## ✨ Fonctionnalités

### 👤 Patient
- Prise de rendez-vous en ligne avec créneaux dynamiques
- Consultation du dossier médical (consultations, prescriptions, examens)
- Gestion du profil personnel
- Centre de notifications

### 🩺 Médecin
- Tableau de bord avec RDV du jour
- Confirmation/annulation des rendez-vous
- Saisie des consultations (signes vitaux, diagnostic, prescriptions)
- Accès aux dossiers patients

### 🛡️ Administrateur
- Gestion des utilisateurs (activation/désactivation)
- Ajout de médecins
- Supervision de tous les rendez-vous
- Rapports et statistiques avec graphiques

---

## 🛠️ Technologies utilisées

| Couche | Technologie |
|--------|------------|
| Back-end | PHP 8.x |
| Base de données | MySQL 8.x via PDO |
| Front-end | HTML5, CSS3, Bootstrap 5 |
| Interactivité | JavaScript, AJAX, Chart.js |
| Environnement | XAMPP (Apache + MySQL) |
| Versioning | Git / GitHub |

---

## 🚀 Installation

### Prérequis
- [XAMPP](https://www.apachefriends.org/) installé
- Git installé

### Étapes

**1. Cloner le dépôt**
```bash
git clone https://github.com/joelbakai68-beep/cms-medical-ungaoundere.git
```
     
**2. Copier dans htdocs**
```
Copiez le dossier cms_medical dans : C:\xampp\htdocs\
```

**3. Importer la base de données**
- Démarrez XAMPP (Apache + MySQL)
- Ouvrez `http://localhost/phpmyadmin`
- Cliquez sur **Importer**
- Sélectionnez `database/cms_medical.sql`
- Cliquez **Exécuter**

**4. Initialiser les mots de passe**
- Ouvrez `http://localhost/cms_medical/generate_password.php`
- Supprimez ce fichier après utilisation ⚠️

**5. Accéder à l'application**
```
http://localhost/cms_medical/
```

---

## 🔑 Comptes de démonstration

| Rôle | Email | Mot de passe |
|------|-------|-------------|
| 🛡️ Administrateur | admin@cms-uni-ngaoundere.cm | Admin1234 |
| 🩺 Médecin | dr.mbarga@cms-uni-ngaoundere.cm | Medecin1234 |
| 👤 Patient | marie.nkoa@etudiant.uni-ngaoundere.cm | Patient1234 |

---

## 📁 Structure du projet

```
cms_medical/
├── 📄 index.php                  → Page d'accueil
├── 📄 generate_password.php      → Utilitaire mots de passe (supprimer après usage)
├── 📄 README.md
│
├── 📂 auth/
│   ├── login.php                 → Connexion
│   ├── register.php              → Inscription
│   └── logout.php                → Déconnexion
│
├── 📂 patient/
│   ├── dashboard.php             → Tableau de bord
│   ├── rendez_vous.php           → Prise de RDV
│   ├── get_creneaux.php          → API AJAX créneaux
│   ├── mes_rdv.php               → Liste des RDV
│   ├── historique.php            → Dossier médical
│   ├── notifications.php         → Notifications
│   └── profil.php                → Profil patient
│
├── 📂 medecin/
│   ├── dashboard.php             → Tableau de bord
│   ├── rendez_vous.php           → Gestion des RDV
│   ├── consultations.php         → Saisie consultations
│   └── patients.php              → Liste des patients
│
├── 📂 admin/
│   ├── dashboard.php             → Tableau de bord
│   ├── utilisateurs.php          → Gestion utilisateurs
│   ├── medecins.php              → Gestion médecins
│   ├── rendez_vous.php           → Supervision RDV
│   └── rapports.php              → Rapports & statistiques
│
├── 📂 includes/
│   ├── connexion.php             → Connexion PDO MySQL
│   ├── fonctions.php             → Fonctions utilitaires
│   ├── header.php                → En-tête commun
│   └── footer.php                → Pied de page commun
│
├── 📂 assets/
│   ├── css/
│   │   ├── style.css             → Styles personnalisés
│   │   ├── bootstrap.min.css     → Bootstrap 5 (local)
│   │   └── bootstrap-icons.css   → Icônes Bootstrap (local)
│   └── js/
│       └── bootstrap.bundle.min.js
│
└── 📂 database/
    └── cms_medical.sql           → Script de création BDD
```

---

## 🔒 Sécurité

- ✅ Mots de passe hachés avec **bcrypt** (`password_hash`)
- ✅ Protection **SQL Injection** via requêtes préparées PDO
- ✅ Protection **XSS** via `htmlspecialchars()`
- ✅ Sessions sécurisées avec `session_regenerate_id()`
- ✅ Contrôle d'accès par rôle sur chaque page

---

## 📊 Schéma de la base de données

| Table | Description |
|-------|-------------|
| `utilisateurs` | Tous les comptes (patient, médecin, admin) |
| `patients` | Informations médicales des patients |
| `medecins` | Informations des médecins |
| `disponibilites` | Créneaux horaires par médecin |
| `rendez_vous` | Gestion des rendez-vous |
| `consultations` | Résultats des consultations |
| `prescriptions` | Médicaments prescrits |
| `examens` | Résultats d'examens médicaux |
| `notifications` | Alertes et rappels |

---

## 📝 Licence

Projet académique — Université de Ngaoundéré — Année 2025–2026

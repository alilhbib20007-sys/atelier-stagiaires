-- Atelier 6 : table des stagiaires (base Aiven "defaultdb")
CREATE TABLE IF NOT EXISTS stagiaires (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    ville VARCHAR(100) NOT NULL,
    photo VARCHAR(500) NOT NULL,
    date_inscription TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

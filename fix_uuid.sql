-- Ajouter la colonne uuid aux tables
ALTER TABLE categories ADD COLUMN uuid CHAR(36) NULL AFTER id;
ALTER TABLE permissions ADD COLUMN uuid CHAR(36) NULL AFTER id;
ALTER TABLE roles ADD COLUMN uuid CHAR(36) NULL AFTER id;
ALTER TABLE drugs ADD COLUMN uuid CHAR(36) NULL AFTER id;
ALTER TABLE drug_units ADD COLUMN uuid CHAR(36) NULL AFTER id;
ALTER TABLE depots ADD COLUMN uuid CHAR(36) NULL AFTER id;
ALTER TABLE transfers ADD COLUMN uuid CHAR(36) NULL AFTER id;
ALTER TABLE stock_requests ADD COLUMN uuid CHAR(36) NULL AFTER id;
ALTER TABLE sales ADD COLUMN uuid CHAR(36) NULL AFTER id;
ALTER TABLE users ADD COLUMN uuid CHAR(36) NULL AFTER id;

-- Générer des UUID pour toutes les lignes existantes
UPDATE categories SET uuid = UUID() WHERE uuid IS NULL;
UPDATE permissions SET uuid = UUID() WHERE uuid IS NULL;
UPDATE roles SET uuid = UUID() WHERE uuid IS NULL;
UPDATE drugs SET uuid = UUID() WHERE uuid IS NULL;
UPDATE drug_units SET uuid = UUID() WHERE uuid IS NULL;
UPDATE depots SET uuid = UUID() WHERE uuid IS NULL;
UPDATE transfers SET uuid = UUID() WHERE uuid IS NULL;
UPDATE stock_requests SET uuid = UUID() WHERE uuid IS NULL;
UPDATE sales SET uuid = UUID() WHERE uuid IS NULL;
UPDATE users SET uuid = UUID() WHERE uuid IS NULL;

-- Rendre la colonne NOT NULL et ajouter un index unique (optionnel)
-- ALTER TABLE categories MODIFY uuid CHAR(36) NOT NULL, ADD UNIQUE INDEX (uuid);
-- ALTER TABLE permissions MODIFY uuid CHAR(36) NOT NULL, ADD UNIQUE INDEX (uuid);

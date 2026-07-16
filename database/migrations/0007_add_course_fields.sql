ALTER TABLE courses ADD COLUMN term VARCHAR(100) NULL AFTER code;
ALTER TABLE courses ADD COLUMN description TEXT NULL AFTER term;
ALTER TABLE courses ADD COLUMN default_language VARCHAR(10) DEFAULT 'tr' AFTER description;

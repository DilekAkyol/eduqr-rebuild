ALTER TABLE courses ADD COLUMN title_en VARCHAR(255) NULL AFTER title;
ALTER TABLE courses ADD COLUMN description_en TEXT NULL AFTER description;

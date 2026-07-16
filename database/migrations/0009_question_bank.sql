-- Question Bank tablosu: öğretmen başına soru havuzu
CREATE TABLE IF NOT EXISTS question_bank (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    source_title VARCHAR(255) DEFAULT NULL,
    question_text TEXT NOT NULL,
    type VARCHAR(50) DEFAULT 'multiple_choice',
    options JSON DEFAULT NULL,
    correct_answer VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    question_text TEXT NOT NULL,
    type VARCHAR(50) NOT NULL, -- multiple_choice, open_ended, yes_no, likert
    options JSON DEFAULT NULL, -- Seçenekler (dizi olarak)
    correct_answer VARCHAR(255) DEFAULT NULL, -- Doğru cevap anahtarı
    status VARCHAR(50) DEFAULT 'draft', -- draft, active, closed
    image_path VARCHAR(255) DEFAULT NULL,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

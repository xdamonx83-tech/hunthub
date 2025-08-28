-- forum_schema.sql
CREATE TABLE IF NOT EXISTS boards (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  slug VARCHAR(150) NOT NULL UNIQUE,
  description TEXT NULL,
  position INT NOT NULL DEFAULT 0,
  threads_count INT NOT NULL DEFAULT 0,
  posts_count INT NOT NULL DEFAULT 0,
  last_post_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS threads (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  board_id INT NOT NULL,
  author_id BIGINT NOT NULL,
  title VARCHAR(200) NOT NULL,
  slug VARCHAR(220) DEFAULT NULL,
  is_locked TINYINT(1) NOT NULL DEFAULT 0,
  is_pinned TINYINT(1) NOT NULL DEFAULT 0,
  posts_count INT NOT NULL DEFAULT 0,
  last_post_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  deleted_at DATETIME NULL,
  CONSTRAINT fk_threads_board FOREIGN KEY (board_id) REFERENCES boards(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS posts (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  thread_id BIGINT NOT NULL,
  author_id BIGINT NOT NULL,
  content TEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  edited_at DATETIME NULL,
  deleted_at DATETIME NULL,
  is_moderator_edit TINYINT(1) NOT NULL DEFAULT 0,
  CONSTRAINT fk_posts_thread FOREIGN KEY (thread_id) REFERENCES threads(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_threads_board ON threads (board_id, is_pinned DESC, last_post_at DESC, id DESC);
CREATE INDEX idx_posts_thread ON posts (thread_id, id ASC);

INSERT INTO boards (name, slug, description, position)
VALUES
('Allgemein', 'allgemein', 'Allgemeine Themen', 1),
('Ankündigungen', 'ankuendigungen', 'Neuigkeiten & Updates', 2)
ON DUPLICATE KEY UPDATE name=VALUES(name);

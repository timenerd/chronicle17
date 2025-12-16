-- TTRPG Session Recap Database Schema

CREATE DATABASE IF NOT EXISTS ttrpg_recap CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ttrpg_recap;

-- Users
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Campaigns (containers for sessions)
CREATE TABLE campaigns (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    game_system VARCHAR(100),
    setting_context TEXT COMMENT 'Fed to LLM for better recaps',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Player Characters in a campaign
CREATE TABLE campaign_characters (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    player_name VARCHAR(255),
    class VARCHAR(100),
    race VARCHAR(100),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    INDEX idx_campaign_id (campaign_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Individual session recordings
CREATE TABLE sessions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    session_number INT,
    session_date DATE,
    audio_file_path VARCHAR(500),
    audio_duration_seconds INT,
    status ENUM('pending', 'transcribing', 'processing', 'complete', 'failed') DEFAULT 'pending',
    error_message TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    INDEX idx_campaign_id (campaign_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Raw transcript from Whisper
CREATE TABLE transcripts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id INT UNSIGNED NOT NULL UNIQUE,
    raw_text LONGTEXT,
    segments JSON COMMENT 'Timestamped segments from Whisper',
    word_count INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE CASCADE,
    INDEX idx_session_id (session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Generated recap content
CREATE TABLE recaps (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id INT UNSIGNED NOT NULL UNIQUE,
    narrative_recap LONGTEXT COMMENT 'The prose summary',
    brief_summary TEXT COMMENT '2-3 sentence version',
    memorable_quotes JSON,
    plot_hooks JSON,
    narration_audio_path VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE CASCADE,
    INDEX idx_session_id (session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Extracted entities (builds the wiki)
CREATE TABLE entities (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT UNSIGNED NOT NULL,
    entity_type ENUM('npc', 'location', 'item', 'faction', 'event') NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    first_session_id INT UNSIGNED COMMENT 'When first mentioned',
    metadata JSON COMMENT 'Flexible storage for type-specific data',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (first_session_id) REFERENCES sessions(id) ON DELETE SET NULL,
    INDEX idx_campaign_id (campaign_id),
    INDEX idx_entity_type (entity_type),
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Link entities to sessions they appear in
CREATE TABLE session_entities (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id INT UNSIGNED NOT NULL,
    entity_id INT UNSIGNED NOT NULL,
    context TEXT COMMENT 'What happened with this entity in this session',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (entity_id) REFERENCES entities(id) ON DELETE CASCADE,
    INDEX idx_session_id (session_id),
    INDEX idx_entity_id (entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Job queue for async processing
CREATE TABLE jobs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    queue VARCHAR(50) NOT NULL DEFAULT 'default',
    payload JSON NOT NULL,
    status ENUM('pending', 'processing', 'complete', 'failed') DEFAULT 'pending',
    attempts INT DEFAULT 0,
    error_message TEXT NULL,
    available_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    INDEX idx_queue_status (queue, status),
    INDEX idx_available_at (available_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert a default user for testing
INSERT INTO users (email, password_hash, name) VALUES 
('admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin User');
-- Password is 'password' (use bcrypt for production)

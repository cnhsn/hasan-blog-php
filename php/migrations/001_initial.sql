-- Initial schema. Applied once by the installer (public/install.php) or
-- by scripts/migrate.php, tracked in schema_migrations so re-running is
-- safe. MySQL 5.7+ / MariaDB 10.3+ compatible (utf8mb4).

CREATE TABLE IF NOT EXISTS schema_migrations (
    version VARCHAR(50) PRIMARY KEY,
    applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    display_name VARCHAR(80) NOT NULL DEFAULT 'Hasan',
    role ENUM('owner') NOT NULL DEFAULT 'owner',
    reset_token_hash CHAR(64) NULL,
    reset_token_expires_at DATETIME NULL,
    failed_login_count INT UNSIGNED NOT NULL DEFAULT 0,
    locked_until DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS media (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    filesize INT UNSIGNED NOT NULL,
    width INT UNSIGNED NULL,
    height INT UNSIGNED NULL,
    alt VARCHAR(200) NOT NULL,
    caption VARCHAR(300) NULL,
    visibility ENUM('private','public') NOT NULL DEFAULT 'private',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_media_filename (filename)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS media_variants (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    media_id INT UNSIGNED NOT NULL,
    size_name VARCHAR(20) NOT NULL,
    filename VARCHAR(255) NOT NULL,
    width INT UNSIGNED NOT NULL,
    height INT UNSIGNED NOT NULL,
    filesize INT UNSIGNED NOT NULL,
    UNIQUE KEY uniq_media_variant (media_id, size_name),
    CONSTRAINT fk_variant_media FOREIGN KEY (media_id) REFERENCES media(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL,
    slug VARCHAR(120) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_category_name (name),
    UNIQUE KEY uniq_category_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS tags (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(40) NOT NULL,
    slug VARCHAR(120) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_tag_name (name),
    UNIQUE KEY uniq_tag_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS posts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(160) NOT NULL,
    slug VARCHAR(120) NOT NULL,
    excerpt VARCHAR(320) NOT NULL,
    content_html MEDIUMTEXT NOT NULL,
    cover_media_id INT UNSIGNED NULL,
    author_name VARCHAR(80) NOT NULL DEFAULT 'Hasan',
    status ENUM('draft','published') NOT NULL DEFAULT 'draft',
    published_at DATETIME NULL,
    featured TINYINT(1) NOT NULL DEFAULT 0,
    seo_title VARCHAR(70) NULL,
    seo_description VARCHAR(170) NULL,
    seo_share_media_id INT UNSIGNED NULL,
    -- Editing a published post stages changes here instead of touching
    -- the live columns above, until the owner explicitly republishes
    -- (docs/CONTENT_MODEL.md: "Restoring history creates an editable
    -- draft; it must not silently replace the public revision").
    pending_draft_json MEDIUMTEXT NULL,
    pending_draft_updated_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_post_slug (slug),
    KEY idx_post_status_published (status, published_at),
    CONSTRAINT fk_post_cover FOREIGN KEY (cover_media_id) REFERENCES media(id) ON DELETE SET NULL,
    CONSTRAINT fk_post_seo_media FOREIGN KEY (seo_share_media_id) REFERENCES media(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS post_categories (
    post_id INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (post_id, category_id),
    CONSTRAINT fk_pc_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    CONSTRAINT fk_pc_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS post_tags (
    post_id INT UNSIGNED NOT NULL,
    tag_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (post_id, tag_id),
    CONSTRAINT fk_pt_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    CONSTRAINT fk_pt_tag FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS post_versions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id INT UNSIGNED NOT NULL,
    data_json MEDIUMTEXT NOT NULL,
    status ENUM('draft','published') NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_version_post (post_id, created_at),
    CONSTRAINT fk_pv_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS projects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(160) NOT NULL,
    slug VARCHAR(120) NOT NULL,
    short_description VARCHAR(320) NOT NULL,
    case_study_html MEDIUMTEXT NOT NULL,
    problem_html MEDIUMTEXT NULL,
    approach_html MEDIUMTEXT NULL,
    results_html MEDIUMTEXT NULL,
    cover_media_id INT UNSIGNED NULL,
    technologies_json VARCHAR(600) NULL,
    repository_url VARCHAR(500) NULL,
    live_url VARCHAR(500) NULL,
    lifecycle_status ENUM('idea','in-progress','completed','archived') NOT NULL DEFAULT 'idea',
    featured TINYINT(1) NOT NULL DEFAULT 0,
    start_date DATE NOT NULL,
    end_date DATE NULL,
    status ENUM('draft','published') NOT NULL DEFAULT 'draft',
    published_at DATETIME NULL,
    seo_title VARCHAR(70) NULL,
    seo_description VARCHAR(170) NULL,
    seo_share_media_id INT UNSIGNED NULL,
    pending_draft_json MEDIUMTEXT NULL,
    pending_draft_updated_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_project_slug (slug),
    KEY idx_project_status_start (status, start_date),
    CONSTRAINT fk_project_cover FOREIGN KEY (cover_media_id) REFERENCES media(id) ON DELETE SET NULL,
    CONSTRAINT fk_project_seo_media FOREIGN KEY (seo_share_media_id) REFERENCES media(id) ON DELETE SET NULL,
    CONSTRAINT chk_project_dates CHECK (end_date IS NULL OR end_date >= start_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS project_gallery (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id INT UNSIGNED NOT NULL,
    media_id INT UNSIGNED NOT NULL,
    caption VARCHAR(300) NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    KEY idx_gallery_project (project_id, sort_order),
    CONSTRAINT fk_gallery_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    CONSTRAINT fk_gallery_media FOREIGN KEY (media_id) REFERENCES media(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS project_versions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id INT UNSIGNED NOT NULL,
    data_json MEDIUMTEXT NOT NULL,
    status ENUM('draft','published') NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_pversion_project (project_id, created_at),
    CONSTRAINT fk_projv_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS redirects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    source_path VARCHAR(300) NOT NULL,
    target_path VARCHAR(300) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_redirect_source (source_path)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS site_settings (
    id TINYINT UNSIGNED PRIMARY KEY DEFAULT 1,
    site_title VARCHAR(100) NOT NULL,
    short_description VARCHAR(300) NOT NULL,
    author_display_name VARCHAR(80) NOT NULL DEFAULT 'Hasan',
    hero_greeting VARCHAR(80) NOT NULL,
    intro_heading VARCHAR(120) NOT NULL,
    intro_body VARCHAR(400) NOT NULL,
    interests_json VARCHAR(500) NULL,
    copyright_name VARCHAR(80) NOT NULL DEFAULT 'Hasan',
    social_links_json TEXT NULL,
    default_seo_title VARCHAR(70) NOT NULL,
    default_seo_description VARCHAR(170) NOT NULL,
    nav_home_label VARCHAR(30) NOT NULL DEFAULT 'Home',
    nav_blog_label VARCHAR(30) NOT NULL DEFAULT 'Blog',
    nav_projects_label VARCHAR(30) NOT NULL DEFAULT 'Projects',
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_settings_singleton CHECK (id = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS login_attempts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    succeeded TINYINT(1) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_login_attempts_email_time (email, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

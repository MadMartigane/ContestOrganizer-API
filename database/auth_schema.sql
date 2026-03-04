-- Enable foreign key constraints
PRAGMA foreign_keys = ON;

-- ============================================
-- USERS TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    role TEXT NOT NULL CHECK (role IN ('admin', 'organizer', 'spectator')),
    created_at INTEGER DEFAULT (strftime('%s', 'now'))
);

-- Index for email lookups (authentication)
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);

-- ============================================
-- FILES TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS files (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    filename TEXT NOT NULL,
    content TEXT NOT NULL CHECK (length(content) <= 2000000),
    created_at INTEGER DEFAULT (strftime('%s', 'now')),
    updated_at INTEGER DEFAULT (strftime('%s', 'now')),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Index for user_id foreign key lookups
CREATE INDEX IF NOT EXISTS idx_files_user_id ON files(user_id);

-- Index for filename searches per user
CREATE INDEX IF NOT EXISTS idx_files_user_filename ON files(user_id, filename);

-- ============================================
-- TOKEN BLACKLIST TABLE
-- ============================================
CREATE TABLE IF NOT EXISTS token_blacklist (
    jti TEXT PRIMARY KEY,
    expires_at INTEGER NOT NULL
);

-- Index for cleanup operations (expired tokens)
CREATE INDEX IF NOT EXISTS idx_token_blacklist_expires_at ON token_blacklist(expires_at);

-- ============================================
-- DEFAULT ADMIN USER
-- ============================================
-- Password hash placeholder: to be replaced with actual hash
-- Default password: admin (hash to be configured)
INSERT INTO users (email, password_hash, role)
VALUES ('admin@contest.local', '$2y$10$placeholder_hash_replace_me', 'admin');

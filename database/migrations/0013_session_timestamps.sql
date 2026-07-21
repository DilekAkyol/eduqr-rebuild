ALTER TABLE sessions
    ADD COLUMN started_at DATETIME NULL AFTER status,
    ADD COLUMN paused_at DATETIME NULL AFTER started_at,
    ADD COLUMN closed_at DATETIME NULL AFTER paused_at;

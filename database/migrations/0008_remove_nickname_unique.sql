-- Participants tablosundan nickname unique constraint kaldır
-- (Artık anonim katılım var, nickname = 'anon-{hash}' otomatik üretiliyor)
ALTER TABLE participants ADD INDEX idx_session_id (session_id);
ALTER TABLE participants DROP INDEX uq_session_participant;
ALTER TABLE participants MODIFY COLUMN nickname VARCHAR(50) NULL DEFAULT NULL;

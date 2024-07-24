CREATE TABLE addresses_favorites
(
    login TEXT,
    object TEXT,
    id INTEGER,
    title TEXT,
    icon TEXT,
    color TEXT
);
CREATE INDEX addresses_favorites_login ON addresses_favorites(login);
CREATE UNIQUE INDEX addresses_favorites_uniq ON addresses_favorites(login, object, id);

-- houses
CREATE TABLE addresses_favorites
(
    login TEXT,
    link TEXT,
    icon TEXT,
    color TEXT
);
CREATE UNIQUE INDEX addresses_favorites_uniq ON addresses_favorites(login, link);

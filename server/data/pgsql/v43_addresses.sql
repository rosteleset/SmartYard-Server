CREATE TABLE addresses_favorites
(
    login CHARACTER VARYING,
    object CHARACTER VARYING,
    id INTEGER,
    title CHARACTER VARYING,
    icon CHARACTER VARYING,
    color CHARACTER VARYING
);
CREATE INDEX addresses_favorites_login ON addresses_favorites(login);
CREATE UNIQUE INDEX addresses_favorites_uniq ON addresses_favorites(login, object, id);

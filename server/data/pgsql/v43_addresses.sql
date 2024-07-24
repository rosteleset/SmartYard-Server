-- houses
CREATE TABLE addresses_favorites
(
    login CHARACTER VARYING,
    link CHARACTER VARYING,
    icon CHARACTER VARYING,
    color CHARACTER VARYING
);
CREATE UNIQUE INDEX addresses_favorites_uniq ON addresses_houses(login, link);

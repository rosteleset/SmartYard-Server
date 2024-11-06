CREATE TABLE IF NOT EXISTS tt_favorite_filters
(
    login CHARACTER VARYING,
    filter CHARACTER VARYING,
    right_side INTEGER DEFAULT 0,
    icon CHARACTER VARYING
);
CREATE UNIQUE INDEX tt_favorite_filters_uniq on tt_favorite_filters (login, filter);
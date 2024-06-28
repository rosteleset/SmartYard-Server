CREATE TABLE tt_favorite_filters
(
    login TEXT,
    filter TEXT,
    right_side INTEGER DEFAULT 0,
    icon TEXT
);
CREATE UNIQUE INDEX tt_favorite_filters_uniq on tt_favorite_filters (login, filter);
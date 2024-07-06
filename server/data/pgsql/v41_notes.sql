CREATE TABLE notes
(
    note_id SERIAL PRIMARY KEY,
    create_date INTEGER,
    owner CHARACTER VARYING,
    note_subject CHARACTER VARYING,
    note_body CHARACTER VARYING,
    remind INTEGER DEFAULT 0,
    reminded INTEGER DEFAULT 0,
    bg_color CHARACTER VARYING,
    fg_color CHARACTER VARYING,
    position_left INTEGER,
    position_top INTEGER,
    category CHARACTER VARYING
);
CREATE INDEX notes_owner ON notes(owner);
CREATE INDEX notes_remind ON notes(remind);
CREATE INDEX notes_reminded ON notes(reminded);
CREATE INDEX notes_category ON notes(category);
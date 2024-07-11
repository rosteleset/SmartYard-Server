CREATE TABLE notes
(
    note_id INTEGER PRIMARY KEY AUTOINCREMENT,
    create_date INTEGER,
    owner TEXT,
    note_subject TEXT,
    note_body TEXT,
    checks INTEGER DEFAULT 0,
    category TEXT,
    remind INTEGER DEFAULT 0,
    icon TEXT,
    font TEXT,
    color TEXT,
    reminded INTEGER DEFAULT 0,
    position_left REAL,
    position_top REAL,
    position_order INTEGER
);
CREATE INDEX notes_owner ON notes(owner);
CREATE INDEX notes_remind ON notes(remind);
CREATE INDEX notes_reminded ON notes(reminded);
CREATE INDEX notes_category ON notes(category);
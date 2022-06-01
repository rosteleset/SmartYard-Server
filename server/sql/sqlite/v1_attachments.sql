-- real files
CREATE TABLE attachments_files
(
    file_id integer not null primary key autoincrement,
    name text,
    hash text
);
CREATE UNIQUE INDEX attachments_files_hash on attachments_files(hash);

-- links (for deduplication)
CREATE TABLE attachments_links
(
    link_id integer not null primary key autoincrement,
    file_id integer,
    uuid text
);
CREATE INDEX attachments_links_uuid on attachments_links(uuid);


-- real files
CREATE TABLE attachments_files
(
    file_id serial primary key,
    name character varying,
    hash character varying
);
CREATE UNIQUE INDEX attachments_files_hash on attachments_files(hash);

-- links (for deduplication)
CREATE TABLE attachments_links
(
    link_id serial not null primary key,
    file_id integer,
    uuid character varying
);
CREATE INDEX attachments_links_uuid on attachments_links(uuid);


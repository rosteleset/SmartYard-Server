-- indexes
CREATE INDEX IF NOT EXISTS link_face_subscriber_group_face_id_idx
    ON link_face_subscriber_group (face_id);

CREATE INDEX IF NOT EXISTS houses_watchers_event_type_event_detail
    ON houses_watchers (event_type, event_detail);

-- trigger on frs_links_faces
CREATE OR REPLACE FUNCTION delete_face_subscriber_group_links()
RETURNS trigger
LANGUAGE plpgsql
AS $$
BEGIN
    DELETE FROM link_face_subscriber_group lfsg
    USING subscriber_groups sg
    WHERE lfsg.subscriber_group_id = sg.subscriber_group_id
      AND lfsg.face_id = OLD.face_id
      AND sg.flat_id = OLD.flat_id
      AND sg.house_subscriber_id = OLD.house_subscriber_id;

    RETURN OLD;
END;
$$;

DROP TRIGGER IF EXISTS frs_links_faces_delete_trigger
    ON frs_links_faces;

CREATE TRIGGER frs_links_faces_delete_trigger
AFTER DELETE ON frs_links_faces
FOR EACH ROW
EXECUTE FUNCTION delete_face_subscriber_group_links();

-- trigger on subscriber_groups
CREATE OR REPLACE FUNCTION delete_subscriber_group_watchers()
RETURNS TRIGGER
LANGUAGE plpgsql
AS $$
BEGIN
    DELETE FROM houses_watchers
    WHERE event_type = '5'
      AND event_detail = OLD.subscriber_group_id::varchar;

    RETURN OLD;
END;
$$;

DROP TRIGGER IF EXISTS subscriber_groups_delete_watchers
    ON subscriber_groups;

CREATE TRIGGER subscriber_groups_delete_watchers
AFTER DELETE ON subscriber_groups
FOR EACH ROW
EXECUTE FUNCTION delete_subscriber_group_watchers();

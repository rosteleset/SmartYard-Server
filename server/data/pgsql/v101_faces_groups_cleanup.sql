-- trigger on frs_links_faces
-- a flat owner can attach a face of another flat subscriber to their own group,
-- so group links are removed for all subscribers of the flat once the face is no longer linked to it
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
      AND (
          sg.house_subscriber_id = OLD.house_subscriber_id
          OR NOT EXISTS (
              SELECT 1
              FROM frs_links_faces lf
              WHERE lf.face_id = OLD.face_id
                AND lf.flat_id = OLD.flat_id
          )
      );

    RETURN OLD;
END;
$$;

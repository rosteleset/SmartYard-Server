-- Return cameras with missing groups to the root without removing their access links.
UPDATE houses_cameras_houses c
SET path = NULL, path_order = NULL
WHERE c.path IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM houses_paths p WHERE p.house_path_id::text = c.path
  );

UPDATE houses_cameras_flats c
SET path = NULL, path_order = NULL
WHERE c.path IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM houses_paths p WHERE p.house_path_id::text = c.path
  );

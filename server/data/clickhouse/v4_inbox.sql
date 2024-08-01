ALTER TABLE inbox
    (ADD COLUMN IF NOT EXISTS `msg_id` integer FIRST );
ALTER TABLE inbox
    (ADD COLUMN IF NOT EXISTS `house_subscriber_id` integer AFTER msg_id);
ALTER TABLE inbox
    (ADD COLUMN IF NOT EXISTS `title` String AFTER id);
ALTER TABLE inbox
    (ADD COLUMN IF NOT EXISTS `code` String AFTER action);
ALTER TABLE inbox
    (MODIFY COLUMN `id` String AFTER house_subscriber_id);

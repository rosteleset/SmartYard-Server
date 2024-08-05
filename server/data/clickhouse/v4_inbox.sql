ALTER TABLE inbox
    ADD COLUMN IF NOT EXISTS `msg_id` integer FIRST,
    ADD COLUMN IF NOT EXISTS `house_subscriber_id` integer AFTER msg_id,
    ADD COLUMN IF NOT EXISTS `title` String AFTER id,
    ADD COLUMN IF NOT EXISTS `code` String AFTER action,
    MODIFY COLUMN `id` String AFTER house_subscriber_id;

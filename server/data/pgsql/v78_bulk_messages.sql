CREATE TABLE IF NOT EXISTS houses_subscribers_messages (
    bulk_message_id SERIAL PRIMARY KEY,
    house_subscriber_id INTEGER,
    title CHARACTER VARYING,
    msg CHARACTER VARYING,
    action CHARACTER VARYING
);
CREATE INDEX IF NOT EXISTS houses_subscribers_messages_house_subscriber_id ON houses_subscribers_messages(house_subscriber_id);
CREATE UNIQUE INDEX IF NOT EXISTS houses_subscribers_messages_uniq ON houses_subscribers_messages (house_subscriber_id, title, msg, action);

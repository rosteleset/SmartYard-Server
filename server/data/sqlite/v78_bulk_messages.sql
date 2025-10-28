CREATE TABLE houses_subscribers_messages (
    bulk_message_id INTEGER AUTOINCREMENT PRIMARY KEY,
    house_subscriber_id INTEGER,
    title TEXT,
    msg TEXT,
    action TEXT
);
CREATE INDEX houses_subscribers_messages_house_subscriber_id ON houses_subscribers_messages(house_subscriber_id);
CREATE UNIQUE INDEX houses_subscribers_messages_uniq ON houses_subscribers_messages (house_subscriber_id, title, msg, action);

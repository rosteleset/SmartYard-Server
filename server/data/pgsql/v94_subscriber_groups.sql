create table if not exists subscriber_groups
(
  subscriber_group_id serial
    constraint subscriber_groups_pk
      primary key,
  house_subscriber_id integer not null
    constraint house_subscriber_id_fk
      references houses_subscribers_mobile (house_subscriber_id)
      on update cascade on delete cascade,
  flat_id integer not null
    constraint house_flat_id_fk
      references houses_flats (house_flat_id)
      on update cascade on delete cascade,
  subscriber_group_name character varying not null,
  constraint subscriber_group_unique
    unique (house_subscriber_id, flat_id, subscriber_group_name)
);

create table if not exists link_face_subscriber_group
(
  subscriber_group_id integer not null
    constraint subscriber_group_id_fk
      references subscriber_groups
      on update cascade on delete cascade,
  face_id integer not null
    constraint face_id_fk
      references frs_faces
      on update cascade on delete cascade,
  constraint link_face_subscriber_group_unique
    unique (subscriber_group_id, face_id)
);

-- Remove orphaned links to flats
delete from frs_links_faces flf
where not exists (
    select 1
    from houses_flats hf
    where hf.house_flat_id = flf.flat_id
);

-- Remove orphaned links to subscribers
delete from frs_links_faces flf
where not exists (
    select 1
    from houses_subscribers_mobile hsm
    where hsm.house_subscriber_id = flf.house_subscriber_id
);

-- Add foreign key to houses_flats
alter table frs_links_faces
    drop constraint if exists frs_links_faces_flat_fk;

alter table frs_links_faces
    add constraint frs_links_faces_flat_fk
    foreign key (flat_id)
    references houses_flats (house_flat_id)
    on delete cascade;

-- Add foreign key to houses_subscribers_mobile
alter table frs_links_faces
    drop constraint if exists frs_links_faces_subscriber_fk;

alter table frs_links_faces
    add constraint frs_links_faces_subscriber_fk
    foreign key (house_subscriber_id)
    references houses_subscribers_mobile (house_subscriber_id)
    on delete cascade;

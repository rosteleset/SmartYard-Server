do $$
begin
    -- Do nothing if the target tables already exist.
    if to_regclass('license_plate_numbers') is not null
       and to_regclass('link_lp_flat') is not null then
        return;
    end if;

    create table if not exists license_plate_numbers
    (
        lp_id serial primary key,
        country_code varchar(2) default 'ru' not null,
        lp_number varchar(30),

        constraint license_plate_numbers_unique_lp
            unique (country_code, lp_number)
    );

    comment on column license_plate_numbers.lp_id
        is 'License plate number identifier';

    comment on column license_plate_numbers.country_code
        is 'Two-letter country code in lowercase';

    comment on column license_plate_numbers.lp_number
        is 'License plate number';

    create table if not exists link_lp_flat
    (
        lp_id integer not null,
        flat_id integer not null,
        valid_to timestamp with time zone,

        constraint link_lp_flat_lp_fk
            foreign key (lp_id)
            references license_plate_numbers (lp_id)
            on delete cascade,

        constraint link_lp_flat_flat_fk
            foreign key (flat_id)
            references houses_flats (house_flat_id)
            on delete cascade,

        constraint link_lp_flat_unique
            unique (lp_id, flat_id)
    );

    comment on table link_lp_flat
        is 'Link between license plate numbers and flats';

    comment on column link_lp_flat.lp_id
        is 'License plate number identifier';

    comment on column link_lp_flat.flat_id
        is 'Flat identifier';

    comment on column link_lp_flat.valid_to
        is 'Date and time when the license plate number link to the flat becomes invalid';

    create index if not exists link_lp_flat_valid_to_index
        on link_lp_flat (valid_to);

    -- Migrate license plate numbers.
    insert into license_plate_numbers (country_code, lp_number)
    select distinct
        'ru',
        trim(car_number)
    from houses_flats hf
    cross join lateral regexp_split_to_table(hf.cars, E'\r?\n') as car_number
    where hf.cars is not null
      and trim(car_number) <> '';

    -- Migrate links between license plate numbers and flats.
    insert into link_lp_flat (lp_id, flat_id)
    select
        lp.lp_id,
        hf.house_flat_id
    from houses_flats hf
    cross join lateral regexp_split_to_table(hf.cars, E'\r?\n') as car_number
    join license_plate_numbers lp
        on lp.country_code = 'ru'
       and lp.lp_number = trim(car_number)
    where hf.cars is not null
      and trim(car_number) <> '';

end;
$$;

-- trigger on link_lp_flat
drop trigger if exists delete_lp_flat_watchers_trigger on link_lp_flat;
drop function if exists delete_lp_flat_watchers();

create function delete_lp_flat_watchers()
returns trigger
language plpgsql
as $$
begin
    delete from houses_watchers
    where event_type = '9'
      and event_detail = (
          select lp_number
          from license_plate_numbers
          where lp_id = old.lp_id
      )
      and house_flat_id = old.flat_id;

    return old;
end;
$$;

create trigger delete_lp_flat_watchers_trigger
after delete on link_lp_flat
for each row
execute function delete_lp_flat_watchers();

do $$
begin
    -- Do nothing if the target tables already exist.
    if to_regclass('parking_configs') is not null
       and to_regclass('parking_areas') is not null
       and to_regclass('parking_config_revisions') is not null then
        return;
    end if;

    create table if not exists parking_configs
    (
        parking_config_id serial primary key,
        camera_id integer not null,
        enabled integer default 1 not null,
        frame_width integer not null,
        frame_height integer not null,
        calibration_model character varying(32) default 'homography' not null,
        calibration_points jsonb,
        calibration_checks jsonb default '[]'::jsonb not null,
        homography double precision[],
        calibration_error real,
        revision integer default 0 not null,
        updated_at timestamp with time zone default now() not null,
        updated_by integer,

        constraint parking_configs_camera_fk
            foreign key (camera_id)
            references cameras (camera_id)
            on delete cascade,

        constraint parking_configs_unique_camera
            unique (camera_id),

        constraint parking_configs_enabled_check
            check (enabled in (0, 1)),

        constraint parking_configs_frame_check
            check (frame_width between 16 and 16384 and frame_height between 16 and 16384),

        constraint parking_configs_calibration_model_check
            check (calibration_model in ('homography')),

        constraint parking_configs_calibration_points_check
            check (calibration_points is null or (jsonb_typeof(calibration_points) = 'array' and jsonb_array_length(calibration_points) >= 4)),

        constraint parking_configs_calibration_checks_check
            check (jsonb_typeof(calibration_checks) = 'array'),

        constraint parking_configs_homography_check
            check (homography is null or (array_ndims(homography) = 1 and array_length(homography, 1) = 9)),

        constraint parking_configs_calibration_consistency_check
            check ((homography is null) = (calibration_points is null)),

        constraint parking_configs_revision_check
            check (revision >= 0)
    );

    comment on table parking_configs
        is 'Parking configuration of a camera';

    comment on column parking_configs.camera_id
        is 'Camera identifier';

    comment on column parking_configs.enabled
        is 'Whether the configuration should be processed by the parking service';

    comment on column parking_configs.frame_width
        is 'Width in pixels of the reference frame used for markup';

    comment on column parking_configs.frame_height
        is 'Height in pixels of the reference frame used for markup';

    comment on column parking_configs.calibration_model
        is 'Geometric model mapping the frame to the parking plane';

    comment on column parking_configs.calibration_points
        is 'Calibration correspondences: array of {u, v, x, y}, u and v are normalized frame coordinates 0..1, x and y are metres';

    comment on column parking_configs.calibration_checks
        is 'Control segments: array of {u1, v1, u2, v2, length}, length in metres';

    comment on column parking_configs.homography
        is 'Row-major 3x3 homography from normalized frame coordinates to metres, unit Frobenius norm, w > 0 inside the calibrated area, computed by the server';

    comment on column parking_configs.calibration_error
        is 'Maximum relative error of control segments or reprojection RMS';

    comment on column parking_configs.revision
        is 'Number of the last saved revision';

    create table if not exists parking_areas
    (
        parking_area_id serial primary key,
        parking_config_id integer not null,
        name character varying(64) default '' not null,
        sort_order integer default 0 not null,
        enabled integer default 1 not null,
        image_points jsonb not null,
        base_edge smallint default 0 not null,
        space_width numeric(5, 2) default 2.5 not null,
        space_length numeric(5, 2) default 5 not null,
        space_angle numeric(5, 2) default 0 not null,
        capacity integer,

        constraint parking_areas_config_fk
            foreign key (parking_config_id)
            references parking_configs (parking_config_id)
            on delete cascade,

        constraint parking_areas_enabled_check
            check (enabled in (0, 1)),

        constraint parking_areas_image_points_check
            check (jsonb_typeof(image_points) = 'array' and jsonb_array_length(image_points) = 4),

        constraint parking_areas_base_edge_check
            check (base_edge between 0 and 3),

        constraint parking_areas_space_width_check
            check (space_width between 1.5 and 6),

        constraint parking_areas_space_length_check
            check (space_length between 2 and 25),

        constraint parking_areas_space_angle_check
            check (space_angle between -90 and 90),

        constraint parking_areas_capacity_check
            check (capacity is null or capacity >= 0)
    );

    comment on table parking_areas
        is 'Parking areas marked on a camera frame';

    comment on column parking_areas.image_points
        is 'Four vertices {u, v} in normalized frame coordinates 0..1, clockwise on screen';

    comment on column parking_areas.base_edge
        is 'Index i of the edge from vertex i to vertex i + 1 along which the row of spaces is placed';

    comment on column parking_areas.space_width
        is 'Parking space width in metres';

    comment on column parking_areas.space_length
        is 'Parking space length in metres';

    comment on column parking_areas.space_angle
        is 'Angle in degrees between the space long axis and the base edge normal';

    comment on column parking_areas.capacity
        is 'Number of spaces computed by the server, null when not calibrated';

    create index if not exists parking_areas_parking_config_id_index
        on parking_areas (parking_config_id);

    create table if not exists parking_config_revisions
    (
        parking_config_id integer not null,
        revision integer not null,
        camera_id integer not null,
        snapshot jsonb not null,
        ready integer not null,
        created_at timestamp with time zone default now() not null,
        created_by integer,

        constraint parking_config_revisions_pk
            primary key (parking_config_id, revision),

        constraint parking_config_revisions_config_fk
            foreign key (parking_config_id)
            references parking_configs (parking_config_id)
            on delete cascade,

        constraint parking_config_revisions_camera_fk
            foreign key (camera_id)
            references cameras (camera_id)
            on delete cascade,

        constraint parking_config_revisions_ready_check
            check (ready in (0, 1))
    );

    comment on table parking_config_revisions
        is 'Immutable snapshots of parking configurations used by the parking service';

    comment on column parking_config_revisions.snapshot
        is 'Normalized configuration snapshot (snapshotVersion 1)';

    comment on column parking_config_revisions.ready
        is 'Whether the configuration was ready for processing when saved';

    create index if not exists parking_config_revisions_camera_id_index
        on parking_config_revisions (camera_id, revision desc);
end;
$$;

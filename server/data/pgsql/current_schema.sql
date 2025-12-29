--
-- PostgreSQL database dump
--

\restrict TRZyKC11xO1yBqdeDsDayRekfdDAEGhznasCuu3AKDwwbjDN8ecegEzbdqtr52a

-- Dumped from database version 17.7
-- Dumped by pg_dump version 18.1

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: public; Type: SCHEMA; Schema: -; Owner: postgres
--

-- *not* creating schema, since initdb creates it


ALTER SCHEMA public OWNER TO postgres;

--
-- Name: fuzzystrmatch; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS fuzzystrmatch WITH SCHEMA public;


--
-- Name: EXTENSION fuzzystrmatch; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION fuzzystrmatch IS 'determine similarities and distance between strings';


--
-- Name: pg_trgm; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS pg_trgm WITH SCHEMA public;


--
-- Name: EXTENSION pg_trgm; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION pg_trgm IS 'text similarity measurement and index searching based on trigrams';


SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: addresses_areas; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.addresses_areas (
    address_area_id integer NOT NULL,
    address_region_id integer NOT NULL,
    area_uuid character varying,
    area_with_type character varying NOT NULL,
    area_type character varying,
    area_type_full character varying,
    area character varying NOT NULL,
    lat real,
    lon real,
    timezone character varying
);


ALTER TABLE public.addresses_areas OWNER TO rbt;

--
-- Name: addresses_areas_address_area_id_seq; Type: SEQUENCE; Schema: public; Owner: rbt
--

CREATE SEQUENCE public.addresses_areas_address_area_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.addresses_areas_address_area_id_seq OWNER TO rbt;

--
-- Name: addresses_areas_address_area_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rbt
--

ALTER SEQUENCE public.addresses_areas_address_area_id_seq OWNED BY public.addresses_areas.address_area_id;


--
-- Name: addresses_cities; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.addresses_cities (
    address_city_id integer NOT NULL,
    address_region_id integer,
    address_area_id integer,
    city_uuid character varying,
    city_with_type character varying NOT NULL,
    city_type character varying,
    city_type_full character varying,
    city character varying NOT NULL,
    lat real,
    lon real,
    timezone character varying
);


ALTER TABLE public.addresses_cities OWNER TO rbt;

--
-- Name: addresses_cities_address_city_id_seq; Type: SEQUENCE; Schema: public; Owner: rbt
--

CREATE SEQUENCE public.addresses_cities_address_city_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.addresses_cities_address_city_id_seq OWNER TO rbt;

--
-- Name: addresses_cities_address_city_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rbt
--

ALTER SEQUENCE public.addresses_cities_address_city_id_seq OWNED BY public.addresses_cities.address_city_id;


--
-- Name: addresses_favorites; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.addresses_favorites (
    login character varying,
    object character varying,
    id integer,
    title character varying,
    icon character varying,
    color character varying
);


ALTER TABLE public.addresses_favorites OWNER TO rbt;

--
-- Name: addresses_houses; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.addresses_houses (
    address_house_id integer NOT NULL,
    address_settlement_id integer,
    address_street_id integer,
    house_uuid character varying,
    house_type character varying,
    house_type_full character varying,
    house_full character varying NOT NULL,
    house character varying NOT NULL,
    lat real,
    lon real,
    company integer,
    house_lat real,
    house_lon real,
    company_id integer DEFAULT 0
);


ALTER TABLE public.addresses_houses OWNER TO rbt;

--
-- Name: addresses_houses_address_house_id_seq; Type: SEQUENCE; Schema: public; Owner: rbt
--

CREATE SEQUENCE public.addresses_houses_address_house_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.addresses_houses_address_house_id_seq OWNER TO rbt;

--
-- Name: addresses_houses_address_house_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rbt
--

ALTER SEQUENCE public.addresses_houses_address_house_id_seq OWNED BY public.addresses_houses.address_house_id;


--
-- Name: addresses_regions; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.addresses_regions (
    address_region_id integer NOT NULL,
    region_uuid character varying,
    region_iso_code character varying,
    region_with_type character varying NOT NULL,
    region_type character varying,
    region_type_full character varying,
    region character varying NOT NULL,
    timezone character varying,
    lat real,
    lon real
);


ALTER TABLE public.addresses_regions OWNER TO rbt;

--
-- Name: addresses_regions_address_region_id_seq; Type: SEQUENCE; Schema: public; Owner: rbt
--

CREATE SEQUENCE public.addresses_regions_address_region_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.addresses_regions_address_region_id_seq OWNER TO rbt;

--
-- Name: addresses_regions_address_region_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rbt
--

ALTER SEQUENCE public.addresses_regions_address_region_id_seq OWNED BY public.addresses_regions.address_region_id;


--
-- Name: addresses_settlements; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.addresses_settlements (
    address_settlement_id integer NOT NULL,
    address_area_id integer,
    address_city_id integer,
    settlement_uuid character varying,
    settlement_with_type character varying NOT NULL,
    settlement_type character varying,
    settlement_type_full character varying,
    settlement character varying NOT NULL,
    lat real,
    lon real
);


ALTER TABLE public.addresses_settlements OWNER TO rbt;

--
-- Name: addresses_settlements_address_settlement_id_seq; Type: SEQUENCE; Schema: public; Owner: rbt
--

CREATE SEQUENCE public.addresses_settlements_address_settlement_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.addresses_settlements_address_settlement_id_seq OWNER TO rbt;

--
-- Name: addresses_settlements_address_settlement_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rbt
--

ALTER SEQUENCE public.addresses_settlements_address_settlement_id_seq OWNED BY public.addresses_settlements.address_settlement_id;


--
-- Name: addresses_streets; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.addresses_streets (
    address_street_id integer NOT NULL,
    address_city_id integer,
    address_settlement_id integer,
    street_uuid character varying,
    street_with_type character varying NOT NULL,
    street_type character varying,
    street_type_full character varying,
    street character varying NOT NULL,
    lat real,
    lon real
);


ALTER TABLE public.addresses_streets OWNER TO rbt;

--
-- Name: addresses_streets_address_street_id_seq; Type: SEQUENCE; Schema: public; Owner: rbt
--

CREATE SEQUENCE public.addresses_streets_address_street_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.addresses_streets_address_street_id_seq OWNER TO rbt;

--
-- Name: addresses_streets_address_street_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rbt
--

ALTER SEQUENCE public.addresses_streets_address_street_id_seq OWNED BY public.addresses_streets.address_street_id;


--
-- Name: camera_records; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.camera_records (
    record_id integer NOT NULL,
    camera_id integer NOT NULL,
    subscriber_id integer NOT NULL,
    start integer,
    finish integer,
    filename text,
    expire integer,
    state integer
);


ALTER TABLE public.camera_records OWNER TO rbt;

--
-- Name: camera_records_record_id_seq; Type: SEQUENCE; Schema: public; Owner: rbt
--

CREATE SEQUENCE public.camera_records_record_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.camera_records_record_id_seq OWNER TO rbt;

--
-- Name: camera_records_record_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rbt
--

ALTER SEQUENCE public.camera_records_record_id_seq OWNED BY public.camera_records.record_id;


--
-- Name: cameras; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.cameras (
    camera_id integer NOT NULL,
    enabled integer NOT NULL,
    model character varying NOT NULL,
    url character varying NOT NULL,
    stream character varying,
    credentials character varying NOT NULL,
    name character varying,
    dvr_stream character varying,
    lat real,
    lon real,
    direction real,
    angle real,
    distance real,
    frs character varying,
    common integer,
    ip text,
    timezone character varying,
    sub_id character varying,
    sound integer DEFAULT 0 NOT NULL,
    comments character varying,
    md_area character varying,
    rc_area character varying,
    frs_mode integer DEFAULT 1,
    ext character varying,
    monitoring integer DEFAULT 1,
    webrtc integer DEFAULT 0
);


ALTER TABLE public.cameras OWNER TO rbt;

--
-- Name: cameras_camera_id_seq; Type: SEQUENCE; Schema: public; Owner: rbt
--

CREATE SEQUENCE public.cameras_camera_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.cameras_camera_id_seq OWNER TO rbt;

--
-- Name: cameras_camera_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rbt
--

ALTER SEQUENCE public.cameras_camera_id_seq OWNED BY public.cameras.camera_id;


--
-- Name: companies; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.companies (
    company_id integer NOT NULL,
    name character varying,
    uid character varying,
    contacts character varying,
    company_type integer,
    comments character varying
);


ALTER TABLE public.companies OWNER TO rbt;

--
-- Name: companies_company_id_seq; Type: SEQUENCE; Schema: public; Owner: rbt
--

CREATE SEQUENCE public.companies_company_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.companies_company_id_seq OWNER TO rbt;

--
-- Name: companies_company_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rbt
--

ALTER SEQUENCE public.companies_company_id_seq OWNED BY public.companies.company_id;


--
-- Name: core_api_methods; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.core_api_methods (
    aid character varying NOT NULL,
    api character varying NOT NULL,
    method character varying NOT NULL,
    request_method character varying NOT NULL,
    permissions_same character varying
);


ALTER TABLE public.core_api_methods OWNER TO rbt;

--
-- Name: core_api_methods_by_backend; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.core_api_methods_by_backend (
    aid character varying NOT NULL,
    backend character varying
);


ALTER TABLE public.core_api_methods_by_backend OWNER TO rbt;

--
-- Name: core_api_methods_common; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.core_api_methods_common (
    aid character varying NOT NULL
);


ALTER TABLE public.core_api_methods_common OWNER TO rbt;

--
-- Name: core_api_methods_personal; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.core_api_methods_personal (
    aid character varying NOT NULL
);


ALTER TABLE public.core_api_methods_personal OWNER TO rbt;

--
-- Name: core_groups; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.core_groups (
    gid integer NOT NULL,
    acronym character varying NOT NULL,
    name character varying NOT NULL,
    admin integer
);


ALTER TABLE public.core_groups OWNER TO rbt;

--
-- Name: core_groups_gid_seq; Type: SEQUENCE; Schema: public; Owner: rbt
--

CREATE SEQUENCE public.core_groups_gid_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.core_groups_gid_seq OWNER TO rbt;

--
-- Name: core_groups_gid_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rbt
--

ALTER SEQUENCE public.core_groups_gid_seq OWNED BY public.core_groups.gid;


--
-- Name: core_groups_rights; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.core_groups_rights (
    gid integer NOT NULL,
    aid character varying NOT NULL,
    allow integer
);


ALTER TABLE public.core_groups_rights OWNER TO rbt;

--
-- Name: core_inbox; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.core_inbox (
    msg_id integer NOT NULL,
    msg_date integer,
    msg_from character varying,
    msg_to character varying,
    msg_subject character varying,
    msg_type character varying,
    msg_body character varying,
    msg_readed integer,
    msg_handler character varying
);


ALTER TABLE public.core_inbox OWNER TO rbt;

--
-- Name: core_inbox_msg_id_seq; Type: SEQUENCE; Schema: public; Owner: rbt
--

CREATE SEQUENCE public.core_inbox_msg_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.core_inbox_msg_id_seq OWNER TO rbt;

--
-- Name: core_inbox_msg_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rbt
--

ALTER SEQUENCE public.core_inbox_msg_id_seq OWNED BY public.core_inbox.msg_id;


--
-- Name: core_running_processes; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.core_running_processes (
    running_process_id integer NOT NULL,
    pid integer,
    ppid integer,
    start integer,
    process character varying,
    params character varying,
    done integer,
    result character varying,
    expire integer
);


ALTER TABLE public.core_running_processes OWNER TO rbt;

--
-- Name: core_running_processes_running_process_id_seq; Type: SEQUENCE; Schema: public; Owner: rbt
--

CREATE SEQUENCE public.core_running_processes_running_process_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.core_running_processes_running_process_id_seq OWNER TO rbt;

--
-- Name: core_running_processes_running_process_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rbt
--

ALTER SEQUENCE public.core_running_processes_running_process_id_seq OWNED BY public.core_running_processes.running_process_id;


--
-- Name: core_users; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.core_users (
    uid integer NOT NULL,
    login character varying NOT NULL,
    password character varying NOT NULL,
    enabled integer,
    real_name character varying,
    e_mail character varying,
    phone character varying,
    default_route character varying,
    tg character varying,
    notification character varying,
    primary_group integer,
    secret character varying,
    settings character varying,
    avatar character varying,
    service_account integer DEFAULT 0,
    sudo integer DEFAULT 0
);


ALTER TABLE public.core_users OWNER TO rbt;

--
-- Name: core_users_groups; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.core_users_groups (
    uid integer,
    gid integer
);


ALTER TABLE public.core_users_groups OWNER TO rbt;

--
-- Name: core_users_notifications; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.core_users_notifications (
    notification_id integer NOT NULL,
    uid integer,
    created integer,
    sended integer,
    delivered integer,
    readed integer,
    caption character varying,
    body character varying,
    data character varying
);


ALTER TABLE public.core_users_notifications OWNER TO rbt;

--
-- Name: core_users_notifications_notification_id_seq; Type: SEQUENCE; Schema: public; Owner: rbt
--

CREATE SEQUENCE public.core_users_notifications_notification_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.core_users_notifications_notification_id_seq OWNER TO rbt;

--
-- Name: core_users_notifications_notification_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rbt
--

ALTER SEQUENCE public.core_users_notifications_notification_id_seq OWNED BY public.core_users_notifications.notification_id;


--
-- Name: core_users_notifications_queue; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.core_users_notifications_queue (
    notification_id integer NOT NULL,
    login character varying,
    uid integer,
    subject character varying,
    message character varying
);


ALTER TABLE public.core_users_notifications_queue OWNER TO rbt;

--
-- Name: core_users_notifications_queue_notification_id_seq; Type: SEQUENCE; Schema: public; Owner: rbt
--

CREATE SEQUENCE public.core_users_notifications_queue_notification_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.core_users_notifications_queue_notification_id_seq OWNER TO rbt;

--
-- Name: core_users_notifications_queue_notification_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rbt
--

ALTER SEQUENCE public.core_users_notifications_queue_notification_id_seq OWNED BY public.core_users_notifications_queue.notification_id;


--
-- Name: core_users_rights; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.core_users_rights (
    uid integer NOT NULL,
    aid character varying NOT NULL,
    allow integer
);


ALTER TABLE public.core_users_rights OWNER TO rbt;

--
-- Name: core_users_tokens; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.core_users_tokens (
    uid integer NOT NULL,
    token character varying
);


ALTER TABLE public.core_users_tokens OWNER TO rbt;

--
-- Name: core_users_uid_seq; Type: SEQUENCE; Schema: public; Owner: rbt
--

CREATE SEQUENCE public.core_users_uid_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.core_users_uid_seq OWNER TO rbt;

--
-- Name: core_users_uid_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rbt
--

ALTER SEQUENCE public.core_users_uid_seq OWNED BY public.core_users.uid;


--
-- Name: core_vars; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.core_vars (
    var_id integer NOT NULL,
    var_name character varying NOT NULL,
    var_value character varying
);


ALTER TABLE public.core_vars OWNER TO rbt;

--
-- Name: core_vars_var_id_seq; Type: SEQUENCE; Schema: public; Owner: rbt
--

CREATE SEQUENCE public.core_vars_var_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.core_vars_var_id_seq OWNER TO rbt;

--
-- Name: core_vars_var_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rbt
--

ALTER SEQUENCE public.core_vars_var_id_seq OWNED BY public.core_vars.var_id;


--
-- Name: custom_fields; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.custom_fields (
    custom_field_id integer NOT NULL,
    apply_to character varying,
    catalog character varying,
    type character varying,
    field character varying,
    field_display character varying,
    field_description character varying,
    regex character varying,
    link character varying,
    format character varying,
    editor character varying,
    indx integer,
    search integer,
    required integer,
    magic_class character varying,
    magic_function character varying,
    magic_hint character varying,
    add integer DEFAULT 0,
    modify integer DEFAULT 1,
    tab character varying,
    weight integer DEFAULT 0
);


ALTER TABLE public.custom_fields OWNER TO rbt;

--
-- Name: custom_fields_custom_field_id_seq; Type: SEQUENCE; Schema: public; Owner: rbt
--

CREATE SEQUENCE public.custom_fields_custom_field_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.custom_fields_custom_field_id_seq OWNER TO rbt;

--
-- Name: custom_fields_custom_field_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rbt
--

ALTER SEQUENCE public.custom_fields_custom_field_id_seq OWNED BY public.custom_fields.custom_field_id;


--
-- Name: custom_fields_options; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.custom_fields_options (
    custom_field_option_id integer NOT NULL,
    custom_field_id integer,
    option character varying,
    display_order integer,
    option_display character varying
);


ALTER TABLE public.custom_fields_options OWNER TO rbt;

--
-- Name: custom_fields_options_custom_field_option_id_seq; Type: SEQUENCE; Schema: public; Owner: rbt
--

CREATE SEQUENCE public.custom_fields_options_custom_field_option_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.custom_fields_options_custom_field_option_id_seq OWNER TO rbt;

--
-- Name: custom_fields_options_custom_field_option_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rbt
--

ALTER SEQUENCE public.custom_fields_options_custom_field_option_id_seq OWNED BY public.custom_fields_options.custom_field_option_id;


--
-- Name: custom_fields_values; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.custom_fields_values (
    custom_fields_value_id integer NOT NULL,
    apply_to character varying,
    id integer,
    field character varying,
    value character varying
);


ALTER TABLE public.custom_fields_values OWNER TO rbt;

--
-- Name: custom_fields_values_custom_fields_value_id_seq; Type: SEQUENCE; Schema: public; Owner: rbt
--

CREATE SEQUENCE public.custom_fields_values_custom_fields_value_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.custom_fields_values_custom_fields_value_id_seq OWNER TO rbt;

--
-- Name: custom_fields_values_custom_fields_value_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rbt
--

ALTER SEQUENCE public.custom_fields_values_custom_fields_value_id_seq OWNED BY public.custom_fields_values.custom_fields_value_id;


--
-- Name: frs_faces; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.frs_faces (
    face_id integer NOT NULL,
    face_uuid character varying NOT NULL,
    event_uuid character varying NOT NULL
);


ALTER TABLE public.frs_faces OWNER TO rbt;

--
-- Name: frs_links_faces; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.frs_links_faces (
    flat_id integer NOT NULL,
    house_subscriber_id integer NOT NULL,
    face_id integer NOT NULL
);


ALTER TABLE public.frs_links_faces OWNER TO rbt;

--
-- Name: houses_cameras_flats; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.houses_cameras_flats (
    camera_id integer NOT NULL,
    house_flat_id integer NOT NULL,
    path character varying
);


ALTER TABLE public.houses_cameras_flats OWNER TO rbt;

--
-- Name: houses_cameras_houses; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.houses_cameras_houses (
    camera_id integer NOT NULL,
    address_house_id integer NOT NULL,
    path character varying
);


ALTER TABLE public.houses_cameras_houses OWNER TO rbt;

--
-- Name: houses_cameras_subscribers; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.houses_cameras_subscribers (
    camera_id integer NOT NULL,
    house_subscriber_id integer NOT NULL
);


ALTER TABLE public.houses_cameras_subscribers OWNER TO rbt;

--
-- Name: houses_domophones; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.houses_domophones (
    house_domophone_id integer NOT NULL,
    enabled integer NOT NULL,
    model character varying NOT NULL,
    server character varying NOT NULL,
    url character varying NOT NULL,
    credentials character varying NOT NULL,
    dtmf character varying NOT NULL,
    first_time integer DEFAULT 1,
    nat integer,
    locks_are_open integer DEFAULT 1,
    ip text,
    sub_id character varying,
    name character varying,
    comments character varying,
    display character varying,
    video character varying,
    ext character varying,
    monitoring integer DEFAULT 1,
    sos character varying,
    concierge character varying
);


ALTER TABLE public.houses_domophones OWNER TO rbt;

--
-- Name: houses_domophones_house_domophone_id_seq; Type: SEQUENCE; Schema: public; Owner: rbt
--

CREATE SEQUENCE public.houses_domophones_house_domophone_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.houses_domophones_house_domophone_id_seq OWNER TO rbt;

--
-- Name: houses_domophones_house_domophone_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rbt
--

ALTER SEQUENCE public.houses_domophones_house_domophone_id_seq OWNED BY public.houses_domophones.house_domophone_id;


--
-- Name: houses_entrances; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.houses_entrances (
    house_entrance_id integer NOT NULL,
    entrance_type character varying,
    entrance character varying NOT NULL,
    lat real,
    lon real,
    shared integer,
    plog integer,
    caller_id character varying,
    camera_id integer,
    house_domophone_id integer NOT NULL,
    domophone_output integer,
    cms character varying,
    cms_type integer,
    cms_levels character varying,
    locks_disabled integer,
    path character varying,
    distance integer,
    alt_camera_id_1 integer,
    alt_camera_id_2 integer,
    alt_camera_id_3 integer,
    alt_camera_id_4 integer,
    alt_camera_id_5 integer,
    alt_camera_id_6 integer,
    alt_camera_id_7 integer
);


ALTER TABLE public.houses_entrances OWNER TO rbt;

--
-- Name: houses_entrances_cmses; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.houses_entrances_cmses (
    house_entrance_id integer NOT NULL,
    cms character varying NOT NULL,
    dozen integer NOT NULL,
    unit character varying NOT NULL,
    apartment integer NOT NULL
);


ALTER TABLE public.houses_entrances_cmses OWNER TO rbt;

--
-- Name: houses_entrances_flats; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.houses_entrances_flats (
    house_entrance_id integer NOT NULL,
    house_flat_id integer NOT NULL,
    apartment integer,
    cms_levels character varying
);


ALTER TABLE public.houses_entrances_flats OWNER TO rbt;

--
-- Name: houses_entrances_house_entrance_id_seq; Type: SEQUENCE; Schema: public; Owner: rbt
--

CREATE SEQUENCE public.houses_entrances_house_entrance_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.houses_entrances_house_entrance_id_seq OWNER TO rbt;

--
-- Name: houses_entrances_house_entrance_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rbt
--

ALTER SEQUENCE public.houses_entrances_house_entrance_id_seq OWNED BY public.houses_entrances.house_entrance_id;


--
-- Name: houses_flats; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.houses_flats (
    house_flat_id integer NOT NULL,
    address_house_id integer NOT NULL,
    floor integer,
    flat character varying NOT NULL,
    code character varying,
    plog integer,
    manual_block integer,
    auto_block integer,
    open_code character varying,
    auto_open integer,
    white_rabbit integer,
    sip_enabled integer,
    sip_password character varying,
    last_opened integer,
    cms_enabled integer,
    admin_block integer,
    contract character varying,
    login character varying,
    password character varying,
    cars character varying,
    subscribers_limit integer DEFAULT '-1'::integer
);


ALTER TABLE public.houses_flats OWNER TO rbt;

--
-- Name: houses_flats_devices; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.houses_flats_devices (
    house_flat_id integer NOT NULL,
    subscriber_device_id integer NOT NULL,
    voip_enabled integer,
    houses_flat_device_id integer NOT NULL
);


ALTER TABLE public.houses_flats_devices OWNER TO rbt;

--
-- Name: houses_flats_devices_houses_flat_device_id_seq; Type: SEQUENCE; Schema: public; Owner: rbt
--

CREATE SEQUENCE public.houses_flats_devices_houses_flat_device_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.houses_flats_devices_houses_flat_device_id_seq OWNER TO rbt;

--
-- Name: houses_flats_devices_houses_flat_device_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rbt
--

ALTER SEQUENCE public.houses_flats_devices_houses_flat_device_id_seq OWNED BY public.houses_flats_devices.houses_flat_device_id;


--
-- Name: houses_flats_house_flat_id_seq; Type: SEQUENCE; Schema: public; Owner: rbt
--

CREATE SEQUENCE public.houses_flats_house_flat_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.houses_flats_house_flat_id_seq OWNER TO rbt;

--
-- Name: houses_flats_house_flat_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rbt
--

ALTER SEQUENCE public.houses_flats_house_flat_id_seq OWNED BY public.houses_flats.house_flat_id;


--
-- Name: houses_flats_subscribers; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.houses_flats_subscribers (
    house_flat_id integer NOT NULL,
    house_subscriber_id integer NOT NULL,
    role integer,
    voip_enabled integer DEFAULT 1
);


ALTER TABLE public.houses_flats_subscribers OWNER TO rbt;

--
-- Name: houses_houses_entrances; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.houses_houses_entrances (
    address_house_id integer NOT NULL,
    house_entrance_id integer NOT NULL,
    prefix integer NOT NULL
);


ALTER TABLE public.houses_houses_entrances OWNER TO rbt;

--
-- Name: houses_paths; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.houses_paths (
    house_path_id integer NOT NULL,
    house_path_tree character varying DEFAULT 'default'::character varying,
    house_path_parent integer,
    house_path_name character varying,
    house_path_icon character varying
);


ALTER TABLE public.houses_paths OWNER TO rbt;

--
-- Name: houses_paths_house_path_id_seq; Type: SEQUENCE; Schema: public; Owner: rbt
--

CREATE SEQUENCE public.houses_paths_house_path_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.houses_paths_house_path_id_seq OWNER TO rbt;

--
-- Name: houses_paths_house_path_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rbt
--

ALTER SEQUENCE public.houses_paths_house_path_id_seq OWNED BY public.houses_paths.house_path_id;


--
-- Name: houses_rfids; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.houses_rfids (
    house_rfid_id integer NOT NULL,
    rfid character varying NOT NULL,
    access_type integer NOT NULL,
    access_to integer NOT NULL,
    last_seen integer,
    comments character varying
);


ALTER TABLE public.houses_rfids OWNER TO rbt;

--
-- Name: houses_rfids_house_rfid_id_seq; Type: SEQUENCE; Schema: public; Owner: rbt
--

CREATE SEQUENCE public.houses_rfids_house_rfid_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.houses_rfids_house_rfid_id_seq OWNER TO rbt;

--
-- Name: houses_rfids_house_rfid_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rbt
--

ALTER SEQUENCE public.houses_rfids_house_rfid_id_seq OWNED BY public.houses_rfids.house_rfid_id;


--
-- Name: houses_subscribers_devices; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.houses_subscribers_devices (
    subscriber_device_id integer NOT NULL,
    house_subscriber_id integer,
    device_token character varying,
    auth_token character varying,
    platform integer,
    push_token character varying,
    push_token_type integer,
    voip_token character varying,
    registered integer,
    last_seen integer,
    voip_enabled integer,
    ua character varying,
    ip text,
    push_disable integer DEFAULT 0,
    money_disable integer DEFAULT 0,
    version character varying,
    bundle character varying DEFAULT 'default'::character varying
);


ALTER TABLE public.houses_subscribers_devices OWNER TO rbt;

--
-- Name: houses_subscribers_devices_subscriber_device_id_seq; Type: SEQUENCE; Schema: public; Owner: rbt
--

CREATE SEQUENCE public.houses_subscribers_devices_subscriber_device_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.houses_subscribers_devices_subscriber_device_id_seq OWNER TO rbt;

--
-- Name: houses_subscribers_devices_subscriber_device_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rbt
--

ALTER SEQUENCE public.houses_subscribers_devices_subscriber_device_id_seq OWNED BY public.houses_subscribers_devices.subscriber_device_id;


--
-- Name: houses_subscribers_messages; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.houses_subscribers_messages (
    bulk_message_id integer NOT NULL,
    house_subscriber_id integer,
    title character varying,
    msg character varying,
    action character varying
);


ALTER TABLE public.houses_subscribers_messages OWNER TO rbt;

--
-- Name: houses_subscribers_messages_bulk_message_id_seq; Type: SEQUENCE; Schema: public; Owner: rbt
--

CREATE SEQUENCE public.houses_subscribers_messages_bulk_message_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.houses_subscribers_messages_bulk_message_id_seq OWNER TO rbt;

--
-- Name: houses_subscribers_messages_bulk_message_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rbt
--

ALTER SEQUENCE public.houses_subscribers_messages_bulk_message_id_seq OWNED BY public.houses_subscribers_messages.bulk_message_id;


--
-- Name: houses_subscribers_mobile; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.houses_subscribers_mobile (
    house_subscriber_id integer NOT NULL,
    id character varying,
    registered integer,
    subscriber_name character varying,
    subscriber_patronymic character varying,
    subscriber_last character varying,
    subscriber_full character varying
);


ALTER TABLE public.houses_subscribers_mobile OWNER TO rbt;

--
-- Name: houses_subscribers_mobile_house_subscriber_id_seq; Type: SEQUENCE; Schema: public; Owner: rbt
--

CREATE SEQUENCE public.houses_subscribers_mobile_house_subscriber_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.houses_subscribers_mobile_house_subscriber_id_seq OWNER TO rbt;

--
-- Name: houses_subscribers_mobile_house_subscriber_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rbt
--

ALTER SEQUENCE public.houses_subscribers_mobile_house_subscriber_id_seq OWNED BY public.houses_subscribers_mobile.house_subscriber_id;


--
-- Name: houses_watchers; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.houses_watchers (
    house_watcher_id integer NOT NULL,
    subscriber_device_id integer,
    house_flat_id integer,
    event_type character varying,
    event_detail character varying,
    comments character varying
);


ALTER TABLE public.houses_watchers OWNER TO rbt;

--
-- Name: houses_watchers_house_watcher_id_seq; Type: SEQUENCE; Schema: public; Owner: rbt
--

CREATE SEQUENCE public.houses_watchers_house_watcher_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.houses_watchers_house_watcher_id_seq OWNER TO rbt;

--
-- Name: houses_watchers_house_watcher_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rbt
--

ALTER SEQUENCE public.houses_watchers_house_watcher_id_seq OWNED BY public.houses_watchers.house_watcher_id;


--
-- Name: inbox; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.inbox (
    msg_id integer NOT NULL,
    id character varying NOT NULL,
    date integer,
    title character varying,
    msg character varying NOT NULL,
    action character varying,
    expire integer,
    push_message_id text,
    delivered integer,
    readed integer,
    code character varying
);


ALTER TABLE public.inbox OWNER TO rbt;

--
-- Name: inbox_msg_id_seq; Type: SEQUENCE; Schema: public; Owner: rbt
--

CREATE SEQUENCE public.inbox_msg_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.inbox_msg_id_seq OWNER TO rbt;

--
-- Name: inbox_msg_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rbt
--

ALTER SEQUENCE public.inbox_msg_id_seq OWNED BY public.inbox.msg_id;


--
-- Name: notes; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.notes (
    note_id integer NOT NULL,
    create_date integer,
    owner character varying,
    note_subject character varying,
    note_body character varying,
    category character varying,
    remind integer DEFAULT 0,
    icon character varying,
    font character varying,
    color character varying,
    reminded integer DEFAULT 0,
    position_left real,
    position_top real,
    position_order integer,
    note_type character varying,
    fyeo integer DEFAULT 0
);


ALTER TABLE public.notes OWNER TO rbt;

--
-- Name: notes_note_id_seq; Type: SEQUENCE; Schema: public; Owner: rbt
--

CREATE SEQUENCE public.notes_note_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.notes_note_id_seq OWNER TO rbt;

--
-- Name: notes_note_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rbt
--

ALTER SEQUENCE public.notes_note_id_seq OWNED BY public.notes.note_id;


--
-- Name: plog_call_done; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.plog_call_done (
    plog_call_done_id integer NOT NULL,
    date integer,
    ip character varying,
    call_id integer,
    expire integer,
    sub_id character varying
);


ALTER TABLE public.plog_call_done OWNER TO rbt;

--
-- Name: plog_call_done_plog_call_done_id_seq; Type: SEQUENCE; Schema: public; Owner: rbt
--

CREATE SEQUENCE public.plog_call_done_plog_call_done_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.plog_call_done_plog_call_done_id_seq OWNER TO rbt;

--
-- Name: plog_call_done_plog_call_done_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rbt
--

ALTER SEQUENCE public.plog_call_done_plog_call_done_id_seq OWNED BY public.plog_call_done.plog_call_done_id;


--
-- Name: plog_door_open; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.plog_door_open (
    plog_door_open_id integer NOT NULL,
    date integer,
    ip character varying,
    event integer,
    door integer,
    detail character varying,
    expire integer,
    sub_id character varying
);


ALTER TABLE public.plog_door_open OWNER TO rbt;

--
-- Name: plog_door_open_plog_door_open_id_seq; Type: SEQUENCE; Schema: public; Owner: rbt
--

CREATE SEQUENCE public.plog_door_open_plog_door_open_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.plog_door_open_plog_door_open_id_seq OWNER TO rbt;

--
-- Name: plog_door_open_plog_door_open_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rbt
--

ALTER SEQUENCE public.plog_door_open_plog_door_open_id_seq OWNED BY public.plog_door_open.plog_door_open_id;


--
-- Name: providers; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.providers (
    provider_id integer NOT NULL,
    id character varying NOT NULL,
    name character varying,
    base_url character varying,
    logo character varying,
    token_common character varying,
    token_flash_call character varying,
    token_sms character varying,
    hidden integer
);


ALTER TABLE public.providers OWNER TO rbt;

--
-- Name: providers_provider_id_seq; Type: SEQUENCE; Schema: public; Owner: rbt
--

CREATE SEQUENCE public.providers_provider_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.providers_provider_id_seq OWNER TO rbt;

--
-- Name: providers_provider_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rbt
--

ALTER SEQUENCE public.providers_provider_id_seq OWNED BY public.providers.provider_id;


--
-- Name: tasks_changes; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.tasks_changes (
    task_change_id integer NOT NULL,
    object_type character varying,
    object_id character varying
);


ALTER TABLE public.tasks_changes OWNER TO rbt;

--
-- Name: tasks_changes_task_change_id_seq; Type: SEQUENCE; Schema: public; Owner: rbt
--

CREATE SEQUENCE public.tasks_changes_task_change_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.tasks_changes_task_change_id_seq OWNER TO rbt;

--
-- Name: tasks_changes_task_change_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rbt
--

ALTER SEQUENCE public.tasks_changes_task_change_id_seq OWNED BY public.tasks_changes.task_change_id;


--
-- Name: tt_crontabs; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.tt_crontabs (
    crontab_id integer NOT NULL,
    crontab character varying,
    project_id integer,
    filter character varying,
    uid integer,
    action character varying
);


ALTER TABLE public.tt_crontabs OWNER TO rbt;

--
-- Name: tt_crontabs_crontab_id_seq; Type: SEQUENCE; Schema: public; Owner: rbt
--

CREATE SEQUENCE public.tt_crontabs_crontab_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.tt_crontabs_crontab_id_seq OWNER TO rbt;

--
-- Name: tt_crontabs_crontab_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rbt
--

ALTER SEQUENCE public.tt_crontabs_crontab_id_seq OWNED BY public.tt_crontabs.crontab_id;


--
-- Name: tt_favorite_filters; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.tt_favorite_filters (
    login character varying,
    filter character varying,
    left_side integer DEFAULT 0,
    icon character varying,
    color character varying,
    project character varying DEFAULT 'RTL'::character varying
);


ALTER TABLE public.tt_favorite_filters OWNER TO rbt;

--
-- Name: tt_issue_custom_fields; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.tt_issue_custom_fields (
    issue_custom_field_id integer NOT NULL,
    type character varying NOT NULL,
    field character varying NOT NULL,
    field_display character varying NOT NULL,
    field_description character varying,
    regex character varying,
    link character varying,
    format character varying,
    editor character varying,
    indx integer,
    search integer,
    required integer,
    catalog character varying,
    "float" integer DEFAULT 0,
    readonly integer DEFAULT 0,
    field_display_list character varying
);


ALTER TABLE public.tt_issue_custom_fields OWNER TO rbt;

--
-- Name: tt_issue_custom_fields_issue_custom_field_id_seq; Type: SEQUENCE; Schema: public; Owner: rbt
--

CREATE SEQUENCE public.tt_issue_custom_fields_issue_custom_field_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.tt_issue_custom_fields_issue_custom_field_id_seq OWNER TO rbt;

--
-- Name: tt_issue_custom_fields_issue_custom_field_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rbt
--

ALTER SEQUENCE public.tt_issue_custom_fields_issue_custom_field_id_seq OWNED BY public.tt_issue_custom_fields.issue_custom_field_id;


--
-- Name: tt_issue_custom_fields_options; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.tt_issue_custom_fields_options (
    issue_custom_field_option_id integer NOT NULL,
    issue_custom_field_id integer,
    option character varying NOT NULL,
    option_display character varying,
    display_order integer
);


ALTER TABLE public.tt_issue_custom_fields_options OWNER TO rbt;

--
-- Name: tt_issue_custom_fields_options_issue_custom_field_option_id_seq; Type: SEQUENCE; Schema: public; Owner: rbt
--

CREATE SEQUENCE public.tt_issue_custom_fields_options_issue_custom_field_option_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.tt_issue_custom_fields_options_issue_custom_field_option_id_seq OWNER TO rbt;

--
-- Name: tt_issue_custom_fields_options_issue_custom_field_option_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rbt
--

ALTER SEQUENCE public.tt_issue_custom_fields_options_issue_custom_field_option_id_seq OWNED BY public.tt_issue_custom_fields_options.issue_custom_field_option_id;


--
-- Name: tt_issue_resolutions; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.tt_issue_resolutions (
    issue_resolution_id integer NOT NULL,
    resolution character varying,
    alias character varying
);


ALTER TABLE public.tt_issue_resolutions OWNER TO rbt;

--
-- Name: tt_issue_resolutions_issue_resolution_id_seq; Type: SEQUENCE; Schema: public; Owner: rbt
--

CREATE SEQUENCE public.tt_issue_resolutions_issue_resolution_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.tt_issue_resolutions_issue_resolution_id_seq OWNER TO rbt;

--
-- Name: tt_issue_resolutions_issue_resolution_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rbt
--

ALTER SEQUENCE public.tt_issue_resolutions_issue_resolution_id_seq OWNED BY public.tt_issue_resolutions.issue_resolution_id;


--
-- Name: tt_issue_statuses; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.tt_issue_statuses (
    issue_status_id integer NOT NULL,
    status character varying NOT NULL,
    final integer DEFAULT 0
);


ALTER TABLE public.tt_issue_statuses OWNER TO rbt;

--
-- Name: tt_issue_statuses_issue_status_id_seq; Type: SEQUENCE; Schema: public; Owner: rbt
--

CREATE SEQUENCE public.tt_issue_statuses_issue_status_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.tt_issue_statuses_issue_status_id_seq OWNER TO rbt;

--
-- Name: tt_issue_statuses_issue_status_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rbt
--

ALTER SEQUENCE public.tt_issue_statuses_issue_status_id_seq OWNED BY public.tt_issue_statuses.issue_status_id;


--
-- Name: tt_prints; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.tt_prints (
    tt_print_id integer NOT NULL,
    form_name character varying NOT NULL,
    extension character varying NOT NULL,
    description character varying NOT NULL
);


ALTER TABLE public.tt_prints OWNER TO rbt;

--
-- Name: tt_prints_tt_print_id_seq; Type: SEQUENCE; Schema: public; Owner: rbt
--

CREATE SEQUENCE public.tt_prints_tt_print_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.tt_prints_tt_print_id_seq OWNER TO rbt;

--
-- Name: tt_prints_tt_print_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rbt
--

ALTER SEQUENCE public.tt_prints_tt_print_id_seq OWNED BY public.tt_prints.tt_print_id;


--
-- Name: tt_projects; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.tt_projects (
    project_id integer NOT NULL,
    acronym character varying NOT NULL,
    project character varying NOT NULL,
    max_file_size integer DEFAULT 16777216,
    search_subject integer DEFAULT 1,
    search_description integer DEFAULT 1,
    search_comments integer DEFAULT 1,
    assigned integer DEFAULT 0,
    comments character varying
);


ALTER TABLE public.tt_projects OWNER TO rbt;

--
-- Name: tt_projects_custom_fields; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.tt_projects_custom_fields (
    project_custom_field_id integer NOT NULL,
    project_id integer,
    issue_custom_field_id integer,
    childrens integer DEFAULT '-1'::integer,
    links integer DEFAULT '-1'::integer
);


ALTER TABLE public.tt_projects_custom_fields OWNER TO rbt;

--
-- Name: tt_projects_custom_fields_nojournal; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.tt_projects_custom_fields_nojournal (
    project_custom_field_id integer NOT NULL,
    project_id integer,
    issue_custom_field_id integer
);


ALTER TABLE public.tt_projects_custom_fields_nojournal OWNER TO rbt;

--
-- Name: tt_projects_custom_fields_nojournal_project_custom_field_id_seq; Type: SEQUENCE; Schema: public; Owner: rbt
--

CREATE SEQUENCE public.tt_projects_custom_fields_nojournal_project_custom_field_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.tt_projects_custom_fields_nojournal_project_custom_field_id_seq OWNER TO rbt;

--
-- Name: tt_projects_custom_fields_nojournal_project_custom_field_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rbt
--

ALTER SEQUENCE public.tt_projects_custom_fields_nojournal_project_custom_field_id_seq OWNED BY public.tt_projects_custom_fields_nojournal.project_custom_field_id;


--
-- Name: tt_projects_custom_fields_project_custom_field_id_seq; Type: SEQUENCE; Schema: public; Owner: rbt
--

CREATE SEQUENCE public.tt_projects_custom_fields_project_custom_field_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.tt_projects_custom_fields_project_custom_field_id_seq OWNER TO rbt;

--
-- Name: tt_projects_custom_fields_project_custom_field_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rbt
--

ALTER SEQUENCE public.tt_projects_custom_fields_project_custom_field_id_seq OWNED BY public.tt_projects_custom_fields.project_custom_field_id;


--
-- Name: tt_projects_fields_settings; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.tt_projects_fields_settings (
    tt_projects_field character varying NOT NULL,
    childrens integer DEFAULT '-1'::integer,
    links integer DEFAULT '-1'::integer
);


ALTER TABLE public.tt_projects_fields_settings OWNER TO rbt;

--
-- Name: tt_projects_filters; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.tt_projects_filters (
    project_filter_id integer NOT NULL,
    project_id integer,
    personal integer,
    filter character varying
);


ALTER TABLE public.tt_projects_filters OWNER TO rbt;

--
-- Name: tt_projects_filters_project_filter_id_seq; Type: SEQUENCE; Schema: public; Owner: rbt
--

CREATE SEQUENCE public.tt_projects_filters_project_filter_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.tt_projects_filters_project_filter_id_seq OWNER TO rbt;

--
-- Name: tt_projects_filters_project_filter_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rbt
--

ALTER SEQUENCE public.tt_projects_filters_project_filter_id_seq OWNED BY public.tt_projects_filters.project_filter_id;


--
-- Name: tt_projects_project_id_seq; Type: SEQUENCE; Schema: public; Owner: rbt
--

CREATE SEQUENCE public.tt_projects_project_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.tt_projects_project_id_seq OWNER TO rbt;

--
-- Name: tt_projects_project_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rbt
--

ALTER SEQUENCE public.tt_projects_project_id_seq OWNED BY public.tt_projects.project_id;


--
-- Name: tt_projects_resolutions; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.tt_projects_resolutions (
    project_resolution_id integer NOT NULL,
    project_id integer,
    issue_resolution_id integer
);


ALTER TABLE public.tt_projects_resolutions OWNER TO rbt;

--
-- Name: tt_projects_resolutions_project_resolution_id_seq; Type: SEQUENCE; Schema: public; Owner: rbt
--

CREATE SEQUENCE public.tt_projects_resolutions_project_resolution_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.tt_projects_resolutions_project_resolution_id_seq OWNER TO rbt;

--
-- Name: tt_projects_resolutions_project_resolution_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rbt
--

ALTER SEQUENCE public.tt_projects_resolutions_project_resolution_id_seq OWNED BY public.tt_projects_resolutions.project_resolution_id;


--
-- Name: tt_projects_roles; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.tt_projects_roles (
    project_role_id integer NOT NULL,
    project_id integer NOT NULL,
    role_id integer NOT NULL,
    uid integer DEFAULT 0,
    gid integer DEFAULT 0
);


ALTER TABLE public.tt_projects_roles OWNER TO rbt;

--
-- Name: tt_projects_roles_project_role_id_seq; Type: SEQUENCE; Schema: public; Owner: rbt
--

CREATE SEQUENCE public.tt_projects_roles_project_role_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.tt_projects_roles_project_role_id_seq OWNER TO rbt;

--
-- Name: tt_projects_roles_project_role_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rbt
--

ALTER SEQUENCE public.tt_projects_roles_project_role_id_seq OWNED BY public.tt_projects_roles.project_role_id;


--
-- Name: tt_projects_viewers; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.tt_projects_viewers (
    project_view_id integer NOT NULL,
    project_id integer,
    field character varying,
    name character varying
);


ALTER TABLE public.tt_projects_viewers OWNER TO rbt;

--
-- Name: tt_projects_viewers_project_view_id_seq; Type: SEQUENCE; Schema: public; Owner: rbt
--

CREATE SEQUENCE public.tt_projects_viewers_project_view_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.tt_projects_viewers_project_view_id_seq OWNER TO rbt;

--
-- Name: tt_projects_viewers_project_view_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rbt
--

ALTER SEQUENCE public.tt_projects_viewers_project_view_id_seq OWNED BY public.tt_projects_viewers.project_view_id;


--
-- Name: tt_projects_workflows; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.tt_projects_workflows (
    project_workflow_id integer NOT NULL,
    project_id integer,
    workflow character varying
);


ALTER TABLE public.tt_projects_workflows OWNER TO rbt;

--
-- Name: tt_projects_workflows_project_workflow_id_seq; Type: SEQUENCE; Schema: public; Owner: rbt
--

CREATE SEQUENCE public.tt_projects_workflows_project_workflow_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.tt_projects_workflows_project_workflow_id_seq OWNER TO rbt;

--
-- Name: tt_projects_workflows_project_workflow_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rbt
--

ALTER SEQUENCE public.tt_projects_workflows_project_workflow_id_seq OWNED BY public.tt_projects_workflows.project_workflow_id;


--
-- Name: tt_roles; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.tt_roles (
    role_id integer NOT NULL,
    name character varying,
    name_display character varying,
    level integer
);


ALTER TABLE public.tt_roles OWNER TO rbt;

--
-- Name: tt_roles_role_id_seq; Type: SEQUENCE; Schema: public; Owner: rbt
--

CREATE SEQUENCE public.tt_roles_role_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.tt_roles_role_id_seq OWNER TO rbt;

--
-- Name: tt_roles_role_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rbt
--

ALTER SEQUENCE public.tt_roles_role_id_seq OWNED BY public.tt_roles.role_id;


--
-- Name: tt_tags; Type: TABLE; Schema: public; Owner: rbt
--

CREATE TABLE public.tt_tags (
    tag_id integer NOT NULL,
    project_id integer NOT NULL,
    tag character varying,
    color character varying
);


ALTER TABLE public.tt_tags OWNER TO rbt;

--
-- Name: tt_tags_tag_id_seq; Type: SEQUENCE; Schema: public; Owner: rbt
--

CREATE SEQUENCE public.tt_tags_tag_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.tt_tags_tag_id_seq OWNER TO rbt;

--
-- Name: tt_tags_tag_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: rbt
--

ALTER SEQUENCE public.tt_tags_tag_id_seq OWNED BY public.tt_tags.tag_id;


--
-- Name: addresses_areas address_area_id; Type: DEFAULT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.addresses_areas ALTER COLUMN address_area_id SET DEFAULT nextval('public.addresses_areas_address_area_id_seq'::regclass);


--
-- Name: addresses_cities address_city_id; Type: DEFAULT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.addresses_cities ALTER COLUMN address_city_id SET DEFAULT nextval('public.addresses_cities_address_city_id_seq'::regclass);


--
-- Name: addresses_houses address_house_id; Type: DEFAULT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.addresses_houses ALTER COLUMN address_house_id SET DEFAULT nextval('public.addresses_houses_address_house_id_seq'::regclass);


--
-- Name: addresses_regions address_region_id; Type: DEFAULT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.addresses_regions ALTER COLUMN address_region_id SET DEFAULT nextval('public.addresses_regions_address_region_id_seq'::regclass);


--
-- Name: addresses_settlements address_settlement_id; Type: DEFAULT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.addresses_settlements ALTER COLUMN address_settlement_id SET DEFAULT nextval('public.addresses_settlements_address_settlement_id_seq'::regclass);


--
-- Name: addresses_streets address_street_id; Type: DEFAULT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.addresses_streets ALTER COLUMN address_street_id SET DEFAULT nextval('public.addresses_streets_address_street_id_seq'::regclass);


--
-- Name: camera_records record_id; Type: DEFAULT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.camera_records ALTER COLUMN record_id SET DEFAULT nextval('public.camera_records_record_id_seq'::regclass);


--
-- Name: cameras camera_id; Type: DEFAULT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.cameras ALTER COLUMN camera_id SET DEFAULT nextval('public.cameras_camera_id_seq'::regclass);


--
-- Name: companies company_id; Type: DEFAULT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.companies ALTER COLUMN company_id SET DEFAULT nextval('public.companies_company_id_seq'::regclass);


--
-- Name: core_groups gid; Type: DEFAULT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.core_groups ALTER COLUMN gid SET DEFAULT nextval('public.core_groups_gid_seq'::regclass);


--
-- Name: core_inbox msg_id; Type: DEFAULT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.core_inbox ALTER COLUMN msg_id SET DEFAULT nextval('public.core_inbox_msg_id_seq'::regclass);


--
-- Name: core_running_processes running_process_id; Type: DEFAULT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.core_running_processes ALTER COLUMN running_process_id SET DEFAULT nextval('public.core_running_processes_running_process_id_seq'::regclass);


--
-- Name: core_users uid; Type: DEFAULT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.core_users ALTER COLUMN uid SET DEFAULT nextval('public.core_users_uid_seq'::regclass);


--
-- Name: core_users_notifications notification_id; Type: DEFAULT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.core_users_notifications ALTER COLUMN notification_id SET DEFAULT nextval('public.core_users_notifications_notification_id_seq'::regclass);


--
-- Name: core_users_notifications_queue notification_id; Type: DEFAULT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.core_users_notifications_queue ALTER COLUMN notification_id SET DEFAULT nextval('public.core_users_notifications_queue_notification_id_seq'::regclass);


--
-- Name: core_vars var_id; Type: DEFAULT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.core_vars ALTER COLUMN var_id SET DEFAULT nextval('public.core_vars_var_id_seq'::regclass);


--
-- Name: custom_fields custom_field_id; Type: DEFAULT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.custom_fields ALTER COLUMN custom_field_id SET DEFAULT nextval('public.custom_fields_custom_field_id_seq'::regclass);


--
-- Name: custom_fields_options custom_field_option_id; Type: DEFAULT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.custom_fields_options ALTER COLUMN custom_field_option_id SET DEFAULT nextval('public.custom_fields_options_custom_field_option_id_seq'::regclass);


--
-- Name: custom_fields_values custom_fields_value_id; Type: DEFAULT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.custom_fields_values ALTER COLUMN custom_fields_value_id SET DEFAULT nextval('public.custom_fields_values_custom_fields_value_id_seq'::regclass);


--
-- Name: houses_domophones house_domophone_id; Type: DEFAULT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.houses_domophones ALTER COLUMN house_domophone_id SET DEFAULT nextval('public.houses_domophones_house_domophone_id_seq'::regclass);


--
-- Name: houses_entrances house_entrance_id; Type: DEFAULT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.houses_entrances ALTER COLUMN house_entrance_id SET DEFAULT nextval('public.houses_entrances_house_entrance_id_seq'::regclass);


--
-- Name: houses_flats house_flat_id; Type: DEFAULT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.houses_flats ALTER COLUMN house_flat_id SET DEFAULT nextval('public.houses_flats_house_flat_id_seq'::regclass);


--
-- Name: houses_flats_devices houses_flat_device_id; Type: DEFAULT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.houses_flats_devices ALTER COLUMN houses_flat_device_id SET DEFAULT nextval('public.houses_flats_devices_houses_flat_device_id_seq'::regclass);


--
-- Name: houses_paths house_path_id; Type: DEFAULT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.houses_paths ALTER COLUMN house_path_id SET DEFAULT nextval('public.houses_paths_house_path_id_seq'::regclass);


--
-- Name: houses_rfids house_rfid_id; Type: DEFAULT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.houses_rfids ALTER COLUMN house_rfid_id SET DEFAULT nextval('public.houses_rfids_house_rfid_id_seq'::regclass);


--
-- Name: houses_subscribers_devices subscriber_device_id; Type: DEFAULT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.houses_subscribers_devices ALTER COLUMN subscriber_device_id SET DEFAULT nextval('public.houses_subscribers_devices_subscriber_device_id_seq'::regclass);


--
-- Name: houses_subscribers_messages bulk_message_id; Type: DEFAULT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.houses_subscribers_messages ALTER COLUMN bulk_message_id SET DEFAULT nextval('public.houses_subscribers_messages_bulk_message_id_seq'::regclass);


--
-- Name: houses_subscribers_mobile house_subscriber_id; Type: DEFAULT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.houses_subscribers_mobile ALTER COLUMN house_subscriber_id SET DEFAULT nextval('public.houses_subscribers_mobile_house_subscriber_id_seq'::regclass);


--
-- Name: houses_watchers house_watcher_id; Type: DEFAULT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.houses_watchers ALTER COLUMN house_watcher_id SET DEFAULT nextval('public.houses_watchers_house_watcher_id_seq'::regclass);


--
-- Name: inbox msg_id; Type: DEFAULT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.inbox ALTER COLUMN msg_id SET DEFAULT nextval('public.inbox_msg_id_seq'::regclass);


--
-- Name: notes note_id; Type: DEFAULT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.notes ALTER COLUMN note_id SET DEFAULT nextval('public.notes_note_id_seq'::regclass);


--
-- Name: plog_call_done plog_call_done_id; Type: DEFAULT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.plog_call_done ALTER COLUMN plog_call_done_id SET DEFAULT nextval('public.plog_call_done_plog_call_done_id_seq'::regclass);


--
-- Name: plog_door_open plog_door_open_id; Type: DEFAULT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.plog_door_open ALTER COLUMN plog_door_open_id SET DEFAULT nextval('public.plog_door_open_plog_door_open_id_seq'::regclass);


--
-- Name: providers provider_id; Type: DEFAULT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.providers ALTER COLUMN provider_id SET DEFAULT nextval('public.providers_provider_id_seq'::regclass);


--
-- Name: tasks_changes task_change_id; Type: DEFAULT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.tasks_changes ALTER COLUMN task_change_id SET DEFAULT nextval('public.tasks_changes_task_change_id_seq'::regclass);


--
-- Name: tt_crontabs crontab_id; Type: DEFAULT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.tt_crontabs ALTER COLUMN crontab_id SET DEFAULT nextval('public.tt_crontabs_crontab_id_seq'::regclass);


--
-- Name: tt_issue_custom_fields issue_custom_field_id; Type: DEFAULT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.tt_issue_custom_fields ALTER COLUMN issue_custom_field_id SET DEFAULT nextval('public.tt_issue_custom_fields_issue_custom_field_id_seq'::regclass);


--
-- Name: tt_issue_custom_fields_options issue_custom_field_option_id; Type: DEFAULT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.tt_issue_custom_fields_options ALTER COLUMN issue_custom_field_option_id SET DEFAULT nextval('public.tt_issue_custom_fields_options_issue_custom_field_option_id_seq'::regclass);


--
-- Name: tt_issue_resolutions issue_resolution_id; Type: DEFAULT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.tt_issue_resolutions ALTER COLUMN issue_resolution_id SET DEFAULT nextval('public.tt_issue_resolutions_issue_resolution_id_seq'::regclass);


--
-- Name: tt_issue_statuses issue_status_id; Type: DEFAULT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.tt_issue_statuses ALTER COLUMN issue_status_id SET DEFAULT nextval('public.tt_issue_statuses_issue_status_id_seq'::regclass);


--
-- Name: tt_prints tt_print_id; Type: DEFAULT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.tt_prints ALTER COLUMN tt_print_id SET DEFAULT nextval('public.tt_prints_tt_print_id_seq'::regclass);


--
-- Name: tt_projects project_id; Type: DEFAULT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.tt_projects ALTER COLUMN project_id SET DEFAULT nextval('public.tt_projects_project_id_seq'::regclass);


--
-- Name: tt_projects_custom_fields project_custom_field_id; Type: DEFAULT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.tt_projects_custom_fields ALTER COLUMN project_custom_field_id SET DEFAULT nextval('public.tt_projects_custom_fields_project_custom_field_id_seq'::regclass);


--
-- Name: tt_projects_custom_fields_nojournal project_custom_field_id; Type: DEFAULT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.tt_projects_custom_fields_nojournal ALTER COLUMN project_custom_field_id SET DEFAULT nextval('public.tt_projects_custom_fields_nojournal_project_custom_field_id_seq'::regclass);


--
-- Name: tt_projects_filters project_filter_id; Type: DEFAULT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.tt_projects_filters ALTER COLUMN project_filter_id SET DEFAULT nextval('public.tt_projects_filters_project_filter_id_seq'::regclass);


--
-- Name: tt_projects_resolutions project_resolution_id; Type: DEFAULT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.tt_projects_resolutions ALTER COLUMN project_resolution_id SET DEFAULT nextval('public.tt_projects_resolutions_project_resolution_id_seq'::regclass);


--
-- Name: tt_projects_roles project_role_id; Type: DEFAULT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.tt_projects_roles ALTER COLUMN project_role_id SET DEFAULT nextval('public.tt_projects_roles_project_role_id_seq'::regclass);


--
-- Name: tt_projects_viewers project_view_id; Type: DEFAULT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.tt_projects_viewers ALTER COLUMN project_view_id SET DEFAULT nextval('public.tt_projects_viewers_project_view_id_seq'::regclass);


--
-- Name: tt_projects_workflows project_workflow_id; Type: DEFAULT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.tt_projects_workflows ALTER COLUMN project_workflow_id SET DEFAULT nextval('public.tt_projects_workflows_project_workflow_id_seq'::regclass);


--
-- Name: tt_roles role_id; Type: DEFAULT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.tt_roles ALTER COLUMN role_id SET DEFAULT nextval('public.tt_roles_role_id_seq'::regclass);


--
-- Name: tt_tags tag_id; Type: DEFAULT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.tt_tags ALTER COLUMN tag_id SET DEFAULT nextval('public.tt_tags_tag_id_seq'::regclass);


--
-- Name: addresses_areas addresses_areas_pkey; Type: CONSTRAINT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.addresses_areas
    ADD CONSTRAINT addresses_areas_pkey PRIMARY KEY (address_area_id);


--
-- Name: addresses_cities addresses_cities_pkey; Type: CONSTRAINT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.addresses_cities
    ADD CONSTRAINT addresses_cities_pkey PRIMARY KEY (address_city_id);


--
-- Name: addresses_houses addresses_houses_pkey; Type: CONSTRAINT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.addresses_houses
    ADD CONSTRAINT addresses_houses_pkey PRIMARY KEY (address_house_id);


--
-- Name: addresses_regions addresses_regions_pkey; Type: CONSTRAINT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.addresses_regions
    ADD CONSTRAINT addresses_regions_pkey PRIMARY KEY (address_region_id);


--
-- Name: addresses_settlements addresses_settlements_pkey; Type: CONSTRAINT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.addresses_settlements
    ADD CONSTRAINT addresses_settlements_pkey PRIMARY KEY (address_settlement_id);


--
-- Name: addresses_streets addresses_streets_pkey; Type: CONSTRAINT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.addresses_streets
    ADD CONSTRAINT addresses_streets_pkey PRIMARY KEY (address_street_id);


--
-- Name: camera_records camera_records_pkey; Type: CONSTRAINT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.camera_records
    ADD CONSTRAINT camera_records_pkey PRIMARY KEY (record_id);


--
-- Name: cameras cameras_pkey; Type: CONSTRAINT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.cameras
    ADD CONSTRAINT cameras_pkey PRIMARY KEY (camera_id);


--
-- Name: companies companies_pkey; Type: CONSTRAINT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.companies
    ADD CONSTRAINT companies_pkey PRIMARY KEY (company_id);


--
-- Name: core_api_methods_by_backend core_api_methods_by_backend_pkey; Type: CONSTRAINT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.core_api_methods_by_backend
    ADD CONSTRAINT core_api_methods_by_backend_pkey PRIMARY KEY (aid);


--
-- Name: core_api_methods_common core_api_methods_common_pkey; Type: CONSTRAINT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.core_api_methods_common
    ADD CONSTRAINT core_api_methods_common_pkey PRIMARY KEY (aid);


--
-- Name: core_api_methods_personal core_api_methods_personal_pkey; Type: CONSTRAINT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.core_api_methods_personal
    ADD CONSTRAINT core_api_methods_personal_pkey PRIMARY KEY (aid);


--
-- Name: core_api_methods core_api_methods_pkey; Type: CONSTRAINT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.core_api_methods
    ADD CONSTRAINT core_api_methods_pkey PRIMARY KEY (aid);


--
-- Name: core_groups core_groups_pkey; Type: CONSTRAINT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.core_groups
    ADD CONSTRAINT core_groups_pkey PRIMARY KEY (gid);


--
-- Name: core_inbox core_inbox_pkey; Type: CONSTRAINT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.core_inbox
    ADD CONSTRAINT core_inbox_pkey PRIMARY KEY (msg_id);


--
-- Name: core_running_processes core_running_processes_pkey; Type: CONSTRAINT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.core_running_processes
    ADD CONSTRAINT core_running_processes_pkey PRIMARY KEY (running_process_id);


--
-- Name: core_users_notifications core_users_notifications_pkey; Type: CONSTRAINT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.core_users_notifications
    ADD CONSTRAINT core_users_notifications_pkey PRIMARY KEY (notification_id);


--
-- Name: core_users_notifications_queue core_users_notifications_queue_pkey; Type: CONSTRAINT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.core_users_notifications_queue
    ADD CONSTRAINT core_users_notifications_queue_pkey PRIMARY KEY (notification_id);


--
-- Name: core_users core_users_pkey; Type: CONSTRAINT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.core_users
    ADD CONSTRAINT core_users_pkey PRIMARY KEY (uid);


--
-- Name: core_vars core_vars_pkey; Type: CONSTRAINT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.core_vars
    ADD CONSTRAINT core_vars_pkey PRIMARY KEY (var_id);


--
-- Name: custom_fields_options custom_fields_options_pkey; Type: CONSTRAINT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.custom_fields_options
    ADD CONSTRAINT custom_fields_options_pkey PRIMARY KEY (custom_field_option_id);


--
-- Name: custom_fields custom_fields_pkey; Type: CONSTRAINT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.custom_fields
    ADD CONSTRAINT custom_fields_pkey PRIMARY KEY (custom_field_id);


--
-- Name: custom_fields_values custom_fields_values_pkey; Type: CONSTRAINT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.custom_fields_values
    ADD CONSTRAINT custom_fields_values_pkey PRIMARY KEY (custom_fields_value_id);


--
-- Name: frs_faces frs_faces_pk; Type: CONSTRAINT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.frs_faces
    ADD CONSTRAINT frs_faces_pk PRIMARY KEY (face_id);


--
-- Name: houses_domophones houses_domophones_pkey; Type: CONSTRAINT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.houses_domophones
    ADD CONSTRAINT houses_domophones_pkey PRIMARY KEY (house_domophone_id);


--
-- Name: houses_entrances houses_entrances_pkey; Type: CONSTRAINT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.houses_entrances
    ADD CONSTRAINT houses_entrances_pkey PRIMARY KEY (house_entrance_id);


--
-- Name: houses_flats_devices houses_flats_devices_pkey; Type: CONSTRAINT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.houses_flats_devices
    ADD CONSTRAINT houses_flats_devices_pkey PRIMARY KEY (houses_flat_device_id);


--
-- Name: houses_flats houses_flats_pkey; Type: CONSTRAINT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.houses_flats
    ADD CONSTRAINT houses_flats_pkey PRIMARY KEY (house_flat_id);


--
-- Name: houses_paths houses_paths_pkey; Type: CONSTRAINT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.houses_paths
    ADD CONSTRAINT houses_paths_pkey PRIMARY KEY (house_path_id);


--
-- Name: houses_rfids houses_rfids_pkey; Type: CONSTRAINT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.houses_rfids
    ADD CONSTRAINT houses_rfids_pkey PRIMARY KEY (house_rfid_id);


--
-- Name: houses_subscribers_devices houses_subscribers_devices_pkey; Type: CONSTRAINT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.houses_subscribers_devices
    ADD CONSTRAINT houses_subscribers_devices_pkey PRIMARY KEY (subscriber_device_id);


--
-- Name: houses_subscribers_messages houses_subscribers_messages_pkey; Type: CONSTRAINT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.houses_subscribers_messages
    ADD CONSTRAINT houses_subscribers_messages_pkey PRIMARY KEY (bulk_message_id);


--
-- Name: houses_subscribers_mobile houses_subscribers_mobile_pkey; Type: CONSTRAINT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.houses_subscribers_mobile
    ADD CONSTRAINT houses_subscribers_mobile_pkey PRIMARY KEY (house_subscriber_id);


--
-- Name: houses_watchers houses_watchers_pkey; Type: CONSTRAINT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.houses_watchers
    ADD CONSTRAINT houses_watchers_pkey PRIMARY KEY (house_watcher_id);


--
-- Name: inbox inbox_pkey; Type: CONSTRAINT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.inbox
    ADD CONSTRAINT inbox_pkey PRIMARY KEY (msg_id);


--
-- Name: notes notes_pkey; Type: CONSTRAINT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.notes
    ADD CONSTRAINT notes_pkey PRIMARY KEY (note_id);


--
-- Name: plog_call_done plog_call_done_pkey; Type: CONSTRAINT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.plog_call_done
    ADD CONSTRAINT plog_call_done_pkey PRIMARY KEY (plog_call_done_id);


--
-- Name: plog_door_open plog_door_open_pkey; Type: CONSTRAINT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.plog_door_open
    ADD CONSTRAINT plog_door_open_pkey PRIMARY KEY (plog_door_open_id);


--
-- Name: providers providers_pkey; Type: CONSTRAINT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.providers
    ADD CONSTRAINT providers_pkey PRIMARY KEY (provider_id);


--
-- Name: tasks_changes tasks_changes_pkey; Type: CONSTRAINT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.tasks_changes
    ADD CONSTRAINT tasks_changes_pkey PRIMARY KEY (task_change_id);


--
-- Name: tt_crontabs tt_crontabs_pkey; Type: CONSTRAINT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.tt_crontabs
    ADD CONSTRAINT tt_crontabs_pkey PRIMARY KEY (crontab_id);


--
-- Name: tt_issue_custom_fields_options tt_issue_custom_fields_options_pkey; Type: CONSTRAINT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.tt_issue_custom_fields_options
    ADD CONSTRAINT tt_issue_custom_fields_options_pkey PRIMARY KEY (issue_custom_field_option_id);


--
-- Name: tt_issue_custom_fields tt_issue_custom_fields_pkey; Type: CONSTRAINT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.tt_issue_custom_fields
    ADD CONSTRAINT tt_issue_custom_fields_pkey PRIMARY KEY (issue_custom_field_id);


--
-- Name: tt_issue_resolutions tt_issue_resolutions_pkey; Type: CONSTRAINT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.tt_issue_resolutions
    ADD CONSTRAINT tt_issue_resolutions_pkey PRIMARY KEY (issue_resolution_id);


--
-- Name: tt_issue_statuses tt_issue_statuses_pkey; Type: CONSTRAINT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.tt_issue_statuses
    ADD CONSTRAINT tt_issue_statuses_pkey PRIMARY KEY (issue_status_id);


--
-- Name: tt_prints tt_prints_pkey; Type: CONSTRAINT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.tt_prints
    ADD CONSTRAINT tt_prints_pkey PRIMARY KEY (tt_print_id);


--
-- Name: tt_projects_custom_fields_nojournal tt_projects_custom_fields_nojournal_pkey; Type: CONSTRAINT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.tt_projects_custom_fields_nojournal
    ADD CONSTRAINT tt_projects_custom_fields_nojournal_pkey PRIMARY KEY (project_custom_field_id);


--
-- Name: tt_projects_custom_fields tt_projects_custom_fields_pkey; Type: CONSTRAINT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.tt_projects_custom_fields
    ADD CONSTRAINT tt_projects_custom_fields_pkey PRIMARY KEY (project_custom_field_id);


--
-- Name: tt_projects_fields_settings tt_projects_fields_settings_pkey; Type: CONSTRAINT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.tt_projects_fields_settings
    ADD CONSTRAINT tt_projects_fields_settings_pkey PRIMARY KEY (tt_projects_field);


--
-- Name: tt_projects_filters tt_projects_filters_pkey; Type: CONSTRAINT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.tt_projects_filters
    ADD CONSTRAINT tt_projects_filters_pkey PRIMARY KEY (project_filter_id);


--
-- Name: tt_projects tt_projects_pkey; Type: CONSTRAINT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.tt_projects
    ADD CONSTRAINT tt_projects_pkey PRIMARY KEY (project_id);


--
-- Name: tt_projects_resolutions tt_projects_resolutions_pkey; Type: CONSTRAINT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.tt_projects_resolutions
    ADD CONSTRAINT tt_projects_resolutions_pkey PRIMARY KEY (project_resolution_id);


--
-- Name: tt_projects_roles tt_projects_roles_pkey; Type: CONSTRAINT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.tt_projects_roles
    ADD CONSTRAINT tt_projects_roles_pkey PRIMARY KEY (project_role_id);


--
-- Name: tt_projects_viewers tt_projects_viewers_pkey; Type: CONSTRAINT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.tt_projects_viewers
    ADD CONSTRAINT tt_projects_viewers_pkey PRIMARY KEY (project_view_id);


--
-- Name: tt_projects_workflows tt_projects_workflows_pkey; Type: CONSTRAINT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.tt_projects_workflows
    ADD CONSTRAINT tt_projects_workflows_pkey PRIMARY KEY (project_workflow_id);


--
-- Name: tt_roles tt_roles_pkey; Type: CONSTRAINT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.tt_roles
    ADD CONSTRAINT tt_roles_pkey PRIMARY KEY (role_id);


--
-- Name: tt_tags tt_tags_pkey; Type: CONSTRAINT; Schema: public; Owner: rbt
--

ALTER TABLE ONLY public.tt_tags
    ADD CONSTRAINT tt_tags_pkey PRIMARY KEY (tag_id);


--
-- Name: addresses_areas_address_region_id; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX addresses_areas_address_region_id ON public.addresses_areas USING btree (address_region_id);


--
-- Name: addresses_areas_area; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX addresses_areas_area ON public.addresses_areas USING btree (address_region_id, area);


--
-- Name: addresses_areas_area_uuid; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX addresses_areas_area_uuid ON public.addresses_areas USING btree (area_uuid);


--
-- Name: addresses_cities_address_area_id; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX addresses_cities_address_area_id ON public.addresses_cities USING btree (address_area_id);


--
-- Name: addresses_cities_address_region_id; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX addresses_cities_address_region_id ON public.addresses_cities USING btree (address_region_id);


--
-- Name: addresses_cities_city; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX addresses_cities_city ON public.addresses_cities USING btree (address_region_id, address_area_id, city);


--
-- Name: addresses_cities_city_uuid; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX addresses_cities_city_uuid ON public.addresses_cities USING btree (city_uuid);


--
-- Name: addresses_favorites_login; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX addresses_favorites_login ON public.addresses_favorites USING btree (login);


--
-- Name: addresses_favorites_uniq; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX addresses_favorites_uniq ON public.addresses_favorites USING btree (login, object, id);


--
-- Name: addresses_houses_address_settlement_id; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX addresses_houses_address_settlement_id ON public.addresses_houses USING btree (address_settlement_id);


--
-- Name: addresses_houses_address_street_id; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX addresses_houses_address_street_id ON public.addresses_houses USING btree (address_street_id);


--
-- Name: addresses_houses_company_id; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX addresses_houses_company_id ON public.addresses_houses USING btree (company_id);


--
-- Name: addresses_houses_house; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX addresses_houses_house ON public.addresses_houses USING btree (address_settlement_id, address_street_id, house);


--
-- Name: addresses_houses_house_full; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX addresses_houses_house_full ON public.addresses_houses USING btree (house_full);


--
-- Name: addresses_houses_house_full_fts; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX addresses_houses_house_full_fts ON public.addresses_houses USING gin (to_tsvector('simple'::regconfig, (house_full)::text));


--
-- Name: addresses_houses_house_full_trgm; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX addresses_houses_house_full_trgm ON public.addresses_houses USING gist (house_full public.gist_trgm_ops);


--
-- Name: addresses_houses_house_uuid; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX addresses_houses_house_uuid ON public.addresses_houses USING btree (house_uuid);


--
-- Name: addresses_regions_region; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX addresses_regions_region ON public.addresses_regions USING btree (region);


--
-- Name: addresses_regions_region_uuid; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX addresses_regions_region_uuid ON public.addresses_regions USING btree (region_uuid);


--
-- Name: addresses_settlements_address_area_id; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX addresses_settlements_address_area_id ON public.addresses_settlements USING btree (address_area_id);


--
-- Name: addresses_settlements_address_region_id; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX addresses_settlements_address_region_id ON public.addresses_settlements USING btree (address_city_id);


--
-- Name: addresses_settlements_settlement; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX addresses_settlements_settlement ON public.addresses_settlements USING btree (address_area_id, address_city_id, settlement);


--
-- Name: addresses_settlements_settlement_uuid; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX addresses_settlements_settlement_uuid ON public.addresses_settlements USING btree (settlement_uuid);


--
-- Name: addresses_streets_address_address_city_id; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX addresses_streets_address_address_city_id ON public.addresses_streets USING btree (address_city_id);


--
-- Name: addresses_streets_address_address_settlement_id; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX addresses_streets_address_address_settlement_id ON public.addresses_streets USING btree (address_settlement_id);


--
-- Name: addresses_streets_street; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX addresses_streets_street ON public.addresses_streets USING btree (address_city_id, address_settlement_id, street);


--
-- Name: addresses_streets_street_uuid; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX addresses_streets_street_uuid ON public.addresses_streets USING btree (street_uuid);


--
-- Name: camera_records_expire; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX camera_records_expire ON public.camera_records USING btree (expire);


--
-- Name: camera_records_status; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX camera_records_status ON public.camera_records USING btree (state);


--
-- Name: cameras_common; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX cameras_common ON public.cameras USING btree (common);


--
-- Name: cameras_url; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX cameras_url ON public.cameras USING btree (url);


--
-- Name: company_uid; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX company_uid ON public.companies USING btree (uid);


--
-- Name: core_api_methods_uniq; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX core_api_methods_uniq ON public.core_api_methods USING btree (api, method, request_method);


--
-- Name: core_groups_acronym; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX core_groups_acronym ON public.core_groups USING btree (acronym);


--
-- Name: core_groups_admin; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX core_groups_admin ON public.core_groups USING btree (admin);


--
-- Name: core_groups_name; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX core_groups_name ON public.core_groups USING btree (name);


--
-- Name: core_groups_rights_uniq; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX core_groups_rights_uniq ON public.core_groups_rights USING btree (gid, aid);


--
-- Name: core_msg_date; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX core_msg_date ON public.core_inbox USING btree (msg_date);


--
-- Name: core_msg_readed; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX core_msg_readed ON public.core_inbox USING btree (msg_readed);


--
-- Name: core_msg_to; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX core_msg_to ON public.core_inbox USING btree (msg_to);


--
-- Name: core_msg_type; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX core_msg_type ON public.core_inbox USING btree (msg_type);


--
-- Name: core_users_e_mail; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX core_users_e_mail ON public.core_users USING btree (e_mail);


--
-- Name: core_users_groups_gid; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX core_users_groups_gid ON public.core_users_groups USING btree (gid);


--
-- Name: core_users_groups_uid; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX core_users_groups_uid ON public.core_users_groups USING btree (uid);


--
-- Name: core_users_groups_uniq; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX core_users_groups_uniq ON public.core_users_groups USING btree (uid, gid);


--
-- Name: core_users_login; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX core_users_login ON public.core_users USING btree (login);


--
-- Name: core_users_notifications_sended; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX core_users_notifications_sended ON public.core_users_notifications USING btree (sended);


--
-- Name: core_users_notifications_uid; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX core_users_notifications_uid ON public.core_users_notifications USING btree (uid);


--
-- Name: core_users_phone; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX core_users_phone ON public.core_users USING btree (phone);


--
-- Name: core_users_primary_group; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX core_users_primary_group ON public.core_users USING btree (primary_group);


--
-- Name: core_users_real_name; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX core_users_real_name ON public.core_users USING btree (real_name);


--
-- Name: core_users_rights_uniq; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX core_users_rights_uniq ON public.core_users_rights USING btree (uid, aid);


--
-- Name: core_users_tokens_uid; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX core_users_tokens_uid ON public.core_users_tokens USING btree (uid);


--
-- Name: core_users_tokens_uniq; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX core_users_tokens_uniq ON public.core_users_tokens USING btree (uid, token);


--
-- Name: core_vars_id; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX core_vars_id ON public.core_vars USING btree (var_id);


--
-- Name: core_vars_var_name; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX core_vars_var_name ON public.core_vars USING btree (var_name);


--
-- Name: core_vars_var_uniq; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX core_vars_var_uniq ON public.core_vars USING btree (var_name);


--
-- Name: custom_fields_apply_to; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX custom_fields_apply_to ON public.custom_fields USING btree (apply_to);


--
-- Name: custom_fields_name; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX custom_fields_name ON public.custom_fields USING btree (field);


--
-- Name: custom_fields_options_custom_field_id; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX custom_fields_options_custom_field_id ON public.custom_fields_options USING btree (custom_field_id);


--
-- Name: custom_fields_values_apply_to; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX custom_fields_values_apply_to ON public.custom_fields_values USING btree (apply_to);


--
-- Name: custom_fields_values_field; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX custom_fields_values_field ON public.custom_fields_values USING btree (field);


--
-- Name: custom_fields_values_id; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX custom_fields_values_id ON public.custom_fields_values USING btree (id);


--
-- Name: custom_fields_values_uniq; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX custom_fields_values_uniq ON public.custom_fields_values USING btree (apply_to, id, field);


--
-- Name: custom_fields_values_value; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX custom_fields_values_value ON public.custom_fields_values USING btree (value);


--
-- Name: domophones_ip_port; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX domophones_ip_port ON public.houses_domophones USING btree (url);


--
-- Name: frs_faces_event_uuid_uindex; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX frs_faces_event_uuid_uindex ON public.frs_faces USING btree (event_uuid);


--
-- Name: frs_faces_face_uuid_uindex; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX frs_faces_face_uuid_uindex ON public.frs_faces USING btree (face_uuid);


--
-- Name: frs_links_faces_face_id_index; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX frs_links_faces_face_id_index ON public.frs_links_faces USING btree (face_id);


--
-- Name: frs_links_faces_main_uindex; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX frs_links_faces_main_uindex ON public.frs_links_faces USING btree (flat_id, house_subscriber_id, face_id);


--
-- Name: houses_cameras_flats_camera_id; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX houses_cameras_flats_camera_id ON public.houses_cameras_flats USING btree (camera_id);


--
-- Name: houses_cameras_flats_flat_id; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX houses_cameras_flats_flat_id ON public.houses_cameras_flats USING btree (house_flat_id);


--
-- Name: houses_cameras_flats_uniq; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX houses_cameras_flats_uniq ON public.houses_cameras_flats USING btree (camera_id, house_flat_id);


--
-- Name: houses_cameras_houses_house_id; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX houses_cameras_houses_house_id ON public.houses_cameras_houses USING btree (address_house_id);


--
-- Name: houses_cameras_houses_id; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX houses_cameras_houses_id ON public.houses_cameras_houses USING btree (camera_id);


--
-- Name: houses_cameras_houses_uniq; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX houses_cameras_houses_uniq ON public.houses_cameras_houses USING btree (camera_id, address_house_id);


--
-- Name: houses_cameras_subscribers_camera_id; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX houses_cameras_subscribers_camera_id ON public.houses_cameras_subscribers USING btree (camera_id);


--
-- Name: houses_cameras_subscribers_subscriber_id; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX houses_cameras_subscribers_subscriber_id ON public.houses_cameras_subscribers USING btree (house_subscriber_id);


--
-- Name: houses_cameras_subscribers_uniq; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX houses_cameras_subscribers_uniq ON public.houses_cameras_subscribers USING btree (camera_id, house_subscriber_id);


--
-- Name: houses_entrances_cmses_uniq1; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX houses_entrances_cmses_uniq1 ON public.houses_entrances_cmses USING btree (house_entrance_id, cms, dozen, unit);


--
-- Name: houses_entrances_cmses_uniq2; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX houses_entrances_cmses_uniq2 ON public.houses_entrances_cmses USING btree (house_entrance_id, apartment);


--
-- Name: houses_entrances_flats_house_entrance_id; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX houses_entrances_flats_house_entrance_id ON public.houses_entrances_flats USING btree (house_entrance_id);


--
-- Name: houses_entrances_flats_house_flat_id; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX houses_entrances_flats_house_flat_id ON public.houses_entrances_flats USING btree (house_flat_id);


--
-- Name: houses_entrances_flats_uniq; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX houses_entrances_flats_uniq ON public.houses_entrances_flats USING btree (house_entrance_id, house_flat_id);


--
-- Name: houses_entrances_multihouse; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX houses_entrances_multihouse ON public.houses_entrances USING btree (shared);


--
-- Name: houses_entrances_uniq; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX houses_entrances_uniq ON public.houses_entrances USING btree (house_domophone_id, domophone_output);


--
-- Name: houses_flats__contract; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX houses_flats__contract ON public.houses_flats USING btree (contract);


--
-- Name: houses_flats_address_house_id; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX houses_flats_address_house_id ON public.houses_flats USING btree (address_house_id);


--
-- Name: houses_flats_cars; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX houses_flats_cars ON public.houses_flats USING btree (cars);


--
-- Name: houses_flats_cars_gin; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX houses_flats_cars_gin ON public.houses_flats USING gin (cars public.gin_trgm_ops);


--
-- Name: houses_flats_devices_uniq; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX houses_flats_devices_uniq ON public.houses_flats_devices USING btree (house_flat_id, subscriber_device_id);


--
-- Name: houses_flats_login; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX houses_flats_login ON public.houses_flats USING btree (login);


--
-- Name: houses_flats_subscribers_uniq; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX houses_flats_subscribers_uniq ON public.houses_flats_subscribers USING btree (house_flat_id, house_subscriber_id);


--
-- Name: houses_flats_uniq; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX houses_flats_uniq ON public.houses_flats USING btree (address_house_id, flat);


--
-- Name: houses_houses_entrances_address_house_id; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX houses_houses_entrances_address_house_id ON public.houses_houses_entrances USING btree (address_house_id);


--
-- Name: houses_houses_entrances_house_entrance_id; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX houses_houses_entrances_house_entrance_id ON public.houses_houses_entrances USING btree (house_entrance_id);


--
-- Name: houses_houses_entrances_prefix; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX houses_houses_entrances_prefix ON public.houses_houses_entrances USING btree (prefix);


--
-- Name: houses_houses_entrances_uniq1; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX houses_houses_entrances_uniq1 ON public.houses_houses_entrances USING btree (address_house_id, house_entrance_id);


--
-- Name: houses_houses_entrances_uniq2; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX houses_houses_entrances_uniq2 ON public.houses_houses_entrances USING btree (house_entrance_id, prefix);


--
-- Name: houses_paths_name; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX houses_paths_name ON public.houses_paths USING btree (house_path_name);


--
-- Name: houses_paths_parent; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX houses_paths_parent ON public.houses_paths USING btree (house_path_parent);


--
-- Name: houses_paths_tree; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX houses_paths_tree ON public.houses_paths USING btree (house_path_tree);


--
-- Name: houses_paths_uniq; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX houses_paths_uniq ON public.houses_paths USING btree (house_path_parent, house_path_name);


--
-- Name: houses_rfids_rfid; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX houses_rfids_rfid ON public.houses_rfids USING btree (rfid);


--
-- Name: houses_rfids_uniq; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX houses_rfids_uniq ON public.houses_rfids USING btree (rfid, access_type, access_to);


--
-- Name: houses_subscribers_devices_device_token; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX houses_subscribers_devices_device_token ON public.houses_subscribers_devices USING btree (device_token);


--
-- Name: houses_subscribers_devices_house_subscriber_id; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX houses_subscribers_devices_house_subscriber_id ON public.houses_subscribers_devices USING btree (house_subscriber_id);


--
-- Name: houses_subscribers_devices_uniq_1; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX houses_subscribers_devices_uniq_1 ON public.houses_subscribers_devices USING btree (device_token);


--
-- Name: houses_subscribers_devices_uniq_2; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX houses_subscribers_devices_uniq_2 ON public.houses_subscribers_devices USING btree (auth_token);


--
-- Name: houses_subscribers_devices_uniq_3; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX houses_subscribers_devices_uniq_3 ON public.houses_subscribers_devices USING btree (push_token);


--
-- Name: houses_subscribers_messages_house_subscriber_id; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX houses_subscribers_messages_house_subscriber_id ON public.houses_subscribers_messages USING btree (house_subscriber_id);


--
-- Name: houses_subscribers_messages_uniq; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX houses_subscribers_messages_uniq ON public.houses_subscribers_messages USING btree (house_subscriber_id, title, msg, action);


--
-- Name: houses_subscribers_mobile_subscriber_full; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX houses_subscribers_mobile_subscriber_full ON public.houses_subscribers_mobile USING btree (subscriber_full);


--
-- Name: houses_subscribers_mobile_subscriber_full_fts; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX houses_subscribers_mobile_subscriber_full_fts ON public.houses_subscribers_mobile USING gin (to_tsvector('simple'::regconfig, (subscriber_full)::text));


--
-- Name: houses_subscribers_mobile_subscriber_full_trgm; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX houses_subscribers_mobile_subscriber_full_trgm ON public.houses_subscribers_mobile USING gist (subscriber_full public.gist_trgm_ops);


--
-- Name: houses_watchers_house_flat_id; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX houses_watchers_house_flat_id ON public.houses_watchers USING btree (house_flat_id);


--
-- Name: houses_watchers_subscriber_device_id; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX houses_watchers_subscriber_device_id ON public.houses_watchers USING btree (subscriber_device_id);


--
-- Name: houses_watchers_uniq; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX houses_watchers_uniq ON public.houses_watchers USING btree (subscriber_device_id, house_flat_id, event_type, event_detail);


--
-- Name: inbox_date; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX inbox_date ON public.inbox USING btree (date);


--
-- Name: inbox_expire; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX inbox_expire ON public.inbox USING btree (expire);


--
-- Name: inbox_readed; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX inbox_readed ON public.inbox USING btree (readed);


--
-- Name: notes_category; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX notes_category ON public.notes USING btree (category);


--
-- Name: notes_owner; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX notes_owner ON public.notes USING btree (owner);


--
-- Name: notes_remind; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX notes_remind ON public.notes USING btree (remind);


--
-- Name: notes_reminded; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX notes_reminded ON public.notes USING btree (reminded);


--
-- Name: plog_call_done_date; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX plog_call_done_date ON public.plog_call_done USING btree (date);


--
-- Name: plog_call_done_expire; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX plog_call_done_expire ON public.plog_call_done USING btree (expire);


--
-- Name: plog_door_open_date; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX plog_door_open_date ON public.plog_door_open USING btree (date);


--
-- Name: plog_door_open_expire; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX plog_door_open_expire ON public.plog_door_open USING btree (expire);


--
-- Name: providers_id; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX providers_id ON public.providers USING btree (id);


--
-- Name: providers_name; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX providers_name ON public.providers USING btree (name);


--
-- Name: subscribers_mobile_id; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX subscribers_mobile_id ON public.houses_subscribers_mobile USING btree (id);


--
-- Name: tasks_changes_uniq; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX tasks_changes_uniq ON public.tasks_changes USING btree (object_type, object_id);


--
-- Name: tt_crontabs_crontab; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX tt_crontabs_crontab ON public.tt_crontabs USING btree (crontab);


--
-- Name: tt_crontabs_uniq; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX tt_crontabs_uniq ON public.tt_crontabs USING btree (project_id, filter, uid, action);


--
-- Name: tt_favorite_filters_uniq; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX tt_favorite_filters_uniq ON public.tt_favorite_filters USING btree (login, filter);


--
-- Name: tt_issue_custom_fields_name; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX tt_issue_custom_fields_name ON public.tt_issue_custom_fields USING btree (field);


--
-- Name: tt_issue_custom_fields_options_uniq; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX tt_issue_custom_fields_options_uniq ON public.tt_issue_custom_fields_options USING btree (issue_custom_field_id, option);


--
-- Name: tt_issue_resolutions_uniq1; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX tt_issue_resolutions_uniq1 ON public.tt_issue_resolutions USING btree (resolution);


--
-- Name: tt_issue_resolutions_uniq2; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX tt_issue_resolutions_uniq2 ON public.tt_issue_resolutions USING btree (alias);


--
-- Name: tt_issue_stauses_uniq; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX tt_issue_stauses_uniq ON public.tt_issue_statuses USING btree (status);


--
-- Name: tt_prints_uniq; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX tt_prints_uniq ON public.tt_prints USING btree (form_name);


--
-- Name: tt_projects_acronym; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX tt_projects_acronym ON public.tt_projects USING btree (acronym);


--
-- Name: tt_projects_custom_fields_nojournal_uniq; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX tt_projects_custom_fields_nojournal_uniq ON public.tt_projects_custom_fields USING btree (project_id, issue_custom_field_id);


--
-- Name: tt_projects_custom_fields_uniq; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX tt_projects_custom_fields_uniq ON public.tt_projects_custom_fields USING btree (project_id, issue_custom_field_id);


--
-- Name: tt_projects_filters_uniq; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX tt_projects_filters_uniq ON public.tt_projects_filters USING btree (project_id, filter, personal);


--
-- Name: tt_projects_name; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX tt_projects_name ON public.tt_projects USING btree (project);


--
-- Name: tt_projects_resolutions_uniq; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX tt_projects_resolutions_uniq ON public.tt_projects_resolutions USING btree (project_id, issue_resolution_id);


--
-- Name: tt_projects_roles_gid; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX tt_projects_roles_gid ON public.tt_projects_roles USING btree (gid);


--
-- Name: tt_projects_roles_project_id; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX tt_projects_roles_project_id ON public.tt_projects_roles USING btree (project_id);


--
-- Name: tt_projects_roles_role_id; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX tt_projects_roles_role_id ON public.tt_projects_roles USING btree (role_id);


--
-- Name: tt_projects_roles_uid; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX tt_projects_roles_uid ON public.tt_projects_roles USING btree (uid);


--
-- Name: tt_projects_roles_uniq; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX tt_projects_roles_uniq ON public.tt_projects_roles USING btree (project_id, role_id, uid, gid);


--
-- Name: tt_projects_viewers_uniq; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX tt_projects_viewers_uniq ON public.tt_projects_viewers USING btree (project_id, field);


--
-- Name: tt_projects_workflows_uniq; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX tt_projects_workflows_uniq ON public.tt_projects_workflows USING btree (project_id, workflow);


--
-- Name: tt_roles_level; Type: INDEX; Schema: public; Owner: rbt
--

CREATE INDEX tt_roles_level ON public.tt_roles USING btree (level);


--
-- Name: tt_tags_uniq; Type: INDEX; Schema: public; Owner: rbt
--

CREATE UNIQUE INDEX tt_tags_uniq ON public.tt_tags USING btree (project_id, tag);


--
-- Name: SCHEMA public; Type: ACL; Schema: -; Owner: postgres
--

REVOKE USAGE ON SCHEMA public FROM PUBLIC;
GRANT ALL ON SCHEMA public TO rbt;
GRANT ALL ON SCHEMA public TO PUBLIC;


--
-- PostgreSQL database dump complete
--

\unrestrict TRZyKC11xO1yBqdeDsDayRekfdDAEGhznasCuu3AKDwwbjDN8ecegEzbdqtr52a


SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

SET default_tablespace = '';

SET default_with_oids = false;

CREATE TABLE public.statistics (
    id integer DEFAULT nextval(('statistics_pk_seq'::text)::regclass) NOT NULL,
    studyid integer NOT NULL,
    groupid integer NOT NULL,
    userid integer NOT NULL,
    email text NOT NULL,
    typeid integer NOT NULL,
    info text,
    dateentered timestamp with time zone,
    firstname character varying(50),
    lastname character varying(50),
    params_list text,
    filters_user text,
    filters_admin text,
    bytes bigint DEFAULT '-1'::integer,
    agreement text
);

ALTER TABLE public.file_filter OWNER TO postgres;


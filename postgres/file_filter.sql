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

CREATE TABLE public.file_filter (
    metadata_name text NOT NULL,
    dirnames_selection_admin text,
    dirnames_selection_user text
);

ALTER TABLE public.file_filter OWNER TO postgres;

ALTER TABLE ONLY public.file_filter
    ADD CONSTRAINT file_filter_pkey PRIMARY KEY (metadata_name);

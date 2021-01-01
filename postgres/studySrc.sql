CREATE SCHEMA studySrc 
  AUTHORIZATION postgres;

CREATE TABLE studySrc.metadata
(
  id serial NOT NULL,
  json jsonb,
  CONSTRAINT pkid PRIMARY KEY (id)
)
WITH (
  OIDS=FALSE
);
ALTER TABLE studySrc.metadata
  OWNER TO postgres;


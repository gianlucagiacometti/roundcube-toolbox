CREATE SEQUENCE public.toolbox_customise_domains_seq;

ALTER SEQUENCE public.toolbox_customise_domains_seq
    OWNER TO roundcube;

CREATE TABLE public.toolbox_customise_domains
(
    id integer NOT NULL DEFAULT nextval(('toolbox_customise_domains_seq'::text)::regclass),
    domain_name character varying(255) COLLATE pg_catalog."default" NOT NULL,
    purge_trash integer NOT NULL DEFAULT 0,
    purge_junk integer NOT NULL DEFAULT 0,
    modified timestamp with time zone NOT NULL DEFAULT now(),
    modified_by character varying(255) COLLATE pg_catalog."default" NOT NULL,
    CONSTRAINT toolbox_customise_domains_pkey PRIMARY KEY (id),
    CONSTRAINT toolbox_customise_domains_domain_name_key UNIQUE (domain_name)
)
WITH (
    OIDS = FALSE
)
TABLESPACE pg_default;

ALTER TABLE public.toolbox_customise_domains
    OWNER to roundcube;

CREATE SEQUENCE public.toolbox_customise_skins_seq;

ALTER SEQUENCE public.toolbox_customise_skins_seq
    OWNER TO roundcube;

CREATE TABLE public.toolbox_customise_skins
(
    id integer NOT NULL DEFAULT nextval(('toolbox_customise_skins_seq'::text)::regclass),
    toolbox_customise_domain_id integer DEFAULT NULL,
    skin character varying(255) COLLATE pg_catalog."default",
    customise_blankpage boolean NOT NULL DEFAULT false,
    blankpage_type text COLLATE pg_catalog."default",
    blankpage_image text COLLATE pg_catalog."default",
    blankpage_url character varying(1024) COLLATE pg_catalog."default",
    blankpage_custom text COLLATE pg_catalog."default",
    customise_css boolean NOT NULL DEFAULT false,
    additional_css text COLLATE pg_catalog."default",
    customise_favicon boolean NOT NULL DEFAULT false,
    favicon text COLLATE pg_catalog."default",
    customise_logo boolean NOT NULL DEFAULT false,
    customised_logo text COLLATE pg_catalog."default",
    modified timestamp with time zone NOT NULL DEFAULT now(),
    modified_by character varying(255) COLLATE pg_catalog."default" NOT NULL,
    CONSTRAINT toolbox_customise_skins_pkey PRIMARY KEY (id),
    CONSTRAINT toolbox_customise_skins_skin_key UNIQUE (toolbox_customise_domain_id, skin)
)
WITH (
    OIDS = FALSE
)
TABLESPACE pg_default;

ALTER TABLE public.toolbox_customise_skins
    OWNER to roundcube;

CREATE OR REPLACE VIEW public.toolbox_customise_skins_view AS
  SELECT
    toolbox_customise_domains.domain_name,
    toolbox_customise_skins.skin,
    toolbox_customise_skins.customise_blankpage,
    toolbox_customise_skins.blankpage_type,
    toolbox_customise_skins.blankpage_image,
    toolbox_customise_skins.blankpage_url,
    toolbox_customise_skins.blankpage_custom,
    toolbox_customise_skins.customise_css,
    toolbox_customise_skins.additional_css,
    toolbox_customise_skins.customise_logo,
    toolbox_customise_skins.customised_logo
  FROM toolbox_customise_skins
    LEFT JOIN toolbox_customise_domains ON toolbox_customise_domains.id = toolbox_customise_skins.toolbox_customise_domain_id;

ALTER TABLE public.toolbox_customise_skins_view
    OWNER TO roundcube;

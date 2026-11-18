CREATE TABLE toolbox_cloud_vaults (
    user_id INTEGER NOT NULL PRIMARY KEY REFERENCES users(user_id) ON DELETE CASCADE,
    password_salt VARCHAR(64) COLLATE pg_catalog."default",
    password_ops INTEGER,
    password_memory BIGINT,
    password_wrap TEXT COLLATE pg_catalog."default",
    recovery_wrap TEXT COLLATE pg_catalog."default",
    key_check TEXT NOT NULL,
    created timestamp with time zone NOT NULL DEFAULT now(),
    modified timestamp with time zone NOT NULL DEFAULT now()
);

ALTER TABLE public.toolbox_cloud_vaults
    OWNER to roundcube;

CREATE SEQUENCE toolbox_cloud_accounts_seq AS BIGINT;

ALTER SEQUENCE public.toolbox_cloud_accounts_seq
    OWNER to roundcube;

CREATE TABLE toolbox_cloud_accounts (
    id BIGINT NOT NULL DEFAULT nextval('toolbox_cloud_accounts_seq') PRIMARY KEY,
    crypto_id CHAR(32) COLLATE pg_catalog."default" NOT NULL UNIQUE,
    user_id INTEGER NOT NULL REFERENCES toolbox_cloud_vaults(user_id) ON DELETE CASCADE,
    provider VARCHAR(32) COLLATE pg_catalog."default" NOT NULL,
    display_name VARCHAR(255) COLLATE pg_catalog."default" NOT NULL,
    endpoint VARCHAR(2048) COLLATE pg_catalog."default",
    credentials TEXT COLLATE pg_catalog."default" NOT NULL,
    expires BIGINT,
    created timestamp with time zone NOT NULL DEFAULT now(),
    modified timestamp with time zone NOT NULL DEFAULT now()
);

CREATE INDEX idx_tbx_cloud_accounts_user
    ON toolbox_cloud_accounts(user_id, display_name);

ALTER TABLE public.toolbox_cloud_accounts
    OWNER to roundcube;

ALTER SEQUENCE toolbox_cloud_accounts_seq
    OWNED BY toolbox_cloud_accounts.id;

CREATE SEQUENCE public.toolbox_login_monitor_devices_seq AS BIGINT;

ALTER SEQUENCE public.toolbox_login_monitor_devices_seq
    OWNER TO roundcube;

CREATE TABLE public.toolbox_login_monitor_devices (
    id BIGINT NOT NULL DEFAULT nextval('public.toolbox_login_monitor_devices_seq'::regclass) PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES public.users(user_id),
    token_digest CHAR(64) NOT NULL,
    first_seen_at BIGINT NOT NULL,
    last_seen_at BIGINT NOT NULL,
    device_type VARCHAR(32),
    os_name VARCHAR(100),
    browser_name VARCHAR(100),
    created TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT now(),
    modified TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT now(),
    modified_by VARCHAR(255) NOT NULL DEFAULT 'login_monitor',
    CONSTRAINT ux_tbx_lm_devices_user_token UNIQUE (user_id, token_digest)
);

ALTER TABLE public.toolbox_login_monitor_devices
    OWNER TO roundcube;

ALTER SEQUENCE public.toolbox_login_monitor_devices_seq
    OWNED BY public.toolbox_login_monitor_devices.id;

CREATE INDEX ix_tbx_lm_devices_user_seen
    ON public.toolbox_login_monitor_devices (user_id, last_seen_at);

CREATE SEQUENCE public.toolbox_login_monitor_logins_seq AS BIGINT;

ALTER SEQUENCE public.toolbox_login_monitor_logins_seq
    OWNER TO roundcube;

CREATE TABLE public.toolbox_login_monitor_logins (
    id BIGINT NOT NULL DEFAULT nextval('public.toolbox_login_monitor_logins_seq'::regclass) PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES public.users(user_id),
    device_id BIGINT REFERENCES public.toolbox_login_monitor_devices(id) ON DELETE SET NULL,
    logged_in_at BIGINT NOT NULL,
    device_type VARCHAR(32),
    os_name VARCHAR(100),
    os_version VARCHAR(50),
    device_brand VARCHAR(100),
    device_model VARCHAR(150),
    city VARCHAR(150),
    country VARCHAR(100),
    ip_address VARCHAR(45),
    user_agent TEXT,
    browser_name VARCHAR(100),
    browser_version VARCHAR(50),
    browser_engine VARCHAR(100),
    bot_detection SMALLINT,
    created TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT now()
);

ALTER TABLE public.toolbox_login_monitor_logins
    OWNER TO roundcube;

ALTER SEQUENCE public.toolbox_login_monitor_logins_seq
    OWNED BY public.toolbox_login_monitor_logins.id;

CREATE INDEX ix_tbx_lm_logins_user_time
    ON public.toolbox_login_monitor_logins (user_id, logged_in_at);

CREATE INDEX ix_tbx_lm_logins_device
    ON public.toolbox_login_monitor_logins (device_id);

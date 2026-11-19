CREATE SEQUENCE public.toolbox_login_monitor_logins_seq AS BIGINT;

ALTER SEQUENCE public.toolbox_login_monitor_logins_seq
    OWNER TO roundcube;

CREATE TABLE public.toolbox_login_monitor_logins (
    id BIGINT NOT NULL DEFAULT nextval('public.toolbox_login_monitor_logins_seq'::regclass) PRIMARY KEY,
    user_id INTEGER NOT NULL,
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

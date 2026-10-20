BEGIN;

CREATE TABLE public.password_reset_codes (
    email_hash char(64) PRIMARY KEY,
    user_id INTEGER REFERENCES public.user_tbl(user_id) ON DELETE CASCADE,
    otp_hash varchar(255) NOT NULL,
    requested_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMPTZ NOT NULL,
    attempt_count SMALLINT NOT NULL DEFAULT 0 CHECK (attempt_count BETWEEN 0 AND 5),
    used_at TIMESTAMPTZ DEFAULT NULL
);

CREATE INDEX password_reset_codes_requested_at_idx
    ON public.password_reset_codes (requested_at);

ALTER TABLE public.password_reset_codes ENABLE ROW LEVEL SECURITY;

COMMIT;

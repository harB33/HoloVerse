CREATE TABLE IF NOT EXISTS public.collector_favorites_tbl (
    user_id INTEGER NOT NULL REFERENCES public.user_tbl (user_id) ON DELETE CASCADE,
    product_id INTEGER NOT NULL REFERENCES public.product_tbl (product_id) ON DELETE CASCADE,
    created_at TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, product_id)
);

ALTER TABLE public.collector_favorites_tbl ENABLE ROW LEVEL SECURITY;

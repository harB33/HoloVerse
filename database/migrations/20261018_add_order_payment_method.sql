ALTER TABLE order_tbl
ADD COLUMN IF NOT EXISTS payment_method varchar(30);

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM pg_constraint
        WHERE conname = 'order_tbl_payment_method_check'
          AND conrelid = 'order_tbl'::regclass
    ) THEN
        ALTER TABLE order_tbl
        ADD CONSTRAINT order_tbl_payment_method_check
        CHECK (payment_method IS NULL OR payment_method IN ('card', 'e_wallet', 'cash_on_delivery'));
    END IF;
END
$$;

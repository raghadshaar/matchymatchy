ALTER TABLE product_reviews
    ADD COLUMN device_hash VARCHAR(64) NULL AFTER user_id;


ALTER TABLE product_reviews
    ADD CONSTRAINT uq_pr_device UNIQUE (product_id, device_hash);

CREATE INDEX idx_pr_device ON product_reviews(device_hash);
CREATE INDEX idx_pr_user    ON product_reviews(user_id);

ALTER TABLE product_reviews
    ADD COLUMN device_hash VARCHAR(64) NULL AFTER user_id;

ALTER TABLE product_reviews
    ADD CONSTRAINT uq_pr_device UNIQUE (product_id, device_hash);

CREATE INDEX idx_pr_device ON product_reviews(device_hash);
CREATE INDEX idx_pr_user    ON product_reviews(user_id);

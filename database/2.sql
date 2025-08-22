

-- تقييمات المستخدمين (1-5 نجوم + تعليق اختياري)
CREATE TABLE IF NOT EXISTS product_reviews (
                                               id         INT AUTO_INCREMENT PRIMARY KEY,
                                               product_id INT NOT NULL,
                                               user_id    INT NULL,              -- لو عندك جدول users؛ وإلا خليه NULL
                                               rating     TINYINT NOT NULL,      -- 1..5 (ملاحظة: MariaDB قد يتجاهل CHECK)
                                               comment    TEXT NULL,
                                               created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                               CONSTRAINT fk_pr_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    );
CREATE INDEX idx_pr_product ON product_reviews(product_id);

-- لايكات (من مستخدم مسجل أو جهاز مجهول)
CREATE TABLE IF NOT EXISTS product_likes (
                                             id          INT AUTO_INCREMENT PRIMARY KEY,
                                             product_id  INT NOT NULL,
                                             user_id     INT NULL,               -- لو مسجل
                                             device_hash VARCHAR(64) NULL,       -- fingerprint/كوكي للزوار
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pl_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    CONSTRAINT uq_like_user   UNIQUE (product_id, user_id),
    CONSTRAINT uq_like_device UNIQUE (product_id, device_hash)
    );
CREATE INDEX idx_pl_product ON product_likes(product_id);


CREATE OR REPLACE VIEW v_product_metrics AS
SELECT
    p.id AS product_id,
    r.rating_avg,
    r.rating_count,
    l.likes
FROM products p
         LEFT JOIN (
    SELECT product_id,
           ROUND(AVG(rating), 1) AS rating_avg,
           COUNT(*)              AS rating_count
    FROM product_reviews
    GROUP BY product_id
) r ON r.product_id = p.id
         LEFT JOIN (
    SELECT product_id,
           COUNT(*) AS likes
    FROM product_likes
    GROUP BY product_id
) l ON l.product_id = p.id;


ALTER TABLE product_reviews
    ADD UNIQUE KEY uq_pr_user (product_id, user_id),
    ADD UNIQUE KEY uq_pr_dev  (product_id, device_hash);
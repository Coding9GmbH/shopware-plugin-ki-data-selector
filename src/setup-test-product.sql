-- Find a product to test with
SELECT 
    p.id,
    p.product_number,
    pt.name,
    p.stock,
    cf.product_set_code
FROM product p
LEFT JOIN product_translation pt ON p.id = pt.product_id AND pt.language_id = UNHEX('2FBB5FE2E29A4D70AA5854CE7CE3E20B')
LEFT JOIN product_custom_field_set cf ON p.id = cf.product_id
WHERE p.product_number IN ('SWDEMO10001', 'SWDEMO10002', 'SWDEMO10003')
LIMIT 10;

-- Update one product to have a set code
UPDATE product p
SET p.custom_fields = JSON_SET(
    COALESCE(p.custom_fields, '{}'),
    '$.product_set_code',
    'BUNDLE001'
)
WHERE p.product_number = 'SWDEMO10003';

-- Verify the update
SELECT 
    p.product_number,
    pt.name,
    p.stock,
    JSON_EXTRACT(p.custom_fields, '$.product_set_code') as set_code
FROM product p
LEFT JOIN product_translation pt ON p.id = pt.product_id AND pt.language_id = UNHEX('2FBB5FE2E29A4D70AA5854CE7CE3E20B')
WHERE p.product_number = 'SWDEMO10003';
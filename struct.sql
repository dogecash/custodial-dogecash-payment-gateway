CREATE TABLE IF NOT EXISTS `invoice_status` (
    `dogec_addr` text,
    `invoice` text,
    `amount` text,
    `status` text,
    `txid` text
);

CREATE TABLE IF NOT EXISTS `api_keys` (
    `dogec_addr` text,
    `key` text
);
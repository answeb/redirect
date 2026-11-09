<?php

return 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'redirect_rule` (
    `id_redirect_rule` INT(11) NOT NULL AUTO_INCREMENT,
    `source` VARCHAR(2048) NOT NULL,
    `destination` VARCHAR(2048) NOT NULL,
    `is_regex` TINYINT(1) NOT NULL DEFAULT 0,
    `http_code` SMALLINT(3) NOT NULL DEFAULT 301,
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `position` INT(11) NOT NULL DEFAULT 0,
    `date_add` DATETIME NOT NULL,
    `date_upd` DATETIME NOT NULL,
    PRIMARY KEY (`id_redirect_rule`),
    INDEX `idx_active_position` (`active`, `position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';

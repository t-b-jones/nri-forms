CREATE TABLE IF NOT EXISTS `#__nriforms_submissions` (
    `id` int NOT NULL AUTO_INCREMENT,
    `group_id` int NOT NULL DEFAULT 0,
    `group_title` varchar(255) NOT NULL DEFAULT '',
    `data` mediumtext,
    `mail_sent` tinyint NOT NULL DEFAULT 0,
    `created` datetime NOT NULL,
    `expires` datetime NULL DEFAULT NULL,
    `state` tinyint NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    KEY `idx_group_created` (`group_id`, `created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

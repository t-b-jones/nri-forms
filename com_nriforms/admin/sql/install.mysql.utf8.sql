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

CREATE TABLE IF NOT EXISTS `#__nriforms_forms` (
    `id` int NOT NULL AUTO_INCREMENT,
    `group_id` int NOT NULL DEFAULT 0,
    `recipient` varchar(1024) NOT NULL DEFAULT '',
    `replyto_field` varchar(255) NOT NULL DEFAULT 'email',
    `save_submissions` tinyint NOT NULL DEFAULT 1,
    `retention_days` int NOT NULL DEFAULT 0,
    `encrypt` tinyint NOT NULL DEFAULT 0,
    `captcha` varchar(100) NOT NULL DEFAULT '',
    `consent_enabled` tinyint NOT NULL DEFAULT 0,
    `consent_label` varchar(500) NOT NULL DEFAULT '',
    `consent_article_id` int NOT NULL DEFAULT 0,
    `terms_enabled` tinyint NOT NULL DEFAULT 0,
    `terms_label` varchar(500) NOT NULL DEFAULT '',
    `terms_article_id` int NOT NULL DEFAULT 0,
    `success_message` text,
    `redirect_url` varchar(1024) NOT NULL DEFAULT '',
    `params` text,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_group` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

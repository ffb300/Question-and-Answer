-- Component installation script for com_question

-- Questions table
CREATE TABLE IF NOT EXISTS `#__question_questions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `title` varchar(255) NOT NULL,
    `body` text NOT NULL,
    `alias` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
    `language` varchar(7) NOT NULL DEFAULT '*',
    `catid` int(11) NOT NULL DEFAULT 0,
    `created_by` int(11) NOT NULL DEFAULT 0,
    `created_by_alias` varchar(255) NOT NULL DEFAULT '',
    `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
    `modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
    `modified_by` int(11) NOT NULL DEFAULT 0,
    `published` tinyint(1) NOT NULL DEFAULT 0,
    `publish_up` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
    `publish_down` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
    `featured` tinyint(1) NOT NULL DEFAULT 0,
    `hits` int(11) NOT NULL DEFAULT 0,
    `votes_up` int(11) NOT NULL DEFAULT 0,
    `votes_down` int(11) NOT NULL DEFAULT 0,
    `tags` text,
    `meta_description` varchar(255) NOT NULL DEFAULT '',
    `meta_keywords` varchar(255) NOT NULL DEFAULT '',
    `params` text NOT NULL,
    `checked_out` int(11) NOT NULL DEFAULT 0,
    `checked_out_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
    `ordering` int(11) NOT NULL DEFAULT 0,
    `access` int(11) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    KEY `idx_alias` (`alias`),
    KEY `idx_language` (`language`),
    KEY `idx_catid` (`catid`),
    KEY `idx_created_by` (`created_by`),
    KEY `idx_published` (`published`),
    KEY `idx_created` (`created`),
    KEY `idx_featured` (`featured`),
    KEY `idx_access` (`access`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Answers table
CREATE TABLE IF NOT EXISTS `#__question_answers` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `question_id` int(11) NOT NULL,
    `body` text NOT NULL,
    `created_by` int(11) NOT NULL DEFAULT 0,
    `created_by_alias` varchar(255) NOT NULL DEFAULT '',
    `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
    `modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
    `modified_by` int(11) NOT NULL DEFAULT 0,
    `published` tinyint(1) NOT NULL DEFAULT 0,
    `votes_up` int(11) NOT NULL DEFAULT 0,
    `votes_down` int(11) NOT NULL DEFAULT 0,
    `is_best` tinyint(1) NOT NULL DEFAULT 0,
    `checked_out` int(11) NOT NULL DEFAULT 0,
    `checked_out_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
    `ordering` int(11) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_question_id` (`question_id`),
    KEY `idx_created_by` (`created_by`),
    KEY `idx_published` (`published`),
    KEY `idx_created` (`created`),
    KEY `idx_is_best` (`is_best`),
    CONSTRAINT `fk_question_answers_question_id` FOREIGN KEY (`question_id`) REFERENCES `#__question_questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Votes table
CREATE TABLE IF NOT EXISTS `#__question_votes` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `item_id` int(11) NOT NULL,
    `item_type` enum('question','answer') NOT NULL,
    `user_id` int(11) NOT NULL,
    `vote` tinyint(1) NOT NULL COMMENT '1 for up, -1 for down',
    `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
    `ip_address` varchar(45) NOT NULL DEFAULT '',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_user_item` (`user_id`,`item_id`,`item_type`),
    KEY `idx_item_id` (`item_id`),
    KEY `idx_item_type` (`item_type`),
    KEY `idx_created` (`created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Languages table
CREATE TABLE IF NOT EXISTS `#__question_languages` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `lang_code` varchar(7) NOT NULL,
    `title` varchar(255) NOT NULL,
    `title_native` varchar(255) NOT NULL,
    `sef` varchar(50) NOT NULL,
    `image` varchar(50) NOT NULL,
    `published` tinyint(1) NOT NULL DEFAULT 0,
    `ordering` int(11) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_lang_code` (`lang_code`),
    KEY `idx_published` (`published`),
    KEY `idx_ordering` (`ordering`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Events table for live updates
CREATE TABLE IF NOT EXISTS `#__question_events` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `event_type` varchar(50) NOT NULL,
    `question_id` int(11) NOT NULL,
    `item_id` int(11) NOT NULL DEFAULT 0,
    `user_id` int(11) NOT NULL DEFAULT 0,
    `data` text,
    `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
    `processed` tinyint(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_event_type` (`event_type`),
    KEY `idx_question_id` (`question_id`),
    KEY `idx_item_id` (`item_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_created` (`created`),
    KEY `idx_processed` (`processed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default languages
INSERT INTO `#__question_languages` (`lang_code`, `title`, `title_native`, `sef`, `image`, `published`, `ordering`) VALUES
('en-GB', 'English (UK)', 'English (UK)', 'en', 'en', 1, 1),
('en-US', 'English (US)', 'English (US)', 'en-us', 'en_us', 1, 2),
('fr-FR', 'French (France)', 'Français', 'fr', 'fr', 1, 3),
('de-DE', 'German (Germany)', 'Deutsch', 'de', 'de', 1, 4),
('es-ES', 'Spanish (Spain)', 'Español', 'es', 'es', 1, 5),
('it-IT', 'Italian (Italy)', 'Italiano', 'it', 'it', 1, 6),
('pt-BR', 'Portuguese (Brazil)', 'Português', 'pt', 'pt', 1, 7),
('ru-RU', 'Russian (Russia)', 'Русский', 'ru', 'ru', 1, 8),
('zh-CN', 'Chinese (China)', '简体中文', 'zh', 'zh', 1, 9),
('ja-JP', 'Japanese (Japan)', '日本語', 'ja', 'ja', 1, 10);

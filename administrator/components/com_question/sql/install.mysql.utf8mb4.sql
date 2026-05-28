-- Joomla Question & Answer Component
-- Installation SQL for MySQL 8.0+
-- Charset: UTF8MB4
-- Created: 2026-05-28

-- Create Questions Table
CREATE TABLE IF NOT EXISTS `#__question_questions` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `catid` int UNSIGNED NOT NULL DEFAULT 0,
  `title` varchar(255) NOT NULL DEFAULT '',
  `slug` varchar(255) NOT NULL DEFAULT '',
  `description` longtext NOT NULL DEFAULT '',
  `introtext` text NOT NULL DEFAULT '',
  `fulltext` longtext NOT NULL DEFAULT '',
  `created_by` int UNSIGNED NOT NULL DEFAULT 0,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int UNSIGNED DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `published` tinyint(1) NOT NULL DEFAULT 1,
  `state` tinyint(4) NOT NULL DEFAULT 1,
  `access` int UNSIGNED NOT NULL DEFAULT 1,
  `language` char(7) NOT NULL DEFAULT '*',
  `featured` tinyint(1) NOT NULL DEFAULT 0,
  `hits` int UNSIGNED NOT NULL DEFAULT 0,
  `answer_count` int UNSIGNED NOT NULL DEFAULT 0,
  `vote_count` int NOT NULL DEFAULT 0,
  `best_answer_id` int UNSIGNED DEFAULT NULL,
  `locked` tinyint(1) NOT NULL DEFAULT 0,
  `metadata` json DEFAULT NULL,
  `params` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_catid` (`catid`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_created_date` (`created_date`),
  KEY `idx_published` (`published`),
  KEY `idx_featured` (`featured`),
  KEY `idx_access` (`access`),
  KEY `idx_language` (`language`),
  KEY `idx_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create Answers Table
CREATE TABLE IF NOT EXISTS `#__question_answers` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `question_id` int UNSIGNED NOT NULL,
  `created_by` int UNSIGNED NOT NULL DEFAULT 0,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_by` int UNSIGNED DEFAULT NULL,
  `modified_date` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `text` longtext NOT NULL DEFAULT '',
  `published` tinyint(1) NOT NULL DEFAULT 1,
  `state` tinyint(4) NOT NULL DEFAULT 1,
  `access` int UNSIGNED NOT NULL DEFAULT 1,
  `is_best_answer` tinyint(1) NOT NULL DEFAULT 0,
  `vote_count` int NOT NULL DEFAULT 0,
  `helpful_count` int UNSIGNED NOT NULL DEFAULT 0,
  `unhelpful_count` int UNSIGNED NOT NULL DEFAULT 0,
  `locked` tinyint(1) NOT NULL DEFAULT 0,
  `metadata` json DEFAULT NULL,
  `params` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_question_id` (`question_id`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_created_date` (`created_date`),
  KEY `idx_is_best_answer` (`is_best_answer`),
  KEY `idx_published` (`published`),
  CONSTRAINT `fk_answer_question` FOREIGN KEY (`question_id`) 
    REFERENCES `#__question_questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create Votes Table
CREATE TABLE IF NOT EXISTS `#__question_votes` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL,
  `item_type` enum('question','answer') NOT NULL,
  `item_id` int UNSIGNED NOT NULL,
  `vote_value` tinyint(2) NOT NULL DEFAULT 0,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_address` varchar(45) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_vote` (`user_id`, `item_type`, `item_id`),
  KEY `idx_item_id` (`item_id`),
  KEY `idx_item_type` (`item_type`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_date` (`created_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create Language Support Table
CREATE TABLE IF NOT EXISTS `#__question_languages` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `item_type` enum('question','answer') NOT NULL,
  `item_id` int UNSIGNED NOT NULL,
  `language` char(7) NOT NULL DEFAULT 'en-GB',
  `title` varchar(255) DEFAULT NULL,
  `description` longtext,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_translation` (`item_type`, `item_id`, `language`),
  KEY `idx_item_id` (`item_id`),
  KEY `idx_language` (`language`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create Events Queue for Live Updates
CREATE TABLE IF NOT EXISTS `#__question_events` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_type` varchar(50) NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `item_type` enum('question','answer','vote','moderation') NOT NULL,
  `item_id` int UNSIGNED NOT NULL,
  `event_data` json DEFAULT NULL,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `processed` tinyint(1) NOT NULL DEFAULT 0,
  `processed_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_processed` (`processed`),
  KEY `idx_created_date` (`created_date`),
  KEY `idx_event_type` (`event_type`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create Rating/Reputation Table
CREATE TABLE IF NOT EXISTS `#__question_reputation` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL,
  `reputation_points` int NOT NULL DEFAULT 0,
  `badge_count` int UNSIGNED NOT NULL DEFAULT 0,
  `answers_accepted` int UNSIGNED NOT NULL DEFAULT 0,
  `questions_asked` int UNSIGNED NOT NULL DEFAULT 0,
  `answers_given` int UNSIGNED NOT NULL DEFAULT 0,
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified_date` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user` (`user_id`),
  KEY `idx_reputation_points` (`reputation_points`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create Moderation/Flags Table
CREATE TABLE IF NOT EXISTS `#__question_flags` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int UNSIGNED NOT NULL,
  `item_type` enum('question','answer','comment') NOT NULL,
  `item_id` int UNSIGNED NOT NULL,
  `reason` varchar(255) NOT NULL DEFAULT '',
  `description` text,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reviewed_by` int UNSIGNED DEFAULT NULL,
  `reviewed_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_item_type` (`item_type`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_date` (`created_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create Tags Junction Table
CREATE TABLE IF NOT EXISTS `#__question_tags` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `question_id` int UNSIGNED NOT NULL,
  `tag_id` int UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_tag_mapping` (`question_id`, `tag_id`),
  KEY `idx_tag_id` (`tag_id`),
  CONSTRAINT `fk_tag_question` FOREIGN KEY (`question_id`)
    REFERENCES `#__question_questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

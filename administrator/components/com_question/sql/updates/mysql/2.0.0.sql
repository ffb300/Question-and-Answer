-- Migration: Version 2.0.0 - Joomla 6.1 Support
-- Date: 2026-05-28

-- Add new columns for Joomla 6.1 compatibility
ALTER TABLE `#__question_questions` ADD COLUMN `joomla6_compatible` TINYINT(1) NOT NULL DEFAULT 1;
ALTER TABLE `#__question_answers` ADD COLUMN `joomla6_compatible` TINYINT(1) NOT NULL DEFAULT 1;

-- Update character set to UTF8MB4
ALTER TABLE `#__question_questions` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__question_answers` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__question_votes` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__question_languages` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__question_events` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__question_reputation` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `#__question_flags` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create indexes for performance
CREATE INDEX `idx_question_created_by_date` ON `#__question_questions` (`created_by`, `created_date`);
CREATE INDEX `idx_answer_question_date` ON `#__question_answers` (`question_id`, `created_date`);

-- Update extension settings
UPDATE `#__extensions`
SET `params` = JSON_SET(
    COALESCE(`params`, '{}'),
    '$.joomla_version', '6.1',
    '$.database_version', '2.0.0'
)
WHERE `element` = 'com_question' AND `type` = 'component';

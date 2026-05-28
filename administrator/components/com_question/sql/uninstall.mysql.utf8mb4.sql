-- Joomla Question & Answer Component
-- Uninstallation SQL for MySQL 8.0+

-- Drop all component tables
DROP TABLE IF EXISTS `#__question_tags`;
DROP TABLE IF EXISTS `#__question_flags`;
DROP TABLE IF EXISTS `#__question_reputation`;
DROP TABLE IF EXISTS `#__question_events`;
DROP TABLE IF EXISTS `#__question_languages`;
DROP TABLE IF EXISTS `#__question_votes`;
DROP TABLE IF EXISTS `#__question_answers`;
DROP TABLE IF EXISTS `#__question_questions`;

-- Remove component categories
DELETE FROM `#__categories` WHERE `extension` = 'com_question';

-- Remove component from extensions
DELETE FROM `#__extensions` WHERE `element` = 'com_question' AND `type` = 'component';

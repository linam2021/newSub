SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `display_name` varchar(255) NOT NULL,
  `social_id` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'hero',
  `created_at` TIMESTAMP , 
  `updated_at` TIMESTAMP ,
  `is_banned` tinyint(4) NOT NULL DEFAULT false,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_social_id_unique` (`social_id`)
);

CREATE TABLE IF NOT EXISTS `challenges` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `hero_instagram` varchar(255) NOT NULL,
  `hero_target` varchar(255) NOT NULL,
  `points` int(11) NOT NULL DEFAULT 0,
  `in_leader_board` tinyint(1) NOT NULL DEFAULT 0,
  `is_verefied` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP ,
  `updated_at` TIMESTAMP ,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),	
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS `sessions` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `number` int(11) NOT NULL,
  `unLock_date` varchar(255)  NOT NULL,
  `complete_state` tinyint(4) NOT NULL DEFAULT false,
  `points` int(11) NOT NULL DEFAULT 0,
  `week_number` int(11) NOT NULL,
  `challenge_id` bigint(20) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`challenge_id`) REFERENCES `challenges` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS `base_tasks` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `points` int(11) NOT NULL DEFAULT 0,
  `complete_state` tinyint(1) NOT NULL DEFAULT 0,
  `session_id` bigint(20) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS `migrations` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ;

INSERT IGNORE INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2014_10_12_000000_create_users_table', 1),
(2, '2020_09_20_084154_create_challenges_table', 1),
(3, '2020_09_20_084834_create_sessions_table', 1),
(4, '2020_09_20_084921_create_base_tasks_table', 1);	

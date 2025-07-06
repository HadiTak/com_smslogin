CREATE TABLE IF NOT EXISTS `#__smslogin_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mobile` varchar(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `verification_code` varchar(4) DEFAULT NULL,
  `code_expire` datetime DEFAULT NULL,
  `attempts` int(11) DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `sms_count` int(11) DEFAULT 0,
  `last_sms` datetime DEFAULT NULL,
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mobile` (`mobile`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

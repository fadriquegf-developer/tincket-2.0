/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `applications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `applications` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `brand_id` int unsigned NOT NULL,
  `code_name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `key` varchar(64) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `applications_key_unique` (`key`),
  KEY `applications_brand_id_foreign` (`brand_id`),
  CONSTRAINT `applications_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `assignated_rates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `assignated_rates` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `rate_id` int unsigned NOT NULL,
  `assignated_rate_type` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `assignated_rate_id` int unsigned NOT NULL,
  `session_id` int unsigned DEFAULT NULL,
  `price` decimal(8,2) NOT NULL,
  `max_on_sale` smallint unsigned NOT NULL DEFAULT '0',
  `max_per_order` smallint unsigned NOT NULL DEFAULT '0',
  `available_since` datetime DEFAULT NULL,
  `available_until` datetime DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT '0',
  `is_private` tinyint(1) NOT NULL DEFAULT '0',
  `max_per_code` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `validator_class` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin COMMENT 'Class and attributes used to allow user to use this rate',
  PRIMARY KEY (`id`),
  KEY `assignated_rates_rate_id_foreign` (`rate_id`),
  KEY `assignated_rates_session_id_foreign` (`session_id`),
  KEY `assignated_rates_assignated_rate_id_index` (`assignated_rate_id`),
  KEY `assignated_rates_is_public_index` (`is_public`),
  CONSTRAINT `assignated_rates_rate_id_foreign` FOREIGN KEY (`rate_id`) REFERENCES `rates` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assignated_rates_session_id_foreign` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `brand_capability`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `brand_capability` (
  `brand_id` int unsigned NOT NULL,
  `capability_id` int unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`brand_id`,`capability_id`),
  KEY `brand_capability_brand_id_index` (`brand_id`),
  KEY `brand_capability_capability_id_index` (`capability_id`),
  CONSTRAINT `brand_capability_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE,
  CONSTRAINT `brand_capability_capability_id_foreign` FOREIGN KEY (`capability_id`) REFERENCES `capabilities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `brand_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `brand_user` (
  `brand_id` int unsigned NOT NULL,
  `user_id` int unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`brand_id`,`user_id`),
  KEY `brand_user_brand_id_index` (`brand_id`),
  KEY `brand_user_user_id_index` (`user_id`),
  CONSTRAINT `brand_user_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE,
  CONSTRAINT `brand_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `brands`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `brands` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `code_name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `key` varchar(64) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `allowed_host` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `type` char(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `brand_color` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `logo` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `banner` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `extra_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `footer` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `legal_notice` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `privacy_policy` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `cookies_policy` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `gdpr_text` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `general_conditions` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `alert_status` tinyint(1) NOT NULL DEFAULT '0',
  `alert` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `custom_script` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `aux_code` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `phone` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `comment` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `brands_code_name_unique` (`code_name`),
  UNIQUE KEY `brands_key_unique` (`key`),
  UNIQUE KEY `brands_allowed_host_unique` (`allowed_host`),
  CONSTRAINT `brands_chk_1` CHECK (json_valid(`privacy_policy`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `brands_register_inputs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `brands_register_inputs` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `brand_id` int unsigned NOT NULL,
  `register_input_id` int unsigned NOT NULL,
  `required` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `brands_register_inputs_brand_id_foreign` (`brand_id`),
  KEY `brands_register_inputs_register_input_id_foreign` (`register_input_id`),
  CONSTRAINT `brands_register_inputs_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE,
  CONSTRAINT `brands_register_inputs_register_input_id_foreign` FOREIGN KEY (`register_input_id`) REFERENCES `register_inputs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_session_slot`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_session_slot` (
  `session_id` int unsigned NOT NULL,
  `slot_id` int unsigned NOT NULL,
  `zone_id` int unsigned NOT NULL,
  `cart_id` int unsigned DEFAULT NULL,
  `is_locked` tinyint(1) NOT NULL,
  `lock_reason` int unsigned DEFAULT NULL,
  `rates_info` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `comment` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  UNIQUE KEY `cache_session_slot_session_id_slot_id_unique` (`session_id`,`slot_id`),
  KEY `cache_session_slot_zone_id_foreign` (`zone_id`),
  KEY `cache_session_slot_slot_id_foreign` (`slot_id`),
  KEY `cache_session_slot_cart_id_foreign` (`cart_id`),
  KEY `cache_session_slot_is_locked_index` (`is_locked`),
  CONSTRAINT `cache_session_slot_cart_id_foreign` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cache_session_slot_session_id_foreign` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cache_session_slot_slot_id_foreign` FOREIGN KEY (`slot_id`) REFERENCES `slots` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cache_session_slot_zone_id_foreign` FOREIGN KEY (`zone_id`) REFERENCES `zones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `capabilities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `capabilities` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `code_name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `name` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `capabilities_code_name_unique` (`code_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `carts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `carts` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `token` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `confirmation_code` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `expires_on` datetime NOT NULL,
  `seller_id` int unsigned NOT NULL,
  `seller_type` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `brand_id` int unsigned DEFAULT NULL,
  `client_id` int unsigned DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_user_id` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `comment` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `carts_token_unique` (`token`),
  UNIQUE KEY `carts_confirmation_code_unique` (`confirmation_code`),
  KEY `carts_brand_id_foreign` (`brand_id`),
  KEY `carts_client_id_foreign` (`client_id`),
  KEY `carts_confirmation_code_index` (`confirmation_code`),
  KEY `carts_expires_on_index` (`expires_on`),
  KEY `carts_deleted_user_id_foreign` (`deleted_user_id`),
  CONSTRAINT `carts_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE,
  CONSTRAINT `carts_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `carts_deleted_user_id_foreign` FOREIGN KEY (`deleted_user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `census`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `census` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `brand_id` int unsigned DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `code` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `census_brand_id_foreign` (`brand_id`),
  CONSTRAINT `census_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cities` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `city_id` int unsigned NOT NULL,
  `region_id` int unsigned NOT NULL,
  `province_id` int unsigned NOT NULL,
  `zip` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cities_region_id_foreign` (`region_id`),
  KEY `cities_province_id_foreign` (`province_id`),
  CONSTRAINT `cities_province_id_foreign` FOREIGN KEY (`province_id`) REFERENCES `provinces` (`id`),
  CONSTRAINT `cities_region_id_foreign` FOREIGN KEY (`region_id`) REFERENCES `regions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `classifiables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `classifiables` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `taxonomy_id` int unsigned NOT NULL,
  `classifiable_id` int unsigned NOT NULL,
  `classifiable_type` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `classifiables_taxonomy_id_foreign` (`taxonomy_id`),
  KEY `classifiables_classifiable_id_index` (`classifiable_id`),
  CONSTRAINT `classifiables_taxonomy_id_foreign` FOREIGN KEY (`taxonomy_id`) REFERENCES `taxonomies` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clients` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `surname` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `phone` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `mobile_phone` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `address` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `postal_code` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `city` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `province` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `dni` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `date_birth` date DEFAULT NULL,
  `newsletter` tinyint(1) NOT NULL DEFAULT '0',
  `locale` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT 'ca',
  `brand_id` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `reset_token` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `reset_token_expires_on` datetime DEFAULT NULL,
  `token_confirm_newsletter` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clients_email_brand_id_unique` (`email`,`brand_id`),
  KEY `clients_brand_id_foreign` (`brand_id`),
  CONSTRAINT `clients_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `codes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `keycode` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `brand_id` int unsigned DEFAULT NULL,
  `promotor_id` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `codes_brand_id_foreign` (`brand_id`),
  KEY `codes_promotor_id_foreign` (`promotor_id`),
  CONSTRAINT `codes_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`),
  CONSTRAINT `codes_promotor_id_foreign` FOREIGN KEY (`promotor_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `events` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned DEFAULT NULL,
  `brand_id` int unsigned DEFAULT NULL,
  `name` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `lead` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `slug` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `image` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `banner` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `phone` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `site` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `social` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `tags` varchar(1000) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `publish_on` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `custom_logo` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `custom_text` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `show_calendar` tinyint(1) NOT NULL DEFAULT '0',
  `full_width_calendar` tinyint(1) NOT NULL DEFAULT '0',
  `hide_exhausted_sessions` tinyint(1) NOT NULL DEFAULT '0',
  `enable_gift_card` tinyint(1) NOT NULL DEFAULT '0',
  `price_gift_card` decimal(5,2) DEFAULT NULL,
  `gift_card_text` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `gift_card_email_text` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `gift_card_legal_text` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `gift_card_footer_text` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `validate_all_event` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `events_user_id_foreign` (`user_id`),
  KEY `events_brand_id_foreign` (`brand_id`),
  KEY `events_publish_on_index` (`publish_on`),
  CONSTRAINT `events_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE,
  CONSTRAINT `events_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `connection` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `queue` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `exception` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `form_field_answers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `form_field_answers` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `client_id` int unsigned NOT NULL,
  `field_id` int unsigned NOT NULL,
  `answer` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `form_field_answers_client_id_foreign` (`client_id`),
  KEY `form_field_answers_field_id_foreign` (`field_id`),
  CONSTRAINT `form_field_answers_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`),
  CONSTRAINT `form_field_answers_field_id_foreign` FOREIGN KEY (`field_id`) REFERENCES `form_fields` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `form_fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `form_fields` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `weight` smallint unsigned NOT NULL,
  `brand_id` int unsigned DEFAULT NULL,
  `type` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `label` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `is_editable` tinyint(1) DEFAULT NULL,
  `lft` int unsigned DEFAULT NULL,
  `rgt` int unsigned DEFAULT NULL,
  `depth` int unsigned DEFAULT NULL,
  `parent_id` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `form_fields_brand_id_foreign` (`brand_id`),
  KEY `form_fields_parent_id_foreign` (`parent_id`),
  CONSTRAINT `form_fields_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`),
  CONSTRAINT `form_fields_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `form_fields` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `form_form_field`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `form_form_field` (
  `form_id` int NOT NULL,
  `form_field_id` int NOT NULL,
  KEY `form_form_field_form_id_foreign` (`form_id`),
  KEY `form_form_field_form_field_id_foreign` (`form_field_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `forms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `forms` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `brand_id` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `forms_brand_id_foreign` (`brand_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `gift_cards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `gift_cards` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `brand_id` int unsigned DEFAULT NULL,
  `event_id` int unsigned NOT NULL,
  `cart_id` int unsigned NOT NULL COMMENT 'Cart that has purchased the gift card',
  `code` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `price` decimal(5,2) NOT NULL DEFAULT '0.00',
  `pdf` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `gift_cards_code_unique` (`code`),
  KEY `gift_cards_brand_id_foreign` (`brand_id`),
  KEY `gift_cards_event_id_foreign` (`event_id`),
  KEY `gift_cards_cart_id_foreign` (`cart_id`),
  CONSTRAINT `gift_cards_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE,
  CONSTRAINT `gift_cards_cart_id_foreign` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `gift_cards_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `group_packs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `group_packs` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `pack_id` int unsigned NOT NULL,
  `cart_id` int unsigned NOT NULL,
  `pdf` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_user_id` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cart_pack_pack_id_foreign` (`pack_id`),
  KEY `cart_pack_cart_id_foreign` (`cart_id`),
  KEY `group_packs_deleted_user_id_foreign` (`deleted_user_id`),
  CONSTRAINT `cart_pack_cart_id_foreign` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`),
  CONSTRAINT `cart_pack_pack_id_foreign` FOREIGN KEY (`pack_id`) REFERENCES `packs` (`id`),
  CONSTRAINT `group_packs_deleted_user_id_foreign` FOREIGN KEY (`deleted_user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `inscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inscriptions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `cart_id` int unsigned DEFAULT NULL,
  `group_pack_id` int unsigned DEFAULT NULL,
  `session_id` int unsigned NOT NULL,
  `rate_id` int unsigned NOT NULL,
  `gift_card_id` int unsigned DEFAULT NULL,
  `slot_id` int unsigned DEFAULT NULL,
  `barcode` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `code` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `pdf` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `price` decimal(5,2) NOT NULL,
  `price_sold` decimal(7,4) NOT NULL,
  `checked_at` datetime DEFAULT NULL,
  `out_event` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_user_id` int unsigned DEFAULT NULL,
  `metadata` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `inscriptions_barcode_unique` (`barcode`),
  KEY `inscriptions_session_id_foreign` (`session_id`),
  KEY `inscriptions_rate_id_foreign` (`rate_id`),
  KEY `inscriptions_slot_id_foreign` (`slot_id`),
  KEY `inscriptions_barcode_index` (`barcode`),
  KEY `inscriptions_checked_at_index` (`checked_at`),
  KEY `inscriptions_group_pack_id_foreign` (`group_pack_id`),
  KEY `inscriptions_cart_id_foreign` (`cart_id`),
  KEY `inscriptions_deleted_user_id_foreign` (`deleted_user_id`),
  KEY `inscriptions_gift_card_id_foreign` (`gift_card_id`),
  CONSTRAINT `inscriptions_cart_id_foreign` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inscriptions_deleted_user_id_foreign` FOREIGN KEY (`deleted_user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `inscriptions_gift_card_id_foreign` FOREIGN KEY (`gift_card_id`) REFERENCES `gift_cards` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inscriptions_group_pack_id_foreign` FOREIGN KEY (`group_pack_id`) REFERENCES `group_packs` (`id`),
  CONSTRAINT `inscriptions_rate_id_foreign` FOREIGN KEY (`rate_id`) REFERENCES `rates` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inscriptions_session_id_foreign` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inscriptions_slot_id_foreign` FOREIGN KEY (`slot_id`) REFERENCES `slots` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_reserved_at_index` (`queue`,`reserved_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `locations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `slug` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `address` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `city_id` int unsigned DEFAULT NULL,
  `postal_code` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `phone1` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `phone2` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `other_info` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `user_id` int unsigned DEFAULT NULL,
  `brand_id` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `locations_user_id_foreign` (`user_id`),
  KEY `locations_brand_id_foreign` (`brand_id`),
  KEY `locations_city_id_foreign` (`city_id`),
  CONSTRAINT `locations_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE,
  CONSTRAINT `locations_city_id_foreign` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`),
  CONSTRAINT `locations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mailings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mailings` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `locale` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `subject` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `is_sent` tinyint(1) NOT NULL DEFAULT '0',
  `emails` longtext CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `succeed_sendings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `extra_content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `user_id` int unsigned DEFAULT NULL,
  `brand_id` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `interests` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mailings_user_id_foreign` (`user_id`),
  KEY `mailings_brand_id_foreign` (`brand_id`),
  CONSTRAINT `mailings_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE,
  CONSTRAINT `mailings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `menu_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `menu_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `brand_id` int unsigned DEFAULT NULL,
  `name` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `type` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `link` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `page_id` int unsigned DEFAULT NULL,
  `parent_id` int unsigned DEFAULT NULL,
  `lft` int unsigned DEFAULT NULL,
  `rgt` int unsigned DEFAULT NULL,
  `depth` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `menu_items_brand_id_foreign` (`brand_id`),
  CONSTRAINT `menu_items_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` int unsigned NOT NULL,
  `model_id` int unsigned NOT NULL,
  `model_type` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_roles` (
  `role_id` int unsigned NOT NULL,
  `model_id` int unsigned NOT NULL,
  `model_type` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `oauth_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `oauth_access_tokens` (
  `id` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `user_id` int DEFAULT NULL,
  `client_id` int NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `scopes` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `revoked` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `oauth_access_tokens_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `oauth_auth_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `oauth_auth_codes` (
  `id` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `user_id` int NOT NULL,
  `client_id` int NOT NULL,
  `scopes` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `revoked` tinyint(1) NOT NULL,
  `expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `oauth_clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `oauth_clients` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `secret` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `redirect` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `personal_access_client` tinyint(1) NOT NULL,
  `password_client` tinyint(1) NOT NULL,
  `revoked` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `oauth_clients_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `oauth_personal_access_clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `oauth_personal_access_clients` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `client_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `oauth_personal_access_clients_client_id_index` (`client_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `oauth_refresh_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `oauth_refresh_tokens` (
  `id` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `access_token_id` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `revoked` tinyint(1) NOT NULL,
  `expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `oauth_refresh_tokens_access_token_id_index` (`access_token_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pack_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pack_rules` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `pack_id` int unsigned NOT NULL,
  `number_sessions` int unsigned DEFAULT NULL,
  `all_sessions` tinyint(1) NOT NULL DEFAULT '0',
  `percent_pack` decimal(8,2) unsigned DEFAULT NULL,
  `price_pack` decimal(8,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pack_rules_pack_id_foreign` (`pack_id`),
  CONSTRAINT `pack_rules_pack_id_foreign` FOREIGN KEY (`pack_id`) REFERENCES `packs` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pack_session`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pack_session` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `pack_id` int unsigned NOT NULL,
  `session_id` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pack_session_pack_id_session_id_unique` (`pack_id`,`session_id`),
  KEY `pack_session_session_id_foreign` (`session_id`),
  CONSTRAINT `pack_session_pack_id_foreign` FOREIGN KEY (`pack_id`) REFERENCES `packs` (`id`),
  CONSTRAINT `pack_session_session_id_foreign` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `packs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `packs` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `brand_id` int unsigned DEFAULT NULL,
  `min_per_cart` int NOT NULL DEFAULT '1',
  `max_per_cart` int unsigned NOT NULL DEFAULT '1',
  `round_to_nearest` tinyint(1) NOT NULL DEFAULT '0',
  `one_session_x_event` tinyint(1) NOT NULL DEFAULT '0',
  `starts_on` datetime NOT NULL,
  `ends_on` datetime DEFAULT NULL,
  `image` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `name` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `slug` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `color` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `banner` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `custom_logo` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `bg_color` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `cart_rounded` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `packs_brand_id_foreign` (`brand_id`),
  CONSTRAINT `packs_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pages` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `brand_id` int unsigned DEFAULT NULL,
  `template` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `title` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `slug` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `extras` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pages_brand_id_foreign` (`brand_id`),
  CONSTRAINT `pages_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_resets` (
  `email` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  KEY `password_resets_email_index` (`email`),
  KEY `password_resets_token_index` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `cart_id` int unsigned NOT NULL,
  `tpv_id` int unsigned DEFAULT NULL,
  `tpv_name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `order_code` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `gateway` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `gateway_response` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_user_id` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payments_cart_id_foreign` (`cart_id`),
  KEY `payments_tpv_id_foreign` (`tpv_id`),
  KEY `payments_deleted_user_id_foreign` (`deleted_user_id`),
  CONSTRAINT `payments_cart_id_foreign` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payments_deleted_user_id_foreign` FOREIGN KEY (`deleted_user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `payments_tpv_id_foreign` FOREIGN KEY (`tpv_id`) REFERENCES `tpvs` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `guard_name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `posts` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `brand_id` int unsigned DEFAULT NULL,
  `name` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `slug` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `publish_on` datetime DEFAULT NULL,
  `meta_description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `lead` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `body` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `image` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `posts_brand_id_foreign` (`brand_id`),
  CONSTRAINT `posts_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `provinces`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `provinces` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `rates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rates` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `brand_id` int unsigned DEFAULT NULL,
  `name` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `needs_code` tinyint(1) NOT NULL DEFAULT '0',
  `validator_class` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `attributes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `form_name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `has_rule` tinyint(1) NOT NULL DEFAULT '0',
  `rule_parameters` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `form_id` int unsigned DEFAULT NULL,
  `parent_id` int unsigned DEFAULT NULL,
  `lft` int unsigned DEFAULT NULL,
  `rgt` int unsigned DEFAULT NULL,
  `depth` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rates_brand_id_foreign` (`brand_id`),
  KEY `rates_form_id_index` (`form_id`),
  CONSTRAINT `rates_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `regions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `regions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `province_id` int unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `regions_province_id_foreign` (`province_id`),
  CONSTRAINT `regions_province_id_foreign` FOREIGN KEY (`province_id`) REFERENCES `provinces` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `register_inputs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `register_inputs` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `name_form` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `type` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `register_inputs_name_form_unique` (`name_form`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` int unsigned NOT NULL,
  `role_id` int unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `guard_name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `brand_id` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_unique` (`name`),
  KEY `roles_brand_id_foreign` (`brand_id`),
  CONSTRAINT `roles_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `session_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `session_codes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `session_id` int unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `code` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `session_codes_session_id_foreign` (`session_id`),
  CONSTRAINT `session_codes_session_id_foreign` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `session_slot`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `session_slot` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `session_id` int unsigned DEFAULT NULL,
  `slot_id` int unsigned DEFAULT NULL,
  `status_id` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `comment` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `session_slot_session_id_foreign` (`session_id`),
  KEY `session_slot_slot_id_foreign` (`slot_id`),
  KEY `session_slot_status_id_foreign` (`status_id`),
  CONSTRAINT `session_slot_session_id_foreign` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`),
  CONSTRAINT `session_slot_slot_id_foreign` FOREIGN KEY (`slot_id`) REFERENCES `slots` (`id`),
  CONSTRAINT `session_slot_status_id_foreign` FOREIGN KEY (`status_id`) REFERENCES `statuses` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `session_temp_slot`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `session_temp_slot` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `session_id` int unsigned NOT NULL,
  `slot_id` int unsigned NOT NULL,
  `cart_id` int unsigned NOT NULL,
  `inscription_id` int unsigned NOT NULL,
  `status_id` int unsigned DEFAULT NULL,
  `expires_on` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `session_temp_slot_session_id_foreign` (`session_id`),
  KEY `session_temp_slot_slot_id_foreign` (`slot_id`),
  KEY `session_temp_slot_cart_id_foreign` (`cart_id`),
  KEY `session_temp_slot_inscription_id_foreign` (`inscription_id`),
  KEY `session_temp_slot_status_id_foreign` (`status_id`),
  KEY `session_temp_slot_expires_on_index` (`expires_on`),
  CONSTRAINT `session_temp_slot_cart_id_foreign` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`),
  CONSTRAINT `session_temp_slot_inscription_id_foreign` FOREIGN KEY (`inscription_id`) REFERENCES `inscriptions` (`id`),
  CONSTRAINT `session_temp_slot_session_id_foreign` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`),
  CONSTRAINT `session_temp_slot_slot_id_foreign` FOREIGN KEY (`slot_id`) REFERENCES `slots` (`id`),
  CONSTRAINT `session_temp_slot_status_id_foreign` FOREIGN KEY (`status_id`) REFERENCES `statuses` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned DEFAULT NULL,
  `brand_id` int unsigned DEFAULT NULL,
  `event_id` int unsigned DEFAULT NULL,
  `space_id` int unsigned DEFAULT NULL,
  `space_configuration_id` int unsigned DEFAULT NULL,
  `tpv_id` int unsigned DEFAULT NULL,
  `is_numbered` tinyint(1) NOT NULL DEFAULT '0',
  `name` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `max_places` mediumint unsigned DEFAULT NULL,
  `max_inscr_per_order` mediumint unsigned DEFAULT NULL,
  `starts_on` datetime NOT NULL,
  `ends_on` datetime NOT NULL,
  `inscription_starts_on` datetime NOT NULL,
  `inscription_ends_on` datetime NOT NULL,
  `tags` varchar(1000) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `external_url` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `autolock_type` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `autolock_n` int unsigned NOT NULL DEFAULT '0',
  `limit_x_100` int NOT NULL DEFAULT '100',
  `liquidation` tinyint(1) NOT NULL DEFAULT '0',
  `visibility` tinyint(1) NOT NULL DEFAULT '1',
  `private` tinyint(1) NOT NULL DEFAULT '0',
  `only_pack` tinyint(1) NOT NULL DEFAULT '0',
  `session_color` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `hide_n_positions` int unsigned NOT NULL DEFAULT '0',
  `banner` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `custom_logo` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `session_bg_color` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `code_type` enum('null','census','session','user') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'null',
  `validate_all_session` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_foreign` (`user_id`),
  KEY `sessions_brand_id_foreign` (`brand_id`),
  KEY `sessions_event_id_foreign` (`event_id`),
  KEY `sessions_space_id_foreign` (`space_id`),
  KEY `sessions_space_configuration_id_foreign` (`space_configuration_id`),
  KEY `sessions_tpv_id_foreign` (`tpv_id`),
  KEY `sessions_visibility_index` (`visibility`),
  KEY `sessions_starts_on_index` (`starts_on`),
  KEY `sessions_ends_on_index` (`ends_on`),
  KEY `sessions_inscription_starts_on_index` (`inscription_starts_on`),
  KEY `sessions_inscription_ends_on_index` (`inscription_ends_on`),
  CONSTRAINT `sessions_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sessions_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sessions_space_configuration_id_foreign` FOREIGN KEY (`space_configuration_id`) REFERENCES `space_configurations` (`id`),
  CONSTRAINT `sessions_space_id_foreign` FOREIGN KEY (`space_id`) REFERENCES `spaces` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sessions_tpv_id_foreign` FOREIGN KEY (`tpv_id`) REFERENCES `tpvs` (`id`),
  CONSTRAINT `sessions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `brand_id` int unsigned NOT NULL,
  `key` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `value` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `category` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `access` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `settings_brand_id_foreign` (`brand_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `slots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `slots` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `space_id` int unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `status_id` int unsigned DEFAULT NULL,
  `comment` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `x` int unsigned DEFAULT NULL,
  `y` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `slots_space_id_foreign` (`space_id`),
  KEY `slots_status_id_foreign` (`status_id`),
  KEY `slots_x_index` (`x`),
  KEY `slots_y_index` (`y`),
  CONSTRAINT `slots_space_id_foreign` FOREIGN KEY (`space_id`) REFERENCES `spaces` (`id`),
  CONSTRAINT `slots_status_id_foreign` FOREIGN KEY (`status_id`) REFERENCES `statuses` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `space_configuration_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `space_configuration_details` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `space_configuration_id` int unsigned NOT NULL,
  `zone_id` int unsigned NOT NULL,
  `slot_id` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `space_config_zone_slot_unique` (`space_configuration_id`,`slot_id`),
  KEY `space_configuration_details_zone_id_foreign` (`zone_id`),
  KEY `space_configuration_details_slot_id_foreign` (`slot_id`),
  KEY `space_configuration_details_space_configuration_id_index` (`space_configuration_id`),
  CONSTRAINT `space_configuration_details_slot_id_foreign` FOREIGN KEY (`slot_id`) REFERENCES `slots` (`id`),
  CONSTRAINT `space_configuration_details_space_configuration_id_foreign` FOREIGN KEY (`space_configuration_id`) REFERENCES `space_configurations` (`id`),
  CONSTRAINT `space_configuration_details_zone_id_foreign` FOREIGN KEY (`zone_id`) REFERENCES `zones` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `space_configurations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `space_configurations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `space_id` int unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `space_configurations_space_id_foreign` (`space_id`),
  CONSTRAINT `space_configurations_space_id_foreign` FOREIGN KEY (`space_id`) REFERENCES `spaces` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `spaces`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `spaces` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `slug` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `capacity` mediumint unsigned NOT NULL DEFAULT '10',
  `user_id` int unsigned DEFAULT NULL,
  `brand_id` int unsigned DEFAULT NULL,
  `location_id` int unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `svg_path` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `hide` tinyint(1) NOT NULL DEFAULT '0',
  `zoom` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `spaces_user_id_foreign` (`user_id`),
  KEY `spaces_brand_id_foreign` (`brand_id`),
  KEY `spaces_location_id_foreign` (`location_id`),
  CONSTRAINT `spaces_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE,
  CONSTRAINT `spaces_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `spaces_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stats_sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stats_sales` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `client_id` int unsigned DEFAULT NULL,
  `event_id` int unsigned DEFAULT NULL,
  `inscription_id` int unsigned DEFAULT NULL,
  `session_id` int unsigned DEFAULT NULL,
  `event_name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stats_sales_client_id_foreign` (`client_id`),
  KEY `stats_sales_event_id_foreign` (`event_id`),
  KEY `stats_sales_session_id_foreign` (`session_id`),
  KEY `stats_sales_inscription_id_foreign` (`inscription_id`),
  CONSTRAINT `stats_sales_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`),
  CONSTRAINT `stats_sales_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`),
  CONSTRAINT `stats_sales_inscription_id_foreign` FOREIGN KEY (`inscription_id`) REFERENCES `inscriptions` (`id`),
  CONSTRAINT `stats_sales_session_id_foreign` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `statuses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `statuses` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `slug` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sync_validations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sync_validations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `request` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `response` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `event_id` int unsigned NOT NULL,
  `user_id` int unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sync_validations_event_id_foreign` (`event_id`),
  KEY `sync_validations_user_id_foreign` (`user_id`),
  CONSTRAINT `sync_validations_event_id_foreign` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sync_validations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `taxonomies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `taxonomies` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `slug` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `lft` int unsigned DEFAULT NULL,
  `rgt` int unsigned DEFAULT NULL,
  `depth` int unsigned DEFAULT NULL,
  `parent_id` int unsigned DEFAULT NULL,
  `user_id` int unsigned DEFAULT NULL,
  `brand_id` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `taxonomies_parent_id_foreign` (`parent_id`),
  KEY `taxonomies_user_id_foreign` (`user_id`),
  KEY `taxonomies_brand_id_foreign` (`brand_id`),
  FULLTEXT KEY `tax_slug` (`slug`),
  CONSTRAINT `taxonomies_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE,
  CONSTRAINT `taxonomies_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `taxonomies` (`id`),
  CONSTRAINT `taxonomies_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tpvs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tpvs` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `brand_id` int unsigned DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `omnipay_type` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tpvs_brand_id_foreign` (`brand_id`),
  CONSTRAINT `tpvs_brand_id_foreign` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `update_notification_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `update_notification_user` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `update_notification_id` int unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `update_notification_user_user_id_index` (`user_id`),
  KEY `update_notification_user_update_notification_id_index` (`update_notification_id`),
  CONSTRAINT `update_notification_user_update_notification_id_foreign` FOREIGN KEY (`update_notification_id`) REFERENCES `update_notifications` (`id`),
  CONSTRAINT `update_notification_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `update_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `update_notifications` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `version` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `subject` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `remember_token` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `allowed_ips` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `visibility` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `zones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `zones` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (66,'2014_10_12_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (67,'2014_10_12_100000_create_password_resets_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (68,'2017_02_03_093147_create_brands_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (69,'2017_02_03_093203_create_applications_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (70,'2017_02_03_093928_create_capabilities_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (71,'2017_02_03_094901_create_brand_capability_pivot_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (72,'2017_02_06_114855_create_brand_user_pivot_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (73,'2017_02_06_133721_create_clients_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (74,'2017_02_08_130946_create_permission_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (75,'2017_02_21_122720_create_events_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (76,'2017_05_18_175015_create_locations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (77,'2017_05_18_175016_create_spaces_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (78,'2017_05_19_092941_create_sessions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (79,'2017_07_18_091735_create_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (80,'2017_07_18_102324_create_failed_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (81,'2017_07_24_141640_create_tpvs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (82,'2017_07_26_084957_create_rates_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (83,'2017_07_26_085505_create_assignated_rates_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (84,'2017_07_26_091114_create_inscriptions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (85,'2017_08_01_103843_create_carts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (86,'2017_08_01_104207_create_cartables_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (87,'2017_08_24_090256_create_packs_structure',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (88,'2017_09_19_100705_create_payments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (89,'2017_10_17_120002_add_image_to_sessions',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (90,'2017_10_24_133720_create_all_session_in_pack_rules',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (91,'2017_10_31_125005_re_create_permission_tables',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (92,'2017_10_31_120329_numbered_sessions_tables',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (93,'2017_11_22_195508_alter_user_add_allowed_ip',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (94,'2017_11_24_085414_ticket_office_migration',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (95,'2017_11_28_090009_create_tags',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (99,'2017_12_14_123543_create_new_attributes_event_and_session',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (100,'2017_12_07_095533_numbered_sessions_v2_table',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (101,'2017_12_19_175348_delete_unused_tables',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (102,'2018_01_16_121956_create_statuses_table',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (103,'2018_01_16_124839_add_slot_status_relation',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (104,'2018_01_16_145228_create_session_slot_table',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (105,'2018_02_22_165626_create_custom_forms',13);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (106,'2018_03_14_115335_reset_password_token',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (107,'2018_03_22_124726_create_taxonomies_tables',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (108,'2018_04_13_104054_create_posts_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (109,'2018_04_13_115641_create_menu_items_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (110,'2018_04_13_115641_create_pages_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (111,'2018_04_13_115841_change_extras_to_longtext',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (113,'2018_04_19_091525_create_stats_sales',16);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (114,'2018_04_20_092327_multiple_tpv_altertions',17);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (115,'2018_04_24_132634_create_table_mailings',17);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (116,'2018_05_24_192536_add_success_sent_mailings',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (117,'2018_07_02_134846_alter_brands_table',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (119,'2018_07_20_084403_alter_mailings_table',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (120,'2018_09_14_124210_alter_sessions_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (121,'2018_11_27_101252_alter_rates_table',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (122,'2018_11_28_120711_add_extra_fields_assignated_rates',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (123,'2018_11_28_085822_create_update_notifications_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (124,'2018_11_28_152701_create_user_update_notification_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (125,'2019_01_11_133212_create_settings_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (126,'2019_01_20_155910_alter_carts_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (127,'2019_01_24_234154_alter_slot_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (128,'2019_01_30_164040_alter_table_brands',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (129,'2019_09_23_180945_add_visibility_field_to_users',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (130,'2019_10_03_114817_add_fields_to_settings_table',28);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (131,'2019_11_07_113925_add_form_to_rates',29);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (132,'2019_11_07_181849_add_metadata_field_to_inscriptions_table',29);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (133,'2019_11_14_170851_add_interests_field_to_mailings_table',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (134,'2019_12_05_113335_add_should_round_to_packs_table',31);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (135,'2020_05_15_165200_add_only_one_event_packs_table',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (136,'2020_04_23_113700_add_description_brands_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (137,'2020_06_17_201552_add_has_rule_field_to_rates_table',34);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (138,'2020_06_29_203022_add_alert_fields_to_brand_table',35);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (139,'2020_07_06_172105_create_forms_table',36);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (140,'2020_06_29_203022_add_token_confirm_newsletter_clients_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (141,'2020_09_29_133322_add_custom_script_to_brand_table',38);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (142,'2020_11_11_151515_add_x_y_slots_table',39);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (143,'2020_11_12_160000_create_session_temp_slot_table',39);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (144,'2020_11_18_121800_add_session_autolock_table',39);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (145,'2016_06_01_000001_create_oauth_auth_codes_table',40);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (146,'2016_06_01_000002_create_oauth_access_tokens_table',40);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (147,'2016_06_01_000003_create_oauth_refresh_tokens_table',40);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (148,'2016_06_01_000004_create_oauth_clients_table',40);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (149,'2016_06_01_000005_create_oauth_personal_access_clients_table',40);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (150,'2021_02_09_171200_add_session_limitX100_table',41);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (151,'2021_02_22_094500_add_reorder_rates_table',42);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (152,'2021_02_22_131700_add_liquidation_sessions_table',43);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (153,'2021_05_04_155500_alter_role_table',44);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (154,'2021_05_05_172300_alter_remove_columns_user_table',44);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (155,'2021_06_02_100729_add_visibility_to_sessions_table',45);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (156,'2021_06_10_172052_create_codes_table',46);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (157,'2021_06_13_083634_add_phone_to_brand_table',46);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (158,'2021_07_22_094700_add_custom_logo_event_table',47);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (159,'2021_07_19_135901_create_provinces_table',48);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (160,'2021_07_19_140501_create_regions_table',48);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (161,'2021_07_19_140901_create_cities_table',48);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (162,'2021_07_19_171500_add_city_locations_table',48);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (163,'2021_08_30_103249_add_status_to_cities_table',49);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (165,'2021_09_23_103558_add_privacy_policy_to_brand_table',50);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (166,'2021_10_21_095418_add_email_and_comment_to_brands_table',51);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (167,'2021_11_09_160000_add_hide_space_table',52);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (168,'2021_11_15_112400_add_iframe_gmaps_to_location_table',53);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (169,'2021_11_24_064237_delete_city_to_locations_table',54);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (170,'2021_11_24_065514_delete_image_to_locations_table',54);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (171,'2021_11_24_102611_delete_info_from_sessions_table',55);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (172,'2021_12_21_092907_add_show_calendar_to_events_table',56);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (173,'2021_12_23_103530_add_full_width_calendar_to_events_table',57);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (174,'2021_12_23_122201_add_hide_exhausted_sessions_to_events_table',58);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (175,'2022_01_31_114216_add_index_to_assignated_rates_table',59);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (176,'2022_01_31_114551_add_indexes_to_cities_table',59);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (177,'2022_01_31_121617_add_indexes_to_classifiables_table',59);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (178,'2022_01_31_121937_add_indexes_to_form_form_field_table',59);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (179,'2022_01_31_122531_add_index_to_inscriptions_table',59);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (180,'2022_01_31_123441_add_foreign_to_locations_table',59);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (181,'2022_01_31_123942_add_foreign_to_regions_table',59);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (182,'2022_01_31_124835_add_index_to_space_configuration_details_table',59);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (183,'2022_01_31_125247_add_index_to_rates_table',59);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (192,'2022_02_22_113647_add_index_expires_on_to_carts_table',60);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (193,'2022_02_22_114100_add_index_to_name_on_events_table',60);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (194,'2022_02_22_120409_add_index_to_is_locked_on_cache_session_slot__table',60);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (195,'2022_02_22_120608_add_indexos_to_inscriptions_table',60);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (196,'2022_02_22_123316_add_index_to_slots_table',60);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (197,'2022_02_22_123712_add_index_to_sessions_table',60);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (198,'2022_02_22_124520_add_index_to_expires_on_on_session_temp_slot_table',60);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (199,'2022_02_22_130752_add_index_is_public_to_assignated_rates_table',60);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (200,'2022_02_22_125033_add_v_name_to_events_table',61);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (201,'2022_05_05_103300_add_private_to_sessions_table',61);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (202,'2022_05_14_094905_add_banner_image_to_brand_table',62);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (203,'2022_05_16_132220_add_banner_to_events_table',63);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (204,'2022_05_17_081015_add_out_event_to_inscriptions_table',64);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (205,'2022_05_17_111459_add_extra_info_to_clients_table',65);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (206,'2022_05_19_194352_create_register_inputs_table',65);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (207,'2022_06_14_111480_add_zoom_space_table',66);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (208,'2022_06_21_113426_add_just_pack_to_sessions_table',67);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (209,'2022_07_06_121416_add_min_per_cart_to_packs_table',68);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (210,'2022_07_12_085405_add_session_color_to_sessions_table',69);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (211,'2022_07_12_162615_add_pack_color_to_packs_table',69);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (212,'2022_08_03_101530_add_legals_to_brands_table',70);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (213,'2022_08_04_110219_add_newsletter_to_clients_table',71);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (214,'2022_08_10_105902_add_packs_id_and_cart_id_to_inscription',72);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (215,'2022_09_12_122300_add_hide_n_positions_sessions_table',73);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (216,'2022_09_20_133211_add_deleted_at_to_payments_table',74);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (217,'2023_03_09_124800_create_sync_validations_table',75);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (218,'2023_05_10_150936_add_deleted_user_id_to_carts_table',76);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (219,'2023_05_10_154247_add_deleted_user_id_to_inscriptions_table',76);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (220,'2023_05_10_154729_add_deleted_user_id_to_payments_table',76);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (221,'2023_05_10_160249_add_deleted_user_id_to_group_packs_table',76);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (222,'2023_06_01_091740_add_banner_and_custom_logo_to_sessions_table',77);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (223,'2023_06_06_122506_add_row_to_packs_table',78);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (224,'2023_06_06_174804_add_cart_rounded_to_packs_table',79);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (225,'2023_09_27_090654_add_aux_code_to_brands_table',80);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (226,'2023_10_10_145300_create_gift_card_table',81);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (227,'2023_10_11_102700_add_gift_events_table',81);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (228,'2023_10_18_095700_add_gift_inscriptions_table',81);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (229,'2023_10_25_121000_add_custom_gift_text_table',82);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (230,'2023_11_10_080955_add_gift_footer_text_to_events_table',83);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (231,'2023_11_07_140600_add_custom_gift_text_table',84);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (232,'2024_01_09_090958_add_is_private_to_assignated_rates_table',85);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (233,'2024_01_09_092553_create_session_codes_table',85);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (234,'2024_01_09_113431_add_datetime_to_assignated_rate_table',85);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (235,'2024_01_10_150021_add_max_per_code_to_assignated_rates_table',85);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (236,'2024_01_11_111229_add_code_to_inscriptions_table',85);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (237,'2024_06_20_113501_create_census_table',86);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (238,'2024_06_20_143402_add_type_code_to_sessions_table',86);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (239,'2024_12_17_123004_add_validate_all_session_to_sessions_table',87);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (240,'2025_01_15_083513_add_validate_all_sessions_to_events_table',88);

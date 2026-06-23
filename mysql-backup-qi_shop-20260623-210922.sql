-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: qi_shop
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `admin_users`
--

DROP TABLE IF EXISTS `admin_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(80) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'active',
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `idx_admin_users_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_users`
--

LOCK TABLES `admin_users` WRITE;
/*!40000 ALTER TABLE `admin_users` DISABLE KEYS */;
INSERT INTO `admin_users` VALUES (1,'admin','$2y$10$s/zlDsGUognAxiEsln/.r.UGWkjRyJdq2SJOuc467TEvTXs1prewK','active','2026-06-23 16:27:31','2026-06-04 15:19:50','2026-06-23 16:27:31');
/*!40000 ALTER TABLE `admin_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `content_settings`
--

DROP TABLE IF EXISTS `content_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `content_settings` (
  `setting_key` varchar(120) NOT NULL,
  `setting_value` text NOT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `content_settings`
--

LOCK TABLES `content_settings` WRITE;
/*!40000 ALTER TABLE `content_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `content_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `coupons`
--

DROP TABLE IF EXISTS `coupons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `coupons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(80) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `min_order` decimal(10,2) NOT NULL DEFAULT 0.00,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'active',
  `max_usage` int(11) DEFAULT NULL,
  `used_count` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `coupons`
--

LOCK TABLES `coupons` WRITE;
/*!40000 ALTER TABLE `coupons` DISABLE KEYS */;
INSERT INTO `coupons` VALUES (3,'qii5','1',1.00,0.99,'2026-06-08','2026-05-14','active',1,0,'2026-06-05 00:45:02');
/*!40000 ALTER TABLE `coupons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customer_action_tokens`
--

DROP TABLE IF EXISTS `customer_action_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customer_action_tokens` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `purpose` varchar(30) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token_hash` (`token_hash`),
  KEY `idx_action_customer` (`customer_id`),
  KEY `idx_action_purpose` (`purpose`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_action_tokens`
--

LOCK TABLES `customer_action_tokens` WRITE;
/*!40000 ALTER TABLE `customer_action_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `customer_action_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customer_favorites`
--

DROP TABLE IF EXISTS `customer_favorites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customer_favorites` (
  `customer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`customer_id`,`product_id`),
  KEY `idx_favorites_product` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_favorites`
--

LOCK TABLES `customer_favorites` WRITE;
/*!40000 ALTER TABLE `customer_favorites` DISABLE KEYS */;
INSERT INTO `customer_favorites` VALUES (1,2,'2026-06-09 21:03:57'),(1,8,'2026-06-09 21:44:49');
/*!40000 ALTER TABLE `customer_favorites` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customer_remember_tokens`
--

DROP TABLE IF EXISTS `customer_remember_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customer_remember_tokens` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `selector` varchar(64) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `selector` (`selector`),
  KEY `idx_remember_customer` (`customer_id`),
  KEY `idx_remember_expires` (`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_remember_tokens`
--

LOCK TABLES `customer_remember_tokens` WRITE;
/*!40000 ALTER TABLE `customer_remember_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `customer_remember_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(160) NOT NULL,
  `email` varchar(190) NOT NULL,
  `email_verified_at` datetime DEFAULT NULL,
  `phone` varchar(80) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'active',
  `last_login_at` datetime DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `admin_tags` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_customers_status` (`status`),
  KEY `idx_customers_phone` (`phone`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
INSERT INTO `customers` VALUES (1,'lau hongcher','mone2854@gmail.com',NULL,'0189694982','$2y$10$cfrp1AayEvPG78pRZjnT1.9Un049uhmrTXnP/ZzOIylWMuCh1gaLa','active','2026-06-09 21:15:30','11','11','2026-06-08 23:34:16','2026-06-09 22:08:37');
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(160) NOT NULL,
  `email` varchar(190) DEFAULT NULL,
  `message` text NOT NULL,
  `reply` text DEFAULT NULL,
  `replied_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `messages`
--

LOCK TABLES `messages` WRITE;
/*!40000 ALTER TABLE `messages` DISABLE KEYS */;
INSERT INTO `messages` VALUES (1,'Hongcher Lau','admin@khorbeauty.com','1',NULL,NULL,'2026-06-04 16:24:37');
/*!40000 ALTER TABLE `messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `variant_name` varchar(160) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `sku` varchar(100) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_order_items_order` (`order_id`),
  CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
INSERT INTO `order_items` VALUES (1,1,'手机链/挂包 蓝色小挂件','',2,6.99,'','2026-06-04 15:52:57'),(2,2,'手机链/挂包 蓝色小挂件','',2,6.99,'','2026-06-04 16:01:06'),(4,4,'可爱零食小包','',1,3.99,'S001','2026-06-23 14:35:36'),(5,5,'可爱零食小包','',1,3.99,'S001','2026-06-23 15:22:29'),(6,5,'11','',1,111.00,'A0031','2026-06-23 15:22:29'),(7,6,'11','',1,111.00,'A0031','2026-06-23 16:29:06'),(8,7,'11','',1,111.00,'A0031','2026-06-23 16:40:56'),(9,8,'11','',2,111.00,'A0031','2026-06-23 16:47:57');
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) DEFAULT NULL,
  `order_number` varchar(80) NOT NULL,
  `receipt_token` varchar(128) DEFAULT NULL,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `shipping` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `coupon_code` varchar(80) DEFAULT NULL,
  `grand_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `region` varchar(20) DEFAULT NULL,
  `order_status` varchar(80) NOT NULL DEFAULT 'draft',
  `addr_name` varchar(160) DEFAULT NULL,
  `addr_phone` varchar(80) DEFAULT NULL,
  `addr_address` text DEFAULT NULL,
  `addr_postcode` varchar(20) DEFAULT NULL,
  `addr_state` varchar(80) DEFAULT NULL,
  `order_note` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_number` (`order_number`),
  KEY `idx_orders_created` (`created_at`),
  KEY `idx_orders_status` (`order_status`),
  KEY `idx_orders_customer` (`customer_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (1,NULL,'QII260604155251979',NULL,13.98,10.00,0.00,NULL,23.98,'west','pending','Hongcher Lau','0189694982','1 taman ria jalan salleh 84000 muar','84000','1',NULL,'2026-06-04 15:52:57',NULL),(2,NULL,'QII260604160100218',NULL,13.98,10.00,0.00,NULL,23.98,'west','paid','Hongcher Lau','0189694982','1 taman ria jalan salleh 84000 muar','84000','Johor',NULL,'2026-06-04 16:01:06','2026-06-05 00:44:09'),(4,NULL,'QII260623143530471','89f9e7b9a96b78d7ebb0248b103bbc33355ecfdc35975771',3.99,10.00,0.00,NULL,13.99,'west','pending','lui','','118, Jalan Salleh','84000','Johor',NULL,'2026-06-23 14:35:36',NULL),(5,NULL,'QII260623152222539','71f2840f6efda9404ecb952eb4936190e29b8ff13e45c319',114.99,0.00,0.00,NULL,114.99,'west','pending','Hongcher lau','0189694982','118, Jalan Salleh','84000','Johor',NULL,'2026-06-23 15:22:29',NULL),(6,NULL,'QII260623162901827','e6efaad32f00e00843633121cea446257ea119187d2bdc7b',111.00,0.00,0.00,NULL,111.00,'west','pending','lui','','118, Jalan Salleh','84000','Johor',NULL,'2026-06-23 16:29:06',NULL),(7,NULL,'QII260623164052399','eebaf2817a715de2993f9cf5206e77483226d50a90e71997',111.00,0.00,0.00,NULL,111.00,'west','pending','lui','','118, Jalan Salleh','84000','Johor',NULL,'2026-06-23 16:40:56',NULL),(8,NULL,'QII260623164752786','21f3bbd91a48cddfc023f6ea9e49e9b50f7a9bb8f60e61b3',222.00,0.00,0.00,NULL,222.00,'west','pending','lui','','118, Jalan Salleh','84000','Johor',NULL,'2026-06-23 16:47:57',NULL);
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_categories`
--

DROP TABLE IF EXISTS `product_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_key` varchar(80) NOT NULL,
  `name` text NOT NULL,
  `emoji` varchar(20) NOT NULL DEFAULT '',
  `color` varchar(20) NOT NULL DEFAULT '#2b223d',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `status` varchar(30) NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `category_key` (`category_key`)
) ENGINE=InnoDB AUTO_INCREMENT=3206 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_categories`
--

LOCK TABLES `product_categories` WRITE;
/*!40000 ALTER TABLE `product_categories` DISABLE KEYS */;
INSERT INTO `product_categories` VALUES (1,'phone','手机配件\r\n配件','📱','#5e5969',1,'active','2026-06-08 23:19:01'),(2,'hair','发夹发饰','🎀','#2b223d',2,'active','2026-06-08 23:19:01'),(3,'snack','零食\r\nqq','🍬','#8a5151',3,'active','2026-06-08 23:19:01'),(4,'creative','文创','💗','#2b223d',4,'active','2026-06-08 23:19:01'),(5,'case','手机壳','📱','#2b223d',5,'active','2026-06-08 23:19:01'),(6,'nail','穿戴甲','💅','#2b223d',6,'active','2026-06-08 23:19:01'),(7,'scent','香片','🌸','#2b223d',7,'active','2026-06-08 23:19:01'),(8,'doll','娃娃','🧸','#2b223d',8,'active','2026-06-08 23:19:01'),(9,'stationery','文具','✏️','#2b223d',9,'active','2026-06-08 23:19:01');
/*!40000 ALTER TABLE `product_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_groups`
--

DROP TABLE IF EXISTS `product_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `group_name` varchar(120) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_groups_product` (`product_id`),
  CONSTRAINT `fk_groups_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_groups`
--

LOCK TABLES `product_groups` WRITE;
/*!40000 ALTER TABLE `product_groups` DISABLE KEYS */;
INSERT INTO `product_groups` VALUES (2,1,'规格',1,'2026-06-04 17:44:11'),(5,5,'规格',1,'2026-06-05 00:06:50'),(9,8,'规格',1,'2026-06-10 16:03:25'),(12,9,'规格',1,'2026-06-10 16:49:11');
/*!40000 ALTER TABLE `product_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_variants`
--

DROP TABLE IF EXISTS `product_variants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_variants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL,
  `variant_name` varchar(160) NOT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `image_url` varchar(255) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_variants_group` (`group_id`),
  CONSTRAINT `fk_variants_group` FOREIGN KEY (`group_id`) REFERENCES `product_groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_variants`
--

LOCK TABLES `product_variants` WRITE;
/*!40000 ALTER TABLE `product_variants` DISABLE KEYS */;
INSERT INTO `product_variants` VALUES (3,5,'1','H001-01-01',4.99,12,'images/products/product_6a21a29a86a132.65330471.png',1,'2026-06-05 00:06:50',NULL),(4,5,'21','11',0.00,1,'images/products/product_6a21a270568669.46474422.png',2,'2026-06-05 00:06:50',NULL),(7,9,'11','A001-01',6.99,19,'images/products/product_6a291a4d45ec47.74251515.jpg',1,'2026-06-10 16:03:25',NULL),(8,9,'11','11',1.00,1,'images/products/product_6a291a4d495770.59300153.png',2,'2026-06-10 16:03:25',NULL),(15,12,'11','H001-01-0111',4.99,12,'images/products/9bf773331c0ea831394df577d33571e0.jpg',1,'2026-06-10 16:49:11',NULL),(16,12,'1111','111111',0.00,0,'images/products/8fcbf71423753d7d99802ec056522f46.png',2,'2026-06-10 16:49:11',NULL);
/*!40000 ALTER TABLE `product_variants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sku` varchar(100) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `stock` int(11) NOT NULL DEFAULT 0,
  `warning_level` int(11) NOT NULL DEFAULT 5,
  `category` varchar(80) NOT NULL DEFAULT 'phone',
  `brand` varchar(120) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'active',
  `image_url` varchar(255) DEFAULT NULL,
  `has_variant` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_products_category` (`category`),
  KEY `idx_products_sku` (`sku`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,'A001-01-01','手机链/挂包 蓝色小挂件',6.99,16,5,'phone','','inactive','images/10.png',1,6,'2026-06-03 13:11:49','2026-06-12 16:09:34'),(2,'A002','粉色可爱钥匙扣',6.99,20,5,'phone',NULL,'active','images/11.png',0,3,'2026-06-03 13:11:49','2026-06-12 16:09:34'),(3,'A003','蓝色小熊挂件',7.99,16,5,'phone','','active','images/12.png',0,4,'2026-06-03 13:11:49','2026-06-12 16:09:34'),(4,'A004','粉色甜心挂件',5.99,18,5,'phone',NULL,'active','images/13.png',0,5,'2026-06-03 13:11:49','2026-06-12 16:09:34'),(5,'H001-01-01','蝴蝶结发夹',4.99,13,5,'hair','','active','images/14.png',1,1,'2026-06-03 13:11:49','2026-06-12 16:15:31'),(6,'S001','可爱零食小包',3.99,28,5,'snack',NULL,'active','images/15.png',0,1,'2026-06-03 13:11:49','2026-06-23 15:22:29'),(8,'A001-01','手机链/挂包 蓝色小挂件',6.99,20,5,'phone','1','active','images/10.png',1,2,'2026-06-08 23:14:16','2026-06-12 16:15:35'),(9,'H001-01-0111','ÞØ┤ÞØÂþ╗ôÕÅæÕñ╣',4.99,12,5,'hair','','active','images/products/30bc5ed50bc9a1a9980309eee624108b.jpg',1,2,'2026-06-08 23:14:16','2026-06-12 16:15:31'),(12,'A0031','11',111.00,6,5,'phone','11','active','images/products/ea5e5f9c0608138c4812569d11ab8deb.jpg',0,1,'2026-06-12 16:09:34','2026-06-23 16:47:57');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'qi_shop'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-23 21:09:22

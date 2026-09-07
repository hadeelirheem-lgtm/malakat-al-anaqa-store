-- ============================================================
--  قاعدة بيانات متجر "ملكة الأناقة" (هيكل + بيانات التيست)
--  استيراد: mysql -u root -p < sql/store.sql
-- ============================================================
CREATE DATABASE IF NOT EXISTS store DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE store;

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
DROP TABLE IF EXISTS `coupons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `coupons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `discount_percent` decimal(5,2) NOT NULL,
  `product_code` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `coupons` WRITE;
/*!40000 ALTER TABLE `coupons` DISABLE KEYS */;
INSERT INTO `coupons` VALUES (3,'SARA',20.00,NULL,1,'2026-08-28 18:16:35'),(4,'POP',10.00,NULL,1,'2026-09-04 09:27:38'),(5,'HAD',2.00,NULL,1,'2026-09-04 09:27:46'),(6,'SARA123',3.00,NULL,1,'2026-09-04 09:28:00'),(8,'SARA755818',12.00,NULL,1,'2026-09-04 18:10:21'),(9,'HAD127',30.00,'SKU-0005',1,'2026-09-05 19:44:35');
/*!40000 ALTER TABLE `coupons` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_name` varchar(255) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `address` text NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `receipt_path` varchar(255) DEFAULT NULL,
  `order_items` text NOT NULL,
  `status` varchar(50) DEFAULT 'جديد',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `shipping_fee` decimal(10,2) DEFAULT 0.00,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (1,'hadeel ','0597575215','غزة الزيتون',10.00,'الدفع عند الاستلام',NULL,'تنورة اطفال (العدد: 1)','جديد','2026-08-23 11:42:02',0.00),(2,'hadeel ','0597575215','غزة السرايا',70.00,'محفظة / بنك','uploads/receipts/receipt_1787488003_Screenshot 2025-02-23 021306.png','فستان ناعم (العدد: 1)','جديد','2026-08-23 12:26:43',0.00),(3,'hadeel ','0597575215','غزة الزيتون',20.00,'محفظة / بنك',NULL,'فستان صيفي (العدد: 1)','جديد','2026-08-24 09:05:22',0.00),(4,'hadeel ','0597575215','غزة السرايا',10.00,'محفظة / بنك','uploads/receipts/receipt_1787596358_Screenshot 2025-02-23 021306.png','تنورة  (العدد: 1)','تمت القراءة','2026-08-24 18:32:38',0.00),(5,'سماح السيد','0597575215','غزة السرايا',7.00,'محفظة / بنك','uploads/receipts/receipt_1787665054_Screenshot 2025-12-25 192804.png','بلوزة  (العدد: 1)','تم التوصيل','2026-08-25 13:37:34',0.00),(6,'hadeel ','0597575215','غزة السرايا',40.00,'الدفع عند الاستلام',NULL,'فستان صيفي (العدد: 2)','تم التوصيل','2026-08-28 16:27:17',0.00),(7,'hadeel ','0597780452','دير البلح شارع السلام',9.00,'الدفع عند الاستلام',NULL,'تنورة  (العدد: 1)','جديد','2026-08-28 18:00:10',0.00),(8,'hadeel','0597780452','السرايا بالقرب من مسجد الكنز',10.00,'الدفع عند الاستلام',NULL,'تنورة (العدد: 1)','تم التوصيل','2026-09-04 09:18:16',0.00),(9,'hadeel','0597575215','رفح',1.94,'الدفع عند الاستلام',NULL,'روج (العدد: 1)','تم التوصيل','2026-09-04 09:34:52',0.00),(10,'hadeel','0591510512','نابلس',1.94,'محفظة / بنك','uploads/receipts/receipt_1788544134_Screenshot 2025-02-23 021306.png','روج (العدد: 1)','تم التوصيل','2026-09-04 17:48:54',0.00),(11,'hadeel','0597575215','OP',3.52,'محفظة / بنك',NULL,'شنط كروس (العدد: 1)','تم التوصيل','2026-09-05 13:26:04',0.00),(12,'hadeel','0597780452','اربء',70.00,'الدفع عند الاستلام',NULL,'فستان خمري (العدد: 1)','تم التوصيل','2026-09-06 15:00:24',0.00);
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_code` varchar(50) DEFAULT NULL,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `size` varchar(20) DEFAULT NULL,
  `image` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_archived` tinyint(1) DEFAULT 0,
  `category` varchar(50) NOT NULL DEFAULT 'عام',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (10,'SKU-0001','روج','روج بجميع الالوان ثابت ضد الماء',2.00,'S','uploads/1787943136_Screenshot 2026-08-28 215127.png','2026-08-28 18:52:16',0,'ميك أب'),(11,'SKU-0002','تنورة','تنورة افراح بجميع الالوان',10.00,'S','uploads/1787943877_Screenshot 2026-08-22 110450.png','2026-08-28 19:04:37',0,'أطفال'),(12,'SKU-0003','فستان سهرة','روعة',30.00,'L','uploads/1787943971_Screenshot 2026-08-22 111645.png','2026-08-28 19:06:11',0,'نسائي'),(13,'SKU-0004','طقم كامل','متوفر جميع المقاسات',120.00,'L','uploads/1787944316_Screenshot 2026-08-28 220729.png','2026-08-28 19:11:56',0,'رجالي'),(14,'SKU-0005','اساور فضة','',1.00,'M','uploads/1787944351_Screenshot 2026-08-28 220801.png','2026-08-28 19:12:31',0,'إكسسوارات'),(15,'SKU-0006','شنط كروس','',4.00,'S','uploads/1787944415_Screenshot 2026-08-28 221304.png','2026-08-28 19:13:35',0,'شنط'),(16,'SKU-0016','فستان خمري','متوفر مقاس لارج',70.00,'L','uploads/1788545111_Screenshot 2026-09-04 210434.png','2026-09-04 18:05:11',0,'نسائي');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_name` varchar(100) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `admin_reply` text DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `reviews` WRITE;
/*!40000 ALTER TABLE `reviews` DISABLE KEYS */;
INSERT INTO `reviews` VALUES (7,'hadeel',5,'HI','2026-09-04 14:39:22','HELLO','uploads/user_1788512750_Screenshot 2026-08-30 151312.png');
/*!40000 ALTER TABLE `reviews` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (4,'bank_iban','55578462','2026-09-05 19:07:49'),(5,'wallet_phone','0597575215','2026-09-05 19:07:49'),(6,'paypal_email','','2026-09-05 19:07:49');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `profile_image` varchar(255) DEFAULT NULL,
  `reset_code` varchar(6) DEFAULT NULL,
  `reset_expiry` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (2,'hadeel','hadeelirheem@gmail.com','$2y$10$iJFdJwXsAjrTsDjxttk14eQ3XcF65jvtZlQksEOf2L2udTXCs5Tvm','user','2026-08-22 09:02:47','uploads/user_1788512750_Screenshot 2026-08-30 151312.png',NULL,NULL),(5,'متجر الاناقة','admin@store123.com','$2y$10$C7/Nk6/YjmzCy3Yzrpo0H.TllGO5XN/gklG3BvWbK8W6tQH4DdUzG','admin','2026-09-04 09:21:24','uploads/user_1788514213_Screenshot 2026-09-04 122954.png',NULL,NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;


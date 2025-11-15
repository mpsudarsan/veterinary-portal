CREATE DATABASE  IF NOT EXISTS `veterinary_portal` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `veterinary_portal`;
-- MySQL dump 10.13  Distrib 8.0.42, for Win64 (x86_64)
--
-- Host: localhost    Database: veterinary_portal
-- ------------------------------------------------------
-- Server version	8.0.42

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `full_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_users`
--

LOCK TABLES `admin_users` WRITE;
/*!40000 ALTER TABLE `admin_users` DISABLE KEYS */;
INSERT INTO `admin_users` VALUES (1,'admin','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','System Administrator','admin@veterinaryportal.com','2025-07-17 09:51:08','2025-06-30 06:28:07','2025-07-17 04:21:08');
/*!40000 ALTER TABLE `admin_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `announcements`
--

DROP TABLE IF EXISTS `announcements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `announcements` (
  `announcement_id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `content` text COLLATE utf8mb4_general_ci NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `priority` enum('low','medium','high') COLLATE utf8mb4_general_ci DEFAULT 'medium',
  PRIMARY KEY (`announcement_id`),
  KEY `idx_dates` (`start_date`,`end_date`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `announcements`
--

LOCK TABLES `announcements` WRITE;
/*!40000 ALTER TABLE `announcements` DISABLE KEYS */;
/*!40000 ALTER TABLE `announcements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `appointments`
--

DROP TABLE IF EXISTS `appointments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `appointments` (
  `appointment_id` int NOT NULL AUTO_INCREMENT,
  `pet_id` varchar(20) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'References pets.pet_id',
  `facility_id` int NOT NULL COMMENT 'References veterinary_facilities.facility_id (only VH type)',
  `appointment_type` enum('Checkup','Vaccination','Surgery','Emergency','Other') COLLATE utf8mb4_general_ci NOT NULL,
  `preferred_date` date NOT NULL,
  `preferred_time` time NOT NULL COMMENT 'Preferred time slot',
  `symptoms` text COLLATE utf8mb4_general_ci,
  `additional_notes` text COLLATE utf8mb4_general_ci,
  `token_number` int NOT NULL COMMENT 'Auto-generated daily token',
  `status` enum('Pending','Confirmed','In Progress','Completed','Cancelled','No Show') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Pending',
  `created_by` varchar(20) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'References pet_owners.pet_owner_id',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `assigned_staff_id` int DEFAULT NULL COMMENT 'References facility_staff.staff_id',
  `completed_at` datetime DEFAULT NULL,
  `cancellation_reason` text COLLATE utf8mb4_general_ci,
  `last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`appointment_id`),
  UNIQUE KEY `idx_daily_token` (`facility_id`,`preferred_date`,`token_number`),
  KEY `idx_pet` (`pet_id`),
  KEY `idx_facility_date` (`facility_id`,`preferred_date`),
  KEY `fk_appointment_owner` (`created_by`),
  KEY `idx_appointment_facility_date_status` (`facility_id`,`preferred_date`,`status`),
  KEY `fk_appointment_staff` (`assigned_staff_id`),
  KEY `idx_appointment_completion` (`status`,`preferred_date`),
  KEY `idx_appointment_pet_date` (`pet_id`,`preferred_date`),
  CONSTRAINT `fk_appointment_facility` FOREIGN KEY (`facility_id`) REFERENCES `veterinary_facilities` (`facility_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_appointment_owner` FOREIGN KEY (`created_by`) REFERENCES `pet_owners` (`pet_owner_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_appointment_pet` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`pet_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_appointment_staff` FOREIGN KEY (`assigned_staff_id`) REFERENCES `facility_staff` (`staff_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Pet appointment scheduling system';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `appointments`
--

LOCK TABLES `appointments` WRITE;
/*!40000 ALTER TABLE `appointments` DISABLE KEYS */;
INSERT INTO `appointments` VALUES (1,'PET685AC38EF004D',1,'Checkup','2025-06-28','16:00:00','Fever','',1,'','PO-20d8','2025-06-28 09:37:45','2025-06-28 13:18:57',NULL,NULL,NULL,'2025-07-01 09:17:28'),(4,'PET685e1c3cbdcdd',2,'Vaccination','2025-06-28','17:00:00','','',1,'','PO-20d8','2025-06-28 09:41:22','2025-06-28 13:18:57',NULL,NULL,NULL,'2025-07-01 09:17:28'),(5,'PET685e1c3cbdcdd',1,'Vaccination','2025-06-28','17:00:00','','',2,'','PO-20d8','2025-06-28 09:50:44','2025-06-28 13:18:57',NULL,NULL,NULL,'2025-07-01 09:17:28'),(6,'PET685e1c3cbdcdd',1,'Vaccination','2025-06-28','17:00:00','','',3,'','PO-20d8','2025-06-28 09:56:02','2025-06-28 13:18:57',NULL,NULL,NULL,'2025-07-01 09:17:28'),(7,'PET685e21320a62a',9,'Vaccination','2025-06-28','17:00:00','','',1,'','PO-20d8','2025-06-28 09:56:11','2025-06-28 13:18:57',NULL,NULL,NULL,'2025-07-01 09:17:28'),(8,'PET685e21320a62a',2,'Vaccination','2025-06-28','17:00:00','','',2,'','PO-20d8','2025-06-28 09:56:20','2025-06-28 13:18:57',NULL,NULL,NULL,'2025-07-01 09:17:28'),(9,'PET685e21320a62a',1,'Surgery','2025-06-28','17:00:00','','',4,'','PO-20d8','2025-06-28 09:56:32','2025-06-28 13:18:57',NULL,NULL,NULL,'2025-07-01 09:17:28'),(10,'PET685e21320a62a',2,'Surgery','2025-06-28','17:00:00','bnm','',3,'','PO-20d8','2025-06-28 09:56:45','2025-06-28 13:18:57',NULL,NULL,NULL,'2025-07-01 09:17:28'),(11,'PET685e21320a62a',1,'Surgery','2025-06-28','17:00:00','bnm','',5,'','PO-20d8','2025-06-28 09:57:12','2025-06-28 13:18:57',NULL,NULL,NULL,'2025-07-01 09:17:28'),(12,'PET685e1c3cbdcdd',9,'Checkup','2025-06-29','10:00:00','','',1,'','PO-20d8','2025-06-28 11:51:59','2025-06-29 12:27:18',NULL,NULL,NULL,'2025-07-01 09:17:28'),(13,'PET685e21320a62a',2,'Checkup','2025-06-30','09:00:00','','',1,'','PO-20d8','2025-06-28 12:01:30','2025-06-30 04:19:03',NULL,NULL,NULL,'2025-07-01 09:17:28'),(14,'PET685e21320a62a',9,'Surgery','2025-07-01','09:00:00','','',1,'','PO-20d8','2025-06-28 12:12:07','2025-07-01 06:44:27',NULL,NULL,NULL,'2025-07-01 09:17:28'),(15,'PET685e21320a62a',1,'Other','2025-06-29','12:00:00','Fever','',1,'','PO-20d8','2025-06-28 14:02:42','2025-06-29 12:27:18',NULL,NULL,NULL,'2025-07-01 09:17:28'),(16,'PET685e1c3cbdcdd',1,'Checkup','2025-06-30','09:00:00','Fever','',1,'','PO-20d8','2025-06-29 12:27:42','2025-06-30 04:19:03',NULL,NULL,NULL,'2025-07-01 09:17:28'),(17,'PET685AC38EF004D',1,'Surgery','2025-06-30','14:00:00','','',2,'','PO-20d8','2025-06-30 04:40:31','2025-06-30 10:01:10',NULL,NULL,NULL,'2025-07-01 09:17:28'),(18,'PET685e1c3cbdcdd',15,'Vaccination','2025-06-30','15:00:00','','',1,'','PO-20d8','2025-06-30 06:36:55','2025-06-30 10:01:10',NULL,NULL,NULL,'2025-07-01 09:17:28'),(19,'PET685e1c3cbdcdd',1,'Vaccination','2025-07-01','09:00:00','','',1,'Completed','PO-20d8','2025-06-30 10:01:02','2025-07-01 10:08:37',NULL,NULL,NULL,'2025-07-01 10:08:37'),(20,'PET685e21320a62a',9,'Vaccination','2025-07-01','14:00:00','','',2,'','PO-20d8','2025-06-30 10:58:55','2025-07-01 09:18:27',NULL,NULL,NULL,'2025-07-01 09:18:27'),(21,'PET685AC38EF004D',1,'Vaccination','2025-07-02','10:00:00','','',1,'Completed','PO-20d8','2025-07-01 09:19:15','2025-07-02 06:44:26',NULL,NULL,NULL,'2025-07-02 06:44:26'),(22,'PET685e1c3cbdcdd',1,'Checkup','2025-07-01','16:00:00','','',2,'Completed','PO-20d8','2025-07-01 10:07:21','2025-07-01 10:08:43',NULL,NULL,NULL,'2025-07-01 10:08:43'),(23,'PET685e1c3cbdcdd',1,'Checkup','2025-07-01','18:00:00','','',3,'','PO-20d8','2025-07-01 11:36:45','2025-07-09 05:14:53',NULL,NULL,NULL,'2025-07-09 05:14:53'),(24,'PET685e1c3cbdcdd',1,'Checkup','2025-07-09','12:00:00','','',1,'Completed','PO-20d8','2025-07-09 05:20:22','2025-07-09 05:57:44',NULL,NULL,NULL,'2025-07-09 05:57:44'),(25,'PET685e1c3cbdcdd',1,'Checkup','2025-07-11','12:00:00','','',1,'Pending','PO-20d8','2025-07-11 05:42:00','2025-07-11 05:42:00',NULL,NULL,NULL,'2025-07-11 05:42:00'),(26,'PET685e1c3cbdcdd',15,'Checkup','2025-07-16','09:00:00','','',1,'Pending','PO-20d8','2025-07-15 06:25:23','2025-07-15 06:25:23',NULL,NULL,NULL,'2025-07-15 06:25:23');
/*!40000 ALTER TABLE `appointments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `breeds`
--

DROP TABLE IF EXISTS `breeds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `breeds` (
  `breed_id` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `species_id` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `breed_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`breed_id`),
  KEY `idx_species_breed` (`species_id`,`breed_name`),
  CONSTRAINT `breeds_ibfk_1` FOREIGN KEY (`species_id`) REFERENCES `species` (`species_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `breeds`
--

LOCK TABLES `breeds` WRITE;
/*!40000 ALTER TABLE `breeds` DISABLE KEYS */;
INSERT INTO `breeds` VALUES ('BR001','SP001','Labrador Retriever','2025-06-15 03:45:53','2025-06-15 03:45:53'),('BR002','SP001','German Shepherd','2025-06-15 03:45:53','2025-06-15 03:45:53'),('BR003','SP001','Local','2025-06-15 03:45:53','2025-06-15 03:45:53'),('BR004','SP002','Siamese','2025-06-15 03:45:53','2025-06-15 03:45:53'),('BR005','SP002','Persian','2025-06-15 03:45:53','2025-06-15 03:45:53'),('BR006','SP002','Local','2025-06-15 03:45:53','2025-06-15 03:45:53'),('BR007','SP003','Dutch','2025-06-15 03:45:53','2025-06-15 03:45:53'),('BR008','SP003','Flemish Giant','2025-06-15 03:45:53','2025-06-15 03:45:53'),('BR009','SP003','Local','2025-06-15 03:45:53','2025-06-15 03:45:53'),('BR010','SP004','African Grey','2025-06-15 03:45:53','2025-06-15 03:45:53'),('BR011','SP004','Budgerigar','2025-06-15 03:45:53','2025-06-15 03:45:53'),('BR012','SP004','Local','2025-06-15 03:45:53','2025-06-15 03:45:53'),('BR013','SP007','Holstein','2025-06-15 03:45:53','2025-06-15 03:45:53'),('BR014','SP007','Angus','2025-06-15 03:45:53','2025-06-15 03:45:53'),('BR015','SP007','Local','2025-06-15 03:45:53','2025-06-15 03:45:53'),('BR016','SP012','Nigerian Dwarf','2025-06-15 03:45:53','2025-06-15 03:45:53'),('BR017','SP012','Boer','2025-06-15 03:45:53','2025-06-15 03:45:53'),('BR018','SP012','Local','2025-06-15 03:45:53','2025-06-15 03:45:53');
/*!40000 ALTER TABLE `breeds` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `districts`
--

DROP TABLE IF EXISTS `districts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `districts` (
  `district_code` int NOT NULL,
  `district_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`district_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `districts`
--

LOCK TABLES `districts` WRITE;
/*!40000 ALTER TABLE `districts` DISABLE KEYS */;
INSERT INTO `districts` VALUES (602,'South Andamans'),(603,'Nicobars'),(632,'North And Middle Andaman');
/*!40000 ALTER TABLE `districts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `emergency_reports`
--

DROP TABLE IF EXISTS `emergency_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `emergency_reports` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reporter_phone` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `incident_type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `animal_type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `location` text COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `status` varchar(20) COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `emergency_reports`
--

LOCK TABLES `emergency_reports` WRITE;
/*!40000 ALTER TABLE `emergency_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `emergency_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `facility_staff`
--

DROP TABLE IF EXISTS `facility_staff`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `facility_staff` (
  `staff_id` int NOT NULL AUTO_INCREMENT,
  `facility_id` int NOT NULL COMMENT 'References veterinary_facilities.facility_id',
  `role_id` int NOT NULL COMMENT 'References staff_roles.role_id',
  `username` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `full_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1=active, 0=inactive',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `auto_generated` tinyint(1) DEFAULT '0' COMMENT '1=auto-generated credentials',
  `initial_password` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Temporarily stores initial password',
  `credentials_sent_at` datetime DEFAULT NULL COMMENT 'When credentials were sent',
  PRIMARY KEY (`staff_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `facility_id` (`facility_id`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `facility_staff_ibfk_1` FOREIGN KEY (`facility_id`) REFERENCES `veterinary_facilities` (`facility_id`),
  CONSTRAINT `facility_staff_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `staff_roles` (`role_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `facility_staff`
--

LOCK TABLES `facility_staff` WRITE;
/*!40000 ALTER TABLE `facility_staff` DISABLE KEYS */;
INSERT INTO `facility_staff` VALUES (1,1,5,'vetstaff.priya','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Priya Sharma','priya.sharma@vetclinic.example','+91 98765 43210',1,'2025-07-11 11:53:43','2025-06-29 06:32:57','2025-07-11 06:23:43',0,NULL,NULL),(2,1,1,'drarun.jung','1249ef09dd8f0eb4e6da5347dd631a43e7da8123b7c5e4c8d7f80d0a24d497e1','Dr. Arun Kumar',NULL,'9434280011',1,'2025-07-04 10:16:48','2025-06-30 05:42:25','2025-07-11 02:49:26',1,'Temp@123',NULL),(3,1,1,'drpriya.jung','1249ef09dd8f0eb4e6da5347dd631a43e7da8123b7c5e4c8d7f80d0a24d497e1','Dr. Priya Sharma',NULL,'9434280012',1,'2025-07-11 11:12:38','2025-06-30 05:42:25','2025-07-11 05:42:38',1,'Temp@123',NULL),(4,2,1,'drvikas.gara','1249ef09dd8f0eb4e6da5347dd631a43e7da8123b7c5e4c8d7f80d0a24d497e1','Dr. Vikas Singh',NULL,'9434280021',1,NULL,'2025-06-30 05:42:25','2025-06-30 05:42:25',1,'Temp@123',NULL),(5,2,1,'drneha.gara','1249ef09dd8f0eb4e6da5347dd631a43e7da8123b7c5e4c8d7f80d0a24d497e1','Dr. Neha Patel',NULL,'9434280022',1,NULL,'2025-06-30 05:42:25','2025-06-30 05:42:25',1,'Temp@123',NULL),(6,3,1,'drrahul.wimb','1249ef09dd8f0eb4e6da5347dd631a43e7da8123b7c5e4c8d7f80d0a24d497e1','Dr. Rahul Verma',NULL,'9434280031',1,NULL,'2025-06-30 05:42:25','2025-06-30 05:42:25',1,'Temp@123',NULL),(7,9,1,'dranjali.digl','1249ef09dd8f0eb4e6da5347dd631a43e7da8123b7c5e4c8d7f80d0a24d497e1','Dr. Anjali Das',NULL,'9434280041',1,NULL,'2025-06-30 05:42:25','2025-07-16 03:54:48',1,'Temp@123',NULL),(8,10,1,'drrohit.maya','1249ef09dd8f0eb4e6da5347dd631a43e7da8123b7c5e4c8d7f80d0a24d497e1','Dr. Rohit Banerjee',NULL,'9434280051',1,NULL,'2025-06-30 05:42:25','2025-06-30 05:42:25',1,'Temp@123',NULL),(9,15,1,'drsunil.car','1249ef09dd8f0eb4e6da5347dd631a43e7da8123b7c5e4c8d7f80d0a24d497e1','Dr. Sunil Thomas',NULL,'9434280061',1,NULL,'2025-06-30 05:42:25','2025-06-30 05:42:25',1,'Temp@123',NULL),(10,16,1,'drmeera.camp','1249ef09dd8f0eb4e6da5347dd631a43e7da8123b7c5e4c8d7f80d0a24d497e1','Dr. Meera Nair',NULL,'9434280071',1,NULL,'2025-06-30 05:42:25','2025-06-30 05:42:25',1,'Temp@123',NULL),(11,16,1,'m.p.sudarsan245','$2y$10$lnyuKoY2RPbJ3DXTeKXiZOCKJErVZ6ee9W6wqOT4Cb9/SF/7F/hqe','M.p. Sudarsan','mpsudarsan400@gmail.com','7063945533',1,'2025-07-17 09:55:39','2025-07-16 03:53:15','2025-07-17 04:25:39',1,NULL,'2025-07-16 09:23:15');
/*!40000 ALTER TABLE `facility_staff` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `facility_types`
--

DROP TABLE IF EXISTS `facility_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `facility_types` (
  `short_code` char(3) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'VH, VD, VSD',
  `full_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Full facility type name',
  PRIMARY KEY (`short_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `facility_types`
--

LOCK TABLES `facility_types` WRITE;
/*!40000 ALTER TABLE `facility_types` DISABLE KEYS */;
INSERT INTO `facility_types` VALUES ('VD','Veterinary Dispensary'),('VH','Veterinary Hospital'),('VSD','Veterinary Sub-Dispensary');
/*!40000 ALTER TABLE `facility_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `locations`
--

DROP TABLE IF EXISTS `locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `locations` (
  `location_id` int NOT NULL AUTO_INCREMENT,
  `address` text COLLATE utf8mb4_general_ci NOT NULL,
  `village_code` int NOT NULL,
  `pincode` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`location_id`),
  KEY `idx_village` (`village_code`),
  KEY `idx_pincode` (`pincode`),
  CONSTRAINT `locations_ibfk_1` FOREIGN KEY (`village_code`) REFERENCES `villages` (`village_code`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `locations`
--

LOCK TABLES `locations` WRITE;
/*!40000 ALTER TABLE `locations` DISABLE KEYS */;
INSERT INTO `locations` VALUES (1,'100/10, Shiv Colony',645529,'744103','2025-06-22 11:31:53','2025-06-22 11:31:53'),(2,'100/10, Shiv Colony',645529,'744103','2025-06-22 11:37:42','2025-06-22 11:37:42'),(3,'100/10, Shiv Colony',645529,'744103','2025-06-24 15:07:59','2025-06-24 15:07:59'),(4,'CPWD Colony',645528,'744103','2025-06-27 10:38:40','2025-06-27 10:38:40'),(5,'100/10, Shiv Colony',645529,'744103','2025-06-27 15:07:36','2025-06-27 15:07:36'),(6,'Shiv Colony',645529,'744101','2025-06-27 15:38:11','2025-06-27 15:38:11'),(9,'sdfghjkl',645247,'744101','2025-06-28 04:06:19','2025-06-28 04:06:19'),(10,'asdfghjk',645418,'123456','2025-06-28 04:13:16','2025-06-28 04:13:16'),(11,'wertyghj',645280,'786325','2025-06-28 04:15:27','2025-06-28 04:15:27'),(12,'bcgfcgvhj',645336,'786325','2025-06-28 04:42:51','2025-06-28 04:42:51'),(13,'cfycvgv',645338,'600062','2025-07-16 04:07:14','2025-07-16 04:07:14');
/*!40000 ALTER TABLE `locations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `medical_records`
--

DROP TABLE IF EXISTS `medical_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `medical_records` (
  `record_id` int NOT NULL AUTO_INCREMENT,
  `pet_id` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `staff_id` int DEFAULT NULL,
  `diagnosis` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `treatment` text COLLATE utf8mb4_general_ci,
  `medications` text COLLATE utf8mb4_general_ci,
  `notes` text COLLATE utf8mb4_general_ci,
  `record_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `record_type` enum('Checkup','Vaccination','Surgery','Emergency','Other') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `attending_staff_id` int DEFAULT NULL,
  `appointment_id` int DEFAULT NULL COMMENT 'References appointments.appointment_id',
  PRIMARY KEY (`record_id`),
  KEY `staff_id` (`staff_id`),
  KEY `attending_staff_id` (`attending_staff_id`),
  KEY `idx_medical_record_appointment` (`appointment_id`),
  KEY `idx_medical_pet_date` (`pet_id`,`record_date`),
  CONSTRAINT `fk_medical_record_appointment` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`) ON DELETE SET NULL,
  CONSTRAINT `medical_records_ibfk_1` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`pet_id`),
  CONSTRAINT `medical_records_ibfk_2` FOREIGN KEY (`staff_id`) REFERENCES `facility_staff` (`staff_id`),
  CONSTRAINT `medical_records_ibfk_3` FOREIGN KEY (`attending_staff_id`) REFERENCES `facility_staff` (`staff_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `medical_records`
--

LOCK TABLES `medical_records` WRITE;
/*!40000 ALTER TABLE `medical_records` DISABLE KEYS */;
INSERT INTO `medical_records` VALUES (3,'PET685e1c3cbdcdd',NULL,'Vaccination - ','',NULL,'Appointment completed. ','2025-07-01','2025-07-01 10:08:37','Vaccination',NULL,19),(4,'PET685e1c3cbdcdd',1,'','',NULL,'Auto-generated from appointment #19','2025-07-01','2025-07-01 10:08:37','Vaccination',1,19),(5,'PET685e1c3cbdcdd',NULL,'','',NULL,'Appointment completed. ','2025-07-01','2025-07-01 10:08:43','Checkup',NULL,22),(6,'PET685e1c3cbdcdd',1,'','',NULL,'Auto-generated from appointment #22','2025-07-01','2025-07-01 10:08:43','Checkup',1,22),(7,'PET685AC38EF004D',NULL,'Vaccination - ','',NULL,'Appointment completed. ','2025-07-02','2025-07-02 06:44:26','Vaccination',NULL,21),(8,'PET685AC38EF004D',1,'','',NULL,'Auto-generated from appointment #21','2025-07-02','2025-07-02 06:44:26','Vaccination',1,21),(9,'PET685e1c3cbdcdd',NULL,'','',NULL,'Appointment completed. ','2025-07-09','2025-07-09 05:57:44','Checkup',NULL,24),(10,'PET685e1c3cbdcdd',1,'','',NULL,'Auto-generated from appointment #24','2025-07-09','2025-07-09 05:57:44','Checkup',1,24);
/*!40000 ALTER TABLE `medical_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pet_owners`
--

DROP TABLE IF EXISTS `pet_owners`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pet_owners` (
  `pet_owner_id` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mobile` varchar(15) COLLATE utf8mb4_general_ci NOT NULL,
  `location_id` int NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `last_login` datetime DEFAULT NULL,
  `registration_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `owner_type` enum('individual','ngo','department') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'individual',
  PRIMARY KEY (`pet_owner_id`),
  UNIQUE KEY `mobile` (`mobile`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `id` (`id`),
  KEY `location_id` (`location_id`),
  KEY `idx_mobile` (`mobile`),
  KEY `idx_username` (`username`),
  KEY `idx_email` (`email`),
  CONSTRAINT `pet_owners_ibfk_1` FOREIGN KEY (`location_id`) REFERENCES `locations` (`location_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pet_owners`
--

LOCK TABLES `pet_owners` WRITE;
/*!40000 ALTER TABLE `pet_owners` DISABLE KEYS */;
INSERT INTO `pet_owners` VALUES ('PO-20d8',1,'Trisha Sagar','trishasag7604@gmail.com','7866905247',2,'Trisha_Sagar','$2y$10$MIJjfU7TnczvDpxBrg8b/eTGaj5dZdEW3Utja2Ygm8VZeQXP3dngC','2025-07-16 11:23:47','2025-06-22 17:07:42','2025-07-16 05:53:47','individual'),('PO-8be3',2,'Geeta Sagar','','9933260443',3,'Geeta','$2y$12$PRFq3DJm18bB33Jw7Az8cOCcMXgFGJEimo/hoqM3vE6n2zvJiK.dO',NULL,'2025-06-24 20:37:59','2025-06-24 15:07:59','individual'),('PO-f204',12,'M.p. Sudarsan','mpsudarsan400@gmail.com','7063945533',13,'Sudarsan','$2y$10$bYoWjGG/lmqUKbdqtb2qY.WPcIVP3frQrmlBmZ.G4pcPLPRA5TDCK','2025-07-21 10:19:20','2025-07-16 09:37:15','2025-07-21 04:49:20','individual'),('PO-f413',11,'swedrftgyh','trishasag7604@gmail.com','7896541233',12,'asdf','$2y$12$UkouMxEbhXkyBHurqLZPz.Ji5wRGDuvsavQLwVFyHqpH.5DKn9ssG',NULL,'2025-06-28 10:12:51','2025-06-28 04:42:51','individual'),('PO-f806',5,'Sagar','','9434280471',6,'Sagar','$2y$12$wHNpIa.7IxLo2pNMlz/Av.2kLGzhtZNvqUhnu545kec3Tk03FykVS',NULL,'2025-06-27 21:08:12','2025-06-27 15:38:12','individual'),('PO-f998',3,'Gautam','','9679548910',4,'Gautam','$2y$12$rp7gEMTWufpSIzxGpUxhuuqq7JceGHVu92xgIWxJxE1/cRVgk.aWG','2025-06-27 16:10:37','2025-06-27 16:08:41','2025-06-27 10:40:37','individual');
/*!40000 ALTER TABLE `pet_owners` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pets`
--

DROP TABLE IF EXISTS `pets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pets` (
  `pet_id` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `pet_owner_id` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `species_id` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `breed_id` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `pet_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `sex` enum('Male','Female','Unknown') COLLATE utf8mb4_general_ci NOT NULL,
  `neutered` tinyint(1) DEFAULT '0' COMMENT '0 = No, 1 = Yes',
  `age_value` int NOT NULL COMMENT 'Numerical age value',
  `age_unit` enum('days','months','years') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'years',
  `date_of_birth` date DEFAULT NULL,
  `color` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT NULL COMMENT 'Weight in kg',
  `identification_mark` text COLLATE utf8mb4_general_ci COMMENT 'Distinctive physical features or markings',
  `profile_picture` varchar(255) COLLATE utf8mb4_general_ci DEFAULT 'assets/images/default-pet.jpg',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`pet_id`),
  KEY `breed_id` (`breed_id`),
  KEY `idx_pet_owner` (`pet_owner_id`),
  KEY `idx_species_breed` (`species_id`,`breed_id`),
  KEY `idx_sex_neutered` (`sex`,`neutered`),
  KEY `idx_pet_name` (`pet_name`),
  CONSTRAINT `pets_ibfk_1` FOREIGN KEY (`pet_owner_id`) REFERENCES `pet_owners` (`pet_owner_id`),
  CONSTRAINT `pets_ibfk_2` FOREIGN KEY (`species_id`) REFERENCES `species` (`species_id`),
  CONSTRAINT `pets_ibfk_3` FOREIGN KEY (`breed_id`) REFERENCES `breeds` (`breed_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pets`
--

LOCK TABLES `pets` WRITE;
/*!40000 ALTER TABLE `pets` DISABLE KEYS */;
INSERT INTO `pets` VALUES ('PET685AC38EF004D','PO-20d8','SP001','BR003','Dogesh','Male',0,1,'years',NULL,NULL,8.50,NULL,'assets/images/default-pet.jpg','2025-06-24 15:26:06','2025-06-24 15:26:06'),('PET685e1c3cbdcdd','PO-20d8','SP001','BR003','Biscuit','Male',0,2,'years','2025-06-27','Brown',NULL,NULL,'assets/uploads/pets/pet_685e1c3cb659e.jpg','2025-06-27 04:21:16','2025-06-27 04:21:16'),('PET685e21320a62a','PO-20d8','SP002','BR005','Don','Male',0,2,'years',NULL,NULL,NULL,NULL,'assets/images/default-pet.jpg','2025-06-27 04:42:26','2025-06-27 04:42:26'),('PET685e75bf5a7c1','PO-f998','SP001','BR003','Doogy','Male',1,2,'years','2024-02-13','Black',8.00,NULL,'assets/images/default-pet.jpg','2025-06-27 10:43:11','2025-06-27 10:43:11'),('PET964c','PO-f204','SP002','BR005','toto','Female',1,5,'years',NULL,NULL,NULL,NULL,'assets/images/default-pet.jpg','2025-07-16 10:15:15','2025-07-16 10:15:15'),('PETcedc','PO-f204','SP001','BR002','Tiger','Female',0,10,'years','2010-07-08',NULL,NULL,NULL,'assets/images/default-pet.jpg','2025-07-16 07:33:42','2025-07-16 07:33:42');
/*!40000 ALTER TABLE `pets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `species`
--

DROP TABLE IF EXISTS `species`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `species` (
  `species_id` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `species_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`species_id`),
  KEY `idx_species_name` (`species_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `species`
--

LOCK TABLES `species` WRITE;
/*!40000 ALTER TABLE `species` DISABLE KEYS */;
INSERT INTO `species` VALUES ('SP001','Dog','2025-06-15 03:43:50','2025-06-15 03:43:50'),('SP002','Cat','2025-06-15 03:43:50','2025-06-15 03:43:50'),('SP003','Rabbit','2025-06-15 03:43:50','2025-06-15 03:43:50'),('SP004','Bird','2025-06-15 03:43:50','2025-06-15 03:43:50'),('SP007','Cow','2025-06-15 03:43:50','2025-06-15 03:43:50'),('SP012','Goat','2025-06-15 03:43:50','2025-06-15 03:43:50');
/*!40000 ALTER TABLE `species` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff_roles`
--

DROP TABLE IF EXISTS `staff_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `staff_roles` (
  `role_id` int NOT NULL AUTO_INCREMENT,
  `role_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Receptionist, Technician, etc.',
  `description` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `permission_level` int NOT NULL DEFAULT '1' COMMENT 'Higher number = more permissions',
  PRIMARY KEY (`role_id`),
  UNIQUE KEY `idx_role_name` (`role_name`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff_roles`
--

LOCK TABLES `staff_roles` WRITE;
/*!40000 ALTER TABLE `staff_roles` DISABLE KEYS */;
INSERT INTO `staff_roles` VALUES (1,'Veterinarian','Licensed veterinary doctor with full medical authority',4),(2,'Senior Veterinarian','Experienced vet with additional responsibilities',5),(3,'Resident Veterinarian','Veterinarian in training/specialization',3),(4,'Veterinary Surgeon','Specialized in surgical procedures',4),(5,'Veterinary Staff','Front desk operations and appointment management',1),(6,'Veterinary Technician','Assists veterinarians with procedures and tests',2),(7,'Laboratory Technician','Performs diagnostic lab tests',2),(8,'Administrator','Manages facility operations and staff',3),(9,'Animal Caretaker','Animal handling and facility maintenance',1);
/*!40000 ALTER TABLE `staff_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subdistricts`
--

DROP TABLE IF EXISTS `subdistricts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subdistricts` (
  `subdistrict_code` int NOT NULL,
  `subdistrict_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `district_code` int NOT NULL,
  PRIMARY KEY (`subdistrict_code`),
  KEY `district_code` (`district_code`),
  CONSTRAINT `subdistricts_ibfk_1` FOREIGN KEY (`district_code`) REFERENCES `districts` (`district_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subdistricts`
--

LOCK TABLES `subdistricts` WRITE;
/*!40000 ALTER TABLE `subdistricts` DISABLE KEYS */;
INSERT INTO `subdistricts` VALUES (5916,'Car Nicobar',603),(5917,'Nancowry',603),(5918,'Great Nicobar',603),(5919,'Diglipur',632),(5920,'Mayabunder',632),(5921,'Rangat',632),(5922,'Ferrargunj',602),(5923,'Sri Vijaya Puram',602),(5924,'Little Andaman',602);
/*!40000 ALTER TABLE `subdistricts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vaccinations`
--

DROP TABLE IF EXISTS `vaccinations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vaccinations` (
  `vaccination_id` int NOT NULL AUTO_INCREMENT,
  `pet_id` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `vaccine_type_id` int NOT NULL,
  `date_administered` date NOT NULL,
  `next_due_date` date NOT NULL,
  `administered_by` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `notes` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`vaccination_id`),
  KEY `vaccine_type_id` (`vaccine_type_id`),
  KEY `idx_pet_vaccine` (`pet_id`,`vaccine_type_id`),
  KEY `idx_dates` (`date_administered`,`next_due_date`),
  CONSTRAINT `vaccinations_ibfk_1` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`pet_id`) ON DELETE CASCADE,
  CONSTRAINT `vaccinations_ibfk_2` FOREIGN KEY (`vaccine_type_id`) REFERENCES `vaccine_types` (`vaccine_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vaccinations`
--

LOCK TABLES `vaccinations` WRITE;
/*!40000 ALTER TABLE `vaccinations` DISABLE KEYS */;
INSERT INTO `vaccinations` VALUES (1,'PET685e1c3cbdcdd',1,'2025-06-02','2026-06-02','Junglighat Veterinary Hospital','','2025-06-29 17:13:32','2025-06-29 17:13:32'),(2,'PETcedc',5,'2025-07-16','2026-01-16','KKL','','2025-07-17 04:44:34','2025-07-17 04:44:34');
/*!40000 ALTER TABLE `vaccinations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vaccine_types`
--

DROP TABLE IF EXISTS `vaccine_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vaccine_types` (
  `vaccine_id` int NOT NULL AUTO_INCREMENT,
  `vaccine_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `type` enum('new','variation') COLLATE utf8mb4_general_ci NOT NULL,
  `default_duration_months` int DEFAULT '12',
  `description` text COLLATE utf8mb4_general_ci,
  `species` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `initial_dose_time` int NOT NULL,
  `initial_dose_unit` enum('days','weeks','months','years') COLLATE utf8mb4_general_ci NOT NULL,
  `has_additional_doses` tinyint(1) NOT NULL DEFAULT '0',
  `dose_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `available_hospitals` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`vaccine_id`),
  KEY `fk_vaccine_type_species` (`species`),
  CONSTRAINT `fk_vaccine_type_species` FOREIGN KEY (`species`) REFERENCES `species` (`species_id`),
  CONSTRAINT `vaccine_types_chk_1` CHECK (json_valid(`dose_details`)),
  CONSTRAINT `vaccine_types_chk_2` CHECK (json_valid(`available_hospitals`))
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vaccine_types`
--

LOCK TABLES `vaccine_types` WRITE;
/*!40000 ALTER TABLE `vaccine_types` DISABLE KEYS */;
INSERT INTO `vaccine_types` VALUES (1,'Rabies','new',12,'MANDATORY under Prevention of Cruelty to Animals Act. Govt provides free vaccination','',0,'days',0,NULL,NULL,'2025-07-11 01:55:58'),(2,'DHPP (Canine Distemper)','new',12,'Core protection against Distemper, Hepatitis, Parvovirus, Parainfluenza','',0,'days',0,NULL,NULL,'2025-07-11 01:55:58'),(3,'FVRCP (Feline Distemper)','new',12,'Essential for cats - covers 3 deadly viruses','',0,'days',0,NULL,NULL,'2025-07-11 01:55:58'),(4,'Leptospirosis','new',6,'Critical for A&N Islands due to wet climate and water exposure','',0,'days',0,NULL,NULL,'2025-07-11 01:55:58'),(5,'Bordetella','new',6,'Recommended for dogs in boarding/kennels in Port Blair','',0,'days',0,NULL,NULL,'2025-07-11 01:55:58'),(6,'Canine Parvovirus (Standalone)','new',12,'Extra protection against prevalent Indian strains','',0,'days',0,NULL,NULL,'2025-07-11 01:55:58'),(7,'FIP Vaccine (Feline)','new',12,'For cats exposed to stray populations','',0,'days',0,NULL,NULL,'2025-07-11 01:55:58'),(8,'Tick Fever Vaccine','new',12,'Protection against local tick-borne diseases','',0,'days',0,NULL,NULL,'2025-07-11 01:55:58');
/*!40000 ALTER TABLE `vaccine_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vaccines`
--

DROP TABLE IF EXISTS `vaccines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vaccines` (
  `vaccine_id` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `type` enum('new','variation') COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `species` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `initial_dose_time` int NOT NULL,
  `initial_dose_unit` enum('days','weeks','months','years') COLLATE utf8mb4_general_ci NOT NULL,
  `has_additional_doses` tinyint(1) NOT NULL DEFAULT '0',
  `dose_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `available_hospitals` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`vaccine_id`),
  CONSTRAINT `vaccines_chk_1` CHECK (json_valid(`dose_details`)),
  CONSTRAINT `vaccines_chk_2` CHECK (json_valid(`available_hospitals`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vaccines`
--

LOCK TABLES `vaccines` WRITE;
/*!40000 ALTER TABLE `vaccines` DISABLE KEYS */;
/*!40000 ALTER TABLE `vaccines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `veterinary_facilities`
--

DROP TABLE IF EXISTS `veterinary_facilities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `veterinary_facilities` (
  `facility_id` int NOT NULL AUTO_INCREMENT,
  `facility_type` char(3) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'References facility_types.short_code',
  `official_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Official registered name',
  `address_line1` varchar(100) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Primary address line',
  `address_line2` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Secondary address line',
  `landmark` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Nearby landmark',
  `pincode` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Postal code',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1=active, 0=inactive',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation date',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update date',
  `district` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'References districts.district_code',
  PRIMARY KEY (`facility_id`),
  UNIQUE KEY `idx_facility_name` (`official_name`),
  KEY `fk_facility_type` (`facility_type`),
  CONSTRAINT `fk_facility_type` FOREIGN KEY (`facility_type`) REFERENCES `facility_types` (`short_code`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='All veterinary facilities in the region';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `veterinary_facilities`
--

LOCK TABLES `veterinary_facilities` WRITE;
/*!40000 ALTER TABLE `veterinary_facilities` DISABLE KEYS */;
INSERT INTO `veterinary_facilities` VALUES (1,'VH','Junglighat Veterinary Hospital','123 Main Road','Junglighat','Near Police Station','744101',1,'2025-06-26 05:41:39','2025-06-28 13:15:11','602'),(2,'VH','Garacharma Veterinary Hospital','456 Hospital Road','Garacharma','Opposite Community Hall','744105',1,'2025-06-26 05:41:39','2025-06-26 05:41:39','602'),(3,'VH','Wimberly Gunj Veterinary Hospital','789 Central Avenue','Wimberly Gunj','Near Bus Stand','744107',1,'2025-06-26 05:41:39','2025-06-26 05:41:39','602'),(4,'VD','Port Mout Veterinary Dispensary','159 Coastal Highway','Port Mout','Near Fish Market','744102',1,'2025-06-26 05:41:39','2025-06-26 05:41:39','602'),(5,'VD','Manglutan Veterinary Dispensary','753 Village Road','Manglutan','Opposite Panchayat Office','744103',1,'2025-06-26 05:41:39','2025-06-26 05:41:39','602'),(6,'VD','Swaraj Dweep Veterinary Dispensary','357 Beach Road','Havelock Island','Near Dolphin Resort','744101',1,'2025-06-26 05:41:39','2025-06-26 05:41:39','602'),(7,'VSD','Calicut Veterinary Sub-Dispensary','258 Rural Road','Calicut Village','Near Primary Health Center','744104',1,'2025-06-26 05:41:39','2025-06-26 05:41:39','602'),(8,'VSD','Ferrar Gunj Veterinary Sub-Dispensary','369 Plantation Road','Ferrar Gunj','Opposite Tea Stall','744106',1,'2025-06-26 05:41:39','2025-06-26 05:41:39','602'),(9,'VH','Diglipur Veterinary Hospital','321 Main Bazaar Road','Diglipur','Near Government School','744202',1,'2025-06-26 05:41:39','2025-06-26 05:41:39','632'),(10,'VH','Mayabunder Veterinary Hospital','654 Hill Road','Mayabunder','Opposite DC Office','744204',1,'2025-06-26 05:41:39','2025-06-26 05:41:39','632'),(11,'VD','Kalighat Veterinary Dispensary','864 Forest Road','Kalighat','Near Forest Office','744203',1,'2025-06-26 05:41:39','2025-06-26 05:41:39','632'),(12,'VD','Billyground Veterinary Dispensary','246 Village Road','Billyground','Opposite Primary School','744205',1,'2025-06-26 05:41:39','2025-06-26 05:41:39','632'),(13,'VSD','Shibpur Veterinary Sub-Dispensary','147 Tribal Road','Shibpur','Near Community Hall','744207',1,'2025-06-26 05:41:39','2025-06-26 05:41:39','632'),(14,'VSD','Kadamtala Veterinary Sub-Dispensary','258 Valley Road','Kadamtala','Opposite Anganwadi Center','744208',1,'2025-06-26 05:41:39','2025-06-26 05:41:39','632'),(15,'VH','Car Nicobar Veterinary Hospital','147 Jetty Road','Car Nicobar','Near Helipad','744301',1,'2025-06-26 05:41:39','2025-06-26 05:41:39','603'),(16,'VH','Campbell Bay Veterinary Hospital','258 Great Nicobar Road','Campbell Bay','Opposite Police Station','744302',1,'2025-06-26 05:41:39','2025-06-26 05:41:39','603'),(17,'VD','Kamorta Veterinary Dispensary','579 Island Road','Kamorta','Near Jetty','744303',1,'2025-06-26 05:41:39','2025-06-26 05:41:39','603'),(18,'VD','Katchal Veterinary Dispensary','864 Coconut Plantation Road','Katchal','Opposite Church','744304',1,'2025-06-26 05:41:39','2025-06-26 05:41:39','603'),(19,'VSD','Chowra Veterinary Sub-Dispensary','369 Island Road','Chowra','Near Boat Jetty','744305',1,'2025-06-26 05:41:39','2025-06-26 05:41:39','603'),(20,'VSD','Teressa Veterinary Sub-Dispensary','147 Coastal Road','Teressa','Opposite Community Center','744306',1,'2025-06-26 05:41:39','2025-06-26 05:41:39','603');
/*!40000 ALTER TABLE `veterinary_facilities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `villages`
--

DROP TABLE IF EXISTS `villages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `villages` (
  `village_code` int NOT NULL,
  `village_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `subdistrict_code` int NOT NULL,
  PRIMARY KEY (`village_code`),
  KEY `subdistrict_code` (`subdistrict_code`),
  CONSTRAINT `villages_ibfk_1` FOREIGN KEY (`subdistrict_code`) REFERENCES `subdistricts` (`subdistrict_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `villages`
--

LOCK TABLES `villages` WRITE;
/*!40000 ALTER TABLE `villages` DISABLE KEYS */;
INSERT INTO `villages` VALUES (645012,'Mus',5916),(645013,'Teetop',5916),(645014,'Sawai',5916),(645015,'Arong',5916),(645016,'Kimois',5916),(645017,'Kakana',5916),(645018,'Iaf Camp',5916),(645019,'Malacca',5916),(645020,'Perka',5916),(645021,'Tamaloo',5916),(645022,'Kinyuka',5916),(645023,'Chuckchucha',5916),(645024,'Tapoiming',5916),(645025,'Big Lapati',5916),(645026,'Small Lapati',5916),(645027,'Kinmai',5916),(645028,'Tahaila',5917),(645029,'Chongkamong',5917),(645030,'Alhiat',5917),(645031,'Kuitasuk',5917),(645032,'Raihion',5917),(645033,'Tillang Chong Island*',5917),(645034,'Aloorang',5917),(645035,'Aloora*',5917),(645036,'Enam',5917),(645037,'Luxi',5917),(645038,'Kalara*',5917),(645039,'Chukmachi',5917),(645040,'Safedbalu*',5917),(645041,'Minyuk',5917),(645042,'Kanahinot',5917),(645043,'Kalasi',5917),(645044,'Bengali',5917),(645045,'Bompoka Island*',5917),(645046,'Jhoola*',5917),(645047,'Jansin*',5917),(645048,'Hitlat*',5917),(645049,'Mavatapis/Maratapia*',5917),(645050,'Chonghipoh*',5917),(645051,'Sanaya*',5917),(645052,'Alkaipoh/Alkripoh*',5917),(645053,'Alhitoth/Alhiloth*',5917),(645054,'Katahuwa*',5917),(645055,'Kumikia*',5917),(645056,'Kamriak*',5917),(645057,'Hutnyak*',5917),(645058,'Ongulongho*',5917),(645059,'Chonsiyala*',5917),(645060,'Hontona*',5917),(645061,'Kulatapangia*',5917),(645062,'Vyavtapu*',5917),(645063,'Hoipoh*',5917),(645064,'Mapayala*',5917),(645065,'Chengtamilan*',5917),(645066,'Atkuna/Alkun*',5917),(645067,'Tani*',5917),(645068,'Kalminikum/Kalmenkam*',5917),(645069,'Hakonhala*',5917),(645070,'Reakomlong*',5917),(645071,'Sonomkuwa*',5917),(645072,'Tavinkin/Tavakin*',5917),(645073,'Halnatai/Hoinatai*',5917),(645074,'Altaful*',5917),(645075,'Kavatinpeu/Karahinpoh*',5917),(645076,'Alsama*',5917),(645077,'Kapanga*',5917),(645078,'Kupinga*',5917),(645079,'Mildera',5917),(645080,'Upper Katchal',5917),(645081,'Meenakshi Ram Nagar',5917),(645082,'Japan Tikri',5917),(645083,'E-Wall',5917),(645084,'W.B.Katchal/Hindra*',5917),(645085,'Alipa/Alips*',5917),(645086,'Lapat*',5917),(645087,'Hindra*',5917),(645088,'Mus*',5917),(645089,'Payak*',5917),(645090,'Neang*',5917),(645091,'Tapong Incl. Kabila',5917),(645092,'Lanuanga*',5917),(645093,'Altheak',5917),(645094,'Al-Hit-Touch/Balu Basti',5917),(645095,'Malacca*',5917),(645096,'Champin',5917),(645097,'Hinnunga*',5917),(645098,'Tapani/Tapainy*',5917),(645099,'Inroak/Chinlak*',5917),(645100,'Itoi*(Hitui)',5917),(645101,'Alreak*',5917),(645102,'Hintona*',5917),(645103,'Uper Tapu*',5917),(645104,'Pilpilow',5917),(645105,'Neeche Tapu*',5917),(645106,'Manjula*',5917),(645107,'Okiya/Chiya*',5917),(645108,'Olinpon/Alhinpon*',5917),(645109,'Bumpal*',5917),(645110,'Karan/Karav*',5917),(645111,'Daring',5917),(645112,'Maru*',5917),(645113,'Chanel/Chanol*',5917),(645114,'Tanae*',5917),(645115,'Alointung*',5917),(645116,'Banderkari/Pulu',5917),(645117,'Alpintu/Alpintung*',5917),(645118,'Tomae/Inmae*',5917),(645119,'Changua/Changup',5917),(645120,'Masala Tapu*',5917),(645121,'Alukian/Alhukheck',5917),(645122,'Knot',5917),(645123,'Inmae*',5917),(645124,'Payuha',5917),(645125,'Munak Incl. Ponioo/Moul',5917),(645126,'Ramzoo*(Ramjaw)',5917),(645127,'Kamorta/Kalatapu(Incl.Sanuh)',5917),(645128,'Chota Inak',5917),(645129,'Berainak/Badnak',5917),(645130,'Vikas Nagar',5917),(645131,'Kakana',5917),(645132,'Nyicalang*',5917),(645133,'Mohreak/Kohreakap*',5917),(645134,'Kuikua*',5917),(645135,'Safedbalu*',5917),(645136,'Hockook*',5917),(645137,'Trinket*',5917),(645138,'Tapiang*',5917),(645139,'Kapila*',5917),(645140,'Pulomilo',5918),(645141,'Minlana/Minlan',5918),(645142,'Anul/Anula',5918),(645143,'Makhahu/Makachua',5918),(645144,'Akupa',5918),(645145,'Inlock/Infock',5918),(645146,'Pulotalia/Pulotohio',5918),(645147,'Pulobaha/Pathathifen',5918),(645148,'Bewai/Kuwak',5918),(645149,'Kiyang',5918),(645150,'Pulloullo/Puloulo',5918),(645151,'Hoin Incl. Ikuia',5918),(645152,'Pea',5918),(645153,'Pulobha/Pulobahan',5918),(645154,'Hokesiang',5918),(645155,'Pattia (Pulopattia)',5918),(645156,'Olinchi/Bombay',5918),(645157,'Inlock/Pattia',5918),(645158,'Pulopanja',5918),(645159,'Elahi/Ilhoya',5918),(645160,'Inod',5918),(645161,'Pehayo',5918),(645162,'Bahua',5918),(645163,'Kondul',5918),(645164,'Pulobed/Lababu',5918),(645165,'Katahu',5918),(645166,'Afra Bay',5918),(645167,'Dairkurat',5918),(645168,'Alexandera River',5918),(645169,'Ayouk',5918),(645170,'Pulobed',5918),(645171,'Pulokunji',5918),(645172,'Shompen Village-A',5918),(645173,'Renguang',5918),(645174,'Dogmar River',5918),(645175,'Kopenheat',5918),(645176,'Shompen Village-B',5918),(645177,'Kasintung',5918),(645178,'Koe',5918),(645179,'Danlet',5918),(645180,'Pulobhabi',5918),(645181,'Patatiya',5918),(645182,'Hin-Pou-Chi',5918),(645183,'Kokeon',5918),(645184,'Dakhiyon (Fc)',5918),(645185,'Pulopucca',5918),(645186,'In-Hig-Loi',5918),(645187,'Pulobaha',5918),(645188,'Indira Point',5918),(645189,'Chingen (Incl.Fc At Magar Nalla',5918),(645190,'Galathia River (Fc)',5918),(645191,'Sastri Nagar',5918),(645192,'Gandhi Nagar',5918),(645193,'Laxmi Nagar',5918),(645194,'Vijoy Nagar',5918),(645195,'Joginder Nagar',5918),(645196,'7 Km Farm',5918),(645197,'Not Yet Named (At 27.9 Km)-A',5918),(645198,'Shompen Hut',5918),(645199,'Govinda Nagar',5918),(645200,'Campbell Bay',5918),(645201,'Ranganathan Bay',5918),(645202,'Not Yet Named',5918),(645203,'Not Yet Named',5918),(645204,'Chaw Nallaha',5918),(645205,'Gol Tikrey',5918),(645206,'Navy Dera',5918),(645207,'Lawful',5918),(645208,'Trinket Bay',5918),(645209,'Patisang',5918),(645210,'Lanaya',5918),(645211,'Pitayo',5918),(645212,'Shyam Nagar (Rv)',5919),(645213,'Radha Nagar (Rv)',5919),(645214,'Swarajgram (Rv)',5919),(645215,'Milangram (Rv)',5919),(645216,'Laxmipur (Rv)',5919),(645217,'Deshbandhugram (Rv)',5919),(645218,'Madhupur (Rv)',5919),(645219,'Krishnapuri (Rv)',5919),(645220,'Sitanagar (Rv)',5919),(645221,'Rabindrapalli (Rv)',5919),(645222,'Subhashgram (Rv)',5919),(645223,'Diglipur Part-Ii',5919),(645224,'Ramakrishnagram (Rv)',5919),(645225,'Vidyasagarpalli (Rv)',5919),(645226,'Keralapuram (Rv)',5919),(645227,'Aerial Bay (Rv)',5919),(645228,'Durgapur (Rv)',5919),(645229,'Shibpur (Rv)',5919),(645230,'Kalipur (Rv)',5919),(645231,'Khudirampur (Rv)',5919),(645232,'Nabagram (Rv)',5919),(645233,'Paranghara (Rv)',5919),(645234,'Kishori Nagar (Rv)',5919),(645235,'Madhyamgram (Rv)',5919),(645236,'Nischintapur (Rv)',5919),(645237,'Kalighat (Rv)',5919),(645238,'Jagannath Dera (Rv)',5919),(645239,'Ramnagar (Rv)',5919),(645240,'Borang (Rv)',5919),(645241,'Mohanpur (Rv)',5919),(645242,'Sagar Dweep (Rv)',5919),(645243,'East Island (Police Post & Light House)',5919),(645244,'Narcondam Island (Police Post)',5919),(645245,'Karen Basti (Efa)',5919),(645246,'Elezabeth Bay (Efa)',5919),(645247,'Beach Dera (Efa)',5919),(645248,'Coffe Dera (Efa)',5919),(645249,'Bandhan Nallaha (Efa)',5919),(645250,'Haridas Kattai (Efa)',5919),(645251,'Shyam Nagar',5919),(645252,'Ganesh Nagar',5919),(645253,'Amber Chad (Efa)',5919),(645254,'Santi Nagar',5919),(645255,'Gandhi Nagar',5919),(645256,'Gandhi Nagar (Forest Beat)',5919),(645257,'Radha Nagar (Efa)',5919),(645258,'Burmachad (Efa)',5919),(645259,'Haran Nallaha (Efa)',5919),(645260,'Laxmipur (Efa)',5919),(645261,'Paschimsagar (Efa)',5919),(645262,'Tal Bagan (Efa)',5919),(645263,'Sitanagar, (Efa)',5919),(645264,'Khudirampur (Efa)',5919),(645265,'Lamiya Bay (Efa)',5919),(645266,'Lamiya Bay (Wls)',5919),(645267,'Baskata Nallaha (Efa)',5919),(645268,'Mutha Nallaha (Efa)',5919),(645269,'Bali Nallaha (Efa)',5919),(645270,'Bamboo Nallaha (Efa)',5919),(645271,'Srinagar (Efa)',5919),(645272,'Ganna Level (Efa)',5919),(645273,'Narayan Tikri (Efa)',5919),(645274,'Pemaiahdera (Fdca)',5919),(645275,'Narkul Danga (Efa)',5919),(645276,'Pathi Level (Efa)',5919),(645277,'Bara Dabla (Efa)',5919),(645278,'Hoari Bay (Efa)',5919),(645279,'Pilone Nallaha (Fdca)',5919),(645280,'Aam Tikry (Fdca)',5919),(645281,'Ganna Dabla (Efa)',5919),(645282,'Austin Creek (Fdca)',5919),(645283,'Rahil (Fdca)',5919),(645284,'Bamboo Nallaha (Efa)',5919),(645285,'Austin IX (Fc)',5919),(645286,'Hara Tikry (Efa)',5919),(645287,'Austin II (Efa)',5919),(645288,'Smith (Efa)',5919),(645289,'Smith (Timber Export Depot)',5919),(645290,'Curlew Island (Fcpa)',5919),(645291,'Stewart Island',5919),(645292,'Land Fall Island (Police Post)',5919),(645293,'Aves Island (Rv)',5920),(645294,'Mayabunder (Rv)',5920),(645295,'Pokadera (Rv)',5920),(645296,'Danpur (Rv)',5920),(645297,'Rampur (Rv)',5920),(645298,'Karmatang (Rv)',5920),(645299,'Lucknow (Rv)',5920),(645300,'Lataw (Rv)',5920),(645301,'Devpur (Rv)',5920),(645302,'Webi (Rv)',5920),(645303,'Pahalgaon (Rv)',5920),(645304,'Tugapur (Rv)',5920),(645305,'Hanspuri (Rv) (Including Jpp Camps)',5920),(645306,'Chainpur (Rv)',5920),(645307,'Pudumadurai (Rv)',5920),(645308,'Basantipur (Rv)',5920),(645309,'Profullya Nagar (Rv)',5920),(645310,'Govindpur (Rv)',5920),(645311,'Paresh Nagar (Rv)',5920),(645312,'Kamalapur (Rv)',5920),(645313,'Jaipur (Rv)',5920),(645314,'Pinakinagar (Rv)',5920),(645315,'Harinagar (Rv)',5920),(645316,'Dukennagar (Rv)',5920),(645317,'Santipur (Rv)',5920),(645318,'Swadesh Nagar (Rv)',5920),(645319,'Interview Island (Wls)',5920),(645320,'Interview Island (Po)',5920),(645321,'Karmatang IX (Efa)',5920),(645322,'Karmatang X (Efa) Incl.Bihari Plot (Efa)',5920),(645323,'Paiket Bay (Efa)',5920),(645324,'Bamboo Nallaha (Efa)',5920),(645325,'Buddha Nallaha (Efa)',5920),(645326,'Chuglum Gum (Efa)',5920),(645327,'Sundari Khari (Efa)',5920),(645328,'Karanch Khari (Efa)',5920),(645329,'Gora Tikry (Efa)',5920),(645330,'Chappa Nali (Efa)',5920),(645331,'Khukari Tabla (Efa)',5920),(645332,'Ganesh Nagar I & II (Efa)',5920),(645333,'Ganeshpur (Efa)',5920),(645334,'Luis-In-Let-Bay (Jppc)',5920),(645335,'Shippi Tikry (Efa)',5920),(645336,'Chainpur (Efa)',5920),(645337,'Bajato (Efa & 40 Acre Plot)',5920),(645338,'Asha Nagar (Efa)',5920),(645339,'Birsa Nagar (Efa)',5920),(645340,'Kanchi Nallaha (Efa) & Bamboo Nallaha (Efa)',5920),(645341,'Pather Tikry (Efa)',5920),(645342,'Lauki Nallaha (Efa)',5920),(645343,'Dharmapur (Rv)',5921),(645344,'Ramachandra Nagar (Rv)',5921),(645345,'Thiruvanchikulam (Rv)',5921),(645346,'Shivapuram (Rv)',5921),(645347,'Padmanabhapuram (Rv)',5921),(645348,'Panchawati (Rv)',5921),(645349,'Amkunj (Rv)',5921),(645350,'Nimbutala (Rv)',5921),(645351,'Janakpur (Rv)',5921),(645352,'Desharatpur (Rv)',5921),(645353,'Sitapur (Rv)',5921),(645354,'Mithila (Rv)',5921),(645355,'Rangat (Rv)',5921),(645356,'Rampur (Rv)',5921),(645357,'Parnasala (Rv)',5921),(645358,'Sabari (Rv)',5921),(645359,'Bharatpur (Rv)',5921),(645360,'Shyamkund (Rv)',5921),(645361,'Vishnupur (Rv)',5921),(645362,'Laxmanpur (Rv)',5921),(645363,'Urmilapur (Rv)',5921),(645364,'Kalsi (Rv)',5921),(645365,'Kaushalyanagar (Rv)',5921),(645366,'Saktigarh (Rv)',5921),(645367,'Bangaon (Rv)',5921),(645368,'Yeratiljig (Rv)',5921),(645369,'Kadamtala (Rv)',5921),(645370,'Santanu (Rv)',5921),(645371,'Uttara (Rv)',5921),(645372,'Long Island (Rv)',5921),(645373,'Strait Island (As)',5921),(645374,'Adojig (Rv) (Including Efa)',5921),(645375,'Bejoygarh (Rv)',5921),(645376,'Udhaygarh (Rv)',5921),(645377,'Sundergarh (Rv) (Including Efa)',5921),(645378,'Kanchangarh (Rv)',5921),(645379,'Nilambur (Rv)',5921),(645380,'Abaygarh (Rv)',5921),(645381,'Raglachang (Rv)',5921),(645382,'Nayagarh (Rv)',5921),(645383,'Rajatgarh (Rv)',5921),(645384,'Wrafter\'S Creek (Rv)',5921),(645385,'Khatta Khari (Rv)',5921),(645386,'Cutbert Bay (Efa)',5921),(645387,'Thoraktang (Fc)',5921),(645388,'Dhani Nallaha (Efa)',5921),(645389,'Sippi Tikry (Efa)',5921),(645390,'Sukha Nallaha (Efa)',5921),(645391,'Panchawati (Efa)',5921),(645392,'Parnasala II (Fc)',5921),(645393,'Japan Tikri (Efa)',5921),(645394,'Gol Pahar (Efa)',5921),(645395,'Kalsi (Jppc)',5921),(645396,'Sagwan Nallaha (Fc)',5921),(645397,'Kalsi No. 6 (Jppc)',5921),(645398,'Kalsi No. 4 (Jppc)',5921),(645399,'Kalsi No. 3 (Jppc)',5921),(645400,'Charlungta (Fc)',5921),(645401,'Charlungta II (Fc)',5921),(645402,'Boroinyol II (Fc)',5921),(645403,'Porlobjig No. 15 (Fc, Apwdc & Jppc)',5921),(645404,'Dhani Nallaha (Ja)',5921),(645405,'Porlobjig No.10 (Fc & Jppc)',5921),(645406,'Porlobjig No.9 (Jppc)',5921),(645407,'Yeratiljig No. 9 (Jppc)',5921),(645408,'Yeratiljig No. 11 (Jppc)',5921),(645409,'Macarthy Valley (Efa)',5921),(645410,'Boreham Valley (Fs)',5921),(645411,'Foster Valley (Efa)',5921),(645412,'Jermyvalley(Fs)',5921),(645413,'Porlobjig No. 3 (Jppc, Apwdc & Anifpdc)',5921),(645414,'Bamboo Tikry (Jppc)',5921),(645415,'Yeratiljig No. 10 (Jppc)',5921),(645416,'Foul Bay (Ja)',5921),(645417,'Lakra Lungta (Ja)',5921),(645418,'Between Chotaligbang And Foul Bay (Ja)',5921),(645419,'Chotaligbang (Ja)',5921),(645420,'Porlob Depot. (Fc)',5921),(645421,'Lalajig Bay (Ac)',5921),(645422,'Merk Bay (Ac)',5921),(645423,'Elephenstone Harbour (Fc)',5921),(645424,'Pawajig (Fc)',5921),(645425,'Pawajig & Nayadera (Fc)',5921),(645426,'Lorrojig (Fc) & Vishnu Nallaha (Apwdc)',5921),(645427,'Sastri Nallah (Fc)',5921),(645428,'Papita Dera (Fc)',5921),(645429,'Sanker Nallaha (Pwdc)',5921),(645430,'Bolcha (Efa)',5921),(645431,'Nilambur (Efa)',5921),(645432,'South Creek (Efa, Fc & Apwdc)',5921),(645433,'Middle Strait (Jppc)',5921),(645434,'Raglachang (Nayadera) (Fc)',5921),(645435,'Rajatgarh (Efa)',5921),(645436,'Wrafter\'S Creek (Efa)',5921),(645437,'Khatta Khari (Efa)',5921),(645438,'Spike Island (Ja)',5921),(645439,'Bakultala Rv',5921),(645440,'Shoal Bay (Rv)',5922),(645441,'Jirkatang No. 2 (Rv)',5922),(645442,'Kalatang (Rv)',5922),(645443,'Wright Myo (Rv)',5922),(645444,'Madhuban (Rv)',5922),(645445,'Malapuram (Rv)',5922),(645446,'Mannarghat (Rv)',5922),(645447,'Mile Tilek (Rv) & Mile Tilek (Efa)',5922),(645448,'Alipur (Rv)',5922),(645449,'Wimberlygunj (Rv)',5922),(645450,'Mount Harriet (Rv)',5922),(645451,'Stewartgunj (Rv)',5922),(645452,'Govindapuram (Rv)',5922),(645453,'Knapuram (Rv)',5922),(645454,'Mathura (Rv)',5922),(645455,'Temple Myo (Rv)',5922),(645456,'Tirur (Rv)',5922),(645457,'Herbertabad (Rv)',5922),(645458,'Ferrargunj (Rv)',5922),(645459,'Bindraban (Rv)',5922),(645460,'Kadakachang (Rv)',5922),(645461,'Hope Town (Rv)',5922),(645462,'North Bay (Rv)',5922),(645463,'Shore Point (Rv)',5922),(645464,'Aniket (Rv)',5922),(645465,'Caddlegunj (Rv) (Incl. Sona Pahar & Hazari Bagh (Jppc)',5922),(645466,'Namunaghar (Rv)',5922),(645467,'Dundas Point (Rv)',5922),(645468,'Mithakhari (Rv)',5922),(645469,'Tusnabad (Rv)',5922),(645470,'Colinpur (Rv)',5922),(645471,'Manpur (Rv)',5922),(645472,'Mohwa Dera (Rv)',5922),(645473,'Hobdipur (Rv)',5922),(645474,'Ograbraij (Rv)',5922),(645475,'Muslim Basti (Rv)',5922),(645476,'Port Mouat (Rv)',5922),(645477,'Balughat (Rv)',5922),(645478,'Badmash Pahar (Rv)',5922),(645479,'Craikabad (Rv)',5922),(645480,'Chouldari (Rv)',5922),(645481,'Dhanikhari (Rv)',5922),(645482,'Homfreygunj (Rv)',5922),(645483,'Maymyo (Rv)',5922),(645484,'Wandur (Rv)',5922),(645485,'Hashmatabad (Rv)',5922),(645486,'Manglutan (Rv)',5922),(645487,'Nayashahar (Rv)',5922),(645488,'Guptapara (Rv)',5922),(645489,'Manjeri (Rv) & Line Dera (Fc)',5922),(645490,'Viper Island (Rv)',5922),(645491,'Flat Bay (Rv)',5922),(645492,'Middle Strait (Jppc)',5922),(645493,'Potatang (Fc)',5922),(645494,'Between Middle Strait (Jppc) & Jirkatang (Ja)',5922),(645495,'Shoal Bay 17 (Fc)',5922),(645496,'Shoal Bay 19 (Fc)',5922),(645497,'Mrichi Dera (Efa)',5922),(645498,'Jirkatang Camp No. 7 (Fc)',5922),(645499,'Jirkatang No.2 (Efa)',5922),(645500,'Coffee Plot',5922),(645501,'Mile Tilek (Jppc)',5922),(645502,'Mile Tilek (Arf)',5922),(645503,'Tirur (Jppc)',5922),(645504,'Jhinga Nallaha (Jppc & Apwdc)',5922),(645505,'Beach Dera (Efa)',5922),(645506,'Tirur I (Jppc)',5922),(645507,'Anjali Nallaha (Jppc)',5922),(645508,'Tirur IV (Jppc)',5922),(645509,'Tirur (Ja)',5922),(645510,'Chouldari(Lohabarrack)',5922),(645511,'Maymyo (Efa)',5922),(645512,'Hashmatabad (Efa)',5922),(645513,'Manglutan (Efa)',5922),(645514,'Nayashahar (Efa)',5922),(645515,'Pongi Balu (Fc) & Bada Balu (Efa)',5922),(645516,'Bambooflat',5922),(645517,'Govinda Nagar (Rv)',5923),(645518,'Bejoy Nagar (Rv)',5923),(645519,'Shyam Nagar (Rv)',5923),(645520,'Krishna Nagar (Rv)',5923),(645521,'Radha Nagar (Rv)',5923),(645522,'Sitapur (Rv)',5923),(645523,'Bharatpur (Rv)',5923),(645524,'Neil Kendra (Rv)',5923),(645525,'Lakshmanpur (Rv)',5923),(645526,'Ram Nagar (Rv)',5923),(645527,'Pahargaon Part (Rv)',5923),(645528,'School Line Part (Rv)',5923),(645529,'Dollygunj (Rv)',5923),(645530,'Minnie Bay Part (Rv)',5923),(645531,'Brookshabad Part (Rv)',5923),(645532,'Brichgunj (Rv)',5923),(645533,'Teylorabad (Rv)',5923),(645534,'Sippighat (Rv)',5923),(645535,'Bimilitan (Rv) & Kodiyaghat',5923),(645536,'Calicut (Rv)',5923),(645537,'Beadnabad (Rv)',5923),(645538,'Rangachang (Rv)',5923),(645539,'Chidiyatapu (Rv)',5923),(645540,'Rutland (Rv)',5923),(645541,'John Lawrance (Fc)',5923),(645542,'Bada Nallaha/Bada Balu (Efa)',5923),(645543,'Chidiyatapu (Wls)',5923),(645544,'Munda Pahar (Efa)',5923),(645545,'R.M.Point (Fc)',5923),(645546,'Bamboo Nallaha(Efa) Incl. Kichad Nallaha(Efa)',5923),(645547,'Bada Khari (Fc)',5923),(645548,'North Sentinel Island (Sa)',5923),(645549,'Cinque Island (Wls)',5923),(645550,'Prothrapur (Ct)',5923),(645551,'Garacharma (Ct)',5923),(645552,'Dugong Creek (Os)',5924),(645553,'Vivekanandapuram (Rv)',5924),(645554,'Rabindra Nagar (Rv)',5924),(645555,'Ramakrishnapur (Rv)',5924),(645556,'Netaji Nagar (Rv)',5924),(645557,'M/S Asia & Company (Asia Timber Product)',5924),(645558,'Hut Bay (Rv)',5924),(645559,'Harmender Bay (Ns)',5924),(645560,'South Bay (Light House Camp)',5924),(645561,'South Bay (Os)',5924),(645562,'Forest Camp At 19 Km. (Fdca)',5924),(645563,'Forest Camp At 14 Km. 5-Iii (Fdca)',5924),(645564,'Forest Camp At 14 Km. 5-Ii (Fdca)',5924),(645565,'Forest Camp At 14 Km 5 - I (Fdca)',5924),(645566,'Butler Bay Forest Camp 4-Iii (Fdca)',5924),(645567,'Butler Bay Forest Camp 4-Iv (Fdca)',5924),(645568,'Red Oil Palm (Nursery Camp)',5924),(645569,'Butler Bay Forest Camp 4-Ii (Fdca)',5924),(645570,'Butler Bay Forest Camp 4-I (Fdca)',5924);
/*!40000 ALTER TABLE `villages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary view structure for view `vw_appointment_medical_history`
--

DROP TABLE IF EXISTS `vw_appointment_medical_history`;
/*!50001 DROP VIEW IF EXISTS `vw_appointment_medical_history`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `vw_appointment_medical_history` AS SELECT 
 1 AS `appointment_id`,
 1 AS `pet_id`,
 1 AS `pet_name`,
 1 AS `facility_id`,
 1 AS `facility_name`,
 1 AS `appointment_type`,
 1 AS `appointment_date`,
 1 AS `appointment_status`,
 1 AS `assigned_staff_id`,
 1 AS `assigned_staff`,
 1 AS `record_id`,
 1 AS `record_type`,
 1 AS `diagnosis`,
 1 AS `treatment`,
 1 AS `medications`,
 1 AS `treatment_date`*/;
SET character_set_client = @saved_cs_client;

--
-- Dumping routines for database 'veterinary_portal'
--
/*!50003 DROP PROCEDURE IF EXISTS `sp_create_appointment_with_token` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_create_appointment_with_token`(
    IN p_pet_id VARCHAR(20),
    IN p_facility_id INT,
    IN p_appointment_type ENUM('Checkup', 'Vaccination', 'Surgery', 'Emergency', 'Other'),
    IN p_preferred_date DATE,
    IN p_preferred_time TIME,
    IN p_symptoms TEXT,
    IN p_additional_notes TEXT,
    IN p_created_by VARCHAR(20),
    OUT p_appointment_id INT,
    OUT p_token_number INT
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    CALL sp_generate_daily_appointment_token(p_facility_id, p_preferred_date, p_token_number);
    
    INSERT INTO appointments (
        pet_id,
        facility_id,
        appointment_type,
        preferred_date,
        preferred_time,
        symptoms,
        additional_notes,
        token_number,
        created_by
    ) VALUES (
        p_pet_id,
        p_facility_id,
        p_appointment_type,
        p_preferred_date,
        p_preferred_time,
        p_symptoms,
        p_additional_notes,
        p_token_number,
        p_created_by
    );
    
    SET p_appointment_id = LAST_INSERT_ID();
    
    COMMIT;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_generate_daily_appointment_token` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_generate_daily_appointment_token`(
    IN p_facility_id INT,
    IN p_preferred_date DATE,
    OUT p_token_number INT
)
BEGIN
    SELECT IFNULL(MAX(token_number), 0) + 1 INTO p_token_number
    FROM appointments
    WHERE facility_id = p_facility_id
    AND preferred_date = p_preferred_date;
    
    SET p_token_number = GREATEST(IFNULL(p_token_number, 1), 1);
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Final view structure for view `vw_appointment_medical_history`
--

/*!50001 DROP VIEW IF EXISTS `vw_appointment_medical_history`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_appointment_medical_history` AS select `a`.`appointment_id` AS `appointment_id`,`a`.`pet_id` AS `pet_id`,`p`.`pet_name` AS `pet_name`,`a`.`facility_id` AS `facility_id`,`f`.`official_name` AS `facility_name`,`a`.`appointment_type` AS `appointment_type`,`a`.`preferred_date` AS `appointment_date`,`a`.`status` AS `appointment_status`,`a`.`assigned_staff_id` AS `assigned_staff_id`,`s`.`full_name` AS `assigned_staff`,`m`.`record_id` AS `record_id`,`m`.`record_type` AS `record_type`,`m`.`diagnosis` AS `diagnosis`,`m`.`treatment` AS `treatment`,`m`.`medications` AS `medications`,`m`.`record_date` AS `treatment_date` from ((((`appointments` `a` left join `medical_records` `m` on((`a`.`appointment_id` = `m`.`appointment_id`))) left join `facility_staff` `s` on((`a`.`assigned_staff_id` = `s`.`staff_id`))) left join `pets` `p` on((`a`.`pet_id` = `p`.`pet_id`))) left join `veterinary_facilities` `f` on((`a`.`facility_id` = `f`.`facility_id`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-07-21 11:39:21

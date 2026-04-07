CREATE DATABASE  IF NOT EXISTS `comedor` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `comedor`;
-- MySQL dump 10.13  Distrib 8.0.34, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: comedor
-- ------------------------------------------------------
-- Server version	8.1.0

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
-- Table structure for table `bebida`
--

DROP TABLE IF EXISTS `bebida`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bebida` (
  `id_bebida` int NOT NULL AUTO_INCREMENT,
  `nom_bebida` varchar(45) NOT NULL,
  `desc_bebida` varchar(100) NOT NULL,
  `precio_bebida` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id_bebida`),
  UNIQUE KEY `id_bebida_UNIQUE` (`id_bebida`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bebida`
--

LOCK TABLES `bebida` WRITE;
/*!40000 ALTER TABLE `bebida` DISABLE KEYS */;
INSERT INTO `bebida` VALUES (1,'Gaseosa 500 ml','Coca Cola, Fanta o Sprite',2800.00),(2,'Gaseosa 1 litro','Coca Cola, Fanta o Sprite',4000.00),(3,'Gaseosa 1,5 litro','Coca Cola, Fanta o Sprite',4500.00),(4,'Agua saborizada 500 ml','Pomelo, pera, manzana',2500.00),(5,'Agua saborizada 1,5 litro','Pomelo, pera, manzana',3800.00),(6,'Agua 500 ml','Agua sin gas',2500.00),(7,'Agua 2 litros','Agua sin gas',3800.00),(8,'Agua 500 ml con gas','Agua con gas',2500.00),(9,'Agua 2 litros con gas','Agua con gas',3800.00),(10,'Cerveza 800 ml','Rubia Salta',4500.00);
/*!40000 ALTER TABLE `bebida` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cajero`
--

DROP TABLE IF EXISTS `cajero`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cajero` (
  `id_cajero` int NOT NULL AUTO_INCREMENT,
  `nom_cajero` varchar(45) NOT NULL,
  `ape_cajero` varchar(45) NOT NULL,
  `usu_cajero` varchar(45) NOT NULL,
  `pass_cajero` varchar(255) NOT NULL,
  `estado` varchar(10) NOT NULL DEFAULT 'Activo',
  PRIMARY KEY (`id_cajero`),
  UNIQUE KEY `id_cajero_UNIQUE` (`id_cajero`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cajero`
--

LOCK TABLES `cajero` WRITE;
/*!40000 ALTER TABLE `cajero` DISABLE KEYS */;
-- Contraseñas de ejemplo en texto plano para instalación inicial.
-- Ejecutá migrate_passwords.php después de importar para hashearlas con bcrypt.
INSERT INTO `cajero` VALUES (1,'Lourdes','Jaime','Lourdes','1234','Activo'),(2,'Vanesa','Alcala','Vanesa','1234','Activo');
/*!40000 ALTER TABLE `cajero` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `detalle_bebida`
--

DROP TABLE IF EXISTS `detalle_bebida`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `detalle_bebida` (
  `id_detalle_bebida` int NOT NULL AUTO_INCREMENT,
  `id_pedido` int NOT NULL,
  `id_bebida` int NOT NULL,
  `cantidad` int NOT NULL,
  `precio_unitario` int NOT NULL,
  `subtotal` int NOT NULL,
  `observacion` varchar(200) NOT NULL DEFAULT '',
  PRIMARY KEY (`id_detalle_bebida`),
  KEY `fk_detalle_bebida_pedido` (`id_pedido`),
  KEY `fk_detalle_bebida_bebida` (`id_bebida`),
  CONSTRAINT `fk_detalle_bebida_bebida` FOREIGN KEY (`id_bebida`) REFERENCES `bebida` (`id_bebida`),
  CONSTRAINT `fk_detalle_bebida_pedido` FOREIGN KEY (`id_pedido`) REFERENCES `pedido` (`id_pedido`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detalle_bebida`
--

LOCK TABLES `detalle_bebida` WRITE;
/*!40000 ALTER TABLE `detalle_bebida` DISABLE KEYS */;
INSERT INTO `detalle_bebida` VALUES (1,1,1,1,2800,2800,''),(2,2,2,1,4000,4000,'');
/*!40000 ALTER TABLE `detalle_bebida` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `detalle_plato`
--

DROP TABLE IF EXISTS `detalle_plato`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `detalle_plato` (
  `id_detalle_plato` int NOT NULL AUTO_INCREMENT,
  `id_pedido` int NOT NULL,
  `id_plato` int NOT NULL,
  `cantidad` int NOT NULL,
  `precio_unitario` int NOT NULL,
  `subtotal` int NOT NULL,
  `observacion` varchar(200) NOT NULL DEFAULT '',
  PRIMARY KEY (`id_detalle_plato`),
  KEY `fk_detalle_plato_pedido` (`id_pedido`),
  KEY `fk_detalle_plato_plato` (`id_plato`),
  CONSTRAINT `fk_detalle_plato_pedido` FOREIGN KEY (`id_pedido`) REFERENCES `pedido` (`id_pedido`) ON DELETE CASCADE,
  CONSTRAINT `fk_detalle_plato_plato` FOREIGN KEY (`id_plato`) REFERENCES `plato` (`id_plato`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detalle_plato`
--

LOCK TABLES `detalle_plato` WRITE;
/*!40000 ALTER TABLE `detalle_plato` DISABLE KEYS */;
INSERT INTO `detalle_plato` VALUES (1,1,4,1,11000,11000,''),(2,2,6,1,9300,9300,''),(3,2,15,1,8000,8000,'');
/*!40000 ALTER TABLE `detalle_plato` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `detalle_postre`
--

DROP TABLE IF EXISTS `detalle_postre`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `detalle_postre` (
  `id_detalle_postre` int NOT NULL AUTO_INCREMENT,
  `id_pedido` int NOT NULL,
  `id_postre` int NOT NULL,
  `cantidad` int NOT NULL,
  `precio_unitario` int NOT NULL,
  `subtotal` int NOT NULL,
  `observacion` varchar(200) NOT NULL DEFAULT '',
  PRIMARY KEY (`id_detalle_postre`),
  KEY `fk_detalle_postre_pedido` (`id_pedido`),
  KEY `fk_detalle_postre_postre` (`id_postre`),
  CONSTRAINT `fk_detalle_postre_pedido` FOREIGN KEY (`id_pedido`) REFERENCES `pedido` (`id_pedido`) ON DELETE CASCADE,
  CONSTRAINT `fk_detalle_postre_postre` FOREIGN KEY (`id_postre`) REFERENCES `postre` (`id_postre`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detalle_postre`
--

LOCK TABLES `detalle_postre` WRITE;
/*!40000 ALTER TABLE `detalle_postre` DISABLE KEYS */;
INSERT INTO `detalle_postre` VALUES (1,2,1,1,3500,3500,'');
/*!40000 ALTER TABLE `detalle_postre` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mesa`
--

DROP TABLE IF EXISTS `mesa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mesa` (
  `id_mesa` int NOT NULL AUTO_INCREMENT,
  `dispo_mesa` varchar(45) NOT NULL DEFAULT 'Disponible',
  `zona` varchar(50) NOT NULL DEFAULT 'Salón',
  PRIMARY KEY (`id_mesa`),
  UNIQUE KEY `id_mesa_UNIQUE` (`id_mesa`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mesa`
--

LOCK TABLES `mesa` WRITE;
/*!40000 ALTER TABLE `mesa` DISABLE KEYS */;
INSERT INTO `mesa` VALUES (1,'Disponible','Salón'),(2,'Disponible','Salón'),(3,'Disponible','Salón'),(4,'Disponible','Salón'),(5,'Disponible','Salón'),(6,'Disponible','Salón'),(7,'Disponible','Salón'),(8,'Disponible','Salón'),(9,'Disponible','Patio'),(10,'Ocupada','Patio'),(11,'Disponible','Patio'),(12,'Disponible','Patio'),(13,'Disponible','Patio'),(14,'Disponible','Patio'),(15,'Disponible','Patio'),(16,'Disponible','Patio');
/*!40000 ALTER TABLE `mesa` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mozo`
--

DROP TABLE IF EXISTS `mozo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mozo` (
  `id_mozo` int NOT NULL AUTO_INCREMENT,
  `nom_mozo` varchar(45) NOT NULL,
  `ape_mozo` varchar(45) NOT NULL,
  `usu_mozo` varchar(45) NOT NULL,
  `pass_mozo` varchar(255) NOT NULL,
  `estado` varchar(10) NOT NULL DEFAULT 'Activo',
  PRIMARY KEY (`id_mozo`),
  UNIQUE KEY `id_mozo_UNIQUE` (`id_mozo`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mozo`
--

LOCK TABLES `mozo` WRITE;
/*!40000 ALTER TABLE `mozo` DISABLE KEYS */;
-- Contraseñas de ejemplo en texto plano para instalación inicial.
-- Ejecutá migrate_passwords.php después de importar para hashearlas con bcrypt.
INSERT INTO `mozo` VALUES (1,'Luis','Aramayo','Lucho','Lucho','Activo'),(2,'Zulema','Cruz','Zule','Zule','Activo');
/*!40000 ALTER TABLE `mozo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pedido`
--

DROP TABLE IF EXISTS `pedido`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pedido` (
  `id_pedido` int NOT NULL AUTO_INCREMENT,
  `descr_pedido` varchar(100) NOT NULL,
  `estado_pedido` varchar(45) NOT NULL DEFAULT 'En Proceso',
  `id_mozo` int DEFAULT NULL,
  `id_mesa` int NOT NULL,
  `id_cajero` int NOT NULL,
  `total_pedido` decimal(10,2) NOT NULL DEFAULT '0.00',
  `fecha_pedido` datetime DEFAULT NULL,
  PRIMARY KEY (`id_pedido`),
  UNIQUE KEY `id_pedido_UNIQUE` (`id_pedido`),
  KEY `fk_pedido_mozo` (`id_mozo`),
  KEY `fk_pedido_mesa` (`id_mesa`),
  KEY `fk_pedido_cajero` (`id_cajero`),
  CONSTRAINT `fk_pedido_cajero` FOREIGN KEY (`id_cajero`) REFERENCES `cajero` (`id_cajero`),
  CONSTRAINT `fk_pedido_mesa` FOREIGN KEY (`id_mesa`) REFERENCES `mesa` (`id_mesa`),
  CONSTRAINT `fk_pedido_mozo` FOREIGN KEY (`id_mozo`) REFERENCES `mozo` (`id_mozo`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pedido`
--

LOCK TABLES `pedido` WRITE;
/*!40000 ALTER TABLE `pedido` DISABLE KEYS */;
INSERT INTO `pedido` VALUES (1,'Sin Lechuga','En Proceso',1,10,1,12800,'2025-06-12 14:30:00'),(2,'Bien Cocido','Completado',2,5,2,15300,'2025-06-12 13:15:00');
/*!40000 ALTER TABLE `pedido` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plato`
--

DROP TABLE IF EXISTS `plato`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `plato` (
  `id_plato` int NOT NULL AUTO_INCREMENT,
  `nom_plato` varchar(45) NOT NULL,
  `descr_plato` varchar(100) NOT NULL,
  `precio_plato` int NOT NULL,
  PRIMARY KEY (`id_plato`),
  UNIQUE KEY `id_plato_UNIQUE` (`id_plato`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plato`
--

LOCK TABLES `plato` WRITE;
/*!40000 ALTER TABLE `plato` DISABLE KEYS */;
INSERT INTO `plato` VALUES (1,'Empanadas','Carne o Queso',750),(2,'Berenjena','Gratinada con queso de cabra',2700),(3,'Queso de cabra a la plancha','Con oliva y especias',2300),(4,'Bife de lomo','A caballo',11000),(5,'Matambre','Napolitana a caballo',10000),(6,'Milanesa','A caballo, Napolitana, Fugazzeta',9300),(7,'Bife de Pollo','Al limón con huevo frito',8500),(8,'Salteado de Cordero','Con verduras y arroz',11000),(9,'Milanesa al cardón','Incluye verduras salteadas, queso de cabra, quinoa y huevo frito',9800),(10,'Cazuela de cabrito','Cabrito más verduras varias',9000),(11,'Locro','Comida regional',8000),(12,'Omelette verdura y queso','Vegetariano',8300),(13,'Salteado de tallarines','Con verduras y salsa de soja',8000),(14,'Sandwich','Milanesa, Lomo o Hamburguesa',6000),(15,'Pizza Común','Muzzarella, salsa y aceitunas',8000),(16,'Pizza Especial','Muzzarella, jamón, salsa y aceitunas',9300),(17,'Pizza Fugazzeta','Muzzarella, cebolla gratinada y salsa',10000);
/*!40000 ALTER TABLE `plato` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `postre`
--

DROP TABLE IF EXISTS `postre`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `postre` (
  `id_postre` int NOT NULL AUTO_INCREMENT,
  `nom_postre` varchar(45) NOT NULL,
  `desc_postre` varchar(100) NOT NULL,
  `precio_postre` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id_postre`),
  UNIQUE KEY `id_postre_UNIQUE` (`id_postre`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `postre`
--

LOCK TABLES `postre` WRITE;
/*!40000 ALTER TABLE `postre` DISABLE KEYS */;
INSERT INTO `postre` VALUES (1,'Cayote con nueces','Nueces regionales',3500.00),(2,'Queso con Batata','Queso Tybo',3800.00),(3,'Anchi con pelones','Pelones Regioneales',4000.00);
/*!40000 ALTER TABLE `postre` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `configuracion`
--

DROP TABLE IF EXISTS `configuracion`;
CREATE TABLE `configuracion` (
  `clave` varchar(50) NOT NULL,
  `valor` text NOT NULL,
  PRIMARY KEY (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Datos iniciales de configuracion
--

LOCK TABLES `configuracion` WRITE;
INSERT INTO `configuracion` VALUES
('nombre',              'Mi Restaurante'),
('moneda',              '$'),
('zona1_nombre',        'Salón'),
('zona1_hasta',         '8'),
('zona2_nombre',        'Patio'),
('logo_path',           ''),
('trial_inicio',        ''),
('licencia_clave',      ''),
('contacto_email',      'matias.4kfull@gmail.com'),
('contacto_whatsapp',   '3875755630');
UNLOCK TABLES;

-- ─────────────────────────────────────────────────────────────────────────────
-- MIGRACIONES PARA INSTALACIONES EXISTENTES
-- Si ya tenés la base importada y estás actualizando, ejecutá solo este bloque.
-- ─────────────────────────────────────────────────────────────────────────────

-- cajero: ampliar pass y agregar estado
ALTER TABLE `cajero`
  MODIFY COLUMN `pass_cajero` varchar(255) NOT NULL,
  ADD COLUMN IF NOT EXISTS `estado` varchar(10) NOT NULL DEFAULT 'Activo';

-- mozo: ampliar pass y agregar estado
ALTER TABLE `mozo`
  MODIFY COLUMN `pass_mozo` varchar(255) NOT NULL,
  ADD COLUMN IF NOT EXISTS `estado` varchar(10) NOT NULL DEFAULT 'Activo';

-- pedido: mozo nullable + total como decimal
ALTER TABLE `pedido`
  MODIFY COLUMN `id_mozo` int DEFAULT NULL,
  MODIFY COLUMN `total_pedido` decimal(10,2) NOT NULL DEFAULT '0.00';

-- pedido: reemplazar FK de mozo para agregar ON DELETE SET NULL
-- (separado para que un error en DROP no cancele los MODIFY anteriores)
ALTER TABLE `pedido` DROP FOREIGN KEY IF EXISTS `fk_pedido_mozo`;
ALTER TABLE `pedido` ADD CONSTRAINT `fk_pedido_mozo`
  FOREIGN KEY (`id_mozo`) REFERENCES `mozo` (`id_mozo`) ON DELETE SET NULL;

-- detalle_plato: agregar observacion
ALTER TABLE `detalle_plato`
  ADD COLUMN IF NOT EXISTS `observacion` varchar(200) NOT NULL DEFAULT '';

-- detalle_bebida: agregar observacion
ALTER TABLE `detalle_bebida`
  ADD COLUMN IF NOT EXISTS `observacion` varchar(200) NOT NULL DEFAULT '';

-- detalle_postre: agregar observacion
ALTER TABLE `detalle_postre`
  ADD COLUMN IF NOT EXISTS `observacion` varchar(200) NOT NULL DEFAULT '';

-- mesa: agregar columna zona
ALTER TABLE `mesa`
  ADD COLUMN IF NOT EXISTS `zona` varchar(50) NOT NULL DEFAULT 'Salón';
UPDATE `mesa` SET `zona` = 'Patio' WHERE `id_mesa` > 8 AND `zona` = 'Salón';

-- Tabla de configuración (si no existe)
CREATE TABLE IF NOT EXISTS `configuracion` (
  `clave` varchar(50) NOT NULL,
  `valor` text NOT NULL,
  PRIMARY KEY (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT IGNORE INTO `configuracion` (`clave`, `valor`) VALUES
('nombre',              'Mi Restaurante'),
('moneda',              '$'),
('zona1_nombre',        'Salón'),
('zona1_hasta',         '8'),
('zona2_nombre',        'Patio'),
('logo_path',           ''),
('trial_inicio',        ''),
('licencia_clave',      ''),
('contacto_email',      'matias.4kfull@gmail.com'),
('contacto_whatsapp',   '3875755630');

/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-11-11 20:27:37

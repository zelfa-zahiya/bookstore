-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               8.0.30 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.1.0.6537
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for bookstore
CREATE DATABASE IF NOT EXISTS `bookstore` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `bookstore`;

-- Dumping structure for table bookstore.books
CREATE TABLE IF NOT EXISTS `books` (
  `id_buku` int NOT NULL AUTO_INCREMENT,
  `judul` varchar(255) NOT NULL,
  `penulis` varchar(150) NOT NULL,
  `penerbit` varchar(150) DEFAULT NULL,
  `tahun` int DEFAULT NULL,
  `harga` decimal(15,2) DEFAULT '0.00',
  `stok` int DEFAULT '0',
  `id_kategori` int DEFAULT NULL,
  `deskripsi` text,
  `gambar` longtext,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_buku`),
  KEY `id_kategori` (`id_kategori`),
  CONSTRAINT `books_ibfk_1` FOREIGN KEY (`id_kategori`) REFERENCES `categories` (`id_kategori`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table bookstore.books: ~8 rows (approximately)
INSERT INTO `books` (`id_buku`, `judul`, `penulis`, `penerbit`, `tahun`, `harga`, `stok`, `id_kategori`, `deskripsi`, `gambar`, `created_at`, `updated_at`) VALUES
	(1, 'Albert Einstein', 'Walter Isaacson', 'Simon & Schuster', NULL, 170000.00, 5, 3, 'Kisah ilmuwan jenius pencetus teori relativitas', '1775801630_Albert_Einstein.jpg', '2026-04-10 06:13:50', '2026-04-10 06:14:09'),
	(2, 'Bahasa Indonesia SMA Kelas X', 'Kemendikbud', 'Erlangga', NULL, 80000.00, 6, 7, 'Buku pelajaran bahasa indonesia untuk siswa SMA/sederajat kelas 10', '1775801731_Bahasa_Indonesia_SMA_Kelas_X.png', '2026-04-10 06:15:31', '2026-04-10 06:15:31'),
	(3, 'Sangkuriang', ' Heny V. Tinneke', 'Balai Pustakan', NULL, 40000.00, 8, 4, 'Legenda asal-usul Gunung Tangkuban Perahu dari kisah Sangkuriang.', '1775801851_Sangkuriang.jpeg', '2026-04-10 06:17:31', '2026-04-10 06:17:31'),
	(4, 'Bumi Manusia', 'Pramoedya Ananta Toer', 'Lentera Dipantara', NULL, 100000.00, 3, 8, 'Novel sejarah tentang kehidupan pribumi di masa kolonial Belanda.', '1775801928_Bumi_Manusia.jpg', '2026-04-10 06:18:48', '2026-04-10 06:18:48'),
	(5, 'One Piece Vol. 1', 'Eiichiro Oda', 'Shueisha', NULL, 50000.00, 15, 6, 'Petualangan Luffy menjadi raja bajak laut dan mencari harta karun legendaris.', '1775802008_One_Piece_Vol__1.jpg', '2026-04-10 06:20:08', '2026-04-10 06:20:08'),
	(6, 'The 7 Habits of Highly Effective People', 'Stephen R. Covey', 'Fress Press', NULL, 140000.00, 5, 2, 'Panduan membangun kebiasaan efektif untuk meningkatkan kualitas hidup dan karier.', '1775802076_The_7_Habits_of_Highly_Effective_People.png', '2026-04-10 06:21:16', '2026-04-10 06:21:16'),
	(7, 'Think and Grow Rich', 'Napoleon Hill', 'The Ralston Society', NULL, 85000.00, 10, 2, 'Buku motivasi tentang cara mencapai kesuksesan melalui pola pikir dann keyakinan.', '1775802155_Think_and_Grow_Rich.jpg', '2026-04-10 06:22:35', '2026-04-10 06:22:35'),
	(8, 'The Alchemist', 'Paulo Coelho', 'harperCollins', NULL, 95000.00, 12, 1, 'Kisah perjalanan seorang penggembala bernama Santiago dalam mencari makna hidup dan impian.', '1775802235_The_Alchemist.jpeg', '2026-04-10 06:23:55', '2026-04-10 06:23:55'),
	(9, 'Harry Potter and the Sorcere\'s Stone', 'J.K Rowling', 'Bloomsbury', NULL, 120000.00, 16, 1, 'Kisah seorang anak penyihir bernama Harry Potter yang menemukan jati dirinya di sekolah sihir Hogwarts', '1775802343_Harry_Potter_and_the_Sorcere_s_Stone.jpg', '2026-04-10 06:25:43', '2026-04-10 21:09:15');

-- Dumping structure for table bookstore.cart
CREATE TABLE IF NOT EXISTS `cart` (
  `id_cart` int NOT NULL AUTO_INCREMENT,
  `id_user` int DEFAULT NULL,
  `id_buku` int DEFAULT NULL,
  `jumlah` int DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_cart`),
  KEY `id_buku` (`id_buku`),
  CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`id_buku`) REFERENCES `books` (`id_buku`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table bookstore.cart: ~0 rows (approximately)

-- Dumping structure for table bookstore.categories
CREATE TABLE IF NOT EXISTS `categories` (
  `id_kategori` int NOT NULL AUTO_INCREMENT,
  `nama_kategori` varchar(100) NOT NULL,
  `deskripsi` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_kategori`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table bookstore.categories: ~8 rows (approximately)
INSERT INTO `categories` (`id_kategori`, `nama_kategori`, `deskripsi`, `created_at`, `updated_at`) VALUES
	(1, 'Fiksi', 'Buku tentang Fiksi\r\n', '2026-04-10 06:08:29', '2026-04-10 06:08:29'),
	(2, 'Non-Fiksi', 'Buku tentang Non-Fiksi', '2026-04-10 06:08:45', '2026-04-10 06:08:45'),
	(3, 'Biografi', 'Buku tentang Biografi', '2026-04-10 06:09:07', '2026-04-10 06:09:07'),
	(4, 'Cerita Rakyat', 'Buku tentang Cerita Rakyat', '2026-04-10 06:09:31', '2026-04-10 06:09:31'),
	(5, 'Dongeng', 'Buku tentang Dongeng\r\n', '2026-04-10 06:09:47', '2026-04-10 06:09:47'),
	(6, 'Komik', 'Buku tentang Komik', '2026-04-10 06:10:14', '2026-04-10 06:10:14'),
	(7, 'Buku Pelajaran', 'Buku tentang Buku Pelajaran', '2026-04-10 06:10:44', '2026-04-10 06:10:44'),
	(8, 'Novel', 'Buku tentang Novel', '2026-04-10 06:11:12', '2026-04-10 06:11:12');

-- Dumping structure for table bookstore.contacts
CREATE TABLE IF NOT EXISTS `contacts` (
  `id_contact` int NOT NULL AUTO_INCREMENT,
  `id_user` int DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `subjek` varchar(200) NOT NULL,
  `pesan` text NOT NULL,
  `status` enum('unread','read','replied') NOT NULL DEFAULT 'unread',
  `balasan_admin` text,
  `replied_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_contact`),
  KEY `id_user` (`id_user`),
  CONSTRAINT `contacts_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table bookstore.contacts: ~0 rows (approximately)
INSERT INTO `contacts` (`id_contact`, `id_user`, `name`, `email`, `subjek`, `pesan`, `status`, `balasan_admin`, `replied_at`, `created_at`) VALUES
	(1, 2, 'ibnu', 'ibnu@gmail.com', 'pesan', 'semanngat', 'replied', 'siap', '2026-04-10 06:44:50', '2026-04-11 03:44:27');

-- Dumping structure for table bookstore.orders
CREATE TABLE IF NOT EXISTS `orders` (
  `id_order` int NOT NULL AUTO_INCREMENT,
  `id_user` int NOT NULL,
  `total_amount` decimal(15,0) NOT NULL,
  `payment_method` enum('cod','transfer') NOT NULL,
  `order_status` enum('pending','pending_payment','processing','shipping','delivered','completed','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'pending',
  `payment_status` enum('unpaid','paid','cod') NOT NULL DEFAULT 'unpaid',
  `shipping_address` text NOT NULL,
  `notes` text,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id_order`),
  KEY `id_user` (`id_user`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table bookstore.orders: ~0 rows (approximately)
INSERT INTO `orders` (`id_order`, `id_user`, `total_amount`, `payment_method`, `order_status`, `payment_status`, `shipping_address`, `notes`, `created_at`, `updated_at`) VALUES
	(1, 2, 50000, 'cod', 'completed', 'cod', 'ciplak', 'dibungkus rapih', '2026-04-10 07:44:37', NULL),
	(2, 2, 120000, 'cod', 'pending', 'cod', 'smkn 65', 'dibungkus rapih', '2026-04-10 14:09:15', NULL);

-- Dumping structure for table bookstore.order_items
CREATE TABLE IF NOT EXISTS `order_items` (
  `id_order_item` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `id_buku` int NOT NULL,
  `quantity` int NOT NULL,
  `price` decimal(15,0) NOT NULL,
  PRIMARY KEY (`id_order_item`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table bookstore.order_items: ~0 rows (approximately)
INSERT INTO `order_items` (`id_order_item`, `order_id`, `id_buku`, `quantity`, `price`) VALUES
	(1, 1, 10, 1, 50000),
	(2, 2, 9, 1, 120000);

-- Dumping structure for table bookstore.users
CREATE TABLE IF NOT EXISTS `users` (
  `id_user` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `no_telepon` varchar(20) DEFAULT NULL,
  `alamat` text,
  `phone` varchar(20) DEFAULT NULL,
  `address` text,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `foto_profil` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_user`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table bookstore.users: ~2 rows (approximately)
INSERT INTO `users` (`id_user`, `username`, `email`, `no_telepon`, `alamat`, `phone`, `address`, `password`, `nama_lengkap`, `role`, `created_at`, `updated_at`, `foto_profil`) VALUES
	(1, 'admin', 'admin@bookstore.com', NULL, NULL, NULL, NULL, '$2a$12$gr4qjSeoAC.1im1nJjyYIOU30DZ9w/uJzpEP06tsWY2HLLKp0qi5S', 'Admin', 'admin', '2026-04-10 06:03:54', '2026-04-10 06:03:54', NULL),
	(2, 'ibnuu', 'ibnu@gmail.com', NULL, NULL, '081513828556', 'smkn 65', '$2y$10$KIaguuKSCxFEFr0GS/ydQu.Uj2AEPDLrv2lQ6iFUgSEzWx4q02NWy', 'ibnufaqih', 'user', '2026-04-10 06:29:09', '2026-04-10 06:29:09', NULL);

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;

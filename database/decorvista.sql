-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 08, 2025 at 03:20 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `decorvista`
--

-- --------------------------------------------------------

--
-- Table structure for table `blog_posts`
--

CREATE TABLE `blog_posts` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `author` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `blog_posts`
--

INSERT INTO `blog_posts` (`id`, `title`, `content`, `category`, `image_url`, `author`, `created_at`) VALUES
(1, 'Top 10 Living Room Designs', 'Learn how to style your living room...', 'interior-design', '/uploads/living-room.jpg', 'Admin', '2025-09-07 19:14:40'),
(2, 'Kitchen Remodeling Tips', 'Maximize your kitchen space...', 'kitchen', '/uploads/kitchen.jpg', 'Admin', '2025-09-07 19:14:40'),
(3, 'Transform Your Living Room: Modern Interior Design Ideas for 2025', 'The living room is the heart of your home, a place to relax, entertain guests, and showcase your personal style. As we step into 2025, modern interior design trends are embracing a mix of minimalism, bold textures, and sustainable choices.\r\n\r\n### Minimalist Furniture\r\nLess is more in modern interiors. Sleek, low-profile sofas and multifunctional furniture pieces create an open, airy space. Neutral tones like beige, gray, and soft pastels make the room feel calm and inviting.\r\n\r\n### Sustainable Materials\r\nEco-friendly and sustainable materials are trending. Furniture made from recycled wood, bamboo flooring, and organic fabrics bring a natural warmth while reducing environmental impact.\r\n\r\n### Bold Accent Walls\r\nAdd personality with a bold accent wall. Deep green, navy blue, or terracotta adds depth and sophistication. Pair with neutral furniture to maintain balance.\r\n\r\n### Statement Lighting\r\nLighting is now a centerpiece. Choose chandeliers, geometric pendants, or layered lighting setups. Adjustable LED lights let you customize mood for different occasions.\r\n\r\n### Cozy Textures\r\nSoft throws, plush rugs, and textured cushions add warmth and visual interest, making your living room stylish and comfortable.\r\n\r\n### Open Shelving & Art Displays\r\nOpen shelving provides both functionality and decoration. Showcase books, plants, and art pieces. Gallery-style wall arrangements give a curated feel.\r\n\r\n### Smart Technology Integration\r\nSubtle smart home tech like automated curtains, smart lights, and integrated sound systems enhances convenience while maintaining a clean aesthetic.\r\n\r\n### Conclusion\r\nModern living rooms in 2025 balance minimalism and bold statement pieces, natural materials and smart technology. Careful selection of furniture, colors, and accessories creates a functional and visually stunning space.', 'interior-design', '/uploads/living-room-modern-2025.jpg', 'Admin', '2025-09-07 19:22:09');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `cart_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `name`, `description`, `image`, `is_active`, `created_at`) VALUES
(1, 'Furniture', 'Chairs, tables, sofas, and other furniture items', '/images/categories/furniture.jpg', 1, '2025-09-06 16:00:03'),
(2, 'Lighting', 'Lamps, chandeliers, and lighting fixtures', '/images/categories/lighting.jpg', 1, '2025-09-06 16:00:03'),
(3, 'Decor', 'Decorative items and accessories', '/images/categories/decor.jpg', 1, '2025-09-06 16:00:03'),
(4, 'Rugs & Carpets', 'Floor coverings and area rugs', '/images/categories/rugs.jpg', 1, '2025-09-06 16:00:03'),
(5, 'Wall Art', 'Paintings, prints, and wall decorations', '/images/categories/wall-art.jpg', 1, '2025-09-06 16:00:03'),
(6, 'Curtains & Blinds', 'Window treatments and coverings', '/images/categories/curtains.jpg', 1, '2025-09-06 16:00:03');

-- --------------------------------------------------------

--
-- Table structure for table `consultations`
--

CREATE TABLE `consultations` (
  `consultation_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `designer_id` int(11) NOT NULL,
  `scheduled_date` date NOT NULL,
  `scheduled_time` time NOT NULL,
  `duration_hours` int(11) DEFAULT 1,
  `status` enum('pending','confirmed','completed','cancelled') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `client_requirements` text DEFAULT NULL,
  `total_cost` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `consultations`
--

INSERT INTO `consultations` (`consultation_id`, `user_id`, `designer_id`, `scheduled_date`, `scheduled_time`, `duration_hours`, `status`, `notes`, `client_requirements`, `total_cost`, `created_at`, `updated_at`) VALUES
(22, 3, 1, '2025-09-10', '10:30:00', 2, 'confirmed', 'Client wants modern living room ideas.', 'Minimalist design with neutral colors.', 150.00, '2025-09-06 19:22:25', '2025-09-08 01:28:24'),
(23, 3, 1, '2025-09-12', '14:00:00', 1, 'confirmed', 'Kitchen remodeling discussion.', 'Space optimization and modular kitchen.', 200.00, '2025-09-06 19:22:25', '2025-09-06 19:22:25'),
(24, 3, 1, '2025-09-14', '16:00:00', 3, 'completed', 'Bedroom renovation.', 'Luxury theme with wooden textures.', 300.00, '2025-09-06 19:22:25', '2025-09-06 19:22:25'),
(25, 3, 1, '2025-09-18', '11:00:00', 2, 'cancelled', 'xcvxcvcxv', 'in-person', 150.00, '2025-09-07 23:38:16', '2025-09-08 01:28:49'),
(26, 3, 1, '2025-09-24', '13:00:00', 1, 'cancelled', 'hjk', 'in-person', 75.00, '2025-09-07 23:39:01', '2025-09-07 23:40:07'),
(27, 3, 1, '2025-09-29', '09:00:00', 1, 'cancelled', 'hjk', 'online', 75.00, '2025-09-07 23:41:06', '2025-09-07 23:41:26'),
(28, 3, 1, '2025-10-01', '11:00:00', 2, 'completed', 'ghjghjghj', 'in-person', 150.00, '2025-09-07 23:43:55', '2025-09-08 12:22:03'),
(29, 3, 1, '2025-10-03', '11:00:00', 2, 'cancelled', 'ghjghjghj', 'in-person', 150.00, '2025-09-07 23:45:24', '2025-09-08 12:22:08'),
(30, 3, 1, '2025-09-25', '15:00:00', 2, 'confirmed', 'sfsdfsdfsd', 'in-person', 150.00, '2025-09-08 00:35:55', '2025-09-08 01:28:58');

-- --------------------------------------------------------

--
-- Table structure for table `designer_availability`
--

CREATE TABLE `designer_availability` (
  `availability_id` int(11) NOT NULL,
  `designer_id` int(11) NOT NULL,
  `day_of_week` enum('monday','tuesday','wednesday','thursday','friday','saturday','sunday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `designer_availability`
--

INSERT INTO `designer_availability` (`availability_id`, `designer_id`, `day_of_week`, `start_time`, `end_time`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'monday', '09:00:00', '17:00:00', 1, '2025-09-07 23:27:55', '2025-09-07 23:27:55'),
(2, 6, 'tuesday', '09:00:00', '17:00:00', 1, '2025-09-07 23:27:55', '2025-09-07 23:27:55'),
(3, 8, 'wednesday', '09:00:00', '17:00:00', 1, '2025-09-07 23:27:55', '2025-09-07 23:27:55'),
(4, 9, 'monday', '10:00:00', '16:00:00', 1, '2025-09-07 23:27:55', '2025-09-07 23:27:55');

-- --------------------------------------------------------

--
-- Table structure for table `favorites`
--

CREATE TABLE `favorites` (
  `favorite_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `gallery_id` int(11) DEFAULT NULL,
  `designer_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `favorites`
--

INSERT INTO `favorites` (`favorite_id`, `user_id`, `product_id`, `gallery_id`, `designer_id`, `created_at`) VALUES
(18, 3, NULL, 2, NULL, '2025-09-08 00:19:57');

-- --------------------------------------------------------

--
-- Table structure for table `gallery`
--

CREATE TABLE `gallery` (
  `gallery_id` int(11) NOT NULL,
  `designer_id` int(11) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `image_url` varchar(500) NOT NULL,
  `room_type` enum('living_room','bedroom','kitchen','bathroom','office','outdoor') NOT NULL,
  `style` enum('modern','traditional','minimalist','rustic','industrial','scandinavian') NOT NULL,
  `color_scheme` varchar(100) DEFAULT NULL,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `gallery`
--

INSERT INTO `gallery` (`gallery_id`, `designer_id`, `title`, `description`, `image_url`, `room_type`, `style`, `color_scheme`, `is_featured`, `is_active`, `created_at`) VALUES
(2, 1, 'Modern Living Room', 'A spacious living room with minimalist furniture and monochrome decor.', '/uploads/gallery_68becbf7606b5.jpg', 'living_room', 'modern', 'Black & White', 1, 1, '2025-09-07 19:34:15'),
(3, 9, 'Elegant Bedroom', 'Bedroom featuring sleek black furniture, white walls, and soft lighting.', '/uploads/gallery/bedroom_bw.jpg', 'bedroom', '', 'Black & White', 0, 1, '2025-09-07 19:34:15'),
(4, 8, 'Minimalist Kitchen', 'Kitchen with clean lines, black cabinets, and white countertops.', '/uploads/gallery/kitchen_bw.jpg', 'kitchen', 'minimalist', 'Black & White', 1, 1, '2025-09-07 19:34:15'),
(5, 6, 'Office Workspace', 'Home office with black desk, white walls, and minimal accessories.', '/uploads/gallery/office_bw.jpg', 'office', 'modern', 'Black & White', 0, 1, '2025-09-07 19:34:15');

-- --------------------------------------------------------

--
-- Table structure for table `interior_designers`
--

CREATE TABLE `interior_designers` (
  `designer_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `years_experience` int(11) DEFAULT 0,
  `specialization` varchar(100) DEFAULT NULL,
  `portfolio_url` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `hourly_rate` decimal(10,2) DEFAULT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `rating` decimal(3,2) DEFAULT 0.00,
  `total_reviews` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `availability_status` enum('available','busy','unavailable') NOT NULL DEFAULT 'available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `interior_designers`
--

INSERT INTO `interior_designers` (`designer_id`, `user_id`, `first_name`, `last_name`, `phone`, `address`, `years_experience`, `specialization`, `portfolio_url`, `bio`, `hourly_rate`, `is_verified`, `rating`, `total_reviews`, `created_at`, `updated_at`, `availability_status`) VALUES
(1, 2, 'John', 'Designer', '+1234567891', NULL, 0, 'Modern Interior Design', NULL, 'Experienced interior designer specializing in modern and contemporary spaces.', 75.00, 1, 4.80, 0, '2025-09-06 16:00:03', '2025-09-06 16:00:03', 'available'),
(5, 1, 'John', 'Doe', NULL, NULL, 0, 'Modern Interiors', NULL, NULL, NULL, 1, 4.50, 12, '2025-09-06 19:19:18', '2025-09-06 19:19:18', 'available'),
(6, 2, 'Sarah', 'Smith', NULL, NULL, 0, 'Luxury Homes', NULL, NULL, NULL, 1, 4.80, 20, '2025-09-06 19:19:18', '2025-09-06 19:19:18', 'available'),
(7, 3, 'Michael', 'Brown', NULL, NULL, 0, 'Minimalist Design', NULL, NULL, NULL, 0, 4.20, 8, '2025-09-06 19:19:18', '2025-09-06 19:19:18', 'available'),
(8, 5, '', '', NULL, NULL, 0, NULL, NULL, 'Professional interior designer ready to transform your space.', NULL, 0, 0.00, 0, '2025-09-07 19:52:58', '2025-09-07 19:52:58', 'available'),
(9, 7, '', '', NULL, NULL, 0, NULL, NULL, 'Professional interior designer ready to transform your space.', NULL, 0, 0.00, 0, '2025-09-07 20:27:14', '2025-09-07 20:27:14', 'available');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `total_price` decimal(10,2) NOT NULL,
  `status` enum('pending','processing','completed','cancelled') NOT NULL DEFAULT 'pending',
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `user_id`, `product_id`, `quantity`, `total_price`, `status`, `order_date`, `updated_at`) VALUES
(1, 3, 1, 2, 999.98, 'pending', '2025-09-06 20:01:05', '2025-09-06 20:01:05'),
(2, 2, 2, 1, 899.00, 'completed', '2025-09-06 20:01:05', '2025-09-06 20:01:05'),
(3, 3, 3, 1, 250.00, 'processing', '2025-09-06 20:01:05', '2025-09-06 20:01:05'),
(4, 3, 1, 1, 499.99, 'pending', '2025-09-07 22:54:36', '2025-09-07 22:54:36');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `category_id` int(11) NOT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `dimensions` varchar(100) DEFAULT NULL,
  `materials` varchar(200) DEFAULT NULL,
  `stock_quantity` int(11) DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `purchase_url` varchar(500) DEFAULT NULL,
  `rating` decimal(3,2) DEFAULT 0.00,
  `total_reviews` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `name`, `description`, `price`, `category_id`, `brand`, `image`, `dimensions`, `materials`, `stock_quantity`, `is_active`, `purchase_url`, `rating`, `total_reviews`, `created_at`, `updated_at`) VALUES
(1, 'Modern Sofa', 'Comfortable 3-seater sofa with a sleek modern design.', 499.99, 1, 'SofaCo', '/images/products/sofa.jpg', '200x90x85 cm', 'Fabric, Wood', 9, 1, 'https://example.com/modern-sofa', 0.00, 0, '2025-09-06 19:55:30', '2025-09-07 22:54:36'),
(2, 'Crystal Chandelier', 'Elegant chandelier with crystal accents, perfect for dining rooms.', 899.00, 2, 'BrightHome', '/images/products/chandelier.jpg', '80x80x100 cm', 'Crystal, Metal', 5, 1, 'https://example.com/crystal-chandelier', 0.00, 0, '2025-09-06 19:55:30', '2025-09-06 19:55:30'),
(3, 'Abstract Wall Art', 'Large abstract painting to enhance your living space.', 250.00, 5, 'Artify', '/images/products/wallart.jpg', '120x90 cm', 'Canvas, Oil Paint', 15, 1, 'https://example.com/abstract-wall-art', 0.00, 0, '2025-09-06 19:55:30', '2025-09-06 19:55:30'),
(7, 'NewProduct', 'ads', 123.00, 3, 'No Brand', 'prod_68bec97f05a98.png', '12x12', '0', 111, 1, NULL, 0.00, 0, '2025-09-08 12:18:07', '2025-09-08 12:18:07');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `designer_id` int(11) DEFAULT NULL,
  `consultation_id` int(11) DEFAULT NULL,
  `rating` int(1) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `comment` text DEFAULT NULL,
  `is_approved` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`review_id`, `user_id`, `product_id`, `designer_id`, `consultation_id`, `rating`, `comment`, `is_approved`, `created_at`) VALUES
(5, 3, 1, NULL, NULL, 5, 'Absolutely love this sofa, very comfortable and stylish!', 0, '2025-09-06 19:55:30'),
(6, 2, 2, NULL, NULL, 4, 'Looks beautiful in my dining room, though installation took time.', 0, '2025-09-06 19:55:30'),
(7, 3, NULL, 1, NULL, 4, 'John helped me design my living room, very satisfied with his work.', 0, '2025-09-06 19:55:30'),
(8, 3, 3, NULL, NULL, 5, 'The wall art added character to my living room — great quality!', 1, '2025-09-06 19:55:30'),
(9, 1, 7, NULL, NULL, 4, 'asdasdas', 0, '2025-09-08 13:10:47'),
(10, 1, 7, NULL, NULL, 2, 'asdfsdfdsf', 0, '2025-09-08 13:11:09'),
(11, 1, 7, NULL, NULL, 4, 'sdfsd', 0, '2025-09-08 13:11:15');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('homeowner','designer','admin') NOT NULL DEFAULT 'homeowner',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password`, `role`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@decorvista.com', '$2y$10$8cicdsQl1vP8nuHJ0CS.d.AcYM5rHcwuZXTrzOlSW7ZSFjtdUoZLG', 'admin', 1, '2025-09-06 16:00:03', '2025-09-07 22:00:36'),
(2, 'john_designer', 'john@decorvista.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'designer', 1, '2025-09-06 16:00:03', '2025-09-06 16:00:03'),
(3, 'jane_homeowner', 'jane@decorvista.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'homeowner', 1, '2025-09-06 16:00:03', '2025-09-06 16:00:03'),
(5, 'Syed_Ahmed444', 'aginvestments014@gmail.com', '$2y$10$.KJ80FHoBHXDMFjskYYWV.E7M0APYU3ZjdRUr5J5r5P05S91uiRi.', 'designer', 1, '2025-09-07 19:52:58', '2025-09-07 19:52:58'),
(6, 'ahmed', 'khanwaseem872@gmail.com', '$2y$10$KO2Vw2LKjSkmVDgarJc2he8pqnGwI0IcVTF.U9Ql81v9PcS2auXbS', 'homeowner', 1, '2025-09-07 19:56:05', '2025-09-07 19:56:05'),
(7, 'AHAPRAX', 'aptech356@gmail.com', '$2y$10$.KpOLPXdcRRZpDZ3ZAY3MeH9FzPSfzcGW5lTM8ytEuyXWwM6NzLC.', 'designer', 1, '2025-09-07 20:27:14', '2025-09-07 20:27:14');

-- --------------------------------------------------------

--
-- Table structure for table `user_details`
--

CREATE TABLE `user_details` (
  `detail_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_details`
--

INSERT INTO `user_details` (`detail_id`, `user_id`, `first_name`, `last_name`, `phone`, `address`, `profile_image`, `created_at`, `updated_at`) VALUES
(1, 1, 'Admin', 'User', '+1234567890', '123 Admin Street', NULL, '2025-09-06 16:00:03', '2025-09-06 16:00:03'),
(2, 2, 'John', 'Designer', '+1234567891', '456 Design Avenue', NULL, '2025-09-06 16:00:03', '2025-09-06 16:00:03'),
(3, 3, 'Janes', 'Homeowner', '+1234567892', '789 Home Lane', NULL, '2025-09-06 16:00:03', '2025-09-08 00:31:54'),
(4, 5, 'Mushtaq', 'khan', '03111093223', NULL, NULL, '2025-09-07 19:52:58', '2025-09-07 19:52:58'),
(5, 6, 'Rukhsanaaa', 'shafiq', '03111093224', NULL, NULL, '2025-09-07 19:56:05', '2025-09-07 20:07:43'),
(6, 7, 'Ahmeddd', 'Hussain', '3333333333', NULL, NULL, '2025-09-07 20:27:14', '2025-09-07 21:05:02');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `blog_posts`
--
ALTER TABLE `blog_posts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`cart_id`),
  ADD UNIQUE KEY `unique_user_product` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`),
  ADD KEY `idx_categories_active` (`is_active`);

--
-- Indexes for table `consultations`
--
ALTER TABLE `consultations`
  ADD PRIMARY KEY (`consultation_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `designer_id` (`designer_id`),
  ADD KEY `idx_consultations_status` (`status`),
  ADD KEY `idx_consultations_date` (`scheduled_date`);

--
-- Indexes for table `designer_availability`
--
ALTER TABLE `designer_availability`
  ADD PRIMARY KEY (`availability_id`),
  ADD KEY `designer_id` (`designer_id`);

--
-- Indexes for table `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`favorite_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `gallery_id` (`gallery_id`),
  ADD KEY `designer_id` (`designer_id`);

--
-- Indexes for table `gallery`
--
ALTER TABLE `gallery`
  ADD PRIMARY KEY (`gallery_id`),
  ADD KEY `idx_gallery_room_type` (`room_type`),
  ADD KEY `idx_gallery_style` (`style`),
  ADD KEY `idx_gallery_featured` (`is_featured`),
  ADD KEY `designer_id` (`designer_id`);

--
-- Indexes for table `interior_designers`
--
ALTER TABLE `interior_designers`
  ADD PRIMARY KEY (`designer_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_designers_verified` (`is_verified`),
  ADD KEY `idx_designers_rating` (`rating`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `fk_orders_user` (`user_id`),
  ADD KEY `fk_orders_product` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `idx_products_active` (`is_active`),
  ADD KEY `idx_products_rating` (`rating`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `designer_id` (`designer_id`),
  ADD KEY `consultation_id` (`consultation_id`),
  ADD KEY `idx_reviews_approved` (`is_approved`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_email` (`email`),
  ADD KEY `idx_users_role` (`role`);

--
-- Indexes for table `user_details`
--
ALTER TABLE `user_details`
  ADD PRIMARY KEY (`detail_id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `blog_posts`
--
ALTER TABLE `blog_posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `consultations`
--
ALTER TABLE `consultations`
  MODIFY `consultation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `designer_availability`
--
ALTER TABLE `designer_availability`
  MODIFY `availability_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `favorites`
--
ALTER TABLE `favorites`
  MODIFY `favorite_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `gallery`
--
ALTER TABLE `gallery`
  MODIFY `gallery_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `interior_designers`
--
ALTER TABLE `interior_designers`
  MODIFY `designer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `user_details`
--
ALTER TABLE `user_details`
  MODIFY `detail_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `consultations`
--
ALTER TABLE `consultations`
  ADD CONSTRAINT `consultations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `consultations_ibfk_2` FOREIGN KEY (`designer_id`) REFERENCES `interior_designers` (`designer_id`) ON DELETE CASCADE;

--
-- Constraints for table `designer_availability`
--
ALTER TABLE `designer_availability`
  ADD CONSTRAINT `designer_availability_ibfk_1` FOREIGN KEY (`designer_id`) REFERENCES `interior_designers` (`designer_id`) ON DELETE CASCADE;

--
-- Constraints for table `favorites`
--
ALTER TABLE `favorites`
  ADD CONSTRAINT `favorites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `favorites_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `favorites_ibfk_3` FOREIGN KEY (`gallery_id`) REFERENCES `gallery` (`gallery_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `favorites_ibfk_4` FOREIGN KEY (`designer_id`) REFERENCES `interior_designers` (`designer_id`) ON DELETE CASCADE;

--
-- Constraints for table `gallery`
--
ALTER TABLE `gallery`
  ADD CONSTRAINT `gallery_ibfk_1` FOREIGN KEY (`designer_id`) REFERENCES `interior_designers` (`designer_id`) ON DELETE CASCADE;

--
-- Constraints for table `interior_designers`
--
ALTER TABLE `interior_designers`
  ADD CONSTRAINT `interior_designers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`);

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`designer_id`) REFERENCES `interior_designers` (`designer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_4` FOREIGN KEY (`consultation_id`) REFERENCES `consultations` (`consultation_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_details`
--
ALTER TABLE `user_details`
  ADD CONSTRAINT `user_details_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 24, 2026 at 03:34 PM
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
-- Database: `pixelpod_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(10) UNSIGNED NOT NULL,
  `booking_ref` varchar(20) NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `service_id` int(10) UNSIGNED NOT NULL,
  `event_name` varchar(200) NOT NULL,
  `event_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `venue` varchar(255) DEFAULT NULL,
  `guest_count` smallint(5) UNSIGNED DEFAULT 1,
  `special_requests` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','cancelled','completed') NOT NULL DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `photo_sizes` text DEFAULT NULL COMMENT 'JSON array of chosen size IDs',
  `layout_id` int(10) UNSIGNED DEFAULT NULL,
  `filter_id` int(10) UNSIGNED DEFAULT NULL,
  `design_id` int(10) UNSIGNED DEFAULT NULL,
  `addon_total` decimal(8,2) NOT NULL DEFAULT 0.00 COMMENT 'Extra from photo sizes'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `booking_ref`, `user_id`, `service_id`, `event_name`, `event_date`, `start_time`, `end_time`, `venue`, `guest_count`, `special_requests`, `status`, `admin_notes`, `created_at`, `photo_sizes`, `layout_id`, `filter_id`, `design_id`, `addon_total`) VALUES
(7, 'PPB-2026-0001', 13, 4, 'Birthday', '2026-05-29', '07:00:00', '17:00:00', 'Southwoods', 40, '', 'pending', NULL, '2026-05-18 01:40:01', NULL, NULL, NULL, NULL, 0.00),
(8, 'PPB-2026-0002', 11, 2, 'Anniversay Day', '2026-06-08', '13:00:00', '17:00:00', 'Splash Island', 10, 'Add one boquet and add music band', 'pending', NULL, '2026-05-18 01:42:11', NULL, NULL, NULL, NULL, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `designs`
--

CREATE TABLE `designs` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `category` varchar(50) DEFAULT 'general',
  `sample_image` varchar(500) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` tinyint(4) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `designs`
--

INSERT INTO `designs` (`id`, `name`, `description`, `category`, `sample_image`, `is_active`, `sort_order`, `created_at`) VALUES
(1, 'Minimalist', 'Clean white border — lets your photos do all the talking.', 'general', 'https://images.unsplash.com/photo-1519741497674-611481863552?w=400&q=80', 1, 1, '2026-05-15 07:20:52'),
(2, 'Elegant Gold', 'Luxurious gold foil-style frame. Perfect for weddings and debuts.', 'wedding', 'https://images.unsplash.com/photo-1516450360452-9312f5e86fc7?w=400&q=80', 1, 2, '2026-05-15 07:20:52'),
(3, 'Floral Bloom', 'Soft pastel flowers around the border — romantic and beautiful.', 'wedding', 'https://images.unsplash.com/photo-1530103862676-de8c9debad1d?w=400&q=80', 1, 3, '2026-05-15 07:20:52'),
(4, 'Festive Burst', 'Colorful confetti and balloons — made for birthday parties!', 'birthday', 'https://images.unsplash.com/photo-1607434472257-d9f8e57a643d?w=400&q=80', 1, 4, '2026-05-15 07:20:52'),
(5, 'Corporate Sleek', 'Professional dark border with subtle branding space.', 'corporate', 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=400&q=80', 1, 5, '2026-05-15 07:20:52'),
(6, 'Neon Glow', 'Electric neon glowing borders — great for night events and parties.', 'birthday', 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?w=400&q=80', 1, 6, '2026-05-15 07:20:52');

-- --------------------------------------------------------

--
-- Table structure for table `filters`
--

CREATE TABLE `filters` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `sample_image` varchar(500) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` tinyint(4) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `filters`
--

INSERT INTO `filters` (`id`, `name`, `description`, `sample_image`, `is_active`, `sort_order`, `created_at`) VALUES
(1, 'Natural', 'No filter — your true colors shine through.', 'https://images.unsplash.com/photo-1519741497674-611481863552?w=400&q=80', 1, 1, '2026-05-15 07:20:52'),
(2, 'Black & White', 'Timeless classic monochrome look.', 'https://images.unsplash.com/photo-1516450360452-9312f5e86fc7?w=400&q=80', 1, 2, '2026-05-15 07:20:52'),
(3, 'Vintage', 'Warm sepia tones with a retro 70s feel.', 'https://images.unsplash.com/photo-1530103862676-de8c9debad1d?w=400&q=80', 1, 3, '2026-05-15 07:20:52'),
(4, 'Glam', 'Bright, smooth, and flattering — perfect for debut and weddings.', 'https://images.unsplash.com/photo-1607434472257-d9f8e57a643d?w=400&q=80', 1, 4, '2026-05-15 07:20:52'),
(5, 'Film Grain', 'Authentic grainy film camera look for an artistic vibe.', 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=400&q=80', 1, 5, '2026-05-15 07:20:52'),
(6, 'Vivid Pop', 'Boosted colors that make everything look extra bright and fun!', 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?w=400&q=80', 1, 6, '2026-05-15 07:20:52');

-- --------------------------------------------------------

--
-- Table structure for table `inquiries`
--

CREATE TABLE `inquiries` (
  `id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(200) NOT NULL,
  `email` varchar(191) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `layouts`
--

CREATE TABLE `layouts` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `photos_count` tinyint(4) DEFAULT 3,
  `sample_image` varchar(500) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` tinyint(4) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `layouts`
--

INSERT INTO `layouts` (`id`, `name`, `description`, `photos_count`, `sample_image`, `is_active`, `sort_order`, `created_at`) VALUES
(1, 'Classic Strip', 'Three photos stacked vertically — the timeless photobooth look.', 3, 'https://images.unsplash.com/photo-1609234563539-ad88d66a4b0e?w=400&q=80', 1, 1, '2026-05-15 07:20:52'),
(2, 'Grid 2x2', 'Four photos arranged in a neat 2x2 grid. Fun for groups!', 4, 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=400&q=80', 1, 2, '2026-05-15 07:20:52'),
(3, 'Single Shot', 'One large, stunning photo that takes up the whole print.', 1, 'https://images.unsplash.com/photo-1516450360452-9312f5e86fc7?w=400&q=80', 1, 3, '2026-05-15 07:20:52'),
(4, 'Collage 3+1', 'Three small shots on the left, one big shot on the right.', 4, 'https://images.unsplash.com/photo-1530103862676-de8c9debad1d?w=400&q=80', 1, 4, '2026-05-15 07:20:52'),
(5, 'Wide Panorama', 'Two wide landscape photos stacked — great for large group shots.', 2, 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?w=400&q=80', 1, 5, '2026-05-15 07:20:52'),
(6, 'Passport Style', 'Six small passport-sized photos on one print.', 6, 'https://images.unsplash.com/photo-1519741497674-611481863552?w=400&q=80', 1, 6, '2026-05-15 07:20:52');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(10) UNSIGNED NOT NULL,
  `booking_id` int(10) UNSIGNED NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('gcash','maya','cod') DEFAULT 'gcash',
  `payment_status` enum('pending','partial','paid','refunded') NOT NULL DEFAULT 'pending',
  `reference_number` varchar(100) DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `booking_id`, `amount`, `payment_method`, `payment_status`, `reference_number`, `paid_at`, `notes`, `created_at`) VALUES
(7, 7, 8000.00, 'gcash', 'pending', NULL, NULL, NULL, '2026-05-18 01:40:01'),
(8, 8, 4500.00, 'maya', 'pending', NULL, NULL, NULL, '2026-05-18 01:42:11');

-- --------------------------------------------------------

--
-- Table structure for table `photo_sessions`
--

CREATE TABLE `photo_sessions` (
  `id` int(10) UNSIGNED NOT NULL,
  `session_code` varchar(20) NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `layout` enum('strip','grid') NOT NULL DEFAULT 'strip',
  `filter_name` varchar(50) DEFAULT 'natural',
  `photos` text NOT NULL,
  `qr_code` varchar(500) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `photo_sizes`
--

CREATE TABLE `photo_sizes` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `dimensions` varchar(50) DEFAULT NULL,
  `addon_price` decimal(8,2) NOT NULL DEFAULT 0.00,
  `sample_image` varchar(500) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` tinyint(4) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `photo_sizes`
--

INSERT INTO `photo_sizes` (`id`, `name`, `description`, `dimensions`, `addon_price`, `sample_image`, `is_active`, `sort_order`, `created_at`) VALUES
(1, '2x6 Strip', 'Classic long photo strip — 3 shots in a row. The most popular!', '2', 0.00, 'https://images.unsplash.com/photo-1578736641330-3155e606cd40?w=600&q=80', 1, 1, '2026-05-15 07:20:52'),
(2, '4x6 Print', 'Standard postcard-size print. Great for framing.', '4\" × 6\"', 200.00, 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=400&q=80', 1, 2, '2026-05-15 07:20:52'),
(3, '5x7 Print', 'Medium size — perfect for displaying at home.', '5\" × 7\"', 300.00, 'https://images.unsplash.com/photo-1519741497674-611481863552?w=400&q=80', 1, 3, '2026-05-15 07:20:52'),
(4, 'Wallet Size', 'Small 2x2 prints — great for giveaways and souvenirs.', '2\" × 2\"', 150.00, 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?w=400&q=80', 1, 4, '2026-05-15 07:20:52'),
(5, 'Digital Only', 'No physical print — just high-res digital copies via QR code.', 'Digital', 0.00, 'https://images.unsplash.com/photo-1607434472257-d9f8e57a643d?w=400&q=80', 1, 5, '2026-05-15 07:20:52');

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `duration_hours` tinyint(3) UNSIGNED NOT NULL DEFAULT 2,
  `max_guests` smallint(5) UNSIGNED DEFAULT NULL,
  `image` varchar(500) DEFAULT NULL,
  `image_type` enum('upload','url') DEFAULT 'url',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `name`, `description`, `price`, `duration_hours`, `max_guests`, `image`, `image_type`, `is_active`, `created_at`) VALUES
(1, 'Mini Pod', 'Compact budget-friendly option perfect for small spaces and intimate celebrations. Includes basic props and digital copies.', 3500.00, 2, 20, 'https://images.unsplash.com/photo-1530103862676-de8c9debad1d?w=600&q=80', 'url', 1, '2026-05-15 06:25:06'),
(2, 'Classic Booth', 'Our signature open-air photobooth with standard backdrop, unlimited 2x6 prints, and a dedicated attendant. Perfect for birthdays.', 4500.00, 2, 40, 'https://images.unsplash.com/photo-1516450360452-9312f5e86fc7?w=600&q=80', 'url', 1, '2026-05-15 06:25:06'),
(3, 'Roving Booth', 'Our roving photographer-style booth that moves through your event capturing candid moments with instant on-site printing.', 6000.00, 3, 150, 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=600&q=80', 'url', 1, '2026-05-15 06:25:06'),
(4, 'Premium Package', 'Upgraded experience with LED ring lights, premium backdrops, digital gallery, and 2 dedicated attendants. Ideal for debuts and weddings.', 8000.00, 4, 80, 'https://images.unsplash.com/photo-1519741497674-611481863552?w=600&q=80', 'url', 1, '2026-05-15 06:25:06'),
(5, 'Mirror Booth', 'Full-length magic mirror with animated effects, on-screen prompts, and digital signature. An unforgettable interactive photo experience.', 9500.00, 4, 80, 'https://images.unsplash.com/photo-1607434472257-d9f8e57a643d?w=600&q=80', 'url', 1, '2026-05-15 06:25:06'),
(6, '360 Spinner Booth', 'Immersive 360-degree slow-motion video booth. Guests get a cinematic clip sent directly to their phones. The ultimate event experience.', 12000.00, 3, 100, 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?w=600&q=80', 'url', 1, '2026-05-15 06:25:06');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(60) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(191) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('admin','customer') NOT NULL DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `first_name`, `last_name`, `email`, `password`, `phone`, `role`, `created_at`) VALUES
(10, 'pixelpod', 'Pixel', 'Pod', 'pixelpod.ph@gmail.com', '$2y$12$/sJP.znrwkT0vA6yDTQkaeWroVNUB/ngrc3Nr061KocPGA.nkzhDe', '', 'admin', '2026-05-15 06:29:30'),
(11, 'bryansacueza', 'Bryan', 'Sacueza', 'bry.sacueza@gmail.com', '$2y$12$vCMVE7rCMDhW/RgYd8e.EuWWmS6tyQef3hv4QlCOjY466PjpG4QxK', '09914629549', 'customer', '2026-05-15 06:30:43'),
(12, 'admin1', 'admin1', 'admin1', '', '$2y$12$8RPSdEPpylqcIubLZUGOKuE/7AvketFLXOlIHGikRDk2jrg0sS/LS', '', 'admin', '2026-05-15 06:58:36'),
(13, 'MarkSantos', 'Mark', 'Santos', '', '$2y$12$Ryg2ZJ11y7zF2gOJK7hCKuygQ28WZIZUATK4IFsQk50lRlmZBqYOK', '', 'customer', '2026-05-18 01:33:30');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `booking_ref` (`booking_ref`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `designs`
--
ALTER TABLE `designs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `filters`
--
ALTER TABLE `filters`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inquiries`
--
ALTER TABLE `inquiries`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `layouts`
--
ALTER TABLE `layouts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Indexes for table `photo_sessions`
--
ALTER TABLE `photo_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_code` (`session_code`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `photo_sizes`
--
ALTER TABLE `photo_sizes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `designs`
--
ALTER TABLE `designs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `filters`
--
ALTER TABLE `filters`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `inquiries`
--
ALTER TABLE `inquiries`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `layouts`
--
ALTER TABLE `layouts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `photo_sessions`
--
ALTER TABLE `photo_sessions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `photo_sizes`
--
ALTER TABLE `photo_sizes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `photo_sessions`
--
ALTER TABLE `photo_sessions`
  ADD CONSTRAINT `photo_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

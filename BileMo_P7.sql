-- phpMyAdmin SQL Dump
-- version 4.9.7
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Generation Time: Jun 07, 2023 at 06:27 PM
-- Server version: 5.7.32
-- PHP Version: 7.4.12

SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `BileMo_P7`
--

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `id` int(11) NOT NULL,
  `email` varchar(180) NOT NULL,
  `roles` json NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)'
) ;

--
-- Dumping data for table `clients`
--

INSERT INTO `clients` (`id`, `email`, `roles`, `password`, `name`, `created_at`) VALUES
(22, 'jose71@schamberger.com', '[\"ROLE_CLIENT\"]', '$2y$13$n4N2ur34mtTPYU121LZbTOhGMP7EyUwBekWLz3oyXSFoG1bx0Adja', 'Matteo Gibson', '2023-01-16 16:57:41'),
(23, 'harvey57@hotmail.com', '[\"ROLE_CLIENT\"]', '$2y$13$VXZx1WPtjL.DzGQhYBS.peI0dha//I.e2I3vCLVnXr/6Xo3NCykui', 'Brenda Hills', '2023-01-16 16:57:41'),
(24, 'maymie.stokes@yahoo.com', '[\"ROLE_CLIENT\"]', '$2y$13$yLcbcm6eSVraZyCzzmeFPeQumm5x1IVXEEVmnJl8DZk9jgtBJB7bu', 'Wanda Beer', '2023-01-16 16:57:42'),
(25, 'gilbert.senger@yahoo.com', '[\"ROLE_CLIENT\"]', '$2y$13$c7TYGVJEyX/HEiv1AEQVV.cuQwbkspepDkcgwC0uOXxn/i5/Kv5f.', 'Prof. Destiny Stanton', '2023-01-16 16:57:42'),
(26, 'lora19@gmail.com', '[\"ROLE_CLIENT\"]', '$2y$13$3iUIf2MBQ0O/VBynEq1cPuG7UXpRw.2US4aNWNrYHrXkk.Nw4UK8.', 'Dr. Benton Goyette', '2023-01-16 16:57:43'),
(27, 'admin@bilemo.com', '[\"ROLE_ADMIN\"]', '$2y$13$yGvbfeaMFESIjTds1dr3vuZEPkam4TPSaLQNoG8F1CtPATUXHckIq', 'admin', '2023-01-17 09:58:13'),
(28, 'Master-mind@company.com', '[\"ROLE_CLIENT\"]', '$2y$13$TOXvxQnUDgsqHKJzpuElKOM7nnPDiD8GTZaWTLAOwvdDyZ2wzvqcm', 'Mind Master System', '2023-01-17 10:12:30'),
(29, 'john&co@mail.com', '[\"ROLE_CLIENT\"]', '$2y$13$Z5.pX62DMzDDkzCJgXfaTOEyjb8CTnL.TnN0tTZakUTXfDANxS832', 'john & co', '2023-06-03 11:01:11');

-- --------------------------------------------------------

--
-- Table structure for table `product`
--

CREATE TABLE `product` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `brand` varchar(255) NOT NULL,
  `release_date` date NOT NULL,
  `operating_system` varchar(255) NOT NULL,
  `price` int(11) NOT NULL
) ;

--
-- Dumping data for table `product`
--

INSERT INTO `product` (`id`, `name`, `brand`, `release_date`, `operating_system`, `price`) VALUES
(144, 'Galaxy S22', 'Samsung', '2022-02-09', 'Android', 650),
(145, 'mobile nº: 1', 'Apple', '2023-01-16', 'IOS', 1500),
(146, 'mobile nº: 2', 'Apple', '2023-01-16', 'Android', 1000),
(147, 'mobile nº: 3', 'LG', '2023-01-16', 'Android', 500),
(148, 'mobile nº: 4', 'LG', '2023-01-16', 'Android', 1000),
(149, 'mobile nº: 5', 'Huawei', '2023-01-16', 'Android', 1000),
(150, 'mobile nº: 6', 'Samsung', '2023-01-16', 'Android', 500),
(151, 'mobile nº: 7', 'LG', '2023-01-16', 'IOS', 500),
(152, 'mobile nº: 8', 'Samsung', '2023-01-16', 'Android', 500),
(153, 'mobile nº: 9', 'Samsung', '2023-01-16', 'Android', 1000),
(154, 'mobile nº: 10', 'Samsung', '2023-01-16', 'Android', 500),
(155, 'mobile nº: 11', 'Samsung', '2023-01-16', 'IOS', 1500),
(156, 'mobile nº: 12', 'Huawei', '2023-01-16', 'IOS', 1500),
(157, 'mobile nº: 13', 'Samsung', '2023-01-16', 'Android', 500),
(158, 'mobile nº: 14', 'LG', '2023-01-16', 'Android', 1500),
(159, 'mobile nº: 15', 'Apple', '2023-01-16', 'IOS', 1500),
(160, 'mobile nº: 16', 'Apple', '2023-01-16', 'Android', 1500),
(161, 'mobile nº: 17', 'LG', '2023-01-16', 'IOS', 1500),
(162, 'mobile nº: 18', 'LG', '2023-01-16', 'IOS', 500),
(163, 'mobile nº: 19', 'Huawei', '2023-01-16', 'IOS', 1000);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `email` varchar(180) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `roles` json NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)'
) ;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `client_id`, `email`, `last_name`, `first_name`, `roles`, `password`, `created_at`) VALUES
(1, 26, 'update@user.com', 'User', 'update', '[\"ROLE_USER\"]', '$2y$13$FMq75q5EloldOAXlpBg6g.0wQ/fRYSKpcQiu6NEVfipvI2yFqfLvK', '2023-01-16 16:57:43'),
(2, 24, 'jabbott@gmail.com', 'Graham', 'Rowland', '[\"ROLE_USER\"]', '$2y$13$GjzYN56qRXDdtw.2lleiI.wZo0/pLdJr75wZs3plU7DRRp45VYD3K', '2023-01-16 16:57:44'),
(3, 25, 'alexane00@gmail.com', 'Monahan', 'Arlo', '[\"ROLE_USER\"]', '$2y$13$KK08MqPoPsV3Y2T8miCw7.kXVWR0YplXNMiRJp2Uj9FqJF8W.Ok6S', '2023-01-16 16:57:44'),
(4, 23, 'wprosacco@corkery.info', 'Littel', 'Raymond', '[\"ROLE_USER\"]', '$2y$13$fCxEW5CQUYR4n3SogJBwQODkoHhOxaXiBpOUp04hnnYJbtc4ezfi6', '2023-01-16 16:57:45'),
(5, 26, 'austen39@gmail.com', 'Baumbach', 'Destinee', '[\"ROLE_USER\"]', '$2y$13$pYf2mP7LDsLN2ZLgr16TJuhQJN75i7sSTfRgXMIS7N7A.fLtCEZ0u', '2023-01-16 16:57:45'),
(6, 26, 'claude.swift@gmail.com', 'Satterfield', 'Travis', '[\"ROLE_USER\"]', '$2y$13$0LusZKboXuCJwKTZHmkN3OGfm6vi28xjwOFTwq05lie8EXd.t/Uk2', '2023-01-16 16:57:46'),
(7, 22, 'ufeest@yahoo.com', 'VonRueden', 'Trisha', '[\"ROLE_USER\"]', '$2y$13$8CLopCWqXt876i8hdYVyru/1GwbB5jzDkgammVPEj7P.8vszQ3J5e', '2023-01-16 16:57:46'),
(8, 23, 'clabadie@hotmail.com', 'Huel', 'Sigmund', '[\"ROLE_USER\"]', '$2y$13$L7mXY6BIIHhV6HpI8FuLWu/L.bvoLn4wbjkgtJ5qofMe3YHAey.W6', '2023-01-16 16:57:47'),
(9, 23, 'larue.ullrich@cruickshank.com', 'Bernhard', 'Ebony', '[\"ROLE_USER\"]', '$2y$13$e.S5cvFCtvp2174jfOG12ejRxWYsYPrl6/SFzWSmTx/LwOr8HPz5m', '2023-01-16 16:57:47'),
(10, 25, 'enrico.trantow@hotmail.com', 'Funk', 'Kobe', '[\"ROLE_USER\"]', '$2y$13$4SQUnJE9sd16nNoUIQbxweNq2aPRYwqPTfNfS53AWREBzfO.G0YQi', '2023-01-16 16:57:48'),
(11, 23, 'funk.kole@mayert.com', 'Powlowski', 'Brannon', '[\"ROLE_USER\"]', '$2y$13$jRMz/NopYxthYTrQ9m4DGuXEvr02eOee9kJmaKA103JyoVo3Fx0sO', '2023-01-16 16:57:48'),
(12, 25, 'moore.renee@hotmail.com', 'Schuster', 'Henriette', '[\"ROLE_USER\"]', '$2y$13$gqTavyAftGYOvICCQR7nWOqLVBmL6YvbteVDSvzsjc4t2NQlEJf5S', '2023-01-16 16:57:49'),
(13, 23, 'oreilly.moises@bins.com', 'Schaefer', 'Stephen', '[\"ROLE_USER\"]', '$2y$13$AdcDhhoawUuguxTtHvJpD.VPq7n60jNf0SZOR0yAZbqzD36eq3QWi', '2023-01-16 16:57:49'),
(14, 24, 'terry.dulce@gmail.com', 'Windler', 'Marc', '[\"ROLE_USER\"]', '$2y$13$IvRKJEGJm2iAiIW8eKbzyOOxd2.JasPZOueM5tU7ZW5aHsLibcjDK', '2023-01-16 16:57:50'),
(15, 22, 'kuphal.porter@gmail.com', 'Stracke', 'Americo', '[\"ROLE_USER\"]', '$2y$13$wrEMhnflZox40y5tzoV2Be2KBov45PGBEaJKQZ6sEUkt8LCJ9bb8C', '2023-01-16 16:57:50'),
(16, 22, 'beier.bethany@pacocha.com', 'Kozey', 'Constance', '[\"ROLE_USER\"]', '$2y$13$IeEV30CjbSiNaycJsOj19OAaI/QjFv2yww3PdakkbdeHEZc/0eO6K', '2023-01-16 16:57:50'),
(17, 22, 'celestine.gottlieb@windler.com', 'Stoltenberg', 'Amina', '[\"ROLE_USER\"]', '$2y$13$8Y0F0qa1afgetOT7G.Y.A.i1p/TmZdEKw06DGGWQwy56sVg086/KW', '2023-01-16 16:57:51'),
(18, 24, 'marlee.hoeger@yahoo.com', 'Jacobi', 'Alberto', '[\"ROLE_USER\"]', '$2y$13$LoSbjjn5i2vE1guzqG4RJejNscNtB56iZJKgHUjNhymauptmyVXdW', '2023-01-16 16:57:51'),
(19, 22, 'delta.pagac@gmail.com', 'Bernhard', 'Demond', '[\"ROLE_USER\"]', '$2y$13$aQ.nU7wi1gUnou0BRjfIjeAXJKerPdfOcWF7J8SUzM.bDDqrzduOC', '2023-01-16 16:57:52'),
(24, 23, 'test@mail.com', 'Testing', 'Jean-michel', '[\"ROLE_USER\"]', '$2y$13$WTGnFmiDm.aIFmTkUZoqlOjoGEyy.v5yvgfM5b8zzaNIM/gsPPbC2', '2023-01-20 12:44:13');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UNIQ_C82E74E7927C74` (`email`);

--
-- Indexes for table `product`
--
ALTER TABLE `product`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UNIQ_1483A5E9E7927C74` (`email`),
  ADD KEY `IDX_1483A5E919EB6921` (`client_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product`
--
ALTER TABLE `product`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `FK_1483A5E919EB6921` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

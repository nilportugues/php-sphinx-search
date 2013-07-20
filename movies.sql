CREATE TABLE IF NOT EXISTS `movies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `year` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=7 ;


INSERT INTO `movies` (`id`, `title`, `year`) VALUES
(1, 'The Amazing Spider-Man', 2012),
(2, 'The Amazing Spider-Man 2', 2014),
(3, 'Spider-Man 2', 2004),
(4, 'Spider-Man 3', 2007),
(5, 'Spider-Man', 2002),
(6, 'Spider-Man', 1977);

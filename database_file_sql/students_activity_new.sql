-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1:3306
-- Время создания: Мар 24 2026 г., 21:05
-- Версия сервера: 8.0.24
-- Версия PHP: 8.0.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `students_activity`
--

-- --------------------------------------------------------

--
-- Структура таблицы `bonuses`
--

CREATE TABLE `bonuses` (
  `bonus_ID` int NOT NULL,
  `bonus_Name` varchar(64) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `bonuses`
--

INSERT INTO `bonuses` (`bonus_ID`, `bonus_Name`) VALUES
(2, 'Билет'),
(3, 'Встреча'),
(4, 'Игрушка'),
(1, 'Мерч');

-- --------------------------------------------------------

--
-- Структура таблицы `category`
--

CREATE TABLE `category` (
  `category_ID` int NOT NULL,
  `category_Type` varchar(64) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `category`
--

INSERT INTO `category` (`category_ID`, `category_Type`) VALUES
(1, 'IT'),
(2, 'Медия');

-- --------------------------------------------------------

--
-- Структура таблицы `coef`
--

CREATE TABLE `coef` (
  `coef_ID` int NOT NULL,
  `coef_Value` decimal(3,2) NOT NULL,
  `coef_Difficult` varchar(64) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `coef`
--

INSERT INTO `coef` (`coef_ID`, `coef_Value`, `coef_Difficult`) VALUES
(1, '1.00', 'Легко'),
(2, '1.25', 'Средне'),
(3, '1.50', 'Сложно');

-- --------------------------------------------------------

--
-- Структура таблицы `events`
--

CREATE TABLE `events` (
  `event_ID` int NOT NULL,
  `event_Organizer` int NOT NULL,
  `event_Title` varchar(256) COLLATE utf8mb4_general_ci NOT NULL,
  `event_Description` text COLLATE utf8mb4_general_ci,
  `event_Status` int NOT NULL,
  `event_Category` int NOT NULL,
  `event_DateTimeStart` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `event_DateTimeEnd` datetime NOT NULL,
  `event_Points` int NOT NULL,
  `event_Bonuses` int DEFAULT NULL,
  `event_Coef` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `events`
--

INSERT INTO `events` (`event_ID`, `event_Organizer`, `event_Title`, `event_Description`, `event_Status`, `event_Category`, `event_DateTimeStart`, `event_DateTimeEnd`, `event_Points`, `event_Bonuses`, `event_Coef`) VALUES
(1, 1, 'Хакатон \"Разум 2.0\"', 'Ты школьник или студент и увлекаешься технологиями? Программируешь, создаёшь 3D-модели или интересуешься робототехникой? Тогда «РАЗУМ 2.0» — именно для тебя!\n\n🌟 Что ждёт участников:\n— реальные кейсы от индустриальных партнёров\n— работа в команде и общение с экспертами\n— новые знания, опыт и полезные знакомства\n— призы для победителей: дополнительные баллы при поступлении, сертификат на обучение, возможность пройти практику и поработать над коммерческими проектами', 4, 1, '2026-03-23 19:43:54', '2026-03-30 00:00:00', 120, NULL, 3),
(2, 1, 'Хакатон \"Разум 3.0\"', 'Ты школьник или студент и увлекаешься технологиями? Программируешь, создаёшь 3D-модели или интересуешься робототехникой? Тогда «РАЗУМ 2.0» — именно для тебя!', 4, 1, '2026-03-24 17:20:00', '2026-03-24 13:20:00', 150, 3, 2),
(3, 1, 'Олимпиада по видеомонтажу', 'Описание', 4, 1, '2026-03-24 17:22:00', '2026-03-24 13:21:00', 200, 3, 2),
(4, 1, 'Тест', 'Тест', 4, 2, '2026-03-24 17:23:00', '2026-03-24 13:22:00', 1, 2, 1),
(5, 7, 'Test test test', '', 4, 1, '2026-03-24 20:00:00', '2026-03-25 20:00:00', 120, 2, 1),
(6, 1, 'Первая встреча', 'тут описание', 4, 1, '2026-04-20 08:00:00', '2026-04-20 09:00:00', 100, NULL, 1),
(7, 1, 'ф', '', 4, 1, '2026-03-05 20:00:00', '2026-03-10 20:00:00', 250, NULL, 2),
(8, 1, 'Тестовое мероприятие', 'мероприятие для тестирования', 2, 1, '2026-03-25 01:03:42', '2026-03-24 21:03:11', 120, 2, 1),
(9, 1, 'Тестовое мероприятие', 'мероприятие для тестирования', 2, 2, '2026-03-25 01:03:59', '2026-03-24 21:03:11', 120, 2, 1);

--
-- Триггеры `events`
--
DELIMITER $$
CREATE TRIGGER `update_organizer_events_on_event_completion` AFTER UPDATE ON `events` FOR EACH ROW BEGIN
    IF NEW.event_Status = 4 AND OLD.event_Status != 4 THEN
        UPDATE organizer_profiles
        SET organizer_events = organizer_events + 1
        WHERE organizer_ID = NEW.event_Organizer;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Структура таблицы `event_participation`
--

CREATE TABLE `event_participation` (
  `participation_ID` int NOT NULL,
  `participant_ID` int NOT NULL,
  `event_ID` int NOT NULL,
  `status_ID` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `event_participation`
--

INSERT INTO `event_participation` (`participation_ID`, `participant_ID`, `event_ID`, `status_ID`) VALUES
(1, 6, 5, 1),
(2, 6, 7, 1);

-- --------------------------------------------------------

--
-- Структура таблицы `levelsrezerv`
--

CREATE TABLE `levelsrezerv` (
  `lvl_ID` int NOT NULL,
  `lvl_Name` varchar(64) COLLATE utf8mb4_general_ci NOT NULL,
  `lvl_TargetScore` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `levelsrezerv`
--

INSERT INTO `levelsrezerv` (`lvl_ID`, `lvl_Name`, `lvl_TargetScore`) VALUES
(1, 'Низкий', 100),
(2, 'Средний', 250),
(3, 'Высокий', 650);

-- --------------------------------------------------------

--
-- Структура таблицы `organizer_profiles`
--

CREATE TABLE `organizer_profiles` (
  `organizer_ID` int NOT NULL,
  `organizer_fullname` varchar(256) COLLATE utf8mb4_general_ci NOT NULL,
  `organizer_events` int NOT NULL DEFAULT '0' COMMENT 'Изначально определяется 0 проведенных мероприятий',
  `organizer_prizes` varchar(256) COLLATE utf8mb4_general_ci NOT NULL,
  `organizer_trust` decimal(3,2) NOT NULL DEFAULT '5.00',
  `organizer_subrole` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `organizer_profiles`
--

INSERT INTO `organizer_profiles` (`organizer_ID`, `organizer_fullname`, `organizer_events`, `organizer_prizes`, `organizer_trust`, `organizer_subrole`) VALUES
(1, 'Петров Петр Петрович', 8, 'Встреча', '4.00', 1),
(7, 'Test test test', 1, 'Билет', '5.00', 2);

-- --------------------------------------------------------

--
-- Структура таблицы `participant_levelsrate`
--

CREATE TABLE `participant_levelsrate` (
  `participant_UserID` int NOT NULL,
  `participant_Level` int NOT NULL DEFAULT '1',
  `participant_TotalScore` int NOT NULL DEFAULT '0',
  `participant_missingScore` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `participant_levelsrate`
--

INSERT INTO `participant_levelsrate` (`participant_UserID`, `participant_Level`, `participant_TotalScore`, `participant_missingScore`) VALUES
(3, 1, 0, 100),
(4, 1, 0, 100),
(5, 2, 213, 38),
(6, 3, 83, 0);

-- --------------------------------------------------------

--
-- Структура таблицы `participant_profiles`
--

CREATE TABLE `participant_profiles` (
  `participant_UserID` int NOT NULL,
  `participant_FullName` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `participant_category` int NOT NULL,
  `participant_Age` int NOT NULL,
  `participant_City` varchar(64) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `participant_profiles`
--

INSERT INTO `participant_profiles` (`participant_UserID`, `participant_FullName`, `participant_category`, `participant_Age`, `participant_City`) VALUES
(3, 'Иванов Иван Иванович', 1, 18, 'Красноярск'),
(4, 'Иванов Марк Иванович', 1, 14, 'Красноярск'),
(5, 'Марк марк марк', 2, 14, 'Красноярск'),
(6, 'po po', 1, 14, 'Красноярск');

-- --------------------------------------------------------

--
-- Структура таблицы `reviews`
--

CREATE TABLE `reviews` (
  `review_ID` int NOT NULL,
  `organizer_ID` int NOT NULL,
  `participant_ID` int NOT NULL,
  `rating` tinyint NOT NULL,
  `comment` text COLLATE utf8mb4_general_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ;

--
-- Дамп данных таблицы `reviews`
--

INSERT INTO `reviews` (`review_ID`, `organizer_ID`, `participant_ID`, `rating`, `comment`, `created_at`) VALUES
(1, 1, 4, 4, ':P', '2026-03-24 21:47:18');

--
-- Триггеры `reviews`
--
DELIMITER $$
CREATE TRIGGER `update_organizer_trust_on_delete` AFTER DELETE ON `reviews` FOR EACH ROW BEGIN
    UPDATE `organizer_profiles` 
    SET `organizer_trust` = (
        SELECT COALESCE(AVG(`rating`), 5.00)
        FROM `reviews` 
        WHERE `organizer_ID` = OLD.`organizer_ID`
    )
    WHERE `organizer_ID` = OLD.`organizer_ID`;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_organizer_trust_on_insert` AFTER INSERT ON `reviews` FOR EACH ROW BEGIN
    UPDATE `organizer_profiles` 
    SET `organizer_trust` = (
        SELECT AVG(`rating`) 
        FROM `reviews` 
        WHERE `organizer_ID` = NEW.`organizer_ID`
    )
    WHERE `organizer_ID` = NEW.`organizer_ID`;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_organizer_trust_on_update` AFTER UPDATE ON `reviews` FOR EACH ROW BEGIN
    UPDATE `organizer_profiles` 
    SET `organizer_trust` = (
        SELECT AVG(`rating`) 
        FROM `reviews` 
        WHERE `organizer_ID` = NEW.`organizer_ID`
    )
    WHERE `organizer_ID` = NEW.`organizer_ID`;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Структура таблицы `roleorganizator`
--

CREATE TABLE `roleorganizator` (
  `roleOrg_ID` int NOT NULL,
  `role_Type` varchar(64) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `roleorganizator`
--

INSERT INTO `roleorganizator` (`roleOrg_ID`, `role_Type`) VALUES
(2, 'Администратор'),
(1, 'Главный администратор'),
(3, 'Модератор');

-- --------------------------------------------------------

--
-- Структура таблицы `spectator_profiles`
--

CREATE TABLE `spectator_profiles` (
  `spec_ID` int NOT NULL,
  `spec_FullName` varchar(256) COLLATE utf8mb4_general_ci NOT NULL,
  `spec_HRDepartament` varchar(256) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `spectator_profiles`
--

INSERT INTO `spectator_profiles` (`spec_ID`, `spec_FullName`, `spec_HRDepartament`) VALUES
(2, 'Романенко Роман Романович', 'Сбербанк');

-- --------------------------------------------------------

--
-- Структура таблицы `status_event`
--

CREATE TABLE `status_event` (
  `status_ID` int NOT NULL,
  `status_Type` varchar(64) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `status_event`
--

INSERT INTO `status_event` (`status_ID`, `status_Type`) VALUES
(1, 'Запланировано'),
(2, 'Регистрация'),
(3, 'Старт'),
(4, 'Завершено');

-- --------------------------------------------------------

--
-- Структура таблицы `status_participation`
--

CREATE TABLE `status_participation` (
  `status_ID` int NOT NULL,
  `status_Type` varchar(64) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `status_participation`
--

INSERT INTO `status_participation` (`status_ID`, `status_Type`) VALUES
(1, 'Зарегистрирован'),
(2, 'На рассмотрении'),
(3, 'Отказано');

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `user_ID` int NOT NULL,
  `user_Login` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `user_Password` varchar(256) COLLATE utf8mb4_general_ci NOT NULL,
  `user_Role` tinyint NOT NULL DEFAULT '1',
  `user_datetimecreated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`user_ID`, `user_Login`, `user_Password`, `user_Role`, `user_datetimecreated`) VALUES
(1, 'organisatorPlatform', 'org12321', 3, '2026-03-23 17:00:00'),
(2, 'spectratorPlatform', 'spect12321', 2, '2026-03-23 17:00:00'),
(3, 'memberPlatform', 'memb12321', 1, '2026-03-23 17:00:00'),
(4, 'ivanov', 'ivan12321', 1, '2026-03-23 21:38:35'),
(5, 'mark', 'mark', 1, '2026-03-24 16:28:26'),
(6, 'pole', 'pole', 1, '2026-03-24 17:19:32'),
(7, 'org', 'org12321', 3, '2026-03-24 21:31:05');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `bonuses`
--
ALTER TABLE `bonuses`
  ADD PRIMARY KEY (`bonus_ID`),
  ADD UNIQUE KEY `bonus_Name` (`bonus_Name`);

--
-- Индексы таблицы `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`category_ID`),
  ADD UNIQUE KEY `category_Type` (`category_Type`);

--
-- Индексы таблицы `coef`
--
ALTER TABLE `coef`
  ADD PRIMARY KEY (`coef_ID`),
  ADD UNIQUE KEY `coef_Difficult` (`coef_Difficult`);

--
-- Индексы таблицы `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`event_ID`),
  ADD KEY `events_ibfk_4` (`event_Bonuses`),
  ADD KEY `idx_organizer` (`event_Organizer`),
  ADD KEY `idx_status` (`event_Status`),
  ADD KEY `idx_category` (`event_Category`),
  ADD KEY `event_Coef` (`event_Coef`);

--
-- Индексы таблицы `event_participation`
--
ALTER TABLE `event_participation`
  ADD PRIMARY KEY (`participation_ID`),
  ADD UNIQUE KEY `unique_participant_event` (`participant_ID`,`event_ID`),
  ADD KEY `event_ID` (`event_ID`) USING BTREE,
  ADD KEY `status_ID` (`status_ID`) USING BTREE;

--
-- Индексы таблицы `levelsrezerv`
--
ALTER TABLE `levelsrezerv`
  ADD PRIMARY KEY (`lvl_ID`),
  ADD UNIQUE KEY `lvl_Name` (`lvl_Name`) USING BTREE;

--
-- Индексы таблицы `organizer_profiles`
--
ALTER TABLE `organizer_profiles`
  ADD UNIQUE KEY `organizer_ID` (`organizer_ID`),
  ADD KEY `organizer_role` (`organizer_subrole`),
  ADD KEY `organizer_trust` (`organizer_trust`);

--
-- Индексы таблицы `participant_levelsrate`
--
ALTER TABLE `participant_levelsrate`
  ADD PRIMARY KEY (`participant_UserID`),
  ADD KEY `participant_Level` (`participant_Level`);

--
-- Индексы таблицы `participant_profiles`
--
ALTER TABLE `participant_profiles`
  ADD PRIMARY KEY (`participant_UserID`) USING BTREE,
  ADD KEY `participant_FullName` (`participant_FullName`),
  ADD KEY `participant_category` (`participant_category`),
  ADD KEY `participant_Age` (`participant_Age`,`participant_City`);

--
-- Индексы таблицы `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_ID`),
  ADD UNIQUE KEY `unique_review` (`organizer_ID`,`participant_ID`),
  ADD KEY `idx_organizer` (`organizer_ID`),
  ADD KEY `idx_participant` (`participant_ID`);

--
-- Индексы таблицы `roleorganizator`
--
ALTER TABLE `roleorganizator`
  ADD PRIMARY KEY (`roleOrg_ID`),
  ADD UNIQUE KEY `role_Type` (`role_Type`);

--
-- Индексы таблицы `spectator_profiles`
--
ALTER TABLE `spectator_profiles`
  ADD PRIMARY KEY (`spec_ID`),
  ADD UNIQUE KEY `spec_FullName` (`spec_FullName`);

--
-- Индексы таблицы `status_event`
--
ALTER TABLE `status_event`
  ADD PRIMARY KEY (`status_ID`);

--
-- Индексы таблицы `status_participation`
--
ALTER TABLE `status_participation`
  ADD PRIMARY KEY (`status_ID`),
  ADD UNIQUE KEY `status_Type` (`status_Type`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_ID`),
  ADD UNIQUE KEY `user_Email` (`user_Login`),
  ADD KEY `user_Role` (`user_Role`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `bonuses`
--
ALTER TABLE `bonuses`
  MODIFY `bonus_ID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT для таблицы `category`
--
ALTER TABLE `category`
  MODIFY `category_ID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT для таблицы `coef`
--
ALTER TABLE `coef`
  MODIFY `coef_ID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `events`
--
ALTER TABLE `events`
  MODIFY `event_ID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT для таблицы `event_participation`
--
ALTER TABLE `event_participation`
  MODIFY `participation_ID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT для таблицы `levelsrezerv`
--
ALTER TABLE `levelsrezerv`
  MODIFY `lvl_ID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_ID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `roleorganizator`
--
ALTER TABLE `roleorganizator`
  MODIFY `roleOrg_ID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `spectator_profiles`
--
ALTER TABLE `spectator_profiles`
  MODIFY `spec_ID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT для таблицы `status_event`
--
ALTER TABLE `status_event`
  MODIFY `status_ID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT для таблицы `status_participation`
--
ALTER TABLE `status_participation`
  MODIFY `status_ID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `user_ID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`event_Organizer`) REFERENCES `organizer_profiles` (`organizer_ID`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `events_ibfk_2` FOREIGN KEY (`event_Status`) REFERENCES `status_event` (`status_ID`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `events_ibfk_3` FOREIGN KEY (`event_Category`) REFERENCES `category` (`category_ID`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `events_ibfk_4` FOREIGN KEY (`event_Bonuses`) REFERENCES `bonuses` (`bonus_ID`) ON DELETE SET NULL ON UPDATE SET NULL,
  ADD CONSTRAINT `events_ibfk_5` FOREIGN KEY (`event_Coef`) REFERENCES `coef` (`coef_ID`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `event_participation`
--
ALTER TABLE `event_participation`
  ADD CONSTRAINT `event_participation_ibfk_1` FOREIGN KEY (`participant_ID`) REFERENCES `participant_profiles` (`participant_UserID`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `event_participation_ibfk_2` FOREIGN KEY (`event_ID`) REFERENCES `events` (`event_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `event_participation_ibfk_3` FOREIGN KEY (`status_ID`) REFERENCES `status_participation` (`status_ID`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Ограничения внешнего ключа таблицы `organizer_profiles`
--
ALTER TABLE `organizer_profiles`
  ADD CONSTRAINT `organizer_profiles_ibfk_1` FOREIGN KEY (`organizer_ID`) REFERENCES `users` (`user_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `organizer_profiles_ibfk_2` FOREIGN KEY (`organizer_subrole`) REFERENCES `roleorganizator` (`roleOrg_ID`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Ограничения внешнего ключа таблицы `participant_levelsrate`
--
ALTER TABLE `participant_levelsrate`
  ADD CONSTRAINT `participant_levelsrate_ibfk_1` FOREIGN KEY (`participant_Level`) REFERENCES `levelsrezerv` (`lvl_ID`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `participant_levelsrate_ibfk_2` FOREIGN KEY (`participant_UserID`) REFERENCES `participant_profiles` (`participant_UserID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `participant_profiles`
--
ALTER TABLE `participant_profiles`
  ADD CONSTRAINT `participant_profiles_ibfk_1` FOREIGN KEY (`participant_UserID`) REFERENCES `users` (`user_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `participant_profiles_ibfk_2` FOREIGN KEY (`participant_category`) REFERENCES `category` (`category_ID`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Ограничения внешнего ключа таблицы `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `fk_reviews_organizer` FOREIGN KEY (`organizer_ID`) REFERENCES `organizer_profiles` (`organizer_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_reviews_participant` FOREIGN KEY (`participant_ID`) REFERENCES `participant_profiles` (`participant_UserID`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `spectator_profiles`
--
ALTER TABLE `spectator_profiles`
  ADD CONSTRAINT `spectator_profiles_ibfk_1` FOREIGN KEY (`spec_ID`) REFERENCES `users` (`user_ID`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

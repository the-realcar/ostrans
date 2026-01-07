INSERT INTO pojazdy (id, nr_taborowy, nr_rejestracyjny, marka, model, rok_produkcji, nazwa_zajezdni, naped, sprawny)
VALUES
-- przykłady (id sekwencyjne, nr_taborowy z CSV, nr_rejestracyjny unikalny WOS-<id>):
(1, '79',    'WOS-1',   'Volvo',  '7700FL',             2007, 'OKM', 5, TRUE),
-- SOR BN 8,5 CB01-CB07
(2, 'CB01',  'WOS-2',   'SOR',    'BN 8,5',             2012, 'OMC', 7, TRUE),
(3, 'CB02',  'WOS-3',   'SOR',    'BN 8,5',             2012, 'OMC', 7, TRUE),
(4, 'CB03',  'WOS-4',   'SOR',    'BN 8,5',             2012, 'OMC', 7, TRUE),
(5, 'CB04',  'WOS-5',   'SOR',    'BN 8,5',             2012, 'OMC', 7, TRUE),
(6, 'CB05',  'WOS-6',   'SOR',    'BN 8,5',             2012, 'OMC', 7, TRUE),
(7, 'CB06',  'WOS-7',   'SOR',    'BN 8,5',             2012, 'OMC', 7, TRUE),
(8, 'CB07',  'WOS-8',   'SOR',    'BN 8,5',             2012, 'OMC', 7, TRUE),

-- Setra S315NF CT01-CT05
(9, 'CT01',  'WOS-9',   'Setra',  'S315NF',             2005, 'OMC', 8, TRUE),
(10,'CT02',  'WOS-10',  'Setra',  'S315NF',             2005, 'OMC', 8, TRUE),
(11,'CT03',  'WOS-11',  'Setra',  'S315NF',             2005, 'OMC', 8, TRUE),
(12,'CT04',  'WOS-12',  'Setra',  'S315NF',             2005, 'OMC', 8, TRUE),
(13,'CT05',  'WOS-13',  'Setra',  'S315NF',             2005, 'OMC', 8, TRUE),

-- SOR BN 12 MB01-MB12 (przykład pierwszych trzech)
(14,'MB01',  'WOS-14',  'SOR',    'BN 12',              2017, 'OKM', 8, TRUE),
(15,'MB02',  'WOS-15',  'SOR',    'BN 12',              2017, 'OKM', 8, TRUE),
(16,'MB03',  'WOS-16',  'SOR',    'BN 12',              2017, 'OKM', 8, TRUE),
(17,'MB04',  'WOS-17',  'SOR',    'BN 12',              2017, 'OKM', 8, TRUE),
(18,'MB05',  'WOS-18',  'SOR',    'BN 12',              2017, 'OKM', 8, TRUE),
(19,'MB06',  'WOS-19',  'SOR',    'BN 12',              2017, 'OKM', 8, TRUE),
(20,'MB07',  'WOS-20',  'SOR',    'BN 12',              2017, 'OKM', 8, TRUE),
(21,'MB08',  'WOS-21',  'SOR',    'BN 12',              2017, 'OKM', 8, TRUE),
(22,'MB09',  'WOS-22',  'SOR',    'BN 12',              2017, 'OKM', 8, TRUE),
(23,'MB10',  'WOS-23',  'SOR',    'BN 12',              2017, 'OKM', 8, TRUE),
(24,'MB11',  'WOS-24',  'SOR',    'BN 12',              2017, 'OKM', 8, TRUE),
(25,'MB12',  'WOS-25',  'SOR',    'BN 12',              2017, 'OKM', 8, TRUE),

-- Solaris Urbino 18 III MD01-MD10 -> M -> OKM, CNG EEV -> 10
(26, 'MD01',  'WOS-26',  'Solaris', 'Urbino 18 III',     2014, 'OKM', 10, TRUE),
(27, 'MD02',  'WOS-27',  'Solaris', 'Urbino 18 III',     2014, 'OKM', 10, TRUE),
(28, 'MD03',  'WOS-28',  'Solaris', 'Urbino 18 III',     2014, 'OKM', 10, TRUE),
(29, 'MD04',  'WOS-29',  'Solaris', 'Urbino 18 III',     2014, 'OKM', 10, TRUE),
(30, 'MD05',  'WOS-30',  'Solaris', 'Urbino 18 III',     2014, 'OKM', 10, TRUE),
(31, 'MD06',  'WOS-31',  'Solaris', 'Urbino 18 III',     2014, 'OKM', 10, TRUE),
(32, 'MD07',  'WOS-32',  'Solaris', 'Urbino 18 III',     2014, 'OKM', 10, TRUE),
(33, 'MD08',  'WOS-33',  'Solaris', 'Urbino 18 III',     2014, 'OKM', 10, TRUE),
(34, 'MD09',  'WOS-34',  'Solaris', 'Urbino 18 III',     2014, 'OKM', 10, TRUE),
(35, 'MD10',  'WOS-35',  'Solaris', 'Urbino 18 III',     2014, 'OKM', 10, TRUE),

-- Solaris Urbino 12 IV MD11-MD16 -> M -> OKM, CNG e6 -> 11
(36, 'MD11',  'WOS-36',  'Solaris', 'Urbino 12 IV',      2023, 'OKM', 11, TRUE),
(37, 'MD12',  'WOS-37',  'Solaris', 'Urbino 12 IV',      2023, 'OKM', 11, TRUE),
(38, 'MD13',  'WOS-38',  'Solaris', 'Urbino 12 IV',      2023, 'OKM', 11, TRUE),
(39, 'MD14',  'WOS-39',  'Solaris', 'Urbino 12 IV',      2023, 'OKM', 11, TRUE),
(40, 'MD15',  'WOS-40',  'Solaris', 'Urbino 12 IV',      2023, 'OKM', 11, TRUE),
(41, 'MD16',  'WOS-41',  'Solaris', 'Urbino 12 IV',      2023, 'OKM', 11, TRUE),

-- Solaris Urbino 18 IV MD17-MD20 -> M -> OKM, CNG e6 -> 11
(42, 'MD17',  'WOS-42',  'Solaris', 'Urbino 18 IV',      2022, 'OKM', 11, TRUE),
(43, 'MD18',  'WOS-43',  'Solaris', 'Urbino 18 IV',      2022, 'OKM', 11, TRUE),
(44, 'MD19',  'WOS-44',  'Solaris', 'Urbino 18 IV',      2022, 'OKM', 11, TRUE),
(45, 'MD20',  'WOS-45',  'Solaris', 'Urbino 18 IV',      2022, 'OKM', 11, TRUE),

-- Solaris Urbino 12 III MD21-MD36 -> M -> OKM, CNG EEV -> 10
(46, 'MD21',  'WOS-46',  'Solaris', 'Urbino 12 III',     2010, 'OKM', 10, TRUE),
(47, 'MD22',  'WOS-47',  'Solaris', 'Urbino 12 III',     2010, 'OKM', 10, TRUE),
(48, 'MD23',  'WOS-48',  'Solaris', 'Urbino 12 III',     2010, 'OKM', 10, TRUE),
(49, 'MD24',  'WOS-49',  'Solaris', 'Urbino 12 III',     2010, 'OKM', 10, TRUE),
(50, 'MD25',  'WOS-50',  'Solaris', 'Urbino 12 III',     2010, 'OKM', 10, TRUE),
(51, 'MD26',  'WOS-51',  'Solaris', 'Urbino 12 III',     2010, 'OKM', 10, TRUE),
(52, 'MD27',  'WOS-52',  'Solaris', 'Urbino 12 III',     2010, 'OKM', 10, TRUE),
(53, 'MD28',  'WOS-53',  'Solaris', 'Urbino 12 III',     2010, 'OKM', 10, TRUE),
(54, 'MD29',  'WOS-54',  'Solaris', 'Urbino 12 III',     2010, 'OKM', 10, TRUE),
(55, 'MD30',  'WOS-55',  'Solaris', 'Urbino 12 III',     2010, 'OKM', 10, TRUE),
(56, 'MD31',  'WOS-56',  'Solaris', 'Urbino 12 III',     2010, 'OKM', 10, TRUE),
(57, 'MD32',  'WOS-57',  'Solaris', 'Urbino 12 III',     2010, 'OKM', 10, TRUE),
(58, 'MD33',  'WOS-58',  'Solaris', 'Urbino 12 III',     2010, 'OKM', 10, TRUE),
(59, 'MD34',  'WOS-59',  'Solaris', 'Urbino 12 III',     2010, 'OKM', 10, TRUE),
(60, 'MD35',  'WOS-60',  'Solaris', 'Urbino 12 III',     2010, 'OKM', 10, TRUE),
(61, 'MD36',  'WOS-61',  'Solaris', 'Urbino 12 III',     2010, 'OKM', 10, TRUE),

-- Solaris Urbino 18 IV MD37-MD41 -> M -> OKM, CNG e6 -> 11
(62, 'MD37',  'WOS-62',  'Solaris', 'Urbino 18 IV',      2024, 'OKM', 11, TRUE),
(63, 'MD38',  'WOS-63',  'Solaris', 'Urbino 18 IV',      2024, 'OKM', 11, TRUE),
(64, 'MD39',  'WOS-64',  'Solaris', 'Urbino 18 IV',      2024, 'OKM', 11, TRUE),
(65, 'MD40',  'WOS-65',  'Solaris', 'Urbino 18 IV',      2024, 'OKM', 11, TRUE),
(66, 'MD41',  'WOS-66',  'Solaris', 'Urbino 18 IV',      2024, 'OKM', 11, TRUE),

-- Jelcz M121M MJ01-MJ08 -> M -> OKM, e6 -> 8
(67, 'MJ01',  'WOS-67',  'Jelcz',  'M121MB',             1997, 'OKM', 8, TRUE),
(68, 'MJ02',  'WOS-68',  'Jelcz',  'M121MB',             1997, 'OKM', 8, TRUE),
(69, 'MJ03',  'WOS-69',  'Jelcz',  'M121MB',             1997, 'OKM', 8, TRUE),
(70, 'MJ04',  'WOS-70',  'Jelcz',  'M121MB',             1997, 'OKM', 8, TRUE),
(71, 'MJ05',  'WOS-71',  'Jelcz',  'M121MB',             1997, 'OKM', 8, TRUE),
(72, 'MJ06',  'WOS-72',  'Jelcz',  'M121MB',             1997, 'OKM', 8, TRUE),
(73, 'MJ07',  'WOS-73',  'Jelcz',  'M121MB',             1997, 'OKM', 8, TRUE),
(74, 'MJ08',  'WOS-74',  'Jelcz',  'M121MB',             1997, 'OKM', 8, TRUE),

-- Jelcz M181MB/3 MJ09-MJ14 -> M -> OKM, e6 -> 8
(75, 'MJ09',  'WOS-75',  'Jelcz',  'M181M/3',             2007, 'OKM', 8, TRUE),
(76, 'MJ10',  'WOS-76',  'Jelcz',  'M181M/3',             2007, 'OKM', 8, TRUE),
(77, 'MJ11',  'WOS-77',  'Jelcz',  'M181M/3',             2007, 'OKM', 8, TRUE),
(78, 'MJ12',  'WOS-78',  'Jelcz',  'M181M/3',             2007, 'OKM', 8, TRUE),
(79, 'MJ13',  'WOS-79',  'Jelcz',  'M181M/3',             2007, 'OKM', 8, TRUE),
(80, 'MJ14',  'WOS-80',  'Jelcz',  'M181M/3',             2007, 'OKM', 8, TRUE),

-- VanHool A300 MF01 -> M -> OKM, e6 -> 8
(81, 'MF01',  'WOS-81',  'VanHool', 'A300',               1993, 'OKM', 8, TRUE),

-- VanHool AG300 MF11 -> M -> OKM, e6 -> 8
(82, 'MF11',  'WOS-82',  'VanHool', 'AG300',              2007, 'OKM', 8, TRUE),

-- Škoda 14Tr.D MK01-MK08 -> M -> OKM, (CSV shows '??') treated as e6 -> 8
(83, 'MK01',  'WOS-83',  'Škoda',  '14Tr.D',              2002, 'OKM', 8, TRUE),
(84, 'MK02',  'WOS-84',  'Škoda',  '14Tr.D',              2002, 'OKM', 8, TRUE),
(85, 'MK03',  'WOS-85',  'Škoda',  '14Tr.D',              2002, 'OKM', 8, TRUE),
(86, 'MK04',  'WOS-86',  'Škoda',  '14Tr.D',              2002, 'OKM', 8, TRUE),
(87, 'MK05',  'WOS-87',  'Škoda',  '14Tr.D',              2002, 'OKM', 8, TRUE),
(88, 'MK06',  'WOS-88',  'Škoda',  '14Tr.D',              2002, 'OKM', 8, TRUE),
(89, 'MK07',  'WOS-89',  'Škoda',  '14Tr.D',              2002, 'OKM', 8, TRUE),
(90, 'MK08',  'WOS-90',  'Škoda',  '14Tr.D',              2002, 'OKM', 8, TRUE),

-- Škoda 15Tr.D MK09-MK13 -> M -> OKM, e6 -> 8
(91, 'MK09',  'WOS-91',  'Škoda',  '15Tr.D',              2002, 'OKM', 8, TRUE),
(92, 'MK10',  'WOS-92',  'Škoda',  '15Tr.D',              2002, 'OKM', 8, TRUE),
(93, 'MK11',  'WOS-93',  'Škoda',  '15Tr.D',              2002, 'OKM', 8, TRUE),
(94, 'MK12',  'WOS-94',  'Škoda',  '15Tr.D',              2002, 'OKM', 8, TRUE),
(95, 'MK13',  'WOS-95',  'Škoda',  '15Tr.D',              2002, 'OKM', 8, TRUE),

-- MAN NL283 MN01-MN07 -> M -> OKM, e6 -> 8
(96, 'MN01',  'WOS-96',  'MAN',    'NL283',               1999, 'OKM', 8, TRUE),
(97, 'MN02',  'WOS-97',  'MAN',    'NL283',               1999, 'OKM', 8, TRUE),
(98, 'MN03',  'WOS-98',  'MAN',    'NL283',               1999, 'OKM', 8, TRUE),
(99, 'MN04',  'WOS-99',  'MAN',    'NL283',               1999, 'OKM', 8, TRUE),
(100,'MN05',  'WOS-100', 'MAN',    'NL283',               1999, 'OKM', 8, TRUE),
(101,'MN06',  'WOS-101', 'MAN',    'NL283',               1999, 'OKM', 8, TRUE),
(102,'MN07',  'WOS-102', 'MAN',    'NL283',               1999, 'OKM', 8, TRUE),

-- MAN NG313 MN08-MN10 -> M -> OKM, e6 -> 8
(103,'MN08',  'WOS-103', 'MAN',    'NG313',              2004, 'OKM', 8, TRUE),
(104,'MN09',  'WOS-104', 'MAN',    'NG313',              2004, 'OKM', 8, TRUE),
(105,'MN10',  'WOS-105', 'MAN',    'NG313',              2004, 'OKM', 8, TRUE),

-- MAN LC NG323 MN11-MN14 -> M -> OKM, EEV -> 7
(106,'MN11',  'WOS-106', 'MAN',    'LC NG323',           2012, 'OKM', 7, TRUE),
(107,'MN12',  'WOS-107', 'MAN',    'LC NG323',           2012, 'OKM', 7, TRUE),
(108,'MN13',  'WOS-108', 'MAN',    'LC NG323',           2012, 'OKM', 7, TRUE),
(109,'MN14',  'WOS-109', 'MAN',    'LC NG323',           2012, 'OKM', 7, TRUE),

-- Solaris Urbino 12 IV MP20-MP21 -> M -> OKM, Electric -> 15
(110,'MP20',  'WOS-110', 'Solaris', 'Urbino 12 IV',      2023, 'OKM', 15, TRUE),
(111,'MP21',  'WOS-111', 'Solaris', 'Urbino 12 IV',      2023, 'OKM', 15, TRUE),

-- Solaris Urbino 18 IV MP22-MP26 -> M -> OKM, Electric -> 15
(112,'MP22',  'WOS-112', 'Solaris', 'Urbino 18 IV',      2023, 'OKM', 15, TRUE),
(113,'MP23',  'WOS-113', 'Solaris', 'Urbino 18 IV',      2023, 'OKM', 15, TRUE),
(114,'MP24',  'WOS-114', 'Solaris', 'Urbino 18 IV',      2023, 'OKM', 15, TRUE),
(115,'MP25',  'WOS-115', 'Solaris', 'Urbino 18 IV',      2023, 'OKM', 15, TRUE),
(116,'MP26',  'WOS-116', 'Solaris', 'Urbino 18 IV',      2023, 'OKM', 15, TRUE),

-- Solaris Urbino 18 IV p MP27-MP29 -> M -> OKM, plug-in Electric -> 16
(117,'MP27',  'WOS-117', 'Solaris', 'Urbino 18 IV p',    2025, 'OKM', 16, TRUE),
(118,'MP28',  'WOS-118', 'Solaris', 'Urbino 18 IV p',    2025, 'OKM', 16, TRUE),
(119,'MP29',  'WOS-119', 'Solaris', 'Urbino 18 IV p',    2025, 'OKM', 16, TRUE),

-- Škoda 14Tr.E MP30-MP34 -> M -> OKM, Electric -> 15
(120,'MP30',  'WOS-120', 'Škoda',  '14Tr.E',              2002, 'OKM', 15, TRUE),
(121,'MP31',  'WOS-121', 'Škoda',  '14Tr.E',              2002, 'OKM', 15, TRUE),
(122,'MP32',  'WOS-122', 'Škoda',  '14Tr.E',              2002, 'OKM', 15, TRUE),
(123,'MP33',  'WOS-123', 'Škoda',  '14Tr.E',              2002, 'OKM', 15, TRUE),
(124,'MP34',  'WOS-124', 'Škoda',  '14Tr.E',              2002, 'OKM', 15, TRUE),

-- Škoda 15Tr.E MP35-MP38 -> M -> OKM, Electric -> 15
(125,'MP35',  'WOS-125', 'Škoda',  '15Tr.E',              2002, 'OKM', 15, TRUE),
(126,'MP36',  'WOS-126', 'Škoda',  '15Tr.E',              2002, 'OKM', 15, TRUE),
(127,'MP37',  'WOS-127', 'Škoda',  '15Tr.E',              2002, 'OKM', 15, TRUE),
(128,'MP38',  'WOS-128', 'Škoda',  '15Tr.E',              2002, 'OKM', 15, TRUE),

-- Solaris Urbino 18 III MR01-MR06 -> M -> OKM, Hybrid EEV -> 12
(129,'MR01',  'WOS-129', 'Solaris', 'Urbino 18 III',     2010, 'OKM', 12, TRUE),
(130,'MR02',  'WOS-130', 'Solaris', 'Urbino 18 III',     2010, 'OKM', 12, TRUE),
(131,'MR03',  'WOS-131', 'Solaris', 'Urbino 18 III',     2010, 'OKM', 12, TRUE),
(132,'MR04',  'WOS-132', 'Solaris', 'Urbino 18 III',     2010, 'OKM', 12, TRUE),
(133,'MR05',  'WOS-133', 'Solaris', 'Urbino 18 III',     2010, 'OKM', 12, TRUE),
(134,'MR06',  'WOS-134', 'Solaris', 'Urbino 18 III',     2010, 'OKM', 12, TRUE),

-- MAN LC18 EH MR07-MR13 -> M -> OKM, Hybrid e6 -> 13
(135,'MR07',  'WOS-135', 'MAN',    'LC18 EH',            2023, 'OKM', 13, TRUE),
(136,'MR08',  'WOS-136', 'MAN',    'LC18 EH',            2023, 'OKM', 13, TRUE),
(137,'MR09',  'WOS-137', 'MAN',    'LC18 EH',            2023, 'OKM', 13, TRUE),
(138,'MR10',  'WOS-138', 'MAN',    'LC18 EH',            2023, 'OKM', 13, TRUE),
(139,'MR11',  'WOS-139', 'MAN',    'LC18 EH',            2023, 'OKM', 13, TRUE),
(140,'MR12',  'WOS-140', 'MAN',    'LC18 EH',            2023, 'OKM', 13, TRUE),
(141,'MR13',  'WOS-141', 'MAN',    'LC18 EH',            2023, 'OKM', 13, TRUE),

-- Solaris Urbino 12 IV MR14-MR23 -> M -> OKM, Mild Hybrid e6 -> 14
(142,'MR14',  'WOS-142', 'Solaris', 'Urbino 12 IV',      2024, 'OKM', 14, TRUE),
(143,'MR15',  'WOS-143', 'Solaris', 'Urbino 12 IV',      2024, 'OKM', 14, TRUE),
(144,'MR16',  'WOS-144', 'Solaris', 'Urbino 12 IV',      2024, 'OKM', 14, TRUE),
(145,'MR17',  'WOS-145', 'Solaris', 'Urbino 12 IV',      2024, 'OKM', 14, TRUE),
(146,'MR18',  'WOS-146', 'Solaris', 'Urbino 12 IV',      2024, 'OKM', 14, TRUE),
(147,'MR19',  'WOS-147', 'Solaris', 'Urbino 12 IV',      2024, 'OKM', 14, TRUE),
(148,'MR20',  'WOS-148', 'Solaris', 'Urbino 12 IV',      2024, 'OKM', 14, TRUE),
(149,'MR21',  'WOS-149', 'Solaris', 'Urbino 12 IV',      2024, 'OKM', 14, TRUE),
(150,'MR22',  'WOS-150', 'Solaris', 'Urbino 12 IV',      2024, 'OKM', 14, TRUE),
(151,'MR23',  'WOS-151', 'Solaris', 'Urbino 12 IV',      2024, 'OKM', 14, TRUE),

-- Solaris Urbino 12 III MS01-MS14 -> M -> OKM, e5 -> 6
(152,'MS01',  'WOS-152', 'Solaris', 'Urbino 12 III',     2013, 'OKM', 6, TRUE),
(153,'MS02',  'WOS-153', 'Solaris', 'Urbino 12 III',     2013, 'OKM', 6, TRUE),
(154,'MS03',  'WOS-154', 'Solaris', 'Urbino 12 III',     2013, 'OKM', 6, TRUE),
(155,'MS04',  'WOS-155', 'Solaris', 'Urbino 12 III',     2013, 'OKM', 6, TRUE),
(156,'MS05',  'WOS-156', 'Solaris', 'Urbino 12 III',     2013, 'OKM', 6, TRUE),
(157,'MS06',  'WOS-157', 'Solaris', 'Urbino 12 III',     2013, 'OKM', 6, TRUE),
(158,'MS07',  'WOS-158', 'Solaris', 'Urbino 12 III',     2013, 'OKM', 6, TRUE),
(159,'MS08',  'WOS-159', 'Solaris', 'Urbino 12 III',     2013, 'OKM', 6, TRUE),
(160,'MS09',  'WOS-160', 'Solaris', 'Urbino 12 III',     2013, 'OKM', 6, TRUE),
(161,'MS10',  'WOS-161', 'Solaris', 'Urbino 12 III',     2013, 'OKM', 6, TRUE),
(162,'MS11',  'WOS-162', 'Solaris', 'Urbino 12 III',     2013, 'OKM', 6, TRUE),
(163,'MS12',  'WOS-163', 'Solaris', 'Urbino 12 III',     2013, 'OKM', 6, TRUE),
(164,'MS13',  'WOS-164', 'Solaris', 'Urbino 12 III',     2013, 'OKM', 6, TRUE),
(165,'MS14',  'WOS-165', 'Solaris', 'Urbino 12 III',     2013, 'OKM', 6, TRUE),

-- Solaris Urbino 18 III MS15-MS22 -> M -> OKM, e5 -> 6
(166,'MS15',  'WOS-166', 'Solaris', 'Urbino 18 III',     2013, 'OKM', 6, TRUE),
(167,'MS16',  'WOS-167', 'Solaris', 'Urbino 18 III',     2013, 'OKM', 6, TRUE),
(168,'MS17',  'WOS-168', 'Solaris', 'Urbino 18 III',     2013, 'OKM', 6, TRUE),
(169,'MS18',  'WOS-169', 'Solaris', 'Urbino 18 III',     2013, 'OKM', 6, TRUE),
(170,'MS19',  'WOS-170', 'Solaris', 'Urbino 18 III',     2013, 'OKM', 6, TRUE),
(171,'MS20',  'WOS-171', 'Solaris', 'Urbino 18 III',     2013, 'OKM', 6, TRUE),
(172,'MS21',  'WOS-172', 'Solaris', 'Urbino 18 III',     2013, 'OKM', 6, TRUE),
(173,'MS22',  'WOS-173', 'Solaris', 'Urbino 18 III',     2013, 'OKM', 6, TRUE),

-- Solaris Urbino 15 II MS23-MS25 -> M -> OKM, e6 -> 8
(174,'MS23',  'WOS-174', 'Solaris', 'Urbino 15 II',      2005, 'OKM', 8, TRUE),
(175,'MS24',  'WOS-175', 'Solaris', 'Urbino 15 II',      2005, 'OKM', 8, TRUE),
(176,'MS25',  'WOS-176', 'Solaris', 'Urbino 15 II',      2005, 'OKM', 8, TRUE),

-- Solaris Urbino 18 III MS26-MS27 -> M -> OKM, EEV -> 7
(177,'MS26',  'WOS-177', 'Solaris', 'Urbino 18 III',     2013, 'OKM', 7, TRUE),
(178,'MS27',  'WOS-178', 'Solaris', 'Urbino 18 III',     2013, 'OKM', 7, TRUE),

-- Solaris Urbino 12 III MS28-MS38 -> M -> OKM, e6 -> 8
(179,'MS28',  'WOS-179', 'Solaris', 'Urbino 12 III',     2015, 'OKM', 8, TRUE),
(180,'MS29',  'WOS-180', 'Solaris', 'Urbino 12 III',     2015, 'OKM', 8, TRUE),
(181,'MS30',  'WOS-181', 'Solaris', 'Urbino 12 III',     2015, 'OKM', 8, TRUE),
(182,'MS31',  'WOS-182', 'Solaris', 'Urbino 12 III',     2015, 'OKM', 8, TRUE),
(183,'MS32',  'WOS-183', 'Solaris', 'Urbino 12 III',     2015, 'OKM', 8, TRUE),
(184,'MS33',  'WOS-184', 'Solaris', 'Urbino 12 III',     2015, 'OKM', 8, TRUE),
(185,'MS34',  'WOS-185', 'Solaris', 'Urbino 12 III',     2015, 'OKM', 8, TRUE),
(186,'MS35',  'WOS-186', 'Solaris', 'Urbino 12 III',     2015, 'OKM', 8, TRUE),
(187,'MS36',  'WOS-187', 'Solaris', 'Urbino 12 III',     2015, 'OKM', 8, TRUE),
(188,'MS37',  'WOS-188', 'Solaris', 'Urbino 12 III',     2015, 'OKM', 8, TRUE),
(189,'MS38',  'WOS-189', 'Solaris', 'Urbino 12 III',     2015, 'OKM', 8, TRUE),

-- Solaris Urbino 18 III MS39-MS44 -> M -> OKM, e6 -> 8
(190,'MS39',  'WOS-190', 'Solaris', 'Urbino 18 III',     2016, 'OKM', 8, TRUE),
(191,'MS40',  'WOS-191', 'Solaris', 'Urbino 18 III',     2016, 'OKM', 8, TRUE),
(192,'MS41',  'WOS-192', 'Solaris', 'Urbino 18 III',     2016, 'OKM', 8, TRUE),
(193,'MS42',  'WOS-193', 'Solaris', 'Urbino 18 III',     2016, 'OKM', 8, TRUE),
(194,'MS43',  'WOS-194', 'Solaris', 'Urbino 18 III',     2016, 'OKM', 8, TRUE),
(195,'MS44',  'WOS-195', 'Solaris', 'Urbino 18 III',     2016, 'OKM', 8, TRUE),

-- Neoplan K4016TD MT01-MT05 -> M -> OKM, e6 -> 8
(196,'MT01',  'WOS-196', 'Neoplan', 'K4016TD',           1999, 'OKM', 8, TRUE),
(197,'MT02',  'WOS-197', 'Neoplan', 'K4016TD',           1999, 'OKM', 8, TRUE),
(198,'MT03',  'WOS-198', 'Neoplan', 'K4016TD',           1999, 'OKM', 8, TRUE),
(199,'MT04',  'WOS-199', 'Neoplan', 'K4016TD',           1999, 'OKM', 8, TRUE),
(200,'MT05',  'WOS-200', 'Neoplan', 'K4016TD',           1999, 'OKM', 8, TRUE),

-- Volvo 7000A.BEV MT06-MT09 -> M -> OKM, Electric -> 15
(201,'MT06',  'WOS-201', 'Volvo',  '7000A.BEV',          2003, 'OKM', 15, TRUE),
(202,'MT07',  'WOS-202', 'Volvo',  '7000A.BEV',          2003, 'OKM', 15, TRUE),
(203,'MT08',  'WOS-203', 'Volvo',  '7000A.BEV',          2003, 'OKM', 15, TRUE),
(204,'MT09',  'WOS-204', 'Volvo',  '7000A.BEV',          2003, 'OKM', 15, TRUE),

-- Volvo 7700 MT10-MT11 -> M -> OKM, e3 -> 4
(205,'MT10',  'WOS-205', 'Volvo',  '7700',               2005, 'OKM', 4, TRUE),
(206,'MT11',  'WOS-206', 'Volvo',  '7700',               2005, 'OKM', 4, TRUE),

-- Solaris Urbino 10,5 IV MU01-MU06 -> M -> OKM, e6 -> 8
(207,'MU01',  'WOS-207', 'Solaris', 'Urbino 10,5 IV',    2024, 'OKM', 8, TRUE),
(208,'MU02',  'WOS-208', 'Solaris', 'Urbino 10,5 IV',    2024, 'OKM', 8, TRUE),
(209,'MU03',  'WOS-209', 'Solaris', 'Urbino 10,5 IV',    2024, 'OKM', 8, TRUE),
(210,'MU04',  'WOS-210', 'Solaris', 'Urbino 10,5 IV',    2024, 'OKM', 8, TRUE),
(211,'MU05',  'WOS-211', 'Solaris', 'Urbino 10,5 IV',    2024, 'OKM', 8, TRUE),
(212,'MU06',  'WOS-212', 'Solaris', 'Urbino 10,5 IV',    2024, 'OKM', 8, TRUE),

-- Inne (poprawione: dodano nr_taborowy i unikalne nr_rejestracyjne WOS-213..)
(213, 'CH01', 'WOS-213', 'Jelcz',  'L090M',    2000, 'OMC', 8, TRUE),
(214, 'CH02', 'WOS-214', 'Autosan','H10-30',   1990, 'OMC', 8, TRUE),
(215, 'CH03', 'WOS-215', 'Autosan','H10-30',   1990, 'OMC', 8, TRUE),
(216, 'CH04', 'WOS-216', 'Autosan','H10-30',   1990, 'OMC', 8, TRUE),
(217, 'MH01', 'WOS-217', 'Karosa', 'B951',     2006, 'OKM', 8, TRUE),
(218, 'MH06', 'WOS-218', 'Jelcz',  'M101/3',   2006, 'OKM', 8, TRUE),
(219, 'MH10', 'WOS-219', 'Škoda',  '14TrM',    1995, 'OKM', NULL, TRUE),
(220, 'MH11', 'WOS-220', 'Škoda',  '15TrM',    1995, 'OKM', NULL, TRUE),
(221, 'MH12', 'WOS-221', 'Ikarus', '260.04',   1982, 'OKM', 8, TRUE),
(222, 'MH13', 'WOS-222', 'Ikarus', '260.04',   1982, 'OKM', 8, TRUE),
(223, 'MH14', 'WOS-223', 'Ikarus', '260.04',   1982, 'OKM', 8, TRUE),
(224, 'MH15', 'WOS-224', 'Ikarus', '260.04',   1982, 'OKM', 8, TRUE),
(225, 'MH16', 'WOS-225', 'Ikarus', '280.37',   1992, 'OKM', 8, TRUE),
(226, 'MH17', 'WOS-226', 'Ikarus', '280.37',   1992, 'OKM', 8, TRUE),
(227, 'MH18', 'WOS-227', 'Ikarus', '280.37',   1992, 'OKM', 8, TRUE),
(228, 'MH19', 'WOS-228', 'Ikarus', '280.37',   1992, 'OKM', 8, TRUE),
(229, 'MH20', 'WOS-229', 'Ikarus', '280.37',   1992, 'OKM', 8, TRUE),

-- Trolejbusy (rozwinąłem pierwsze numery, id = sufiks liczbowy — mogą się pokrywać z autobusami)
(230, 'RK01', 'WOS-230', 'Škoda',  '14Tr.BET',   1996, 'OKM', NULL, TRUE),
(231, 'RK02', 'WOS-231', 'Škoda',  '14Tr.BET',   1996, 'OKM', NULL, TRUE),
(232, 'RK03', 'WOS-232', 'Škoda',  '14Tr.BET',   1996, 'OKM', NULL, TRUE),
(233, 'RK04', 'WOS-233', 'Škoda',  '14Tr.BET',   1996, 'OKM', NULL, TRUE),
(234, 'RK05', 'WOS-234', 'Škoda',  '14Tr.BET',   1996, 'OKM', NULL, TRUE),
(235, 'RK06', 'WOS-235', 'Škoda',  '14Tr.BET',   1996, 'OKM', NULL, TRUE),
(236, 'RK07', 'WOS-236', 'Škoda',  '15Tr.BET',   1996, 'OKM', NULL, TRUE),
(237, 'RK08', 'WOS-237', 'Škoda',  '15Tr.BET',   1996, 'OKM', NULL, TRUE),
(238, 'RK09', 'WOS-238', 'Škoda',  '15Tr.BET',   1996, 'OKM', NULL, TRUE),
(239, 'RK10', 'WOS-239', 'Škoda',  '27Tr III',   2015, 'OKM', NULL, TRUE),
(240, 'RK11', 'WOS-240', 'Škoda',  '27Tr III',   2015, 'OKM', NULL, TRUE),
(241, 'RK12', 'WOS-241', 'Škoda',  '27Tr III',   2015, 'OKM', NULL, TRUE),
(242, 'RK13', 'WOS-242', 'Škoda',  '27Tr III',   2015, 'OKM', NULL, TRUE),
(243, 'RK14', 'WOS-243', 'Škoda',  '27Tr III',   2015, 'OKM', NULL, TRUE),
(244, 'RS01', 'WOS-244', 'Solaris','Trollino 12M III', 2016, 'OKM', NULL, TRUE),
(245, 'RS02', 'WOS-245', 'Solaris','Trollino 12M III', 2016, 'OKM', NULL, TRUE),
(246, 'RS03', 'WOS-246', 'Solaris','Trollino 12M III', 2016, 'OKM', NULL, TRUE),
(247, 'RU01', 'WOS-247', 'Solaris','Trollino 12 IV',   2019, 'OKM', NULL, TRUE),

-- Tramwaje (id = sufiks liczbowy z zakresu; naped = NULL, zajezdnia = OKW)
(248, 'WW01', NULL, 'Tatra',     'T3SUCS', 1987, 'OKW', NULL, TRUE),
(249, 'WW02', NULL, 'Tatra',     'T3SUCS', 1987, 'OKW', NULL, TRUE),
(250, 'WW03', NULL, 'Tatra',     'T3SUCS', 1987, 'OKW', NULL, TRUE),
(251, 'WX01', NULL, 'Konstal',   '105Na',   1975, 'OKW', NULL, TRUE),
(252, 'WX02', NULL, 'Konstal',   '105Na',   1975, 'OKW', NULL, TRUE),
(253, 'WX03', NULL, 'Konstal',   '105Na',   1975, 'OKW', NULL, TRUE),
(254, 'WX04', NULL, 'Konstal',   '105Na',   1975, 'OKW', NULL, TRUE),
(255, 'WX05', NULL, 'Konstal',   '105Na',   1975, 'OKW', NULL, TRUE),
(256, 'WX06', NULL, 'Konstal',   '105Na',   1975, 'OKW', NULL, TRUE),
(257, 'WX07', NULL, 'Duewag',    'GT8N',    1974, 'OKW', NULL, TRUE),
(258, 'WX08', NULL, 'Duewag',    'GT8N',    1974, 'OKW', NULL, TRUE),
(259, 'WX09', NULL, 'Duewag',    'GT8N',    1974, 'OKW', NULL, TRUE),
(260, 'WX10', NULL, 'Duewag',    'GT8N',    1974, 'OKW', NULL, TRUE),
(261, 'WX11', NULL, 'Duewag',    'GT8N',    1974, 'OKW', NULL, TRUE),
(262, 'WX12', NULL, 'Duewag',    'GT8N',    1974, 'OKW', NULL, TRUE),
(263, 'WX13', NULL, 'Duewag',    'GT8N',    1974, 'OKW', NULL, TRUE),
(264, 'WY01', NULL, 'Konstal',   '112N',    1995, 'OKW', NULL, TRUE),
(265, 'WY02', NULL, 'Konstal',   '112N',    1995, 'OKW', NULL, TRUE),
(266, 'WY03', NULL, 'Konstal',   '112N',    1995, 'OKW', NULL, TRUE),
(267, 'WY04', NULL, 'Konstal',   '112N',    1995, 'OKW', NULL, TRUE),
(268, 'WY05', NULL, 'Konstal',   '112N',    1995, 'OKW', NULL, TRUE),
(269, 'WY06', NULL, 'Konstal',   '112N',    1995, 'OKW', NULL, TRUE),
(270, 'WY07', NULL, 'Škoda',     '16T',     2007, 'OKW', NULL, TRUE),
(271, 'WY08', NULL, 'Škoda',     '16T',     2007, 'OKW', NULL, TRUE),

-- koniec wartości
;
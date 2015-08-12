-- Create internal "system" user

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` ( `id`, `name`, `password`, `admin` ) VALUES ( 1, "system", "", 1 );
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

-- Create entries for the open-class annotation layers

INSERT INTO `tagset` (`name`, `class`, `set_type`) VALUES
       ('Normalization', 'norm',  'open'),
       ('Modernization', 'mod',   'open'),
       ('Lemma',         'lemma', 'open');
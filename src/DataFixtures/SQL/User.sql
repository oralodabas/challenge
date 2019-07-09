SET FOREIGN_KEY_CHECKS = 0;
    TRUNCATE TABLE `user`;
    INSERT INTO `user` (`id`,`user_name`,`password`)
    VALUES
    (1,'zingat','7110eda4d09e062aa5e4a390b0a572ac0d2c0220'),
    (2,'oral','7110eda4d09e062aa5e4a390b0a572ac0d2c0220');
    SET FOREIGN_KEY_CHECKS = 1;
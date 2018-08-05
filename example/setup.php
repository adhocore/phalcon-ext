<?php

/*
 * This file is part of the PHALCON-EXT package.
 *
 * (c) Jitendra Adhikari <jiten.adhikary@gmail.com>
 *     <https//:github.com/adhocore>
 *
 * Licensed under MIT license.
 */

$di = require __DIR__ . '/bootstrap.php';
$db = $di->get('db');

$db->execute('
CREATE TABLE IF NOT EXISTS tests (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  prop_a VARCHAR(25),
  prop_b VARCHAR(255),
  prop_c VARCHAR(10)
)');

$db->execute('
CREATE TABLE IF NOT EXISTS users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  username VARCHAR(25),
  password VARCHAR(100),
  scopes VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)');

$db->execute('
CREATE TABLE IF NOT EXISTS tokens (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER,
  type VARCHAR(25),
  token VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  expire_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY(user_id) REFERENCES users(id)
)');

$db->execute('DELETE FROM tests');
$db->execute('DELETE FROM SQLITE_SEQUENCE WHERE name = "tests"');

$db->execute('DELETE FROM users');
$db->execute('DELETE FROM SQLITE_SEQUENCE WHERE name = "users"');

$db->execute('DELETE FROM tokens');
$db->execute('DELETE FROM SQLITE_SEQUENCE WHERE name = "tokens"');

<?php
/*
 * Copyright (C) 2015 Marcel Bollmann <bollmann@linguistics.rub.de>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of
 * this software and associated documentation files (the "Software"), to deal in
 * the Software without restriction, including without limitation the rights to
 * use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
 * the Software, and to permit persons to whom the Software is furnished to do so,
 * subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
 * FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 * COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
 * IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
 * CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */ ?>
<?php
/** @file config.defaults.php
 * Default configuration options for CorA.
 *
 * =============================================================================
 * DO NOT EDIT THIS FILE!
 * =============================================================================
 * Instead, create or modify a file named "config.php" in the same directory
 * and adjust the configuration options there.  Make sure that "config.php"
 * starts with "<?php" and returns the configuration options as an array, just
 * like this file.  If you are unsure, just clone the contents of this file
 * and edit the options as you see fit.
 * =============================================================================
 * DO NOT EDIT THIS FILE!
 * =============================================================================
 */

// do not remove the following line
if (!defined('CORA_CONFIG_FILE')) { return; }

return array(
  /** This array should contain all the info required to connect to a CorA
  database instance. */
  "dbinfo" => array(
    /** The database server to connect to. */
    "HOST" => '127.0.0.1',
    /** The username for database login. */
    "USER" => 'cora',
    /** The password for database login. */
    "PASSWORD" => 'trustthetext',
    /** The name of the database. */
    "DBNAME" => 'cora'
  ),

  /** Person to contact in case of problems; will show up on the "Help" page. */
  "local_maintainer" => array(
    /** At least one of 'name' or 'email' should be filled. */
    "name" => "",
    "email" => ""
  ),

  /** Default interface language for new users. */
  "default_language" => 'en-US',

  /** Directory to store external parametrizations for automatic annotators
      (e.g., tagger parametrizations that have been learned from certain
      projects. */
  "external_param_dir" => '/var/lib/cora/',

  /** Cost of the password-encryption algorithm. */
  "password_cost" => 12,

  /** Options describing this CorA instance. */
  "title" => "CorA",
  "longtitle" => "Corpus Annotator",
  "description" => "A corpus annotation tool for non-standard language varieties.",

  /** PHP session name; affects cookie name in browser. */
  "session_name" => 'PHPSESSID_CORA',

  /** Suffix for database user/password/name used for unit tests. */
  "test_suffix" => "test"
);

?>

<?php
//Sample Usage
require __DIR__ . '/vendor/autoload.php';

if (file_exists('.env')) {
  $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
  $dotenv->load();
}

define("ENV", getenv("ENV"));
define("SITE_EMAIL", getenv("SITE_EMAIL"));
define("AWS_KEY_ID", getenv("AWS_KEY_ID"));
define("AWS_KEY_SECRET", getenv("AWS_KEY_SECRET"));
define("AWS_REGION", getenv("AWS_REGION"));

define("SMTP_HOST", getenv("SMTP_HOST"));
define("SMTP_PORT", getenv("SMTP_PORT"));
define("SMTP_USERNAME", getenv("SMTP_USERNAME"));
define("SMTP_PASSPORT", getenv("SMTP_PASSPORT"));
define("SMTP_SECURE", getenv("SMTP_SECURE"));

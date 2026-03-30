<?php
// logout.php
require_once 'config.php';
configure_secure_session();
session_start();
session_destroy();
header("Location: index.php");
exit;

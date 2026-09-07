<?php
session_start();
session_unset();
session_destroy();
header('Location: /SAINTARA/login.php');
exit;
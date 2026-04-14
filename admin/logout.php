<?php
require_once '../includes/auth_functions.php';
logoutUser();
header('Location: adminlogin.php');
exit();
?>

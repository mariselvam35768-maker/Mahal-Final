<?php
require_once 'includes/auth_functions.php';
logoutUser();
header('Location: index.php');
exit();
?>

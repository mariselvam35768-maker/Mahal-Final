<?php
require_once '../includes/auth_functions.php';

if (isLoggedIn() && isAdmin()) {
    header('Location: dashboard.php');
} else {
    header('Location: adminlogin.php');
}
exit();
?>

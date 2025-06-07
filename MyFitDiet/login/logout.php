<?php
session_start(); 


$_SESSION = array();

session_destroy();

header("Location: /MyFitDiet/login/login.php");
exit();
?>
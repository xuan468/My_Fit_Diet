<?php
$hostname = 'localhost'; 
$user = 'root';
$password = '';
$database  = 'myfitdiet';

$connection = mysqli_connect($hostname, $user, $password, $database);

if ($connection === false) {
    die('Connection failed!' . mysqli_connect_error());
}
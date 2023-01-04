<?php
  ob_start();

  // on va verifier si notre session est active

  if(!isset($_SESSION)) {
    session_start();
  }

  // connexion a ma database

  $host = 'localhost';
  $username = 'root';
  $pwd = '';
  $dbname = 'authenticate';
  
  $conn = mysqli_connect($host,$username,$pwd,$dbname) or die("la connexion a la base de données n'a pas pu être établie")

?>
<?php

include './config/db.php';

global $email_verified, $email_already_verified, $activation_error;

if (!empty($_GET['token'])) {
    $token = $_GET['token'];
} else {
    $token = '';
}

if ($token != '') {
    $sqlQuery = mysqli_query($conn, "SELECT * FROM users WHERE token = '$token'");
    $countRow = mysqli_num_rows($sqlQuery);
    echo $countRow;
    if ($countRow == 1) {
        while ($rowData = mysqli_fetch_array($sqlQuery)) {
            $is_active = $rowData['is_active'];
            if ($is_active == 0) {
                $sql_update = mysqli_query($conn, "UPDATE users SET is_active = '1' WHERE token = '$token'");
                if ($sql_update) {
                    $email_verified = '<div class="alert alert-success">l\'email a été vérifié avec succés.</div>';
                }
            } else {
                $email_already_verified = '<div class="alert alert-danger">l\'email a déjà été vérifié</div>';
            }
        }
    } else {
        $activation_error = '<div class="alert alert-danger">Erreur d\'activation de votre email.</div>';
    }
}

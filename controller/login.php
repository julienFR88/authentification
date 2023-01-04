<?php

include('./config/db.php');

global $wrongPwdErr, $accountNotExistErr, $emailPwdErr, $verificationRequiredErr, $email_empty_err, $pass_empty_err;

if (isset($_POST['login'])) {
  $email_signin = $_POST['email_signin'];
  $password_signin = $_POST['password_signin'];

  $user_email = filter_var($email_signin, FILTER_SANITIZE_EMAIL);
  $pswd = mysqli_real_escape_string($conn, $password_signin);

  $sql = "SELECT * FROM users WHERE email = '$email_signin'";
  $query = mysqli_query($conn, $sql);
  $rowCount = mysqli_num_rows($query);

  if (!$query) {
    die("connexion failed" . mysqli_error($conn));
  }

  if (!empty($email_signin) && !empty($password_signin)) {
    if (preg_match("/^(?=.*\d)(?=.*[A-Z])(?=.*[a-z])(?=.*[^\w\d])[^ ]{6,20}$/", $_pswd)) {
      $wrongPwdErr = 'div class="alert alert-danger">
                        Le mot de passe doit contenir au moins 6 caractères, dont une lettre maj, une lettre min, un chiffre et un caractère spécial
                      </div>';
    }
    if ($rowCount <= 0) {
      $accountNotExistErr = 'div class="alert alert-danger">
                              Le mot de passe doit contenir au moins 6 caractères, dont une lettre maj, une lettre min, un chiffre et un caractère spécial
                            </div>';
    } else {
      while ($row = mysqli_fetch_array($query)) {
        $id             = $row['id'];
        $firstname      = $row['firstname'];
        $lastname       = $row['lastname'];
        $email          = $row['email'];
        $mobilenumber   = $row['mobilenumber'];
        $pass_word      = $row['password'];
        $token          = $row['token'];
        $is_active      = $row['is_active'];
      }
      $password = password_verify($password_signin, $pass_word);

      if ($is_active == '1') {
        if ($email == $email_signin && $password == $password_signin) {
          header('Location: ./dashboard.php');

          $_SESSION['id'] = $id;
          $_SESSION['firstname'] = $firstname;
          $_SESSION['lastname'] = $lastname;
          $_SESSION['email'] = $email;
          $_SESSION['mobilenumber'] = $mobilenumber;
          $_SESSION['token'] = $token;

          echo $lastname;
        } else {
          $emailPwdErr = '<div class="alert alert-danger">
                            l\'email ou le mot de passe est incorrecte.
                          </div>';
        }   
      } else {
        $verificationRequiredErr = '<div class="alert alert-danger">
                                      Account verification is required for login
                                    </div>';
      }
    }
  } else {
    if (empty($email_signing)) {
      $email_empty_err = '<div class="alert alert-danger">
                            Email not provided
                          </div>';
    }
    if (empty($password_signing)) {
      $pass_empty_err = '<div class="alert alert-danger">
                            Password not provided
                          </div>';
    }
  }
}

?>
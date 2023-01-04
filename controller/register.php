<?php
// var_dump($_POST);die;
//on doit appeler le fichier de connexion à la DB
include('config/db.php');

//on installe swiftmailer
require_once('lib/vendor/autoload.php');

//on va traquer les message d'erreur et les success
global $success_msg, $email_exist, $f_NameErr, $l_NameErr, $_emailErr, $_mobileErr, $_passwordErr, $captcha, $w_recaptcha;
global $fNameEmptyErr, $lNameEmptyErr, $emailEmptyErr, $mobileEmptyErr, $passwordEmptyErr, $email_verify_err, $email_verify_success;

//On va définir la variable du formulaire de mappage de validation
$_first_name = $_last_name = $_email = $_mobile_number = $_password = "";

//On va aller vérifier si notre bouton submit est bien soumis
if (isset($_POST["submit"])) {
  $firstname = $_POST['firstname'];
  $lastname = $_POST['lastname'];
  $email = $_POST['email'];
  $mobilenumber = $_POST['mobilenumber'];
  $password = $_POST['password'];

  //on va vérifier si l'email n'est pas déjà utiliser
  $email_check_query = mysqli_query($conn, "SELECT * FROM users WHERE email = '{$email}'");

  //on va vérifier le resultat de notre query
  $rowCount = mysqli_num_rows($email_check_query);

  //validation en php
  //je vérifie que mes champs ne sont pas empty (vide)
  if (!empty($firstname) && !empty($lastname) && !empty($email) && !empty($mobilenumber) && !empty($password)) {

    //verifie si l'user existe déjà
    if ($rowCount > 0) {
      $email_exist = '<div class="alert alert-danger" role="alert">
                        Un utilisateur avec ce mail existe déjà.
                      </div>';
    } else {
      // on nettoie les données avt de les insérer dans la db
      // mysqli_real_escape_string() pour protéger les caractères spéciaux pour les utiliser exclusivement dans une requête sql
      $_first_name = mysqli_real_escape_string($conn, $firstname);
      $_last_name = mysqli_real_escape_string($conn, $lastname);
      $_email = mysqli_real_escape_string($conn, $email);
      $_mobile_number = mysqli_real_escape_string($conn, $mobilenumber);
      $_password = mysqli_real_escape_string($conn, $password);
      
      // on va verifier que tout nos champs correspondent à ce qu'on souhaite en entrée
      if (!preg_match("/^[a-zA-Z]*$/", $_first_name)) {
        $f_NameError = '<div class="alert alert-danger" role="alert">
                                  Seule les lettres et les espaces sont autorisées
                                </div>';
      }
      if (!preg_match("/^[a-zA-Z]*$/", $_last_name)) {
        $f_NameError = '<div class="alert alert-danger" role="alert">
                                  Seule les lettres et les espaces sont autorisées
                                </div>';
      }
      // on va verifier que nos emails sont correct
      if (!filter_var($_email, FILTER_VALIDATE_EMAIL)) {
        $_emailErr = '<div class="alert alert-danger" role="alert">
                                Le format de votrer email est incorrect
                              </div>';
      }
      // on verifie le n° de téléphone
      if (!preg_match("/^[0-9]{10}+$/", $_mobile_number)) {
        $_mobileErr = '<div class="alert alert-danger" role="alert">
                                  Entrer votre n° avec 10 chiffres
                              </div>';
      }
      // On va vérifier le format du mot de passe
      if (!preg_match("/^(?=.*\d)(?=.*[A-Z])(?=.*[a-z])(?=.*[^\w\d])[^ ]{6,20}$/", $_password)) {
        $_passwordError = '  <div class="alert alert-danger" role="alert">
                                  ENTREE ENTRE 6 ET 20 CARACTERE AVEC 1 CARACTERE SPECIAL ET MAGUSCULE ET MINUSCULE
                                </div>   
                              ';
      }

      // on envoie dans la db si toutes les conditions sotn correctes
      if ((preg_match("/^[a-zA-Z]*$/", $_first_name)) && (preg_match("/^[a-zA-Z]*$/", $_last_name)) && (filter_var($_email, FILTER_VALIDATE_EMAIL)) && (preg_match("/^[0-9]{10}+$/", $_mobile_number)) && (preg_match("/^(?=.*\d)(?=.*[A-Z])(?=.*[a-z])(?=.*[^\w\d])[^ ]{6,20}$/", $_password))) {

        // on envoie un email au client pour qu'il puisse valider les infos
        // on génére un token aléatoire en fonction de la function time() de php
        $token = md5(rand().time());
        echo $token;
        // on va hacher le mdp
        $_password_hash = password_hash($password, PASSWORD_BCRYPT);
        echo $_password_hash;
        // reqûête SQL
        $sql = "INSERT INTO users (firstname, lastname, email, mobilenumber, password, token, is_active, date_time)
                  VALUES('{$_first_name}','{$_last_name}','{$_email}','{$_mobile_number}','{$_password_hash}','{$token}','0', now())";
        
        // on va écrire notre clé secrete de google "https://www.google.com/recaptcha/about/"
        $secretKey = "6LdcO84jAAAAAOTj_SEbLQqZMogTi7j1xowejPgo";
        $ip = $_SERVER['REMOTE_ADDR'];
        $url = 'https://www.google.com/recaptcha/api/siteverify?secret='.urlencode($secretKey).'&response='.urlencode($_POST['g-recaptcha-response']);
        $response = file_get_contents($url);
        $responseKey = json_decode($response, true);

        // je retourne le sucess de mon json responseKey
        if ($responseKey["success"]) {
          // on envoie la requête dans la db
          $sql_query = mysqli_query($conn, $sql);
        } else {
          $w_recaptcha = '<div class="alert alert-danger" role="alert">
                            va te faire ... tu es un Robot
                          </div>';
        } 
        
        // on vérifie si notre insertion contient une erreur
        if (!$sql_query) {
          die('mysql failed : '. mysqli_error($conn));
        }
        // on envoie un email a l'utilisateur
        if (!$sql_query) {
          $msg = 'cliquer sur ce lien pour valider votre compte <br><br>
          <a href="user_verification.php?token='.$token.'">cliquez ici</a>';

          // on va créer notre transporteur de mails
          $transport = (new Swift_SmtpTransport('smtp.gmail.com', 465, 'ssl'))
          ->setUsername('avec0235448933@gmail.com')
          ->setPassword('ogdo7251+');

          // on va configurer notre système d'envoi de mail
          $mailer = new Swift_Mailer($transport);
          
          // on va créer notre message
          $message = (new Swift_Message('valider votre email svp'))
              ->setFrom([$_email => $_first_name.' '.$_last_name])
              ->setTo([$_email])
              ->addPart($msg, "text/html")
              ->setBody($msg);
          $result = $mailer->send($message);
          if (!$result) {
            $email_verify_err = '<div class="alert alert-danger" role="alert">
                                  échec d\'envoi. Merci de réessayer
                                </div>';
          } else {
            $email_verify_success = '<div class="alert alert-success" role="alert">
                                        Email envoyé avec succés
                                    </div>'; 
          }
        }
      }
    }
  } else {
    if(empty($firstname)){
      $fNameEmptyErr = '<div class="alert alert-danger">
          First name can not be blank.
      </div>';
    }
    if(empty($lastname)){
      $lNameEmptyErr = '<div class="alert alert-danger">
      Last name can not be blank.
      </div>';
    }
    if(empty($email)){
      $emailEmptyErr = '<div class="alert alert-danger">
      Email can not be blank.
      </div>';
    }
    if(empty($mobilenumber)){
      $mobileEmptyErr = '<div class="alert alert-danger">
      Mobile number can not be blank.
      </div>';
    }
    if(empty($password)){
      $passwordEmptyErr = '<div class="alert alert-danger">
      Password can not be blank.
      </div>';
    }
    if (isset($_POST['g-recaptcha-response'])) {
      $captcha = '<div class="alert alert-danger" role="alert">
                    Veuillez cocher le Captcha de Google
                  </div>';
    }
  }
}

?>
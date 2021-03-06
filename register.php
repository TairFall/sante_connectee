<?php
session_start();
require('vendor/autoload.php');
require('inc/pdo.php');
require('inc/function.php');

if(isLoggedAdmin() || isLoggedUser()) {
  header('Location: 403.php');
  exit(); }
?>


<?php

$errors = array();
if(!empty($_POST['submitted'])){

  // faille XSS

  $nom = cleanXss($_POST['nom']);
  $prenom = cleanXss($_POST['prenom']);
  $email = cleanXss($_POST['email']);
  $civilitee = cleanXss($_POST['civilitee']);
  $datenaissance = cleanXss($_POST['datenaissance']);
  $password = cleanXss($_POST['password']);
  $password2 = cleanXss($_POST['password2']);


  $errors = validText($errors, $nom, 'nom',2,50);
  $errors = validText($errors, $prenom, 'prenom',2,50);
  $errors = validText($errors, $civilitee, 'civilitee',4,10);
  $errors = validMail($errors, $email, 'email');
  $errors = validPass($errors,$password,'password',$password2,2,100);
  $errors = validDate($errors,$datenaissance,'datenaissance');



  if(count($errors) == 0){

    $sql = "SELECT id FROM nf_users WHERE email = :email";
    $query = $pdo->prepare($sql);
    $query->bindValue(':email',$email,PDO::PARAM_STR);
    $query->execute();
    $emailExist = $query->fetch();

    // SI LE MAIL EXISTE DEJA IL Y A UNE ERREUR OR UN COMPTE SE CREE QUE SI ERREUR = 0
    if(!empty($emailExist)){
      $errors['email'] = 'ce mail existe déjà';
    }

    if(count($errors) == 0){

      // GENERATE TOKEN
      $token = generateRandomString(120);

      $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
      $dotenv->load();

      // CODE SECRET A METTRE DANS LE MOT DE PASSE POUR ETRE ADMIN
      $secretadmin = $_ENV['SECRET_ADMIN'];
      $adminpass = false;

      // SI LE MOT DE PASSE CONTIENT LE CODE SECRET ALORS ROLE DEVIENT ADMIN A LA CREATION DU COMPTE
      if (strpos($password, $secretadmin) !== false) {
        $adminpass = true;
        $password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO nf_users (nom,prenom,civilitee,date_naissance,email,role,password,created_at,token,token_at) VALUES (:nom,:prenom,:civilitee,:datenaissance,:email,'admin',:password,NOW(),'$token',NOW())";

      // SI LE MOT DE PASSE NE CONTIENT PAS LE CODE SECRET ALORS LE ROLE DEVIENT USER (ROLE PAR DEFAULT EST USER)
      }
      else {
        $password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO nf_users (nom,prenom,civilitee,date_naissance,email,password,created_at,token,token_at) VALUES (:nom,:prenom,:civilitee,:datenaissance,:email,:password,NOW(),'$token',NOW())";
      }

      // INSERT
      $query = $pdo->prepare($sql);
      $query->bindValue(':nom',$nom,PDO::PARAM_STR);
      $query->bindValue(':prenom',$prenom,PDO::PARAM_STR);
      $query->bindValue(':civilitee',$civilitee,PDO::PARAM_STR);
      $query->bindValue(':datenaissance',$datenaissance,PDO::PARAM_STR);
      $query->bindValue(':email',$email,PDO::PARAM_STR);
      $query->bindValue(':password',$password,PDO::PARAM_STR);
      $query->execute();


      // Si c'est un user non admin il est dirigé vers une page de validation
      if (empty($adminpass)) {
        header('Location: valid_register.php?id='.$token.'');
        exit();

      // ELSE si c'est un admin il n'a pas besoin de valider son compte (plus rapide pour bosser en dev)
      }
      else {
        header('Location: login.php');
        exit();
      }
    }
  }
}
 ?>

<?php
include('inc/header.php'); ?>

<section id="section-password" class="format">



<form class="form" action="" method="post">
  <!-- PRENOM -->
  <h1>Inscription</h1>
  <div class="flexbox">
  <div class="">
    <label id="prenom"  for="prenom">Prénom : </label>
    <input class="inputa" type="text" name="prenom" placeholder="Prénom" value="<?php if(!empty($_POST['prenom'])) { echo $_POST['prenom']; } ?>">
    <span class="error"><?php if(!empty($errors['prenom'])) { echo $errors['prenom']; } ?>&nbsp;</span>
  </div>

  <!-- NOM -->
  <div class="">
    <label id="nom" for="nom">Nom :</label>
    <input type="text" name="nom" placeholder="Nom" value="<?php if(!empty($_POST['nom'])) { echo $_POST['nom']; } ?>">
    <span class="error"><?php if(!empty($errors['nom'])) { echo $errors['nom']; } ?>&nbsp;</span>
  </div>

  <!-- CIVILITEE -->
  <div class="">
    <label id="civilitee" for="civlitee">Civilitée :</label>
    <select name="civilitee">
      <option value="">Veuillez choisir une option</option>
      <option value="Homme" <?php if(!empty($_POST['civilitee']) && $_POST['civilitee'] == 'Homme') { echo 'selected'; } ?> >Homme</option>
      <option value="Femme" <?php if(!empty($_POST['civilitee']) && $_POST['civilitee'] == 'Femme') { echo 'selected'; } ?> >Femme</option>
      <option value="Transgenre" <?php if(!empty($_POST['civilitee']) && $_POST['civilitee'] == 'Transgenre') { echo 'selected'; } ?>>Transgenre</option>
    </select>
    <span class="error"><?php if(!empty($errors['civilitee'])) { echo $errors['civilitee']; } ?>&nbsp;</span>
  </div>

  <!-- DATE NAISSANCE -->
  <div class="">
    <label id="datenaissance" for="datenaissance">Date de naissance :</label>
    <input type="date" name="datenaissance" max="9999-12-31" value="<?php if(!empty($_POST['datenaissance'])) { echo $_POST['datenaissance']; } ?>">
    <span class="error"><?php if(!empty($errors['datenaissance'])) { echo $errors['datenaissance']; } ?>&nbsp;</span>
  </div>

  <!-- EMAIL -->
  <div class="">
    <label id="email" for="email">Email :</label>
    <input type="text" placeholder="Email" name="email"  value="<?php if(!empty($_POST['email'])) { echo $_POST['email']; } ?>">
    <span class="error"><?php if(!empty($errors['email'])) { echo $errors['email']; } ?>&nbsp;</span>
  </div>

  <!-- PASSWORD -->
  <div class="">
    <label for="password">Mot de passe :</label>
    <input type="password" name="password" placeholder="Mot de passe" value="<?php if(!empty($_POST['password'])) { echo $_POST['password']; } ?>">
    <span class="error"><?php if(!empty($errors['password'])) { echo $errors['password']; } ?>&nbsp;</span>
  </div>
  <div class="null">

  </div>
  <!-- CONFIRM PASSWORD -->

  <div class="">
    <label class="debug1" for="password2">Confirmez votre mot de passe :</label>
    <input type="password" name="password2" placeholder="Confirmation Mot de passe" value="<?php if(!empty($_POST['password2'])) { echo $_POST['password2']; } ?>">
    <span><?php if(!empty($errors['password2'])) { echo $errors['password2']; } ?>&nbsp;</span>
  </div>
    </div>
  <!-- SUBMIT -->
  <input class="submit" type="submit" name="submitted" value="Envoyer">

</form>
</section>


<?php
include('inc/footer.php');

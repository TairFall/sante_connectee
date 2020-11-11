<?php

include('inc/pdo.php');
include('inc/function.php');
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
  // $errors = validDate().... ;
  $errors = validPass($errors,$password,'password',$password2,2,100);


  if(count($errors) == 0){

    $sql = "SELECT id FROM nf_users WHERE email = :email";
    $query = $pdo->prepare($sql);
    $query->bindValue(':email',$email,PDO::PARAM_STR);
    $query->execute();
    $emailExist = $query->fetch();

    if(!empty($emailExist)){
      $errors['email'] = 'ce mail existe déjà';
    }

    if(count($errors) == 0){
      // ON ENCODE LE PASSWORD


      // GENERATE TOKEN
      $token = generateRandomString(120);

      // DEFAUT ROLE IS USER
      // CODE SECRET A METTRE DANS LE MOT DE PASSE POUR ETRE ADMIN
        $secretadmin = '667';
        $adminpass = false;

        if (strpos($password, $secretadmin) !== false) {
        $adminpass = true;
        $password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO nf_users (nom,prenom,civilitee,date_naissance,email,role,password,created_at,token,token_at) VALUES (:nom,:prenom,:civilitee,:datenaissance,:email,'admin',:password,NOW(),'$token',NOW())";
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

      if (empty($adminpass)) {
        header('Location: valid_register.php?id='.$token.'');
        exit();

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


<div class="wrap">
  <h1>Inscription</h1>
  <p>Créez votre compte</p>
</div>

<form class="" action="" method="post">
  <!-- PRENOM -->
  <label id="prenom" for="prenom">Prénom</label>
  <input type="text" name="prenom" value="<?php if(!empty($_POST['prenom'])) { echo $_POST['prenom']; } ?>">
  <span class="error"><?php if(!empty($errors['prenom'])) { echo $errors['prenom']; } ?></span>

  <!-- NOM -->
  <label id="nom" for="nom">Nom</label>
  <input type="text" name="nom" value="<?php if(!empty($_POST['nom'])) { echo $_POST['nom']; } ?>">
  <span class="error"><?php if(!empty($errors['nom'])) { echo $errors['nom']; } ?></span>

  <!-- CIVILITEE -->
  <label id="civilitee" for="civlitee">Civilitée</label>
  <select name="civilitee">
    <option value="">--Veuillez choisir une option--</option>
    <option value="Homme">Homme</option>
    <option value="Femme">Femme</option>
    <option value="Transgenre">Transgenre</option>
  </select>
  <span class="error"><?php if(!empty($errors['civilitee'])) { echo $errors['civilitee']; } ?></span>

  <!-- DATE NAISSANCE -->
  <label id="datenaissance" for="datenaissance">Date de naissance</label>
  <input type="date" name="datenaissance" value="<?php if(!empty($_POST['datenaissance'])) { echo $_POST['datenaissance']; } ?>">
  <span class="error"><?php if(!empty($errors['datenaissance'])) { echo $errors['datenaissance']; } ?></span>

  <!-- EMAIL -->
  <label id="email" for="email">Email</label>
  <input type="text" name="email" value="<?php if(!empty($_POST['email'])) { echo $_POST['email']; } ?>">
  <span><?php if(!empty($errors['email'])) { echo $errors['email']; } ?></span>

  <!-- PASSWORD -->
  <label for="password">Mot de passe</label>
  <input type="password" name="password" value="<?php if(!empty($_POST['password'])) { echo $_POST['password']; } ?>">
  <span><?php if(!empty($errors['password'])) { echo $errors['password']; } ?></span>

  <!-- CONFIRM PASSWORD -->
  <label for="password2">Confirmez le mot de passe</label>
  <input type="password" name="password2" value="<?php if(!empty($_POST['password2'])) { echo $_POST['password2']; } ?>">
  <span><?php if(!empty($errors['password2'])) { echo $errors['password2']; } ?></span>

  <!-- SUBMIT -->
  <input type="submit" name="submitted" value="GO">

</form>



<?php
include('inc/footer.php');
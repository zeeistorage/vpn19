<?php 
include("includes/controller.php");
if($session->isAdmin()){
    header("Location: ".$configs->homePage());
    exit;
}
$form = new Form;
?>
<!DOCTYPE html>
<html>
    <head>
        <title>VPN Pro 2019</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <link href="css/bootstrap.min.css" rel="stylesheet">
        <link href="fonts/Open Iconic/css/open-iconic-bootstrap.min.css" rel="stylesheet">

       
        <link href="css/main.css" rel="stylesheet">
        
    </head>

    <body>

<div class="login-page">
  <div class="form">
   <img src="images/logo.png"/>
<br><br><br>
    <form action="includes/process.php" method="POST" class="login-form">

      <input type="text" name="email" placeholder="username" value="<?php echo Form::value("email"); ?>"/>
 <?php if(Form::error("email")) { echo "<div class='help-block' id='user-error'>".Form::error('email')."</div>"; } ?>
      <input type="password" name="encrypted_password" placeholder="Password" value="<?php echo Form::value("encrypted_password"); ?>"/>
<?php if(Form::error("encrypted_password")) { echo "<div class='help-block' id='pass-error'>".Form::error('encrypted_password')."</div>"; } ?>
      <button>Login</button>
<input type="hidden" name="form_submission" value="adminlogin">
    </form>
  </div>
</div>


        <div class="module form-module">
        
           
        <div class="text-muted text-center" id="login-footer">
            <font color="white"> <small>© 2019 VPN Pro 2019. All rights reserved.</small></font>
        </div>

        <script src="js/jquery-2.1.3.min.js"></script>
        <script src="js/jquery-ui.js"></script>
        <script src="js/bootstrap.min.js"></script>
        <script src="js/admin-login.js"></script>
        
        <script>$(function(){ Login.init(); });</script>
        
        <script>
        $('[data-toggle="tooltip"], .show-tooltip').tooltip({container: 'body', animation: false});        
        </script>

    </body>
</html>

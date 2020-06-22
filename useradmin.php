<?php 
include("includes/controller.php");
$pagename = 'useradmin';
$container = '';
if(!$session->isAdmin()){
    header("Location: ".$configs->homePage());
    exit;
}
else{
?>
<!DOCTYPE html>
<html>
    <head>
        <title>VPN Pro 2019</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <link href="css/bootstrap.min.css" rel="stylesheet">
        <link href="fonts/Open Iconic/css/open-iconic-bootstrap.min.css" rel="stylesheet">
        <link href="fonts/font-awesome/css/font-awesome.min.css" rel="stylesheet">

        <link href="css/navigation.css" rel="stylesheet">
        <link href="css/style.css" rel="stylesheet">
        <link href="css/animation.css" rel="stylesheet">
       
        <link href="css/plugins/datatables/dataTables.bootstrap.min.css" rel="stylesheet">     
        
    </head>
    <body>
        <div id="page-wrapper">

            <nav id="side-menu" class="navbar-default navbar-static-side" role="navigation">
                <div id="sidebar-collapse">
                    <div id="logo-element">
                        <a class="logo" href="index.php">
                         <img src="images/logo.png"/>
                        </a>
                    </div>
<br>
                    <?php include('navigation.php'); ?>
                </div>
            </nav>

            <?php include('top-navbar.php'); ?>    

            <div id="page-content" class="gray-bg">

                <div class="title-header white-bg">
                    <i class="oi oi-star"></i>
                    <h2>User Admin</h2>
                    <ol class="breadcrumb">
                        <li>
                            <a href="index.php">Home</a>
                        </li>
                        <li class="active">
                            User Admin
                        </li>
                    </ol>
                </div>
                
                <div class="row">                                     
                    <div class="col-sm-12 col-md-12">
                        <div class="panel">
                            <div class="panel-body">
                                <h4><strong>User Admin</strong></h4>
                            </div>
                        </div>
                    </div>                                     
                </div>
             
                <div class="row">   
                      
                    <div class="col-md-9 col-lg-10">
                        <div class="panel">
                            <div class="panel-heading">
                                <h2 class="panel-title">User's Table</h2>
                            </div>
                            <div class="panel-body table-responsive">
                                <table class="table table-striped table-bordered table-hover" id="dataTable">
                                        <thead>
                                            <tr>
                                                <th>Email</th>
                                                <th>Status</th>
                                               
                                                <th>Registered</th>
                                                <th>Last Login</th>
                                                <th class='text-center'>View</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $sql = "SELECT * FROM users WHERE email != '" . ADMIN_NAME . "'";
                                            $result = $db->prepare($sql);
                                            $result->execute();
                                            while ($row = $result->fetch()) {
                                                $email = $row['email'];
                                                $email = strlen($email) > 25 ? substr($email, 0, 25) . "..." : $email;
                                                $lastlogin = $adminfunctions->displayDate($row['timestamp']);
                                                $reg = $adminfunctions->displayDate($row['created_at']);

                                                echo "<tr><td><a href='adminuseredit.php?usertoedit=" . $row['email'] . "'>" . $row['email'] . "</a></td>"
                                                . "<td>" . $adminfunctions->displayStatus($row['email']) . "</td>"
                                                . "<td>" . $reg . "</td><td>" . $lastlogin . "</td>"
                                                . "<td class='text-center'><div class='btn-group btn-group-xs'><a href='adminuseredit.php?usertoedit=" . $row['email'] . "' title='Edit' class='open_modal btn btn-default'><i class='oi oi-pencil'></i> View</a></td>"
                                                . "</tr>";
                                            }
                                            ?>
                                        </tbody>
                                </table>
                            </div>
                        </div>
                    </div>                                   
                </div>
                
                <?php
                $orderby = 'created_at';
                $result2 = $adminfunctions->displayAdminActivation($orderby);
                ?>
               
			   
			   
			   
			   
			   
                
                <!-- Modal -->
                <div class="modal fade" id="createUser" class="modal" tabindex="-1" role="dialog" aria-labelledby="createUser" aria-hidden="true">
                    <div class="modal-dialog">
                            <div class="modal-content" id="modal-content">
                                <form class="form-horizontal" id="admin-create-user" action="includes/adminprocess.php" method="POST" role="form">
                                    <div class="modal-header">
                                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                                        <h4 class="modal-title" id="myModalLabel">Create New User</h4>
                                    </div>
                                    <div class="modal-body">
                                        
                                        <div class="form-group <?php if (Form::error("user")) { echo 'has-error'; } ?>">
                                            <label for="inputemail" class="col-sm-4 control-label">email:</label>
                                            <div class="col-sm-7">
                                                <input name="user" type="text" class="form-control" id="inputemail" placeholder="email" value="<?php echo Form::value("user"); ?>">                            
                                            </div>
                                            <div class="col-sm-4">
                                                <small><?php echo Form::error("user"); ?></small>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group <?php if(Form::error("firstname")){ echo 'has-error'; } ?> ">
                                            <label for="inputFirstname" class="col-sm-4 control-label">First Name:</label>
                                            <div class="col-sm-7">
                                                <input type="text" name="firstname" class="form-control" id="inputFirstname" placeholder="First Name" value="<?php echo Form::value("firstname"); ?>">                             
                                            </div>
                                            <div class="col-sm-4">
                                                <small><?php echo Form::error("firstname"); ?></small>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group <?php if(Form::error("lastname")){ echo 'has-error'; } ?>">
                                            <label for="inputLastname" class="col-sm-4 control-label">Last Name:</label>
                                            <div class="col-sm-7">
                                                <input type="text" name="lastname" class="form-control" id="inputLastname" placeholder="Last Name" value="<?php echo Form::value("lastname"); ?>">
                                            </div>
                                            <div class="col-sm-4">
                                                <small><?php echo Form::error("lastname"); ?></small>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group <?php if(Form::error("pass")){ echo 'has-error'; } ?>">
                                            <label for="inputPassword" class="col-sm-4 control-label">New Password:</label>
                                            <div class="col-sm-7">
                                                <input type="password" name="pass" class="form-control" id="inputPassword" placeholder="New Password">
                                            </div>
                                            <div class="col-sm-4">
                                                <small><?php echo Form::error("pass"); ?></small>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group <?php if(Form::error("conf_newpass")){ echo 'has-error'; } ?>">
                                            <label for="confirmPassword" class="col-sm-4 control-label">Confirm Password:</label>
                                            <div class="col-sm-7">
                                                <input type="password" name="conf_pass" class="form-control" id="confirmPassword" placeholder="Confirm Password">
                                            </div>
                                            <div class="col-sm-4">
                                                <small><?php echo Form::error("pass"); ?></small>
                                            </div>
                                        </div>
                                      
                                        
                                        <div class="form-group <?php if(Form::error("email")){ echo 'has-error'; } ?>">
                                            <label for="conf_email" class="col-sm-4 control-label">Confirm E-mail:</label>
                                            <div class="col-sm-7">
                                                <input name="conf_email" type="text" id="conf_email" class="form-control" placeholder="Confirm Email" value="<?php echo Form::value("email"); ?>">
                                            </div>
                                            <div class="col-sm-4">
                                                <small><?php echo Form::error("email"); ?></small>
                                            </div>
                                        </div>

                                    <input type="hidden" name="form_submission" value="admin_registration">                                         

                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-primary" id="submit" >Create New User</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                <footer>Copyright &copy; <?php echo date("Y"); ?> <a href="" target="_blank">VPN Pro 2019</a> - All rights reserved.</footer>

            </div>

            <?php include('rightsidebar.php'); ?>

        </div>
        <a href="#" id="to-top" class="to-top"><i class="oi oi-chevron-top"></i></a>

        <script src="js/jquery-2.1.3.min.js"></script>
        <script src="js/bootstrap.min.js"></script>
        <script src="js/plugins/metisMenu/jquery.metisMenu.js"></script>
        <script src="js/xavier.js"></script>
        
        <script src="js/plugins/formValidation/useradminFormsValidation.js"></script>
        <script src="js/plugins/formValidation/jquery.validate.js"></script>
        <script>$(function() { FormsValidation.init(); });</script>    
        
        <script src="js/plugins/datatables/jquery.dataTables.min.js"></script>
        <script src="js/plugins/datatables/dataTables.bootstrap.min.js"></script>
        
        <script>
            $(document).ready(function () {
                $('#dataTable').dataTable();
            });

            $(document).ready(function () {
                $('#dataTable2').dataTable({         
                "order": [[ 1, "desc"]]
                });
            });
        </script>
        
        <script>
        $(function () {
            $('.checkall').on('click', function () {
            $(this).closest('table').find(':checkbox').prop('checked', this.checked);
            });
        });
        </script>        

    </body>
</html>
<?php
}
?>
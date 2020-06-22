<?php 
include("includes/controller.php");
$pagename = 'notification';
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
 
        <link href="css/plugins/chosen/chosen.css" rel="stylesheet">
        
    </head>
    <body>
        <div id="page-wrapper">

            <nav id="side-menu" class="navbar-default navbar-static-side" role="navigation">
                <div id="sidebar-collapse">
                    <div id="logo-element">
                        <a class="logo" href="index.php">
<img src="images/logo.png"/>
                           
                        </a>

                    </div><br>
                    <?php include('navigation.php'); ?>
                </div>
            </nav>

            <?php include('top-navbar.php'); ?>      

            <div id="page-content" class="gray-bg">

      
                <div class="title-header white-bg">
                    <i class="oi oi-star"></i>
                    <h2>Notification Management</h2>
                    <ol class="breadcrumb">
                        <li>
                            <a href="index.php">Home</a>
                        </li>
                        <li class="active">
                            Notification Management
                        </li>
                    </ol>
                </div>
                
                <div class="row">                                     
                    <div class="col-sm-12 col-md-12">
                        <div class="panel">
                            <div class="panel-body">
                                <h4><strong>Notification Management</strong> - send a message to all the users. </h4>
                            </div>
                        </div>
                    </div>                                     
                </div>
             
                <div class="row">
                    
                    <div class="col-sm-8 col-md-9 col-lg-10">
                        <div class="panel">
                            <div class="panel-heading">
                                <h2 class="panel-title">Notification Management</h2>
                            </div>
                            <div class="panel-body">
                                    <table class="table table-striped table-bordered table-hover" id="dataTable1">
                                       
 <?php
        // Enabling error reporting
        error_reporting(-1);
        ini_set('display_errors', 'On');
 
        require_once 'firebase.php';
        require_once 'push.php';
 
        $firebase = new Firebase();
        $push = new Push();
 
        // optional payload
        $payload = array();
        $payload['team'] = 'Nepal';
        $payload['score'] = '5.6';
 
        // notification title
        $title = isset($_GET['title']) ? $_GET['title'] : '';
         
        // notification message
        $message = isset($_GET['message']) ? $_GET['message'] : '';
         
        // push type - topic
        $push_type = isset($_GET['push_type']) ? $_GET['push_type'] : '';
         
        // Preview Image Url 
        $include_image = isset($_GET['include_image']) ? $_GET['include_image'] : '';
 
 
        $push->setTitle($title);
        $push->setMessage($message);
        $push->setImage($include_image);
        $push->setIsBackground(FALSE);
        $push->setPayload($payload);
 
 
        $json = '';
        $response = '';
 
        if ($push_type == 'topic') {
            $json = $push->getPush();
            $response = $firebase->sendToTopic('notificationnn', $json);
			
        } else if ($push_type == 'individual') {
            $json = $push->getPush();
            $regId = isset($_GET['regId']) ? $_GET['regId'] : '';
            $response = $firebase->send($regId, $json);
        }
        ?>
												
												
												
												
												
												
												
												
												
	
      
      
        <div></div><br></br>
     <div>

											
												
												
												 <form class="pure-form pure-form-stacked" method="get">
                <fieldset>
                    <legend>Send Notification</legend>
 
                  <label for="title1">Title : </label> 
                    <input type="text" id="title1" name="title" class="pure-input-1-2" placeholder="Enter title"><br>
 <br>
                    <label for="message1">Message : </label>
                    <textarea class="pure-input-1-2" name="message" id="message1" rows="3" placeholder="Notification message!"></textarea>
 <br><br>
 <label for="include_image">Image Url : </label>
                    <input type="text" id="include_image" name="include_image" class="pure-input-1-2" placeholder="Enter Image Url"><br>
 
                    <input type="hidden" name="push_type" value="topic"/>
					<br>
                    <button type="submit" class="pure-button pure-button-primary btn_send">Send Now </button>
                </fieldset>
            </form>
												
												
										
	
                                    </table>
                            </div>
                        </div>                    
                    </div>                                     
                </div>
                <div class="modal fade" id="createGroup" tabindex="-1" role="dialog" aria-labelledby="createGroup" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content" id="modal-content">
                            <form action="includes/adminprocess.php" id="user-groups-create" class="form-horizontal" method="post">
                                <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                                    <h4 class="modal-title" id="myModalLabel">Create a New Group</h4>
                                </div>
                                <div class="modal-body" id="modal-body">                                   
                                    <div class="form-group">
                                        <label for="group_name" class="col-sm-4 control-label">New Group Name : </label>
                                        <div class="col-md-8">
                                            <input type="text" id="group_name" name="group_name" class="form-control" placeholder="Group Name" />
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="sitedesc" class="col-sm-4 control-label">Assign Group Level : </label>
                                        <div class="col-md-8">
                                            <input type="text" name="group_level" class="form-control" placeholder="Group Level - Enter a number between 2 - 256" data-toggle="tooltip" data-placement="bottom" title="A Group Level is another means of protecting content. For example, protect pages from those in groups lower than level 5." />
                                        </div>
                                    </div>                                       
                                </div>
                                <div class="modal-footer">
                                    <input type="hidden" name="form_submission" value="group_creation">  
                                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-primary" id="submit">Create Group</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="modal fade" class="modal" id="editGroups" tabindex="-1" role="dialog" aria-labelledby="editGroups" aria-hidden="true">
                    <div class="modal-dialog" id="modal-info">
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
        
        <script src="js/plugins/formValidation/userGroupsFormsValidation.js"></script>
        <script src="js/plugins/formValidation/jquery.validate.js"></script>
        <script>$(function() { FormsValidation.init(); });</script> 
        
       

    </body>
</html>
<?php
}
?>
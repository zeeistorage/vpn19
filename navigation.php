<?php
include_once("includes/controller.php");
if(!$session->isAdmin()){
    header("Location: ".$configs->homePage());
    exit;
}
?>
<ul class="nav">
    <li class="sidebar-header">MAIN</li>
    <li <?php if($pagename == 'index') { echo 'class="active selected"'; } ?>>
        <a href="index.php"><i class="oi oi-dashboard"></i> <span class="nav-label">Dashboard</span></a>
    </li>
    
   
   
    
    <li <?php if($pagename == 'useradmin') { echo 'class="active selected"'; } ?>>
        <a href="useradmin.php"><i class="oi oi-person"></i> <span class="nav-label">User Admin</span></a>
    </li>
    <li <?php if($pagename == 'usergroups') { echo 'class="active selected"'; } ?>>
        <a href="usergroups.php"><i class="oi oi-people"></i> <span class="nav-label">User Groups</span></a>
    </li>
   
	
	<li <?php if($pagename == 'notification') { echo 'class="active selected"'; } ?>>
        <a href="notification.php"><i class="oi oi-people"></i> <span class="nav-label">Notification Management</span></a>
    </li>
</ul>

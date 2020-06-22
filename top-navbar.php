<?php
include_once("includes/controller.php");
if(!$session->isAdmin()){
    header("Location: ".$configs->homePage());
    exit;
}
?>
<nav class="navbar navbar-static-top" role="navigation">
   
    <form class="search-form hidden-xs">
        <input class="searchbox" id="searchbox" type="text" placeholder="Search">
        <span class="searchbutton"><i class="oi oi-magnifying-glass"></i></span>
    </form>
    
    <a href="logout.php?path=admin" class="navbar-top-icons btn btn-main" data-original-title="Logout" data-toggle="tooltip" data-placement="bottom"><i class="oi oi-power-standby"></i> </a>
   
   
    <div id="toggle">
        <a id="btn-fullscreen" class="navbar-top-icons btn btn-main hidden-xs" data-original-title="Fullscreen" data-toggle="tooltip" data-placement="bottom" href="#"><i id="toggle" class="oi oi-fullscreen-enter"></i> </a>
    </div>      
</nav> 


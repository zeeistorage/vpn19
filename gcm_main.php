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
												
												
										
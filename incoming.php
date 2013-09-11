<?php
header('Content-type: text/xml'); 
echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<Response>';
/*
---------- Voice messages in this program ---------------------
businesshours.wav: Hello and thank you for calling Dream line. You have reached the Lowes support hotline. To retrieve the status of an order and the tracking information, please press 1. For sales and product information, please press 2. For technical support, R. M. A. , freight and warranty information, please press 3. For all other questions please press 0, or stay on the line.

afterhours.wav: Hello and thank you for calling Dreamline's after-hours support hotline. Our offices are currently closed, but you can use the automated order tracking system to retrieve the status of your order. Or, record a message and our representatives will get in touch with you during business hours. To retrieve the status of an order, please press 1. For sales and product information, please press 2. Or, for technical support, R. M.A. , freight and warranty information, please press 3. 
---------- End of messages ---------------------
*/
date_default_timezone_set('America/New_York');     //---------------------------------- Set the timezone to America(EST). 
$hour = date('G');
$time = "day";


if ($hour >=8 && $hour<18)                         //--------------- Check if the call was made during normal (8:00am - 17:59pm) business hours. If the call was made after hours, then play the fall-back message. 
 { echo '<Gather action="handle-incoming-call.php?time=day" numDigits="1" timeout="5">';
 	   echo '<Play>http://******/voicefile/businesshours.wav</Play>'; //* See below
   echo '</Gather>';
 }

else
 {  $time = "night";
 	echo '<Gather action="handle-incoming-call.php?time=night" numDigits="1" timeout="5">';
 	   echo '<Play>http://******/voicefile/afterhours.wav</Play>'; //* See below	
   echo '</Gather>';
 }
echo '<Dial><Number sendDigits="300">+12159571411</Number></Dial>'; 

echo '</Response>';


?>


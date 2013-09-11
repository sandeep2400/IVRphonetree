<?php
header('Content-type: text/xml'); 
echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<Response>';

//----------- Read the numbers entered by the caller.        
date_default_timezone_set('America/New_York');
$user_pushed = (string) $_POST['Digits'];    // --- Actual numbers entered by the caller. 
$num_digits  = strlen($user_pushed);         // --- Length of the string entered by caller. 
$time        = (string) $_REQUEST['time'];   // --- indicates if caller called during/outside business hours. 

$Call_Sid        = (string) $_REQUEST['CallSid'];   // -- Call metadata
$Call_From       = (string)$_REQUEST['From'];
$Call_From_Zip   = (string)$_REQUEST['FromZip'];	
$Call_To         = (string)$_REQUEST['To'];		  
$Call_datetime   = date('Y-m-d H:i:s');

$tracking_number = $PO_Match = $Store_Number = $Store_Match = $Order_Status = " "; //--Initialize the variables. 
include('/resources.php');

if ($Call_To == "+1******0067") // if the call was from an internal transfer then directly hardcode option 1 for the order tracking system. 
	{$user_pushed = "1";
	}
//---------------------------------- Connect to the database				   
try{$conn = new PDO("sqlsrv:server = tcp:****,1433; Database = ****", "****", "****");
	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); }
catch(PDOException $e)
	{echo $e->getMessage();}


switch($user_pushed)
	{ case "1": 	
			   echo '<Gather action="handle-user-input.php" numDigits="9" timeout="5">';  
			   echo '<Play>http://******/voicefile/1.wav</Play>';  //MSG: Welcome to the BathAuthority order tracking system.To speak with a customer representative, at any time during this call, please press 0.To track an order, please enter your nine digit Purchase order number						
			   echo '</Gather>';

			   echo '<Gather action="handle-user-input.php" numDigits="9" timeout="3">'; 
			   echo '<Play>http://******/voicefile/2.wav</Play>';
			   echo '</Gather>';

//MSG: Sorry, I did not get your response. Let me connect you to a sales representative. Please stay on the line.
			   echo '<Play>http://******/voicefile/3.wav</Play>';
			   echo '<Dial><Number sendDigits="300">+12159571411</Number></Dial>';			   

    		   break;
			   
	  case "2":
//------------- Transfer to the Sales team	  
	           $Caller_exit = "Y";  
			   $Exit_num = $user_pushed; 
			   $stmt=$conn->prepare("insert into IVRLog (Call_Sid, Call_From, Call_From_Zip, Call_datetime, PONumber, PO_Match, Store_Number, Store_Match, Order_Status, Caller_exit, Exit_num) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
			   $stmt->execute(array($Call_Sid, $Call_From, $Call_From_Zip, $Call_datetime, $tracking_number, $PO_Match, $Store_Number, $Store_Match, $Order_Status, $Caller_exit, $Exit_num)); 			   
			   call_sales();
			   break;	 
			   
	  case "3":
//------------- Transfer to the Tech Support team	  
	           $Caller_exit = "Y";  
			   $Exit_num = $user_pushed; 
			   $stmt=$conn->prepare("insert into IVRLog (Call_Sid, Call_From, Call_From_Zip, Call_datetime, PONumber, PO_Match, Store_Number, Store_Match, Order_Status, Caller_exit, Exit_num) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
			   $stmt->execute(array($Call_Sid, $Call_From, $Call_From_Zip, $Call_datetime, $tracking_number, $PO_Match, $Store_Number, $Store_Match, $Order_Status, $Caller_exit, $Exit_num)); 			   
  
			   if ($time == "day")
				 { echo '<Dial><Number sendDigits="302">+12159571411</Number></Dial>';}
			   else 
				 { echo '<Dial><Number sendDigits="433">+12159571411</Number></Dial>';}
			   break;	 					 
			   
	  default :
//------------- By default, transfer to the sales team	     
	           $Caller_exit = "Y";  
			   $Exit_num = $user_pushed; 
			   $stmt=$conn->prepare("insert into IVRLog (Call_Sid, Call_From, Call_From_Zip, Call_datetime, PONumber, PO_Match, Store_Number, Store_Match, Order_Status, Caller_exit, Exit_num) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
			   $stmt->execute(array($Call_Sid, $Call_From, $Call_From_Zip, $Call_datetime, $tracking_number, $PO_Match, $Store_Number, $Store_Match, $Order_Status, $Caller_exit, $Exit_num)); 			   
			   call_sales();
			   break;	 
	}
echo '</Response>';
?>

<?php
header('Content-type: text/xml'); 
echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<Response>';

//-------------------------------------------------------------------------
$tracking_number = $user_pushed = (string) $_POST['Digits'];  // --- Actual numbers entered by the caller
$num_digits      = strlen($user_pushed);   // --- Length of the string entered by caller. 

date_default_timezone_set('America/New_York');
$Call_Sid        = (string) $_REQUEST['CallSid'];   // -- Call metadata
$Call_From       = (string)$_REQUEST['From'];
$Call_From_Zip   = (string)$_REQUEST['FromZip'];		  
$Call_datetime   = date('Y-m-d H:i:s');
include('/resources.php');
//------------------------------------------------------------- Connect to the database				   
try{ $conn = new PDO("sqlsrv:server = tcp:*******,1433; Database = *******", "*******", "*******");
	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); }
catch(PDOException $e)
	{echo $e->getMessage();}	

//------------------------- If the caller enters a single digit, transfer him to the sales group. 
 if ($num_digits == 1)
    {echo '<Play>http://******/voicefile/6.wav</Play>';	 //MSG:Let me connect you to a sales representative. Please stay on the line.
     $Caller_exit = "Y";  
	 $Exit_num = $user_pushed; 
	 $tracking_number =  $PO_Match = $Store_Number = $Store_Match = $Order_Status = " "; 
	 $stmt=$conn->prepare("insert into IVRLog (Call_Sid, Call_From, Call_From_Zip, Call_datetime, PONumber, PO_Match, Store_Number, Store_Match, Order_Status, Caller_exit, Exit_num) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
	 $stmt->execute(array($Call_Sid, $Call_From, $Call_From_Zip, $Call_datetime, $tracking_number, $PO_Match, $Store_Number, $Store_Match, $Order_Status, $Caller_exit, $Exit_num)); 			   
	 call_sales();
    }
else
//------------------------------------------------------------- CHECK IF THE USER ENTERED NINE DIGITS OF THE PURCHASE ORDER NUMBER. 
		{ if ($num_digits == 9) 
				 { 
				   echo '<Play>http://******/voicefile/4.wav</Play>';  //MSG:You entered
				   $po_arr = str_split($user_pushed);
                   for ($i=0; $i<$num_digits; $i++)  
				   			{echo '<Say voice="woman">'.$po_arr[$i].'</Say>';}	//repeat the nine digit purchase order number. 
	
//----------------------------------------------------------------------- Insert a record with the new PO Number into the IVR Log database.. 
				   $PO_Match = $Store_Number = $Store_Match = $Order_Status = $Caller_exit = $Exit_num = " " ; 
				   $stmt=$conn->prepare("insert into IVRLog (Call_Sid, Call_From, Call_From_Zip, Call_datetime, PONumber, PO_Match, Store_Number, Store_Match, Order_Status, Caller_exit, Exit_num) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
				   $stmt->execute(array($Call_Sid, $Call_From, $Call_From_Zip, $Call_datetime, $tracking_number, $PO_Match, $Store_Number, $Store_Match, $Order_Status, $Caller_exit, $Exit_num)); 			   

//------------------------------------------------------------- QUERY THE ORDERTRACK DATABASE FOR THE PO NUMBER
				   $stmt = $conn->prepare( "select SMRFN from ordertrack where PONumber LIKE ?");
				   $tracking_str = '%'.$tracking_number.'%';
				   $stmt->execute(array($tracking_str));
				   $result = $stmt->fetch(PDO::FETCH_ASSOC);
				   if ($result)
                    	{ 
//-------------------------------------------------------------IF THE PO NUMBER EXISTS IN ORDERTRACK, UPDATE THE IVRLOG FOR A PO_MATCH = 'Y' & PROMPT USER FOR A 4 DIGIT STORE NUMBER. 
						  $PO_Match = "Y";
						  $stmt = $conn->prepare("update IVRLog set PO_Match = ? where (Call_Sid = ? AND PONumber = ?)");
                          $stmt->execute(array($PO_Match, $Call_Sid, $tracking_number));
						  echo "<Gather action=\"handle-store-zip.php?order=$tracking_number\" numDigits=\"4\">"; 
		                  echo "<Play>http://******/voicefile/8.wav</Play>";//MSG: Please enter the four digit Lowe's store number.If you don't know your four digit store number, please press zero, to speak with a customer representative.
                          echo "</Gather>";   
						}
				   else
					{ 
//-------------------------------------------------------------IF THE PO NUMBER DOES NOT EXISTS IN ORDERTRACK, UPDATE THE IVRLOG FOR A PO_MATCH = 'N' & PROMPT USER FOR A NEW 9 DIGIT ORDER NUMBER. 							
					  $PO_Match = "N";
					  $stmt = $conn->prepare("update IVRLog set PO_Match = ? where (Call_Sid = ? AND PONumber = ?)");
                      $stmt->execute(array($PO_Match, $Call_Sid, $tracking_number));
 					  echo "<Gather action=\"handle-user-input.php\" numDigits=\"9\" timeout=\"5\">";
 					  echo '<Play>http://******/voicefile/9.wav</Play>'; //MSG::I'm sorry,we could not find that order in our records.If you'd like to try again with a different nine digit order number, please enter it now. Or, to speak with a customer representative, please press zero.	
	    			  echo "</Gather>";
					}
				 }
			else
//----------------------------------IF THE USER ENTERED A NUMBER THAT WAS NOT 9 DIGITS OR 1 DIGIT, PLAY AN ERROR MESSAGE, UPDATE IVRLOG FOR A PO_MATCH = 'n' AND PROMPT USER FOR ANOTHER PO_NUMBER. 
				 { 
				   echo '<Play>http://******/voicefile/4.wav</Play>';  //MSG:You entered
				   $po_arr = str_split($user_pushed);
                   for ($i=0; $i<$num_digits; $i++)  {echo '<Say voice="woman">'.$po_arr[$i].'</Say>';}

				   $Store_Number = $Store_Match = $Order_Status = $Caller_exit = $Exit_num = " " ; 
				   $PO_Match = "N";
				   $stmt=$conn->prepare("insert into IVRLog (Call_Sid, Call_From, Call_From_Zip, Call_datetime, PONumber, PO_Match, Store_Number, Store_Match, Order_Status, Caller_exit, Exit_num) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
				   $stmt->execute(array($Call_Sid, $Call_From, $Call_From_Zip, $Call_datetime, $tracking_number, $PO_Match, $Store_Number, $Store_Match, $Order_Status, $Caller_exit, $Exit_num)); 			   

				   echo "<Gather action=\"handle-user-input.php\" numDigits=\"9\" timeout=\"5\">";
					   echo '<Play>http://******/voicefile/10.wav</Play>'; //MSG:I'm sorry, you did not enter a complete order number. If you would like to try again please enter a nine digit order number. Or, to speak with a customer representative, please press zero. </Say>";
				   echo "</Gather>";
			     }
		}
echo '</Response>';
?>

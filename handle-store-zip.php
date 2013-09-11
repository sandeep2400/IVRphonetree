<?php
header('Content-type: text/xml'); 
echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<Response>';

//-----------------------------------------------------------------------------

$repeat_flag = (boolean) $_REQUEST['repeat_flag'];
$store_code  = (string) $_POST['Digits'];
$tracking_num = (string) $_REQUEST['order'];

date_default_timezone_set('America/New_York');
$Call_Sid        = (string) $_REQUEST['CallSid'];   // -- Call metadata
$Call_From       = (string)$_REQUEST['From'];
$Call_From_Zip   = (string)$_REQUEST['FromZip'];		  
$Call_datetime   = date('Y-m-d H:i:s');
//-----------------------------------------------------------------------------
if (($repeat_flag == TRUE) && ($store_code == '1'))   //---- CHECK TO SEE IF THE CALLER WANTS TO REPEAT THE ORDER INFORMATION
  {$store_code = (string) $_REQUEST['code'];          //---- READ THE STORE CODE FROM THE PARAMETER NUMBER. 
   $repeat_flag = FALSE;                             
  }
else
  {  
	 $debug_len = strlen($store_code);
	 $po_arr = str_split($store_code);
	 if ($debug_len == 4)
		{echo '<Play>http://******/voicefile/4.wav</Play>'; //MSG: You entered 	
		 for ($i=0; $i<$debug_len; $i++) { echo '<Say voice="woman">'.$po_arr[$i].'</Say>';} 	
		}
	}
	
include_once ('resources.php');
try{ $conn = new PDO("sqlsrv:server = tcp:****,1433; Database = ****", "****", "****");   //-----CONNECTING TO THE DATABASE
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); }
catch(PDOException $e)
{echo $e->getMessage(); }	

if (strlen($store_code) == 4)       //------------------------------------------------------------------------------------ CHECK IF THE USER PRESSED 4 DIGITS
   { 
  	$tracking_num = (string) $_REQUEST['order'];

		
    $stmt = $conn->prepare("update IVRLog set Store_Number = ? where (Call_Sid = ? AND PONumber = ?)"); //-------UPDATE IVRLOG WITH THE 4 DIGIT STORE NUMBER ENTERED BY THE USER. 
    $stmt->execute(array($store_code, $Call_Sid, $tracking_num));			 

    $tracking_str = '%'.$tracking_num.'%';   //------ CHECK IF THE STORE NUMBER MATCHES THE PO_NUMBER ENTERED IN THE DATABASE.  
    $stmt = $conn->prepare( "select CustRef_FullName, SMRFN, ESD, tracking, inv_number, inv_shipMethod, ShipDate from ordertrack where PONumber  LIKE :PONumber order by CAST(TimeCreated as datetime) desc");
    $stmt->execute(array(':PONumber' => $tracking_str));
    $result = $stmt->fetch(PDO::FETCH_ASSOC); 
		   
    if ($result)
		{  $store_num = substr($result[CustRef_FullName],11,4); //- IF THERE ICOMPARE THE STORE NUMBER ENTERED BY THE USER WITH THE LAST 4 DIGITS OF THE 'FULL NAME' FIELD ON THE DATABASE. 		
		   if ($store_code == $store_num)

			  {	if ($result[inv_number] == NULL) //--CHECK THE INVOICE-NUMBER FIELDFOR SHIPPING STATUS. A PO W/O AN INVOICE HAS NOT SHIPPED. USE THE 'ESTIMATED SHIP DATE'.  
				   { $check_shipper = $result[SMRFN];
					 $Speak_Ship_Date = $result[ESD];}
				else
					{ $check_shipper = $result[inv_shipMethod];//-- A PO W/ AN INVOICE HAS SHIPPED. USE THE 'SHIP DATE' ON THE INVOICE
					  $Speak_Ship_Date = $result[ShipDate];
					 }
				$est_Ship_Day = substr($Speak_Ship_Date, 8, 2);
				$est_Ship_Month = substr($Speak_Ship_Date, 5, 2);

				$store_match = "Y";  //----UPDATE IVRLOG WITH A STORE-MATCH = 'Y'
				$stmt = $conn->prepare("update IVRLog set Store_Match = ?, Order_Status = ? where (Call_Sid = ? AND PONumber = ?)");
				$stmt->execute(array($store_match, $check_shipper, $Call_Sid, $tracking_num));	
					    						
						 switch ($check_shipper) //-- NOW CHECK THE SHIPPER FILED AND BASED ON ITS VALUE READ OUT FROM A PLETHORA OF OPTIONS. 
							   { 
							    case "BACKORDER": 
							                    echo '<Play>http://******/voicefile/11.wav</Play>';  //MSG:Your order has been back ordered.your order is scheduled to be processed on 
												$est_Ship_Month = getMonth($est_Ship_Month);
												echo '<Say voice="woman"> '.$est_Ship_Month.'  .  '.$est_Ship_Day.'.</Say>'; 
												break; 
								 case "CANCELLED": 
             						            echo '<Play>http://******/voicefile/13.wav</Play>';  //MSG:Your order has been cancelled. To learn more about your order, press 0. 
												break; 
								 case "Claim Declined": 
												echo "<Say voice=\"alice\"> Your order shows a declined claim.</Say>";
												echo "<Say voice=\"alice\"> You can speak with a customer representative to learn more about your order. </Say>";
												break; 
								 case "VOID": 
												echo "<Say voice=\"alice\"> We were not able to process your order.</Say>";
												echo "<Say voice=\"alice\"> Please speak with a customer representative to learn more about your order. </Say>";
												break; 
								 case "DELIVERY": 
												echo '<Play>http://******/voicefile/16.wav</Play>';  //MSG:Your order is being delivered. 
												ship_date($result[ShipDate]);	
												break; 
								 case "DELIVERED": 
              						            echo '<Play>http://******/voicefile/16.wav</Play>';  //MSG:Your order is being delivered. 
												ship_date($result[ShipDate]);	
												break; 
								 case "PICKUP": 
              						            echo '<Play>http://******/voicefile/17.wav</Play>';  //MSG:Your order is ready for pickup
												break; 
								 case "PICKED UP": 
												echo '<Play>http://******/voicefile/17.wav</Play>';  //MSG:Your order has been picked up.
												break; 
								 case "CREDIT HOLD": 
												echo "<Say voice=\"alice\"> Your order shows a credit that is on hold.</Say>";
												echo "<Say voice=\"alice\"> You can speak with a customer representative to learn more about your order. </Say>";
												break; 
								 case "MAP HOLD": 
												echo "<Say voice=\"alice\"> Your order shows a status of Map Hold.</Say>";
												echo "<Say voice=\"alice\"> You can speak with a customer representative to learn more about your order. </Say>";
												break; 
								 case "FEDEX 1 DAY": 
													echo '<Play>http://******/voicefile/21.wav</Play>'; 	//MSG: Your order is being delivered by Fed ex 
													ship_date($result[ShipDate]);
													
													if ($result[tracking] != NULL)
													{  $track_len = strlen($result[tracking]);  
													   $track = str_split($result[tracking]); 
													   echo '<Play>http://******/voicefile/23.wav</Play>'; 	//MSG: Your FEDex tracking number is
													   for ($i=0; $i<$track_len; $i++)
															{ echo '<Say voice="woman">'.$track[$i].' .</Say>';}
													  tracking_msg();															
													}
													break; 
								 case "FEDEX 2 DAY": 
													echo '<Play>http://******/voicefile/21.wav</Play>'; 	//MSG: Your order is being delivered by Fed ex 
													ship_date($result[ShipDate]);
													
													if ($result[tracking] != NULL)
													{  $track_len = strlen($result[tracking]);  
													   $track = str_split($result[tracking]); 
													   echo '<Play>http://******/voicefile/23.wav</Play>'; 	//MSG: Your FEDex tracking number is
													   for ($i=0; $i<$track_len; $i++)
															{ echo '<Say voice="woman">'.$track[$i].' .</Say>';}
													  tracking_msg();															
													}
													break; 
								 case "FEDEX 3 DAY": 
													echo '<Play>http://******/voicefile/21.wav</Play>'; 	//MSG: Your order is being delivered by Fed ex 
													ship_date($result[ShipDate]);
													
													if ($result[tracking] != NULL)
													{  $track_len = strlen($result[tracking]);  
													   $track = str_split($result[tracking]); 
													   echo '<Play>http://******/voicefile/23.wav</Play>'; 	//MSG: Your FEDex tracking number is
													   for ($i=0; $i<$track_len; $i++)
															{ echo '<Say voice="woman">'.$track[$i].' .</Say>';}
													  tracking_msg();															
													}
													break; 
								 case "FEDEX EXPRESS": 
													echo '<Play>http://******/voicefile/21.wav</Play>'; 	//MSG: Your order is being delivered by Fed ex 
													ship_date($result[ShipDate]);
													
													if ($result[tracking] != NULL)
													{  $track_len = strlen($result[tracking]);  
													   $track = str_split($result[tracking]); 
													   echo '<Play>http://******/voicefile/23.wav</Play>'; 	//MSG: Your FEDex tracking number is
													   for ($i=0; $i<$track_len; $i++)
															{ echo '<Say voice="woman">'.$track[$i].' .</Say>';}
													  tracking_msg();															
													}
													break; 
								 case "FEDEX FR": 
													echo '<Play>http://******/voicefile/21.wav</Play>'; 	//MSG: Your order is being delivered by Fed ex 
													ship_date($result[ShipDate]);
													
													if ($result[tracking] != NULL)
													{  $track_len = strlen($result[tracking]);  
													   $track = str_split($result[tracking]); 
													   echo '<Play>http://******/voicefile/23.wav</Play>'; 	//MSG: Your FEDex tracking number is
													   for ($i=0; $i<$track_len; $i++)
															{ echo '<Say voice="woman">'.$track[$i].' .</Say>';}
													  tracking_msg();															
													}
													break; 
								 case "FEDEX FR - PROC": 
													echo '<Play>http://******/voicefile/22.wav</Play>'; 	//MSG: Your order is being delivered by Fed ex 
													ship_date($result[ShipDate]);
													
													if ($result[tracking] != NULL)
													{  $track_len = strlen($result[tracking]);  
													   $track = str_split($result[tracking]); 
													   echo '<Play>http://******/voicefile/23.wav</Play>'; 	//MSG: Your FEDex tracking number is
													   for ($i=0; $i<$track_len; $i++)
															{ echo '<Say voice="woman">'.$track[$i].' .</Say>';}
													  tracking_msg();															
													}
													break; 
								 case "FEDEX GR": 
													echo '<Play>http://******/voicefile/21.wav</Play>'; 	//MSG: Your order is being delivered by Fed ex 
													ship_date($result[ShipDate]);
													
													if ($result[tracking] != NULL)
													{  $track_len = strlen($result[tracking]);  
													   $track = str_split($result[tracking]); 
													   echo '<Play>http://******/voicefile/23.wav</Play>'; 	//MSG: Your FEDex tracking number is
													   for ($i=0; $i<$track_len; $i++)
															{ echo '<Say voice="woman">'.$track[$i].' .</Say>';}
													  tracking_msg();															
													}
													break; 
								 case "FEDEX GR - PROC": 
													echo '<Play>http://******/voicefile/22.wav</Play>'; 	//MSG: Your order is being delivered by Fed ex 
													ship_date($result[ShipDate]);
													
													if ($result[tracking] != NULL)
													{  $track_len = strlen($result[tracking]);  
													   $track = str_split($result[tracking]); 
													   echo '<Play>http://******/voicefile/23.wav</Play>'; 	//MSG: Your FEDex tracking number is
													   for ($i=0; $i<$track_len; $i++)
															{ echo '<Say voice="woman">'.$track[$i].' .</Say>';}
													  tracking_msg();															
													}
													break; 
								 case "ON HOLD": 
							   						echo "<Say voice=\"alice\"> We were not able to process your order. </Say>";
													echo "<Say voice=\"alice\"> Please speak with a customer representative to learn more about your order. </Say>";
													break; 
								 case "R&L": 
													echo '<Play>http://******/voicefile/26.wav</Play>';
													ship_date($result[ShipDate]);
													
													if ($result[tracking] != NULL)
													{
   													   $track_len = strlen($result[tracking]);
	 					                               $track = str_split($result[tracking]); 
													   echo '<Play>http://******/voicefile/28.wav</Play>'; //Your RL tracking number is
													   for ($i=0; $i<$track_len; $i++)
															{ echo '<Say voice="woman">'.$track[$i].' .</Say>';}
													  tracking_msg();															
													}
													break; 
								 case "R&L - PROC": 
													echo '<Play>http://******/voicefile/27.wav</Play>'; //MSH: Your order was processed and will be shipped by RL carriers
													ship_date($result[ShipDate]);
													if ($result[tracking] != NULL)
													{  $track_len = strlen($result[tracking]);
	 					                               $track = str_split($result[tracking]); 
													   echo '<Play>http://******/voicefile/28.wav</Play>'; //Your RL tracking number is
													   for ($i=0; $i<$track_len; $i++)
															{ echo '<Say voice="woman">'.$track[$i].' .</Say>';}
													  tracking_msg();															
													}
													break; 
								 case "USPS": 
													echo '<Play>http://******/voicefile/30.wav</Play>'; //MSH: Your order is being delived by USPS
													ship_date($result[ShipDate]);
													if ($result[tracking] != NULL)
													{  $track_len = strlen($result[tracking]);
	 					                               $track = str_split($result[tracking]); 
													   echo '<Play>http://******/voicefile/31.wav</Play>'; //Your USPS tracking number is
													   for ($i=0; $i<$track_len; $i++)   
															{ echo '<Say voice="woman">'.$track[$i].' .</Say>';}
													  tracking_msg();															
													}
													break; 
								 case "YRC - PROC": 
													echo '<Play>http://******/voicefile/33.wav</Play>'; //MSH: Your order was processed and will be shipped by YRC carriers
													ship_date($result[ShipDate]);
													if ($result[tracking] != NULL)
													{  $track_len = strlen($result[tracking]);
	 					                               $track = str_split($result[tracking]); 
													   echo '<Play>http://******/voicefile/35.wav</Play>'; //Your YRC tracking number is
													   for ($i=0; $i<$track_len; $i++)
															{ echo '<Say voice="woman">'.$track[$i].' .</Say>';}
													  tracking_msg();															
													}
													break; 
								 case "YRC": 
													echo '<Play>http://******/voicefile/34.wav</Play>'; //MSH: Your order is being delivered by YRC carriers
													ship_date($result[ShipDate]);
													if ($result[tracking] != NULL)
													{  $track_len = strlen($result[tracking]);
	 					                               $track = str_split($result[tracking]); 
													   echo '<Play>http://******/voicefile/35.wav</Play>'; //Your YRC tracking number is
													   for ($i=0; $i<$track_len; $i++)
															{ echo '<Say voice="woman">'.$track[$i].' .</Say>';}
													  tracking_msg();															
													}
													break; 
								 case "VR - YRC": 
													echo '<Play>http://******/voicefile/34.wav</Play>'; //MSH: Your order is being delivered by YRC carriers
													ship_date($result[ShipDate]);
													if ($result[tracking] != NULL)
													{  $track_len = strlen($result[tracking]);
	 					                               $track = str_split($result[tracking]); 
													   echo '<Play>http://******/voicefile/35.wav</Play>'; //Your YRC tracking number is
													   for ($i=0; $i<$track_len; $i++)
															{ echo '<Say voice="woman">'.$track[$i].' .</Say>';}
													  tracking_msg();															
													}
													break; 
								 case "NEW ENGL": 
							   						echo "<Say voice=\"alice\"> Your order is being delivered by New England Carriers.</Say>";
													ship_date($result[ShipDate]);
													
													if ($result[tracking] != NULL){
													$track_len = strlen($result[tracking]);
						                            $track = str_split($result[tracking]); 														
													   echo '<Say voice="alice"> Your New England Carrier tracking number is </Say>';
													   for ($i=0; $i<$track_len; $i++)
															{ echo '<Say voice="woman">'.$track[$i].'</Say>';}
													   echo '<Say voice="alice"> Once again, your New England Carrier tracking number is </Say>';
													   for ($i=0; $i<$track_len; $i++)
															{ echo '<Say voice="woman">'.$track[$i].'</Say>';}	
													  tracking_msg();	
													}
													break;  													

								 case "NEW ENGL - PROC": 
							   						echo "<Say voice=\"alice\">  Your order was processed and will be shipped by New England Carriers.</Say>";
													ship_date($result[ShipDate]);
													
													if ($result[tracking] != NULL){
													$track_len = strlen($result[tracking]);
						                            $track = str_split($result[tracking]); 														
													   echo '<Say voice="alice"> Your New England Carrier tracking number is </Say>';
													   for ($i=0; $i<$track_len; $i++)
															{ echo '<Say voice="woman">'.$track[$i].'</Say>';}
													  tracking_msg();	
													}
													break;  													
								 case "UPS": 
											echo '<Play>http://******/voicefile/41.wav</Play>'; //MSH: Your order is being delivered by UPS
											ship_date($result[ShipDate]);
											if ($result[tracking] != NULL)
											{  $track_len = strlen($result[tracking]);
											   $track = str_split($result[tracking]); 
											   echo '<Play>http://******/voicefile/43.wav</Play>'; //Your UPS tracking number is
											   for ($i=0; $i<$track_len; $i++)
													{ echo '<Say voice="woman">'.$track[$i].' .</Say>';}
											  tracking_msg();															
											}
											break; 
								 case "UPS 1 DAY": 
											echo '<Play>http://******/voicefile/41.wav</Play>'; //MSH: Your order is being delivered by UPS
											ship_date($result[ShipDate]);
											if ($result[tracking] != NULL)
											{  $track_len = strlen($result[tracking]);
											   $track = str_split($result[tracking]); 
											   echo '<Play>http://******/voicefile/43.wav</Play>'; //Your UPS tracking number is
											   for ($i=0; $i<$track_len; $i++)
													{ echo '<Say voice="woman">'.$track[$i].' .</Say>';}
											  tracking_msg();															
											}
											break; 
								 case "UPS 2 DAY": 
											echo '<Play>http://******/voicefile/41.wav</Play>'; //MSH: Your order is being delivered by UPS
											ship_date($result[ShipDate]);
											if ($result[tracking] != NULL)
											{  $track_len = strlen($result[tracking]);
											   $track = str_split($result[tracking]); 
											   echo '<Play>http://******/voicefile/43.wav</Play>'; //Your UPS tracking number is
											   for ($i=0; $i<$track_len; $i++)
													{ echo '<Say voice="woman">'.$track[$i].' .</Say>';}
											  tracking_msg();															
											}
											break; 
								 case "UPS 3 DAY": 
											echo '<Play>http://******/voicefile/41.wav</Play>'; //MSH: Your order is being delivered by UPS
											ship_date($result[ShipDate]);
											if ($result[tracking] != NULL)
											{  $track_len = strlen($result[tracking]);
											   $track = str_split($result[tracking]); 
											   echo '<Play>http://******/voicefile/43.wav</Play>'; //Your UPS tracking number is
											   for ($i=0; $i<$track_len; $i++)
													{ echo '<Say voice="woman">'.$track[$i].' .</Say>';}
											  tracking_msg();															
											}
											break; 
								 case "UPS GR": 
											echo '<Play>http://******/voicefile/41.wav</Play>'; //MSH: Your order is being delivered by UPS
											ship_date($result[ShipDate]);
											if ($result[tracking] != NULL)
											{  $track_len = strlen($result[tracking]);
											   $track = str_split($result[tracking]); 
											   echo '<Play>http://******/voicefile/43.wav</Play>'; //Your UPS tracking number is
											   for ($i=0; $i<$track_len; $i++)
													{ echo '<Say voice="woman">'.$track[$i].' .</Say>';}
											  tracking_msg();															
											}
											break; 
								 case "UPS GR - PROC": 
											echo '<Play>http://******/voicefile/42.wav</Play>'; //MSH: Your order is being delivered by UPS
											ship_date($result[ShipDate]);
											if ($result[tracking] != NULL)
											{  $track_len = strlen($result[tracking]);
											   $track = str_split($result[tracking]); 
											   echo '<Play>http://******/voicefile/43.wav</Play>'; //Your UPS tracking number is
											   for ($i=0; $i<$track_len; $i++)
													{ echo '<Say voice="woman">'.$track[$i].' .</Say>';}
											  tracking_msg();															
											}
											break; 
								 case "ABF": 
							   						echo "<Say voice=\"alice\"> Your order is being delivered by A. B. F. shipping.</Say>";
													ship_date($result[ShipDate]);
													
													if ($result[tracking] != NULL){
													$track_len = strlen($result[tracking]);
						                            $track = str_split($result[tracking]); 														
													   echo '<Say voice="alice"> Your  A. B. F. tracking number is </Say>';
													   for ($i=0; $i<$track_len; $i++)
															{ echo '<Say voice="woman">'.$track[$i].'</Say>';}
													  tracking_msg();	
													}
													break;  													
								 case "ABF - PROC": 
							   						echo "<Say voice=\"alice\">  Your order was processed and will be shipped by A. B. F. shipping.</Say>";
													ship_date($result[ShipDate]);
													
													if ($result[tracking] != NULL){
													$track_len = strlen($result[tracking]);
						                            $track = str_split($result[tracking]); 														
													   echo '<Say voice="alice"> Your  A. B. F. tracking number is </Say>';
													   for ($i=0; $i<$track_len; $i++)
															{ echo '<Say voice="woman">'.$track[$i].'</Say>';}
													  tracking_msg();	
													}
													break;  													
								 case "CEVA": 
							   						echo "<Say voice=\"alice\"> Your order is being delivered by Ceva shipping.</Say>";
													ship_date($result[ShipDate]);
													
													if ($result[tracking] != NULL){
													$track_len = strlen($result[tracking]);
						                            $track = str_split($result[tracking]); 														
													   echo '<Say voice="alice"> Your Ceva tracking number is </Say>';
													   for ($i=0; $i<$track_len; $i++)
															{ echo '<Say voice="woman">'.$track[$i].'</Say>';}
													  tracking_msg();	
													}
													break;  					
								 case "CEVA - PROC": 
							   						echo "<Say voice=\"alice\">  Your order was processed and will be shipped by Ceva shipping.</Say>";
													ship_date($result[ShipDate]);
													
													if ($result[tracking] != NULL){
													$track_len = strlen($result[tracking]);
						                            $track = str_split($result[tracking]); 														
													   echo '<Say voice="alice"> Your Ceva tracking number is </Say>';
													   for ($i=0; $i<$track_len; $i++)
															{ echo '<Say voice="woman">'.$track[$i].'</Say>';}
													  tracking_msg();	
													}
													break;  																					
												
								 case "ODFL": 
											echo '<Play>http://******/voicefile/53.wav</Play>'; //MSH: Your order is being delivered by ODFL
											ship_date($result[ShipDate]);
											if ($result[tracking] != NULL)
											{  $track_len = strlen($result[tracking]);
											   $track = str_split($result[tracking]); 
											   echo '<Play>http://******/voicefile/54.wav</Play>'; //Your ODFL tracking number is
											   for ($i=0; $i<$track_len; $i++)
													{ echo '<Say voice="woman">'.$track[$i].' .</Say>';}
											  tracking_msg();															
											}
											break; 													
								 case "PITT OHIO": 
							   						echo "<Say voice=\"alice\"> Your order is being delivered by Pit Ohio carriers.</Say>";
													ship_date($result[ShipDate]);
													
													if ($result[tracking] != NULL){
													$track_len = strlen($result[tracking]);
						                            $track = str_split($result[tracking]); 														
													   echo '<Say voice="alice"> Your  Pit Ohio tracking number is </Say>';
													   for ($i=0; $i<$track_len; $i++)
															{ echo '<Say voice="woman">'.$track[$i].'</Say>';}
													  tracking_msg();	
													}
													break;  													

								 case "ESTES EX": 
							   						echo "<Say voice=\"alice\"> Your order is being delivered by Estes Express.</Say>";
													ship_date($result[ShipDate]);
													
													if ($result[tracking] != NULL){
													$track_len = strlen($result[tracking]);
						                            $track = str_split($result[tracking]); 														
													   echo '<Say voice="alice"> Your  Estes tracking number is </Say>';
													   for ($i=0; $i<$track_len; $i++)
															{ echo '<Say voice="woman">'.$track[$i].'</Say>';}
													  tracking_msg();	
													}
													break;  													
								 case "ESTES EX - PROC": 
							   						echo "<Say voice=\"alice\">  Your order was processed and will be shipped by Estes Express.</Say>";
													ship_date($result[ShipDate]);
													
													if ($result[tracking] != NULL){
													$track_len = strlen($result[tracking]);
						                            $track = str_split($result[tracking]); 														
													   echo '<Say voice="alice"> Your  Estes tracking number is </Say>';
													   for ($i=0; $i<$track_len; $i++)
															{ echo '<Say voice="woman">'.$track[$i].'</Say>';}
													  tracking_msg();	
													}
													break;  																
								 case "CONWAY": 
							   						echo "<Say voice=\"alice\"> Your order is being delivered by Conway Freight.</Say>";
													ship_date($result[ShipDate]);
													
													if ($result[tracking] != NULL){
													$track_len = strlen($result[tracking]);
						                            $track = str_split($result[tracking]); 														
													   echo '<Say voice="alice"> Your  Conway tracking number is </Say>';
													   for ($i=0; $i<$track_len; $i++)
															{ echo '<Say voice="woman">'.$track[$i].'</Say>';}
													  tracking_msg();	
													}
													break;  													
								 case "CONWAY - PROC": 
							   						echo "<Say voice=\"alice\">  Your order was processed and will be shipped by Conway Freight.</Say>";
													ship_date($result[ShipDate]);
													
													if ($result[tracking] != NULL){
													$track_len = strlen($result[tracking]);
						                            $track = str_split($result[tracking]); 														
													   echo '<Say voice="alice"> Your  Conway tracking number is </Say>';
													   for ($i=0; $i<$track_len; $i++)
															{ echo '<Say voice="woman">'.$track[$i].'</Say>';}
													  tracking_msg();	
													}
													break;  																
								 default:           
								                    echo '<Play>http://******/voicefile/67.wav</Play>'; //MSG: I can confirm that we have your order, and it's being processed. But that's all the information I have right now.
													break;  													
								} 
//------ TO TELL THE CALLER HOW UP-TO-DATE THE INFORMATION IS, WE SCAN THE ORDERTRACK DATABASE FOR THE MOST RECENT TIMECREATED VALUE IN ALL RECORDS. 								
                         $stmt = $conn->prepare( "select top 1 CONVERT(datetime, TimeCreated) as TimeCreated from  ordertrack order by CAST(TimeCreated as datetime) desc");
					     $stmt->execute();  
					     $result = $stmt->fetch(PDO::FETCH_ASSOC); 

//--TELL THE CALLER HWHEN THIS INFORMATION WAS UPDATED 
					     if ($result)
						 	{ $est_Latest_Month = substr($result[TimeCreated], 5, 2); 
							  $est_Latest_Month = getMonth($est_Latest_Month);
							  $est_Latest_Day  = substr( $result[TimeCreated], 8, 2); 
							  echo '<Play>http://******/voicefile/68.wav</Play>'; //MSG: This information is the latest as of ---
							  echo '<Say voice="woman"> '.$est_Latest_Month.'  .  '.$est_Latest_Day.'</Say>'; 
						 	}
// -- ASK CALLERS IF THEY WANT TO LISTEN TO THIS MESSAGE AGAIN. 
                         echo "<Gather action=\"handle-store-zip.php?order=$tracking_num&amp;code=$store_code&amp;repeat_flag=TRUE\" timeout=\"3\" numDigits=\"1\">";
						  echo '<Play>http://******/voicefile/69.wav</Play>'; //MSG:If you would like to hear this information again, press one. If you have more questions about your order and would like to speak with a customer representative, please press zero.
						  echo "</Gather>";
						}
					else
						{
// IF THE STORE VALUE DID NOT MATCH, PROMPT THE CALLER FOR A NEW 4 DIGIT STORE NUMBER. 							
						  $store_match = "N";
					      $stmt = $conn->prepare("update IVRLog set Store_Match = ? where (Call_Sid = ? AND PONumber = ?)");
			              $stmt->execute(array($store_match, $Call_Sid, $tracking_num));		
						  echo "<Gather action=\"handle-store-zip.php?order=$tracking_num\" timeout=\"3\" numDigits=\"4\">";
  				             echo '<Play>http://******/voicefile/70.wav</Play>'; //MSG: You have the correct purchase order, but the wrong store number. To protect our customers privacy we need the correct store number.If you'd like to try again with a different four digit store number, please enter it now.Or, to speak with a customer representative, please press zero.
					      echo "</Gather>";
						}
				}
    	   else
//------------------- ASK THE USER TO RE-ENTER THE STORE NUMBER OR CHOOSE TO SPEAK TO AN AGENT. 
				{ $store_match = "N";
				  $stmt = $conn->prepare("update IVRLog set Store_Match = ? where (Call_Sid = ? AND PONumber = ?)");
			      $stmt->execute(array($store_match, $Call_Sid, $tracking_num));		
				  
				  echo "<Gather action=\"handle-store-zip.php?order=$tracking_num\" timeout=\"3\" numDigits=\"4\">";
                     echo '<Play>http://******/voicefile/71.wav</Play>'; //MSG: There seems to be a technical error. If you'd like to try again with a different four digit store number, please enter it now. Or, to speak with a customer representatve, please press zero.
				  echo "</Gather>";
				}
		 }
	else
		{
		if (strlen($store_code) == 1)                       //--------- CHECK IF THE USER PRESSED ONLY 1 DIGIT	TRANSFER THE CALLER TO AN AGENT 
		  {echo '<Play>http://******/voicefile/72.wav</Play>'; //MSG: Please stay on the line while I connect you to a sales representative. 
		   $Caller_exit = "Y";  $Exit_num = $store_code; 
		   $stmt = $conn->prepare("update IVRLog set Caller_exit = ?, Exit_num = ? where (Call_Sid = ? AND PONumber = ?)");
		   $stmt->execute(array($Caller_exit, $Exit_num, $Call_Sid, $tracking_num));		
           call_sales();
// 		   echo '<Dial><Number sendDigits="300">+12159571411</Number></Dial>';
		  }
		else
//--------- IF THE USER PRESSED ANY OTHER OPTION, SPEAK AN ERROR MESSAGE 
			{
			  $store_match = "N"; 
			  $stmt = $conn->prepare("update IVRLog set Store_Match = ?, Store_Number = ? where (Call_Sid = ? AND PONumber = ?)");
			  $stmt->execute(array($store_match, $store_code, $Call_Sid, $tracking_num));		

  			  echo "<Gather action=\"handle-store-zip.php?order=$tracking_num\" timeout=\"3\" numDigits=\"4\">";
     			echo '<Play>http://******/voicefile/74.wav</Play>'; //MSG: I'm sorry, that is not a valid four digit store number.If you'd like to try again with a different four digit order number, please enter it now. Or, to speak with a customer representative, please press zero.
			  echo "</Gather>";

              echo '<Play>http://******/voicefile/72.wav</Play>'; //MSG: Please stay on the line while I connect you to a sales representative. 
              call_sales();
// 		      echo '<Dial><Number sendDigits="300">+12159571411</Number></Dial>';
			}
		}
	  echo "<Gather action=\"handle-user-input.php\" timeout= \"6\" numDigits=\"9\">";
	  echo '<Play>http://******/voicefile/75.wav</Play>'; //MSG: If you'd like to look up another order, please enter the nine digit purchase order number now.
	  echo "</Gather>";
	
echo '</Response>';
?>
	
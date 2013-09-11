<?php 
//*********************************************************************************************************** 
function getMonth ($month)  
{ 
          switch ($month) 
           { case "01": $month = "January"; break; 
             case "02": $month = "February"; break; 
             case "03": $month = "March"; break; 
             case "04": $month = "April"; break; 
             case "05": $month = "May"; break; 
             case "06": $month = "June"; break; 
             case "07": $month = "July"; break; 
             case "08": $month = "August"; break; 
             case "09": $month = "September"; break; 
             case "10": $month = "October"; break; 
             case "11": $month = "November"; break; 
             case "12": $month = "December"; break; 
             default: $month = "Unknown";  
            } 
		return ($month);	
} 

 
function ship_date($est_ship_date)
{
	$est_Ship_Month = substr($est_ship_date, 5, 2); 
	$est_Ship_Month = getMonth($est_Ship_Month);
	$est_Ship_Day  = substr($est_ship_date, 8, 2); 
    echo '<Play>http://****/voicefile/76.wav</Play>'; //MSG: the shipping date for this order is 
	echo '<Say voice="woman"> '.$est_Ship_Month.' '.$est_Ship_Day.' </Say>'; 
}

function tracking_msg()
{ echo '<Play>http://***/voicefile/77.wav</Play>'; //Please note that it may take up to twenty four hours from the date of shipping for the tracking number to show up on the shipper's website.
}

function call_sales()
{
date_default_timezone_set('America/New_York');     //---------------------------------- Set the timezone to America(EST). 
$hour = date('G');

if ($hour >=8 && $hour<18)  
{ echo '<Dial><Number sendDigits="300">+1******411</Number></Dial>';}
else 
{ echo '<Dial><Number sendDigits="431">+1******411</Number></Dial>';}
}

//----Display the headers for the report table. 
function table_headers(){
			echo "<tr>";
				echo "<th>CALLER ID</th>";
				echo "<th>CALLER ZIP CODE</th>";
				echo "<th>CALL TIME STAMP</th>";
				echo "<th>PO NUMBER</th>";
				echo "<th>PO MATCH</th>";
				echo "<th>STORE NUMBER</th>";
				echo "<th>STORE MATCH</th>";
				echo "<th>ORDER STATUS</th>";	
			echo "</tr>";
}

?>
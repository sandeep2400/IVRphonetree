<?php
include_once('/header.php');     
include_once('/resources.php');          

try{$conn = new PDO("sqlsrv:server = tcp:******,1433; Database = ****", "****", "****");
	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); }
catch(PDOException $e){
	 echo $e->getMessage(); }
//----Unique Calls in the last 7 days. 
    $stmt = $conn->prepare("Select count(distinct(Call_sid)) from IVRLog where CONVERT(date,Call_datetime) BETWEEN getdate() - 7 AND getdate();");
    $stmt->execute(); $countseven = $stmt->fetchColumn(); 

//----successful in the last 7 days. 
    $stmt = $conn->prepare("Select count(PONumber) from IVRLog where (CONVERT(date,Call_datetime) BETWEEN getdate() - 7 AND getdate()) and (Order_status!=' ');");
    $stmt->execute(); $POsuccess = $stmt->fetchColumn(); 

//----failed in the last 7 days. 
    $stmt = $conn->prepare("Select count(PONumber) from IVRLog where (CONVERT(date,Call_datetime) BETWEEN getdate() - 7 AND getdate()) and (Order_status=' ') and (PONumber!=' ');");
    $stmt->execute(); $POfail = $stmt->fetchColumn(); 

//----Sales data latest as of
    $stmt = $conn->prepare("select top 1 CONVERT(datetime, TimeCreated) as TimeCreated from  ordertrack order by CAST(TimeCreated as datetime) desc");
    $stmt->execute(); $result = $stmt->fetch(PDO::FETCH_ASSOC);  $refreshdate = $result['TimeCreated'];

//----Unique Calls in the last 7 days. 
    $stmt = $conn->prepare("select count(distinct(Call_sid)) from IVRlog");
    $stmt->execute(); $countall = $stmt->fetchColumn(); 
	
	echo "<div id=\"stats\">";     
		echo '<ul class="quickstats">';
			echo "<li><p> Calls to the IVR (last 7 days): ".$countseven."</p></li>";
			echo "<li><p> Successful PO lookups (last 7 days): ".$POsuccess."</p></li>";
			echo "<li><p> Failed PO lookups (last 7 days): ".$POfail."</p></li>";
			echo "<li><p> Sales data latest as of: ".$refreshdate."</p></li>";
			echo "<li><p> Other Data ?  </p></li>";
			echo "<li><p> All calls to the IVR: ".$countall."</p></li>";
		echo '</ul>';
	echo "</div>";

echo "<div id=\"caltable\">";     
		$sql = "Select Call_From, Call_From_Zip, Call_datetime, PONumber, PO_Match, Store_Number, Store_Match, Order_Status from IVRLog where (CONVERT(date,Call_datetime) BETWEEN getdate() - 7 AND getdate())  AND (PONumber!=' ') order by Call_datetime desc;";
		$stmt = $conn->prepare($sql);     $stmt->execute();     $result = $stmt->fetch(PDO::FETCH_ASSOC);
		echo "<table>";
		echo "<tr><th colspan = \"8\">ALL PURCHASE ORDER LOOKUPS IN THE LAST 7 DAYS</th></tr>";
		table_headers();
		if ($result)
		{ while ($result){
			echo "<tr>";
				echo"<td>". $result['Call_From']."</td>";
				echo"<td>". $result['Call_From_Zip'] ."</td>";
				echo"<td>". $result['Call_datetime'] ."</td>";
				echo"<td>". $result['PONumber'] ."</td>";
				echo"<td>". $result['PO_Match'] ."</td>";
 				echo"<td>". $result['Store_Number'] ."</td>";
 				echo"<td>". $result['Store_Match'] ."</td>";
				echo"<td>". $result['Order_Status'] ."</td>";
			echo "</tr>";
			$result = $stmt->fetch(PDO::FETCH_ASSOC);
          }
		}
	  else
		 {echo "<tr><td colspan = \"5\">THERE ARE NO MATCHING RECORDS IN THE DATABASE</th></tr>";
		 }

		echo "</table>";

		$sql = "Select Call_From, Call_From_Zip, Call_datetime, PONumber, PO_Match, Store_Number, Store_Match, Order_Status from IVRLog where (CONVERT(date,Call_datetime) BETWEEN getdate() - 7 AND getdate())  AND (PONumber!=' ') AND (Order_status!=' ') order by Call_datetime desc;";
		$stmt = $conn->prepare($sql);     $stmt->execute();     $result = $stmt->fetch(PDO::FETCH_ASSOC);
		echo "<table>";
			echo "<tr><th colspan = \"8\">SUCCESSFUL PURCHASE ORDER LOOKUPS IN THE LAST 7 DAYS</th></tr>";
		table_headers();
		if ($result)
		{ while ($result){
			echo "<tr>";
				echo"<td>". $result['Call_From']."</td>";
				echo"<td>". $result['Call_From_Zip'] ."</td>";
				echo"<td>". $result['Call_datetime'] ."</td>";
				echo"<td>". $result['PONumber'] ."</td>";
				echo"<td>". $result['PO_Match'] ."</td>";
				echo"<td>". $result['Store_Number'] ."</td>";
				echo"<td>". $result['Store_Match'] ."</td>";
				echo"<td>". $result['Order_Status'] ."</td>";
			echo "</tr>";
			$result = $stmt->fetch(PDO::FETCH_ASSOC);
          }
		}
	  else
		 {echo "<tr><td colspan = \"5\">THERE ARE NO MATCHING RECORDS IN THE DATABASE</th></tr>";
		 }
		echo "</table>";

		$sql = "Select Call_From, Call_From_Zip, Call_datetime, PONumber, PO_Match, Store_Number, Store_Match, Order_Status from IVRLog where (CONVERT(date,Call_datetime) BETWEEN getdate() - 7 AND getdate())  AND (PONumber!=' ') AND (Order_status=' ') order by Call_datetime desc;";
		$stmt = $conn->prepare($sql);     $stmt->execute();     $result = $stmt->fetch(PDO::FETCH_ASSOC);
		echo "<table>";
			echo "<tr><th colspan = \"8\">FAILED PURCHASE ORDER LOOKUPS IN THE LAST 7 DAYS</th></tr>";
		table_headers();
		if ($result)
		{ while ($result){
			echo "<tr>";
				echo"<td>". $result['Call_From']."</td>";
				echo"<td>". $result['Call_From_Zip'] ."</td>";
				echo"<td>". $result['Call_datetime'] ."</td>";
				echo"<td>". $result['PONumber'] ."</td>";
				echo"<td>". $result['PO_Match'] ."</td>";
				echo"<td>". $result['Store_Number'] ."</td>";
				echo"<td>". $result['Store_Match'] ."</td>";
				echo"<td>". $result['Order_Status'] ."</td>";
			echo "</tr>";
			$result = $stmt->fetch(PDO::FETCH_ASSOC);
          }
		}
	  else
		 {echo "<tr><td colspan = \"8\">THERE ARE NO MATCHING RECORDS IN THE DATABASE</th></tr>";
		 }
		echo "</table>";

echo "</div>";
include_once ('/footer.php');	  
?>	

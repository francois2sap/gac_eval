<?php  

$mysqli = new mysqli("mysql57","root","secret","francois");
if ($mysqli->connect_error){
	die('Erreur de connexion (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
}	

if ($mysqli->query("TRUNCATE `appels`") === FALSE){
echo("Truncate NOT OK.\n");
}
$row = 1;

if (($handle = fopen('tickets_appels_201202.csv', "r")) !== FALSE){
	while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
		$num = count($data);
		$row++;
		$nbr_account = $mysqli->real_escape_string($data[0]);
		$nbr_bill = $mysqli->real_escape_string($data[1]);
		$nbr_sub = $mysqli->real_escape_string($data[2]);
		$date = date_parse_from_format('d-m-Y', $data[3]);
		if (checkdate ($date['month'], $date['day'], $date['year']) == FALSE){
			echo "Date incorrect : '$data[3]', Ligne $row <br>";
			continue;
		}
		$date_mysql = $mysqli->real_escape_string($date['year'].'-'.$date['month'].'-'.$date['day']);
		if(strlen($data[4]) == 0) {
			echo "heure non specifie : '$data[4]', Ligne $row <br>";
			continue;
		}
		if (strtotime($data[4]) == false){
			echo "mauvais format d'heure : '$data[4]', Ligne $row <br>";
			continue;
		}
		$hour = $mysqli->real_escape_string($data[4]);
		if (strpos($data[5], ":") === false) {
			$calling_time = $mysqli->real_escape_string($data[5]);
		}
		else {
			list($hour, $min, $sec) = explode(":", $data[5]);
			$calling_time = $mysqli->real_escape_string((($hour * 3600) + ($min * 60) + $sec));
		}
		
		if (empty ($calling_time)){
			$calling_time = '0';
		}

		if (strpos($data[6], ":") === false){
			$time_billed = $mysqli->real_escape_string($data[6]);
		} 
		
		else {
			list($hour, $min, $sec) = explode(":", $data[6]);
			$time_billed = $mysqli->real_escape_string((($hour * 3600) + ($min * 60) + $sec));
		}
		
		if (empty ($time_billed)){
			$time_billed = '0';
		}
		$type = $mysqli->real_escape_string($data[7]);
		$query = "INSERT INTO `appels` (`num_compte`, `num_fac`, `num_abo`, `date`, `heure`, `temps_appel`, `temps_facture`, `type`) VALUES ('$nbr_account', '$nbr_bill', '$nbr_account', '$date_mysql', '$hour', '$calling_time','$time_billed', '$type')";
		if ($mysqli->query($query) === FALSE){
			echo("$query Insert NOT OK." . $mysqli->error . " " . $mysqli->errno);
		}
	}
	$query = "SELECT SUM(`temps_appel`) FROM `appels` WHERE date >= '2012-02-15'";
 	$resultat_query = $mysqli->query($query);	
	if ($resultat_query === FALSE){
		echo("$query Query NOT OK." . $mysqli->error . " " . $mysqli->errno);
	} else {
		$row = $resultat_query->fetch_array(MYSQLI_NUM);
		if ($row === NULL) {
			echo "<br>error";
		} else {
			echo "La durée totale réelle des appels effectués après le 15/02/2012 est de  " .gmdate("H:i:s", (int)$row[0]) . "h soit " . $row[0] . " secondes. <br>";
		}
		$resultat_query->free();
	}

	$query = "SELECT `temps_facture` FROM `appels` WHERE `heure` NOT BETWEEN '08:00:00' AND '18:00:00' ORDER BY `temps_facture` DESC LIMIT 10";
	$resultat_query = $mysqli->query($query);	
	if ($resultat_query === FALSE){
		echo("<br>$query Query NOT OK." . $mysqli->error . " " . $mysqli->errno);
	} else {
		echo "<br>TOP 10 temps facture en secondes hors 8:00-18:00 <br>";
		while (($row = $resultat_query->fetch_array(MYSQLI_NUM)) !== NULL) {
			echo $row[0] . '<br>';
		}
		$resultat_query->free();
	}
	


	$query = "SELECT COUNT( * ) FROM `appels` WHERE `temps_appel` = 0";
	$resultat_query = $mysqli->query($query);
	if ($resultat_query === FALSE){
		echo("<br>$query Query NOT OK." . $mysqli->error . " " . $mysqli->errno);
	} else {
		$row = $resultat_query->fetch_array(MYSQLI_NUM);
		echo "<br>Le Total des SMS envoyes est de : $row[0] <br>";
		$resultat_query->free();
	}
	
	fclose($handle);
	$mysqli->close();
}
?>
<!-- $sql = "INSERT INTO 'appels'('num_compte','num_fac','num_abo','date','heure','temps_appel','temps_facture','type') VALUES "; -->

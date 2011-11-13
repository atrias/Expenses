<?
//error_reporting(E_ALL ^ E_NOTICE); 

$mysql_host = "localhost";
$mysql_user = "expensesuser";
$mysql_pass = "expensespass";
$mysql_base = "expenses";

$edit_pass = "editpass"; // password to edit entries

$purposes = array(
        'home' => array('Για το Σπίτι',''),
        'user1' => array('Για τον Χρήστη1',''),
        'user2' => array('Για τον Χρήστη2',''),
        'gift' => array('Δώρο για άλλον',''),
        'other' => array('Άλλο','')
);

$weekdays = array(
	'Mon' => 'Δευ',
	'Tue' => 'Τρι',
	'Wed' => 'Τετ',
	'Thu' => 'Πεμ',
	'Fri' => 'Παρ',
	'Sat' => 'Σάβ',
	'Sun' => 'Κυρ'
);

$db = @mysql_connect($mysql_host, $mysql_user, $mysql_pass)  or  die( "Unable  to  connect to  SQL  server");
@mysql_select_db($mysql_base, $db);
mysql_query("SET CHARACTER SET utf8,character_set_results='utf8',NAMES 'utf8' ");

// insert, update and delete
if (isset($_POST["add_expence"]))
{
    if (!empty($_POST["descr"]) and (floatval(str_replace(",", ".", $_POST["amount"])) > 0) and !empty($_POST["time"]) and !empty($_POST["purpose"])) {
		list($d, $m, $y) = explode("/", $_POST["time"]);
		$sql = "INSERT INTO `expenses` SET 
			descr = '".mysql_real_escape_string($_POST["descr"])."', 
			amount = ".floatval(str_replace(",", ".", $_POST["amount"])).", 
			time = ".mktime(12, 0, 0, $m, $d, $y).", 
			purpose = '".mysql_real_escape_string($_POST["purpose"])."'";
		$result = mysql_query($sql);
		if ($result) $okmsg = '<font color="green" size="+2">Επιτυχής Καταχώρηση</font>';	
	}
}
elseif (isset($_POST["edit_expence"]) and $_POST["pswd"] == $edit_pass)
{
	if (isset($_POST["edit"])) {
		list($d, $m, $y) = explode("/", $_POST["time"]);
		$sql = "UPDATE `expenses` SET 
			descr = '".mysql_real_escape_string($_POST["descr"])."', 
			amount = ".floatval(str_replace(",", ".", $_POST["amount"])).", 
			time = ".mktime(12, 0, 0, $m, $d, $y).", 
			purpose = '".mysql_real_escape_string($_POST["purpose"])."'
			WHERE id = ".intval($_POST["edit_expence"]);
		$result = mysql_query($sql);
		if ($result) $okmsg = '<font color="blue" size="+2">Επιτυχής Διόρθωση</font>';
	}
	elseif (isset($_POST["delete"])) {
		$sql = "DELETE FROM `expenses` WHERE id = ".intval($_POST["edit_expence"]);
		$result = mysql_query($sql);
		if ($result) $okmsg = '<font color="red" size="+2">Επιτυχής Διαγραφή</font>';		
	}
}

// search and default select
if (isset($_POST["search_expence"]))
{
	$sqlwhere = '';
	$sqlwhereArr = array();
	
	if (!empty($_POST["searchdescr"])) $sqlwhereArr[] = "`descr` LIKE '%".mysql_real_escape_string($_POST["searchdescr"])."%'";

	if (!empty($_POST["minamount"])) $sqlwhereArr[] = "`amount` >= ".floatval($_POST["minamount"]);

	if (!empty($_POST["maxamount"])) $sqlwhereArr[] = "`amount` <= ".floatval($_POST["maxamount"]);

	if (!empty($_POST["fromtime"]))
	{
		list($d, $m, $y) = explode("/", $_POST["fromtime"]);
		$sqlwhereArr[] = "`time` >= ".mktime(0, 0, 0, $m, $d, $y);
		$fromtime = date("j/n/Y", mktime(0, 0, 0, $m, $d, $y));
	}
	else $fromtime = "";

	if (!empty($_POST["totime"]))
	{
		list($d, $m, $y) = explode("/", $_POST["totime"]);
		$sqlwhereArr[] = "`time` <= ".mktime(23, 59, 59, $m, $d, $y);
		$totime = date("j/n/Y", mktime(23, 59, 59, $m, $d, $y));
	}
	else $totime = "";
	
	if (!empty($_POST["searchpurpose"]))
	{
		$purposeArr = array();
		foreach ($_POST["searchpurpose"] as $purp)
		{
		  $purposeArr[] = "`purpose` = '".$purp."'";
		  $purposes[$purp][1] = 'checked="yes"';
		}
		$sqlwhereArr[] = "(".implode(" OR ",$purposeArr).")";
	}

	if (!empty($sqlwhereArr))
	{
		$sqlwhere = "WHERE ".implode(" AND ",$sqlwhereArr)." ";
	}
	
	$sql = "SELECT * FROM `expenses` ".$sqlwhere." ORDER BY id DESC";
	$search_type = 'Αναζήτηση βάση κριτηρίων';
}
else
{
	$sql = "SELECT * FROM `expenses` WHERE `time` >= ".(time()-604800)." ORDER BY id DESC LIMIT 50";
	$search_type = 'Τελευταίες 7 μέρες';
}
?>

<html>
<head>
	<title>ΕΞΟΔΑ</title>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" /> 
	<LINK REL="SHORTCUT ICON" HREF="favicon.ico">
	<script language="JavaScript" src="calendar.js"></script>
</head>
<body>

<h1>ΕΞΟΔΑ</h1>
<div style="width: 100%; position: relative;">

	<div style="float: left; padding-right:10px;">
		<form action="" method="post" name="addexp">
		<input type=hidden name="add_expence">
		<table style="border-style:solid;" width="290">
			<tr><th colspan="2">Εισαγωγή</th>
			<tr><td align="right">Περιγραφή</td><td><input type="text" name="descr"></td></tr>
			<tr><td align="right">Ποσό</td><td><input type="text" name="amount" size="7"></td></tr>
			<tr><td align="right">Ημερομηνία</td><td><input type="text" name="time" size="11" value="<?= date("j/n/Y", time()); ?>" onClick="show_calendar('document.addexp.time',document.addexp.time.value);"></td></tr>
			<tr>
				<td align="right">Σκοπός</td>
				<td>
					<select name="purpose">
						<option value="home">Για το Σπίτι</option>
						<option value="user1">Για τον Χρήστη1</option>
						<option value="user2">Για τον Χρήστη2</option>
						<option value="gift">Δώρο για άλλον</option>
						<option value="other">Άλλο</option>
					</select>
				</td>
			</tr>
			<tr><td colspan="2" align="center"><input type="submit" value="Καταχώρηση"></td></tr>
		</table>
		</form>

		<form action="" method="post" name="searchexp">
		<input type=hidden name="search_expence">
		<table style="border-style:solid;" width="290">
			<tr><th colspan="2">Αναζήτηση</th>
			<tr><td align="right">Περιγραφή</td><td><input type="text" name="searchdescr" value="<?= $_POST["searchdescr"]; ?>"></td></tr>
			<tr><td align="right">Ελάχιστο Ποσό</td><td><input type="text" name="minamount" value="<?= $_POST["minamount"]; ?>" size="7"></td></tr>
			<tr><td align="right">Μέγιστο Ποσό</td><td><input type="text" name="maxamount" value="<?= $_POST["maxamount"]; ?>" size="7"></td></tr>
			<tr><td align="right">Από Ημερομηνία</td><td><input type="text" name="fromtime" size="11" value="<?= $fromtime; ?>" onClick="show_calendar('document.searchexp.fromtime',document.searchexp.fromtime.value);"></td></tr>
			<tr><td align="right">Έως Ημερομηνία</td><td><input type="text" name="totime" size="11" value="<?= $totime; ?>" onClick="show_calendar('document.searchexp.totime',document.searchexp.totime.value);"></td></tr>
			<tr>
				<td align="right">Σκοπός</td>
				<td>
					<input type="checkbox" name="searchpurpose[]" <?= $purposes['home'][1]; ?> value="home">Για το Σπίτι<br>
					<input type="checkbox" name="searchpurpose[]" <?= $purposes['user1'][1]; ?> value="user1">Για τον Χρήστη1<br>
					<input type="checkbox" name="searchpurpose[]" <?= $purposes['user2'][1]; ?> value="user2">Για τον Χρήστη2<br>
					<input type="checkbox" name="searchpurpose[]" <?= $purposes['gift'][1]; ?> value="gift">Δώρο για άλλον<br>
					<input type="checkbox" name="searchpurpose[]" <?= $purposes['other'][1]; ?> value="other">Άλλο
				</td>
			</tr>
			<tr><td colspan="2" align="center"><input type="submit" value="Αναζήτηση"></td></tr>
			<tr><td colspan="2" align="center"><input type="reset" value="Επαναφορά" onClick="window.location = window.location.href;"></td></tr>
		</table>
		</form>
	</div>

	<div> 
		<div id="editformdiv" style="display:none;">
			<form id="editform" action="" method="post" name="editexp">
			<input type=hidden name="edit_expence">
			<table style="border-style:solid;">
				<tr><th colspan="2">Διόρθωση (<span id="editidshow"></span>)</th>
				<tr><td align="right">Περιγραφή</td><td><input type="text" name="descr"></td></tr>
				<tr><td align="right">Ποσό</td><td><input type="text" name="amount" size="7"></td></tr>
				<tr><td align="right">Ημερομηνία</td><td><input type="text" name="time" size="11" onClick="show_calendar('document.editexp.time',document.editexp.time.value);"></td></tr>
				<tr>
					<td align="right">Σκοπός</td>
					<td>
						<select name="purpose">
							<option value="home">Για το Σπίτι</option>
							<option value="user1">Για τον Χρήστη1</option>
							<option value="user2">Για τον Χρήστη2</option>
							<option value="gift">Δώρο για άλλον</option>
							<option value="other">Άλλο</option>
						</select>
					</td>
				</tr>
				<tr><td align="right">Κωδικός</td><td><input type="password" name="pswd"></td></tr>
				<tr><td align="center" colspan="2"><input type="submit" name="edit" value="Διόρθωση" onClick="return confirm('Να γίνει διόρθωση?');"><input type="submit" name="delete" value="Διαγραφή" onClick="return confirm('Να γίνει διαγραφή?');"></td></tr>
				<tr><td colspan="2" align="center"><input type="button" value="Ακύρωση Διόρθωσης" onClick="hideeditform(document.forms['editform'].elements['edit_expence'].value);"></td></tr>		
			</table>
			<? if (isset($_POST["search_expence"]))	{ ?>
				<input type=hidden name="search_expence">
				<input type=hidden name="searchdescr" value="<?= htmlspecialchars($_POST["searchdescr"]); ?>">
				<input type=hidden name="minamount" value="<?= htmlspecialchars($_POST["minamount"]); ?>">
				<input type=hidden name="maxamount" value="<?= htmlspecialchars($_POST["maxamount"]); ?>">
				<input type=hidden name="fromtime" value="<?= htmlspecialchars($_POST["fromtime"]); ?>">
				<input type=hidden name="totime" value="<?= htmlspecialchars($_POST["totime"]); ?>">
				<? foreach ($_POST["searchpurpose"] as $purp) { ?>
					<input type=hidden name="searchpurpose[]" value="<?= htmlspecialchars($purp); ?>">
				<? } ?>
			<? } ?>
			</form>
		</div>

		<span id="okmsg"><?= $okmsg; ?></span>
		<table border="1" cellpadding="5">
			<tr><th colspan="5"><?= $search_type; ?></th></tr>
			<tr><th colspan="5"><font color="blue" size="+2" id="totalamount">???</font></th></tr>	
			<tr>
				<th>id</th>
				<th>Περιγραφή</th>
				<th>Ποσό</th>
				<th>Ημερομηνία</th>
					<th>Σκοπός</th>
			</tr>
			<?
			$total = 0;
			$last_edit_id = 0;
			$result = mysql_query($sql);
			while ( $row = mysql_fetch_assoc($result) )
			{
				if ($_POST["edit_expence"] == $row["id"]) {
					$last_edit_color = 'bgcolor="#1E90FF"';
					$last_edit_id = $row["id"];
				}
				else $last_edit_color = ''; ?>
				
				<tr id="row_<?= $row["id"]; ?>" <?= $last_edit_color; ?>>
					<td><span onClick="hideeditform(document.forms['editform'].elements['edit_expence'].value); showeditform(<?= $row["id"].",'".$row["descr"]."','".$row["amount"]."','".date("j/n/Y", $row["time"])."','".$row["purpose"]."'"; ?>)" style="cursor: pointer;"><?= $row["id"]; ?></span></td>
					<td ><?= $row["descr"]; ?></td>
					<td nowrap="nowrap"><?= $row["amount"]; ?>€</td>
					<td nowrap="nowrap"><?= $weekdays[date(D, $row["time"])]." ".date("j/n/Y", $row["time"]); ?></td>
					<td nowrap="nowrap"><?= $purposes[$row["purpose"]][0]; ?></td>
				</tr>
				<? $total += $row["amount"];
			}
			?>
		</table>
	</div>

</div>
</body>
</html>

<script type="text/javascript">
	document.getElementById('totalamount').innerHTML = 'Σύνολο για τα παρακάτω <?= sprintf("%.2f", $total); ?> €';
	var last_edit_id = <?= $last_edit_id; ?>; 

	function showeditform(id,descr,amount,time,purpose) {
		if (document.forms['editform'].elements['edit_expence'].value != id)
		{
			document.getElementById('okmsg').style.display = 'none';
			if (last_edit_id > 0) document.getElementById('row_'+last_edit_id).bgColor = '';
						
			document.getElementById('editformdiv').style.display = 'block';
			document.getElementById('row_'+id).bgColor = '#FFFF00';

			document.getElementById('editidshow').innerHTML = id;
			document.forms['editform'].elements["edit_expence"].value = id;
			document.forms['editform'].elements["descr"].value = descr;
			document.forms['editform'].elements["amount"].value = amount;
			document.forms['editform'].elements["time"].value = time;
			for (var i=0; i < document.forms['editform'].elements["purpose"].length; i++) {
				if (document.forms['editform'].elements["purpose"][i].value == purpose) {
					document.forms['editform'].elements["purpose"][i].selected = true;
				}
			}
		}
		else // if edit form for this id is already shown then hide it
		{
			document.getElementById('editformdiv').style.display = 'none';
			document.getElementById('row_'+id).bgColor = '';
			document.forms['editform'].elements['edit_expence'].value = '';
		}
	}
	
	function hideeditform(id) {
		document.getElementById('editformdiv').style.display = 'none';
		if (document.getElementById('row_'+id)) document.getElementById('row_'+id).bgColor = '';
	}
</script>

<? mysql_close(); ?>

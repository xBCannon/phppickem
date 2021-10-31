<?php
require('includes/application_top.php');
require('includes/classes/team.php');

$team = $_GET['team'];
if (empty($team)) {
	$week = $_GET['week'];

	//get current week
	$currentWeek = getCurrentWeek();
	if (empty($week)) $week = $currentWeek;
}

include('includes/header.php');
?>
<h1>Schedules</h1>
<p>Select a Team:
<select name="team" onchange="javascript:location.href='schedules.php?team=' + this.value;">
	<option value=""></option>
<?php
$sql = "select * from " . DB_PREFIX . "teams order by city, team";
$query = $mysqli->query($sql);
if ($query->num_rows > 0) {
	while ($row = $query->fetch_assoc()) {
		echo '	<option value="' . $row['teamID'] . '"' . ((!empty($team) && $team == $row['teamID']) ? ' selected="selected"' : '') . '>' . $row['city'] . ' ' . $row['team'] . '</option>' . "\n";
	}
}
$query->free;
?>
</select> <b>OR</b> Week:
<select name="week" onchange="javascript:location.href='schedules.php?week=' + this.value;">
	<option value="all"<?php echo (($week == 'all') ? ' selected="selected"' : ''); ?>>All</option>
<?php
$sql = "select distinct weekNum from " . DB_PREFIX . "schedule order by weekNum;";
$query = $mysqli->query($sql);
if ($query->num_rows > 0) {
	while ($row = $query->fetch_assoc()) {
		echo '	<option value="' . $row['weekNum'] . '"' . ((!empty($week) && $week == $row['weekNum']) ? ' selected="selected"' : '') . '>' . $row['weekNum'] . '</option>' . "\n";
	}
}
$query->free;
?>
</select></p>
<?php
if (!empty($team)) {
	$teamDetails = new team($team);
	echo '<h2><img src="images/logos/' . $team . '.gif" height="60" /> ' . $teamDetails->teamName . ' Schedule</h2>';
}

$sql = "select s.*, ht.city, ht.team, ht.displayName, vt.city, vt.team, vt.displayName from " . DB_PREFIX . "schedule s ";
$sql .= "inner join " . DB_PREFIX . "teams ht on s.homeID = ht.teamID ";
$sql .= "inner join " . DB_PREFIX . "teams vt on s.visitorID = vt.teamID ";
if (!empty($team)) {
	//filter team
	$where .= " where homeID = '" . $team ."' or visitorID = '" . $team . "'";
} else if (!empty($week)) {
	//filter week
	if ($week !== 'all') {
		$where .= " where weekNum = " . $week;
	}
}
$sql .= $where . " order by gameTimeEastern";
$query = $mysqli->query($sql);
if ($query->num_rows > 0) {
	echo '<table cellpadding="4" cellspacing="0" class="table1">' . "\n";
	echo '	<tr><th align="left">Game</th><th>Time / Result</th></tr>' . "\n";
	$i = 0;
	$prevWeek = 0;
	while ($row = $query->fetch_assoc()) {
		if ($prevWeek !== $row['weekNum'] && empty($team)) {
			echo '	<tr class="subheader"><td colspan="4"><b>Week ' . $row['weekNum'] . '</b></td></tr>' . "\n";
		}
		$homeTeam = new team($row['homeID']);
		$visitorTeam = new team($row['visitorID']);
		$rowclass = (($i % 2 == 0) ? ' class="altrow"' : '');
		echo '	<tr' . $rowclass . '>' . "\n";
		echo '		<td>' . $visitorTeam->teamName . ' @ ' . $homeTeam->teamName . '</td>' . "\n";
		if (is_numeric($row['homeScore']) && is_numeric($row['visitorScore'])) {
            //if score is entered, show result
            if ($row['final'] != 0) {
                if (intval($row['homeScore']) > intval($row['visitorScore'])) {
                    echo '		<td><img src="images/helmets_small/' . $visitorTeam->teamID . 'R.gif" /><span class="cls1"> ' . $visitorTeam->teamID . ' ' . $row['visitorScore'] . ' : </span><b><span class="cls2">' . $row['homeScore'] . ' ' . $homeTeam->teamID . ' </span></b><img src="images/helmets_small/' . $homeTeam->teamID . 'L.gif" />' . '</td>' . "\n";
                } elseif (intval($row['visitorScore']) > intval($row['homeScore'])) {
                    echo '		<td><img src="images/helmets_small/' . $visitorTeam->teamID . 'R.gif" /><b><span class="cls2"> ' . $visitorTeam->teamID . ' ' . $row['visitorScore'] . '</span></b> : <span class="cls1">' . $row['homeScore'] . ' ' . $homeTeam->teamID . ' </span><img src="images/helmets_small/' . $homeTeam->teamID . 'L.gif" />' . '</td>' . "\n";
                } else {
                    echo '		<td><img src="images/helmets_small/' . $visitorTeam->teamID . 'R.gif" /><span class="cls2"> ' . $visitorTeam->teamID . ' ' . $row['visitorScore'] . '</span> : <span class="cls1">' . $row['homeScore'] . ' ' . $homeTeam->teamID . ' </span><img src="images/helmets_small/' . $homeTeam->teamID . 'L.gif" />' . '</td>' . "\n";
                }
            } elseif ($row['final'] = 1) {
                if (intval($row['homeScore']) > intval($row['visitorScore'])) {
                    echo '		<td><b><span class="cls1">LIVE: </span></b><img src="images/helmets_small/' . $visitorTeam->teamID . 'R.gif" /> ' . $visitorTeam->teamID . ' ' . $row['visitorScore'] . ' : <b>' . $row['homeScore'] . ' ' . $homeTeam->teamID . '</b> <img src="images/helmets_small/' . $homeTeam->teamID . 'L.gif" />' . '</td>' . "\n";
                } elseif (intval($row['visitorScore']) > intval($row['homeScore'])) {
                    echo '		<td><b><span class="cls1">LIVE: </span></b><img src="images/helmets_small/' . $visitorTeam->teamID . 'R.gif" /><b> ' . $visitorTeam->teamID . ' ' . $row['visitorScore'] . '</b> : ' . $row['homeScore'] . ' ' . $homeTeam->teamID . ' <img src="images/helmets_small/' . $homeTeam->teamID . 'L.gif" />' . '</td>' . "\n";
                }
            }
        } else {
            //show time
            echo '		<td><img src="images/helmets_small/' . $visitorTeam->teamID . 'R.gif" />' . date('D n/j g:i a', strtotime($row['gameTimeEastern'])) . ' ET <img src="images/helmets_small/' . $homeTeam->teamID . 'L.gif" /></td></td>' . "\n";
        }
		echo '	</tr>' . "\n";
		$prevWeek = $row['weekNum'];
		$i++;
	}
	echo '</table>' . "\n";
}
$query->free;

include('includes/footer.php');

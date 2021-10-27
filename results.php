<?php
header("Refresh: 300;url='results.php'");

require('includes/application_top.php');
require_once('includes/functions.php');

$week = (int)$_GET['week'];
if (empty($week)) {
	//get current week
	$week = (int)getWeekByScore();
}

$cutoffDateTime = getCutoffDateTime($week);
$weekExpired = ((date("U", time() + (SERVER_TIMEZONE_OFFSET * 3600)) > strtotime($cutoffDateTime)) ? 1 : 0);

include('includes/header.php');

//display week nav
$sql = "select distinct weekNum from " . DB_PREFIX . "schedule order by weekNum;";
$query = $mysqli->query($sql);
$weekNav = '<div class="navbar3"><b>Go to week:</b> ';
$i = 0;
while ($row = $query->fetch_assoc()) {
	if ($i > 0) $weekNav .= ' | ';
	if ($week !== (int)$row['weekNum']) {
		$weekNav .= '<a href="results.php?week=' . $row['weekNum'] . '">' . $row['weekNum'] . '</a>';
	} else {
		$weekNav .= $row['weekNum'];
	}
	$i++;
}
$query->free;
$weekNav .= '</div>' . "\n";
echo $weekNav;

//get array of games
$allScoresIn = true;
$games = array();
$sql = "select * from " . DB_PREFIX . "schedule where weekNum = " . $week . " order by gameTimeEastern, gameID";
$query = $mysqli->query($sql);
while ($row = $query->fetch_assoc()) {
	$games[$row['gameID']]['gameID'] = $row['gameID'];
	$games[$row['gameID']]['homeID'] = $row['homeID'];
	$games[$row['gameID']]['visitorID'] = $row['visitorID'];
	// dtjr
	$games[$row['gameID']][$row['homeID']]['score'] = $row['homeScore'];
	$games[$row['gameID']][$row['visitorID']]['score'] = $row['visitorScore'];
	$games[$row['gameID']]['overtime'] = $row['overtime'];
	$games[$row['gameID']]['final'] = $row['final'];
	// dtjr
	$homeScore = (int)$row['homeScore'];
	$visitorScore = (int)$row['visitorScore'];
	if ( ($homeScore + $visitorScore > 0) && ($row['final'] == 1) ) {
		if ($homeScore > $visitorScore) {
			$games[$row['gameID']]['winnerID'] = $row['homeID'];
		}
		if ($visitorScore > $homeScore) {
			$games[$row['gameID']]['winnerID'] = $row['visitorID'];
		}
	} else {
		$games[$row['gameID']]['winnerID'] = '';
		$allScoresIn = false;
	}
}
$query->close();

// dtjr
# include('includes/column_right.php');
if (!$allScoresIn) {
	include('includes/column_countdown.php');
}

$week = (int)$_GET['week'];
if (empty($week)) {
    //get current week
    $week = (int)getWeekByScore();
}
// dtjr

//get array of player picks
$playerPicks = array();
$playerTotals = array();
$sql = "select p.userID, p.gameID, p.pickID, p.points ";
$sql .= "from " . DB_PREFIX . "picks p ";
$sql .= "inner join " . DB_PREFIX . "users u on p.userID = u.userID ";
$sql .= "inner join " . DB_PREFIX . "schedule s on p.gameID = s.gameID ";
$sql .= "where s.weekNum = " . $week . " and u.userName <> 'admin' ";
$sql .= "order by p.userID, s.gameTimeEastern, s.gameID";
$query = $mysqli->query($sql);
$i = 0;
while ($row = $query->fetch_assoc()) {
	$playerPicks[$row['userID']][$row['gameID']] = $row['pickID'];
	if (!empty($games[$row['gameID']]['winnerID']) && $row['pickID'] == $games[$row['gameID']]['winnerID']) {
		//player has picked the winning team
		$playerTotals[$row['userID']] += 1;
	} else {
		$playerTotals[$row['userID']] += 0;
	}
	$i++;
}
$query->free;
?>
<script type="text/javascript">
$(document).ready(function(){
	$(".table tr").mouseover(function() {
		$(this).addClass("over");}).mouseout(function() {$(this).removeClass("over");
	});
	$(".table tr").click(function() {
		if ($(this).attr('class').indexOf('overPerm') > -1) {
			$(this).removeClass("overPerm");
		} else {
			$(this).addClass("overPerm");
		}
	});
	//$( "#table1" ).draggable({ containment: "#table1-container", scroll: false })
});
</script>
<style type="text/css">
.pickTD { width: 24px; font-size: 9px; text-align: center; }
</style>
<!-- dtjr -->
<!-- <h1>Results - Week <?php echo $week; ?></h1> -->
<!-- <h2>Results - Week <?php echo $week; ?> ( <?php echo date('H:i'); ?> ) </h2> -->
		<div class="bg-primary">
			<b>Results - Week <?php echo $week; ?></b>
			<!--
			<span id="jclock1"></span>
			<script type="text/javascript">
			$(function($) {
				var optionsEST = {
			        timeNotation: '24h',
			        am_pm: false,
					utc: true,
					utc_offset: <?php echo -1 * (4 + SERVER_TIMEZONE_OFFSET); ?>
				}
				$('#jclock1').jclock(optionsEST);
		    });
			</script>
			-->
		</div>
<!-- dtjr -->
<?php

$hideMyPicks = hidePicks($user->userID, $week);
if ($hideMyPicks && !$weekExpired) {
    echo '<p style="font-weight: bold; color: green;">** NOTICE: You have selected to hide your picks from other players. Only you can see your picks in the list below.</p>' . "\n";
}

if (!$allScoresIn) {
	echo '<p style="font-weight: bold; color: #DBA400;">** Not all games have been completed and updated for week ' . $week . '.</p>' . "\n";
}

if (sizeof($playerTotals) > 0) {
?>
<!-- dtjr - beg scores -->
<div class="table-responsive">
<table class="table table-striped">
	<thead>
		<tr><th align="left">Scores</th></tr><br>
	</thead>
	<tbody>
<?php
	//loop through all games
	echo '<tr>' . "\n";
	echo '<td>Away</td>' . "\n";
	foreach($games as $game) {
		if ($game['visitorID'] == $game['winnerID'] && (int)$game['final'] == 1) {
			echo '<td><span class="winner">' . $game['visitorID'] . "&nbsp;-&nbsp;" . $game[$game['visitorID']]['score'] . '</span></td>' . "\n";
			// echo '<td><span class="winner">' . $game[$game['visitorID']]['score'] . '</span></td>' . "\n";
		} else {
			echo '<td>' . $game['visitorID'] . "&nbsp;-&nbsp;" . $game[$game['visitorID']]['score'] . '</td>' . "\n";
			// echo '<td> ' . $game[$game['visitorID']]['score'] . ' </td>' . "\n";
		}
	}
	echo '</tr>' . "\n";
	echo '<tr>' . "\n";
	echo '<td>Home</td>' . "\n";
	foreach($games as $game) {
		if ($game['homeID'] == $game['winnerID'] && (int)$game['final'] == 1) {
			echo '<td><span class="winner">' . $game['homeID'] . "&nbsp;-&nbsp;" . $game[$game['homeID']]['score'] . '</span></td>' . "\n";
			// echo '<td><span class="winner">' . $game[$game['homeID']]['score'] . '</span></td>' . "\n";
		} else {
			echo '<td>' . $game['homeID'] . "&nbsp;-&nbsp;" . $game[$game['homeID']]['score'] . '</td>' . "\n";
			// echo '<td> ' . $game[$game['homeID']]['score'] . ' </td>' . "\n";
		}
	}
	echo '</tr>' . "\n";
?>
	</tbody>
</table>

    <h6>
        <span style="font-size:10px;">*Updated every 10 minutes</span></h6>

</div>
<!-- dtjr - end scores -->
<div class="table-responsive">
<table class="table table-striped">
	<thead>
		<tr><th align="left">Player</th><th colspan="<?php echo sizeof($games); ?>">Week <?php echo $week; ?></th><th align="left">Score</th></tr>
	</thead>
	<tbody>
<?php
	$i = 0;
	arsort($playerTotals);
	foreach($playerTotals as $userID => $totalCorrect) {
		$hidePicks = hidePicks($userID, $week);
		if ($i == 0) {
			$topScore = $totalCorrect;
			$winners[] = $userID;
		} else if ($totalCorrect == $topScore) {
			$winners[] = $userID;
		}
		$tmpUser = $login->get_user_by_id($userID);
		$rowclass = (($i % 2 == 0) ? ' class="altrow"' : '');
		//echo '	<tr' . $rowclass . '>' . "\n";
		echo '	<tr>' . "\n";
		switch (USER_NAMES_DISPLAY) {
			case 1:
				echo '		<td>' . trim($tmpUser->firstname . ' ' . $tmpUser->lastname) . '</td>' . "\n";
				break;
			case 2:
				echo '		<td>' . trim($tmpUser->userName) . '</td>' . "\n";
				break;
			default: //3
				echo '		<td><abbr title="' . trim($tmpUser->firstname . ' ' . $tmpUser->lastname) . '">' . trim($tmpUser->userName) . '</abbr></td>' . "\n";
				break;
		}
		//loop through all games
		foreach($games as $game) {
			$pick = '';
			$pick = $playerPicks[$userID][$game['gameID']];
			// dtjr
			$score = $game[$pick]['score'] ;
			// dtjr
			if (!empty($game['winnerID'])) {
				//score has been entered
				if ($playerPicks[$userID][$game['gameID']] == $game['winnerID']) {
					$pick = '<span class="winner">' . $pick . '</span>';
				}
			} else {
				//mask pick if pick and week is not locked and user has opted to hide their picks
				$gameIsLocked = gameIsLocked($game['gameID']);
				if (!$gameIsLocked && !$weekExpired && $hidePicks && (int)$userID !== (int)$user->userID && (int)$user->userID <> 1) {
					$pick = '***';
				}
			}
			echo '		<td class="pickTD">' . $pick . '</td>' . "\n";
		}
		echo '		<td nowrap><b>' . $totalCorrect . '/' . sizeof($games) . ' (' . number_format(($totalCorrect / sizeof($games)) * 100, 2) . '%)</b></td>' . "\n";
		echo '	</tr>' . "\n";
		$i++;
	}

	//if all scores entered, display winner
	if ($allScoresIn) {
		foreach($winners as $winnerID) {
			$winner = $login->get_user_by_id($winnerID);
			if (strlen($winnersHtml) > 0) $winnersHtml .= ', ';
			switch (USER_NAMES_DISPLAY) {
				case 1:
					$winnersHtml .= trim($winner->firstname . ' ' . $winner->lastname);
					break;
				case 2:
					$winnersHtml .= trim($winner->userName);
					break;
				default: //3
					$winnersHtml .= '<abbr title="' . trim($winner->firstname . ' ' . $winner->lastname) . '">' . $winner->userName . '</abbr>';
					break;
			}
		}
		echo '	<tr><th colspan="' . (sizeof($games) + 2) . '" align="left">Winner(s): <span class="cls2">' . $winnersHtml . '</span></th></tr>' . "\n";
	}
?>
	</tbody>
</table>
</div>
<?php
	//display list of absent players
	$sql = "select * from " . DB_PREFIX . "users where `status` = 1 and userID not in(" . implode(',', array_keys($playerTotals)) . ") and userName <> 'admin'";
	$query = $mysqli->query($sql);
	if ($query->num_rows > 0) {
		$absentHtml = '<p><b>Absent Players:</b> ';
		$i = 0;
		while ($row = $query->fetch_assoc()) {
			if ($i > 0) $absentHtml .= ', ';
			switch (USER_NAMES_DISPLAY) {
				case 1:
					$absentHtml .= trim($row['firstname'] . ' ' . $row['lastname']);
					break;
				case 2:
					$absentHtml .= $row['userName'];
					break;
				default: //3
					$absentHtml .= '<abbr title="' . trim($row['firstname'] . ' ' . $row['lastname']) . '">' . $row['userName'] . '</abbr>';
					break;
			}
			$i++;
		}
		echo $absentHtml;
	}
	$query->free;
}

// dtjr
include('includes/column_stats.php');
// dtjr

include('includes/comments.php');

include('includes/footer.php');

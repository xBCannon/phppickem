<?php
require('includes/application_top.php');

$week = (int)$_GET['week'];

if ($_GET['execute'] != 1) {
    if ($user->is_admin) {
        print "<a href=\"getHtmlScores.php?week=" .$week ."&BATCH_SCORE_UPDATE_KEY=" . BATCH_SCORE_UPDATE_KEY . "&execute=1\">Execute?</a></br></br>";
    }
} else {
    print "<a href=\"results.php?week=" .$week . "\">Results</a></br></br>";
}
if (empty($week)) {
    $week = (int)getWeekByScore(); //get current week
}

echo "Week: " . $week . "<br />";

//load source code, depending on the current week, of the website into a variable as a string
//$url = "http://www.nfl.com/ajax/scorestrip?season=".SEASON_YEAR."&seasonType=REG&week=".$week;
$url = "https://site.api.espn.com/apis/site/v2/sports/football/nfl/scoreboard?week=".$week;
if ($xmlData = file_get_contents($url)) {
	$games = json_decode($xmlData, true);
}

//build scores array, to group teams and scores together in games
$scores = array();
$inprogress = array();
$scheduledgames = array();
foreach ($games['events'] as $gameArray) {
	$game = $gameArray['competitions'][0];
	if ($game['status']['type']['completed'] == true) {
        $overtime = (($game['status']['period'] == '5') ? 1 : 0);
        foreach ($game['competitors'] as $gameTeams) {
            if ($gameTeams['homeAway'] == "home") {
                $home_team = $gameTeams['team']['abbreviation'];
                $home_score = (int)$gameTeams['score'];
                if ($home_team == "WSH")
                    $home_team = "WAS";
            }
            if ($gameTeams['homeAway'] == "away") {
                $away_team = $gameTeams['team']['abbreviation'];
                $away_score = (int)$gameTeams['score'];
                if ($away_team == "WSH")
                    $away_team = "WAS";
            }
        }


        $winner = ($away_score > $home_score) ? $away_team : $home_team;
        $gameID = getGameIDByTeamID($week, $home_team);
        if (is_numeric(strip_tags($home_score)) && is_numeric(strip_tags($away_score))) {
            if ($away_score > 0 || $home_score > 0) {
                $final = 1;
                $scores[] = array(
                    'gameID' => $gameID,
                    'awayteam' => $away_team,
                    'visitorScore' => $away_score,
                    'hometeam' => $home_team,
                    'homeScore' => $home_score,
                    'overtime' => $overtime,
                    'winner' => $winner,
                    'final' => $final
                );
            }
        }
    }
	elseif ($game['status']['type']['completed'] == false){
        if ($game['status']['type']['name'] != 'STATUS_SCHEDULED') {
            $overtime = (($game['status']['period'] == '5') ? 1 : 0);
            foreach ($game['competitors'] as $gameTeams) {
                if ($gameTeams['homeAway'] == "home") {
                    $home_team = $gameTeams['team']['abbreviation'];
                    $home_score = (int)$gameTeams['score'];
                    if ($home_team == "WSH")
                        $home_team = "WAS";
                }
                if ($gameTeams['homeAway'] == "away") {
                    $away_team = $gameTeams['team']['abbreviation'];
                    $away_score = (int)$gameTeams['score'];
                    if ($away_team == "WSH")
                        $away_team = "WAS";
                }
            }
            $gameID = getGameIDByTeamID($week, $home_team);
            $final = 0;
            $inprogress[] = array(
                'gameID' => $gameID,
                'awayteam' => $away_team,
                'visitorScore' => $away_score,
                'hometeam' => $home_team,
                'homeScore' => $home_score,
                'overtime' => $overtime,
                'final' => $final
            );
        }
        if($game['status']['type']['name'] == 'STATUS_SCHEDULED'){

            foreach ($game['competitors'] as $gameTeams) {
                if ($gameTeams['homeAway'] == "home") {
                    $home_team = $gameTeams['team']['abbreviation'];
                    $home_score = (int)$gameTeams['score'];
                    if ($home_team == "WSH")
                        $home_team = "WAS";
                }
                if ($gameTeams['homeAway'] == "away") {
                    $away_team = $gameTeams['team']['abbreviation'];
                    $away_score = (int)$gameTeams['score'];
                    if ($away_team == "WSH")
                        $away_team = "WAS";
                }
            }

                $gameID = getGameIDByTeamID($week, $home_team);
                $overtime = 0;
                $final = 0;
                $scheduledgames[] = array(
                    'gameID' => $gameID,
                    'awayteam' => $away_team,
                    'visitorScore' => 0,
                    'hometeam' => $home_team,
                    'homeScore' => 0,
                    'overtime' => 0,
                    'final' => 0
                );

        }
    }

}
if(BATCH_SCORE_UPDATE_ENABLED && !empty($_GET['BATCH_SCORE_UPDATE_KEY']) && $_GET['BATCH_SCORE_UPDATE_KEY'] == BATCH_SCORE_UPDATE_KEY ){
    foreach($scores as $game) {
        $homeScore = ((strlen($game['homeScore']) > 0) ? $game['homeScore'] : 'NULL');
        $visitorScore = ((strlen($game['visitorScore']) > 0) ? $game['visitorScore'] : 'NULL');
        $overtime = ((!empty($game['overtime'])) ? '1' : '0');
        $final =  ((!empty($game['final'])) ? '1' : '0');

        $sql = "update " . DB_PREFIX . "schedule ";
        $sql .= "set homeScore = " . $homeScore . ", visitorScore = " . $visitorScore . ", overtime = " . $overtime . ", final = " . $final . " ";
        $sql .= "where gameID = " . $game['gameID'];
        if ($_GET['execute'] == 1) {
            $mysqli->query($sql) or die('Error updating score: ' . $mysqli->error);
        }
        echo $game['hometeam'] . ' hosting ' . $game['awayteam'] . ' => ' . $sql . '<br />';
    }
    foreach($inprogress as $game) {
        $homeScore = ((strlen($game['homeScore']) > 0) ? $game['homeScore'] : 'NULL');
        $visitorScore = ((strlen($game['visitorScore']) > 0) ? $game['visitorScore'] : 'NULL');
        $overtime = ((!empty($game['overtime'])) ? '1' : '0');
        $final =  ((!empty($game['final'])) ? '1' : '0');

        $sql = "update " . DB_PREFIX . "schedule ";
        $sql .= "set homeScore = " . $homeScore . ", visitorScore = " . $visitorScore . ", overtime = " . $overtime . ", final = " . $final . " ";
        $sql .= "where gameID = " . $game['gameID'];
        if ($_GET['execute'] == 1) {
            $mysqli->query($sql) or die('Error updating score: ' . $mysqli->error);
        }
        echo $game['hometeam'] . ' hosting ' . $game['awayteam'] . ' => ' . $sql . '<br />';
    }
}

//see how the scores array looks
//echo '<pre>' . print_r($scores, true) . '</pre>';
    echo '<p><p><h1>Completed Games:</h1>';
    echo json_encode($scores) . '<br  /> <h1>Games In Progress:</h1> <br />' . json_encode($inprogress) . ' <br /> <h1>Games Unplayed:</h1> <br />' .json_encode($scheduledgames);

//game results and winning teams can now be accessed from the scores array
//e.g. $scores[0]['awayteam'] contains the name of the away team (['awayteam'] part) from the first game on the page ([0] part)
<?php

function get_sort_column($key) {
	switch ($key) {
	    case 'harvested':
	        return 'harvested';
	    case 'destroyed':
	        return 'destroyed';
	    case 'killed':
	        return 'killed';
	}

	return 'score';
}

function get_sql_top($sort) {
	return 
"select 
	s.player player, coalesce(p.name, s.player) name, 
	s.house house, sum(s.killed) killed, sum(s.destroyed) destroyed, sum(s.harvested) harvested, 
	sum(s.score) score 
from 
	scores s 
left join 
	players p on p.player = s.player 
group by 
	s.player, s.house 
order by 
	sum(s.$sort) desc, s.player, s.house;";
}

function get_sql_best($house) {
	return
"select 
	coalesce(p.name, s.player) name, sum(s.score) score 
from 
	scores s 
left join 
	players p on p.player = s.player 
where
	s.house = $house	
group by 
	s.player, s.house 
order by 
	sum(s.score) desc
limit 1;";	
}

function get_house($id) {
	switch ($id) {
		case 1:
			return 'atreides';
		case 2:
			return 'ordos';
	}

	return 'harkonnen';
}

function update_player_name($name) {
  global $db_host;
  global $db_user;
  global $db_password;
  global $db_db;
  global $player;

  $mysqli = new mysqli($db_host, $db_user, $db_password, $db_db);
  $mysqli->query("
  	INSERT INTO players(player, name) VALUES ('$player', '$name')
  	ON DUPLICATE KEY UPDATE name='$name';
  ");
  $mysqli->close();
}

function render_top_3_html() {
  global $db_host;
  global $db_user;
  global $db_password;
  global $db_db;
  global $player;
  global $playerName;

  $harkonnen 	= get_sql_best(0);
  $atreides 	= get_sql_best(1);
  $ordos 		= get_sql_best(2);

  $mysqli = new mysqli($db_host, $db_user, $db_password, $db_db);

  $result = $mysqli->query($atreides);
  if ($row = $result->fetch_object()){
    echo 
"<li>
<div class='score_house atreides'>
  Atreides:
</div>
<div class='score_player atreides'>
	$row->name
</div>
<div class='score atreides'>
	$row->score
</div>
</li>";
  }
  $result->close();

  $result = $mysqli->query($ordos);
  if ($row = $result->fetch_object()){
    echo 
"<li>
<div class='score_house ordos'>
  Ordos:
</div>
<div class='score_player ordos'>
	$row->name
</div>
<div class='score ordos'>
	$row->score
</div>
</li>";
  }
  $result->close();  

  $result = $mysqli->query($harkonnen);
  if ($row = $result->fetch_object()){
    echo 
"<li>
<div class='score_house harkonnen'>
  Harkonnen:
</div>
<div class='score_player harkonnen'>
	$row->name
</div>
<div class='score harkonnen'>
	$row->score
</div>
</li>";
  }
  $result->close();

  $mysqli->close();
}

function render_top_html($sortType) {
  global $db_host;
  global $db_user;
  global $db_password;
  global $db_db;
  global $player;
  global $playerName;

  $sort = get_sort_column($sortType);
  $sql  = get_sql_top($sort);

  $mysqli = new mysqli($db_host, $db_user, $db_password, $db_db);
  $result = $mysqli->query($sql);

  while ($row = $result->fetch_object()){
    $house = get_house($row->house);
    $name = $row->name;
    $editlink = '';

    if ($row->player == $player)  {
		$playerName = $name;
		$name = "(YOU) " . $name;
		$editlink = "<a class='edit' href='#personal' ></a>";
    }

      echo "
  <li>
    <div class='score_player_big'>
      <a class='no-link $house' id='$row->player'>$name</a>
      $editlink
    </div>
    <div class='score score-right $house'>$row->score</div>
    <div class='score score-right $house'>$row->harvested</div>
    <div class='score score-right $house'>$row->destroyed</div>
    <div class='score score-right $house'>$row->killed</div>
  </li> 
  ";
  }

  $result->close();
  $mysqli->close();
}

?>
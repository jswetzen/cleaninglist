<?php
require('db.php');
/*
 * Function definitions
 */
/* Sets status not done on places that were set done before this week */
function unsetOldPlaces($mysqli) {
  $result = $mysqli->query("UPDATE places SET state = 0, changed = NOW() where WEEK(changed,1) <> WEEK(NOW(),1) OR (YEAR(changed) <> YEAR(NOW()))");
  return $result;
}

function switchPlaceStatus($mysqli, $id) {
  if ($stmt = $mysqli->prepare("UPDATE places SET state = NOT state WHERE id = ? LIMIT 1")) {
    $stmt->bind_param("i", $id);
    $return = $stmt->execute();
    $stmt->close();
    return $return;
  } else {
    return false;
  }
}

function addPlace($mysqli, $place) {

  /* Create a prepared statement */
  if ($stmt = $mysqli->prepare("INSERT INTO places (name) VALUES (?)")) {

    /* bind parameters for markers */
    $stmt->bind_param("s", $place);

    /* execute query */
    $return = $stmt->execute();

    if (!$return) {
      //echo $mysqli->error;
    }

    $stmt->close();

    return $return;
  } else {
    return false;
  }
}

function getPlaces($mysqli) {
  $res = $mysqli->query("SELECT * FROM places ORDER BY id");
  return $res;
}

function removePlace($mysqli, $id) {
  if ($stmt = $mysqli->prepare("DELETE FROM places where id=? LIMIT 1")) {
    $stmt->bind_param("i", $id);
    $return = $stmt->execute();
    $stmt->close();
    return $return;
  } else {
    return false;
  }
}

/*
 * Connect to the database
 */
$mysqli = new mysqli($db_host, $db_user, $db_password, $db_database);
if ($mysqli->connect_errno) {
  die("Failed to connect to MySQL: " . $mysqli->connect_error);
}

if (!$mysqli->set_charset('utf8')) {
  printf("Error loading character set utf8: %s\n", $mysqli->error);
}

/*
 * Unset old items
 */
unsetOldPlaces($mysqli);

/*
 * Handle requests
 */
if (isset($_POST['addplace'])) {
  if(!addPlace($mysqli, $_POST['addplace'])) {
    echo "Kunde inte lägga till platsen";
  } else {
    header("Location: " . $_SERVER['PHP_SELF']);
  }
}

if (isset($_GET['switch'])) {
  switchPlaceStatus($mysqli, intval($_GET['switch']));
}

if(isset($_GET['remove'])) {
  removePlace($mysqli, intval($_GET['remove']));
}

/* Get the places */
$places_res = getPlaces($mysqli);

/* close connection */
$mysqli->close();

?><!DOCTYPE html>
<html>
<head>
<meta content="minimum-scale=1.0, width=device-width, maximum-scale=1.0, user-scalable=no" name="viewport" />
<!--<meta content="yes" name="apple-mobile-web-app-capable" /> DISABLED UNTIL APPLE FIXES SAFARI BUGS-->
<meta name="apple-mobile-web-app-status-bar-style" content="black">
<meta charset=utf-8 />
<script src="http://code.jquery.com/jquery.min.js"></script>
<link href="http://getbootstrap.com/dist/css/bootstrap.css" rel="stylesheet" type="text/css" />
<script src="http://getbootstrap.com/dist/js/bootstrap.js"></script>
<title>Connect Church Städ</title>

<script>
/* Asynchronously removes a place both from the database and DOM */
function removePlace(id, name) {
    if(confirm('Ta bort ' + name + '?')) {
$.ajax({
    url: '?remove=' + id,
    success: function () {
        $('span#'+id).prev().remove();
        $('span#'+id).remove();
    }
    });
    }
}

/* Shows or hides the delete buttons */
function switchDeleteButtons() {
    if ($('#edit-link').text() === 'Ändra') {
        $('#edit-link').text('Klar');
        $('.delete').animate(
                {width: "show"},
                {specialEasing: {width:"linear"},
                 duration: 200});
    } else {
        $('#edit-link').text('Ändra');
        $('.delete').animate(
                {width: "hide"},
                {specialEasing: {width:"linear"},
                 duration: 200});
    }
}

/* Add onclick methods to delete and place buttons */
$().ready(function () {
    $('.delete').click(function() {
        var id = $(this).next().attr('id');
        var name = $(this).next().text().trim();
        removePlace(id, name);
    });

    $('.place').click(function() {
        var id = $(this).attr('id');
        $.ajax({
            url: '?switch='+id,
            success: function () {
                $('span#'+id).toggleClass('done');
            }
        });
    });
    /* Fix line-height for delete buttons */
    $('.place').each(function() {
        var height = $(this).outerHeight();
        $(this).prev().find('span').css({'line-height': height+'px'});
    });
});
$( window ).resize(function() {
    /* Fix line-height for delete buttons
       when the window is resized as well */
    $('.place').each(function() {
        var height = $(this).outerHeight();
        $(this).prev().find('span').css({'line-height': height+'px'});
    });
});
</script>

<style>
body {
  text-align: center;
  margin: 10px;
}
a:hover {
  text-decoration: none;
  color: #2A80FA;	
}
a {
  color: #2A80FA;
}
#edit-box {
    text-align: right;
    font-size: 20px;
}
.button-holder {
  position: relative;
}
.delete {
  display: none;
  position: absolute;
  z-index: 1;
  right: 0;
  font-size: 25px;
  font-weight: bold;
  width: 90px;
  border-top-right-radius: 15px;
  border-bottom-right-radius: 15px;
  background-color: red;
}
.place {
  z-index: 2;
  display: block;
  width: auto;
  padding: 10px;
  margin-bottom: 10px;
  background-color: lightgray;
  border-radius: 15px;
  font-size: 25px;
  font-weight: bold;
  text-decoration: none;
  -webkit-touch-callout: none;
}
.done {
  background-color: #00A600;
  color: white;
}
#addplace {
  width: 60%;
  padding: 10px;
  font-size: 25px;
}
</style>
</head>
<body>
  <h1>Connect Church städ</h1>
  <div id="edit-box">
    <a id="edit-link" href="#" onclick="switchDeleteButtons()">Ändra</a>
  </div>
  <div id="places">
    <?php while($place = $places_res->fetch_array()): ?>
    <div class="button-holder">
    <div class="delete">
      <span>X</span>
    </div>
    <span id="<?php echo $place['id']; ?>"
    class="place <?php echo intval($place['state']) ? "done" : ""; ?>">
        <?php echo $place['name']; ?>
    </span>
    </div><?php endwhile;?>
  </div>
  <div class="input-append">
    <form action="" method="post">
      <input type="text" class="span3" placeholder="Nytt område" name="addplace" id="addplace"/>
      <button type="submit" class="btn btn-default">Lägg till</button>
    </form>
  </div>
<script>

</script>
</body>
</html>

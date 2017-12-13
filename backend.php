<?php
// http://localhost/playground/index.php?foo=bar&data=c29tZSBkYXRhIQ==

// data = 'data=' + JSON.stringify(data).replace(/,\"\$\$hashKey\":\"[A-Za-z0-9:]*\"/ig, ''); 
//  $http.post(url, data, { 'headers' : $list.headers }).then(
//    function(success) { $scope.payments.splice(success.data.data._key, 1); },
//    function(error) { $scope.pageSettings.error = error.data.error; });

define("DEBUG", FALSE);

function debugOutput(string $line)
{
  if (DEBUG) {
    echo $line;
  }
}

function debugVarDump($var, string $add = "")
{
  if (DEBUG) {
    ob_start();
    var_dump($var);
    $output = ob_get_clean();
    echo $output . $add;
  }
}

// get DATA parameter from URL and decode BASE64 encoding
$requestUrl = (isset($_SERVER["HTTPS"]) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
debugOutput($requestUrl . "<br>");
$parsedRequestUrl = parse_url($requestUrl);
$jsonString = "";
if (isset($parsedRequestUrl["query"])) {
  debugOutput("URL parameters found<br>");
  parse_str($parsedRequestUrl["query"], $params);
  debugVarDump($params, "<br>");
  if (isset($params["data"])) {
    debugOutput("DATA parameter found<br>");
    $jsonString = base64_decode($params["data"]);
    if ($jsonString) {
      debugOutput("Decoded output: " . $jsonString . "<br>");
    } else {
      debugOutput("Could not decode DATA parameter.");
    }
  }
}

// check decoded parameter
if ($jsonString == "") {
  die(json_encode(array("error" => "Could not decoded parameters.")));
}

// try to read JSON object
$jsonObject = json_decode($jsonString, TRUE);
if ($jsonObject == NULL) {
  die(json_encode(array("error" => "Could not decode JSON object.")));
}
debugVarDump($jsonObject, "<br>");
if (!array_key_exists("action", $jsonObject) || ($jsonObject["action"] == NULL)) {
  die(json_encode(array("error" => "Wrong structure of JSON object.")));
}

$dbHost = "localhost";
$dbUser = "mandala";
$dbPassword = "fooBar!1024";
$dbName = "kita";

// build database connection
$conn = new mysqli($dbHost, $dbUser, $dbPassword, $dbName);
if ($conn->connect_error) {
  die(json_encode(array("error" => "Unable to connect to database: " . $conn->connect_error)));
}
debugOutput("Connected to the database.<br>");

// sanitize JSON object
foreach ($jsonObject as $jsonKey => $jsonValue) {
  debugOutput("sanitized: '" . $jsonKey . "' => '" . $jsonValue . "' to '" . $conn->real_escape_string($jsonValue) . "'<br>");
  $jsonObject[$jsonKey] = $conn->real_escape_string($jsonValue);
}

// establish authorization
if (!array_key_exists("authName", $jsonObject) || !array_key_exists("authPass", $jsonObject) || ($conn->query("SELECT * FROM teachers WHERE name='" . $jsonObject["authName"] . "' AND pass=PASSWORD('" . $jsonObject["authPass"] . "') AND active=1")->num_rows == 0)) {
  die(json_encode(array("error" => "Authorisation failed.")));
}

// REQUESTS
$finalRequestResult = [];
switch ($jsonObject["action"]) {
  // getGroups : [gid/kid/tid/iid]
  case 'getGroups':
    // get group using the 'gid'
    if (array_key_exists("gid", $jsonObject)) {
      $reqResult = $conn->query("SELECT * FROM groups WHERE gid=" . $jsonObject["gid"]);
      $finalRequestResult = $reqResult->fetch_assoc();
    }
    // get group with a certain child using the 'kid'
    if (array_key_exists("kid", $jsonObject)) {
      $reqResult = $conn->query("SELECT g.* FROM groups g JOIN group_assigned_to a ON g.gid=a.gid WHERE a.kid=" . $jsonObject["kid"]);
      $finalRequestResult = $reqResult->fetch_assoc();
    }
    // get group led by a certain teacher using the 'tid'
    if (array_key_exists("tid", $jsonObject)) {
      $reqResult = $conn->query("SELECT g.* FROM groups g JOIN group_led_by l ON g.gid=l.gid WHERE l.tid=" . $jsonObject["tid"]);
      $finalRequestResult = $reqResult->fetch_assoc();
    }
    // get group depicted in a certain image using the 'iid'
    if (array_key_exists("iid", $jsonObject)) {
      $reqResult = $conn->query("SELECT g.* FROM groups g JOIN image_depicts d ON g.gid=d.gid WHERE d.iid=" . $jsonObject["iid"]);
      $finalRequestResult = $reqResult->fetch_assoc();
    }
    // get all (active) groups
    if (!array_key_exists("gid", $jsonObject) && !array_key_exists("kid", $jsonObject) && !array_key_exists("tid", $jsonObject) && !array_key_exists("iid", $jsonObject)) {
      $reqResult = $conn->query("SELECT * FROM groups WHERE active=1");
      while ($row = $reqResult->fetch_assoc()) {
        $finalRequestResult[] = $row;
      }
    }
    break;
  // getKids : [kid/gid/tid/iid/fid]
  case 'getKids':
    // get child using the 'kid'
    if (array_key_exists("kid", $jsonObject)) {
      $reqResult = $conn->query("SELECT * FROM kids WHERE kid=" . $jsonObject["kid"]);
      $finalRequestResult = $reqResult->fetch_assoc();
    }
    // get children from a certain group using the 'gid'
    if (array_key_exists("gid", $jsonObject)) {
      $reqResult = $conn->query("SELECT k.* FROM kids k JOIN group_assigned_to a ON k.kid=a.kid WHERE a.gid=" . $jsonObject["gid"]);
      while ($row = $reqResult->fetch_assoc()) {
        $finalRequestResult[] = $row;
      }
    }
    // get children from a group led by a certain teacher using the 'tid'
    if (array_key_exists("tid", $jsonObject)) {
      $reqResult = $conn->query("SELECT k.* FROM kids k JOIN group_assigned_to a ON k.kid=a.kid JOIN group_led_by l ON a.gid=l.gid WHERE l.tid=" . $jsonObject["tid"]);
      while ($row = $reqResult->fetch_assoc()) {
        $finalRequestResult[] = $row;
      }
    }
    // get children depicted in a certain image using the 'iid'
    if (array_key_exists("iid", $jsonObject)) {
      $reqResult = $conn->query("SELECT k.* FROM kids k JOIN image_depicts d ON k.kid=d.kid WHERE d.iid=" . $jsonObject["iid"]);
      while ($row = $reqResult->fetch_assoc()) {
        $finalRequestResult[] = $row;
      }
    }
    // get children having a certain food problem using the 'fid'
    if (array_key_exists("fid", $jsonObject)) {
      $reqResult = $conn->query("SELECT k.* FROM kids k JOIN food_problematic_for p ON k.kid=p.kid WHERE p.fid=" . $jsonObject["fid"]);
      while ($row = $reqResult->fetch_assoc()) {
        $finalRequestResult[] = $row;
      }
    }
    // get all (active) children
    if (!array_key_exists("kid", $jsonObject) && !array_key_exists("gid", $jsonObject) && !array_key_exists("tid", $jsonObject) && !array_key_exists("iid", $jsonObject) && !array_key_exists("fid", $jsonObject)) {
      $reqResult = $conn->query("SELECT * FROM kids WHERE active=1");
      while ($row = $reqResult->fetch_assoc()) {
        $finalRequestResult[] = $row;
      }
    }
    break;
  // getTeachers : [tid/gid/kid]
  case 'getTeachers':
    // get teacher using the 'tid'
    if (array_key_exists("tid", $jsonObject)) {
      $reqResult = $conn->query("SELECT * FROM teachers WHERE tid=" . $jsonObject["tid"]);
      $finalRequestResult = $reqResult->fetch_assoc();
    }
    // get teachers from a certain group using the 'gid'
    if (array_key_exists("gid", $jsonObject)) {
      $reqResult = $conn->query("SELECT t.* FROM teachers t JOIN group_led_by l ON t.tid=l.tid WHERE l.gid=" . $jsonObject["gid"]);
      while ($row = $reqResult->fetch_assoc()) {
        $finalRequestResult[] = $row;
      }
    }
    // get teachers leading the the group of a certain child using the 'kid'
    if (array_key_exists("kid", $jsonObject)) {
      $reqResult = $conn->query("SELECT t.* FROM teachers t JOIN group_led_by l ON t.tid=l.tid JOIN group_assigned_to a ON l.gid=a.gid WHERE a.kid=" . $jsonObject["kid"]);
      while ($row = $reqResult->fetch_assoc()) {
        $finalRequestResult[] = $row;
      }
    }
    // get all (active) teachers
    if (!array_key_exists("tid", $jsonObject) && !array_key_exists("gid", $jsonObject) && !array_key_exists("kid", $jsonObject)) {
      $reqResult = $conn->query("SELECT * FROM teachers WHERE active=1");
      while ($row = $reqResult->fetch_assoc()) {
        $finalRequestResult[] = $row;
      }
    }
    break;
  // getImages : [kid/gid]
  case 'getImages':
    // get images depicting a certain child using the 'kid'
    if (array_key_exists("kid", $jsonObject)) {
      $reqResult = $conn->query("SELECT i.* FROM images i JOIN image_depicts d ON i.iid=d.iid WHERE d.kid=" . $jsonObject["kid"]);
      while ($row = $reqResult->fetch_assoc()) {
        $finalRequestResult[] = $row;
      }
    }
    // get images depicting a certain group using the 'gid'
    if (array_key_exists("gid", $jsonObject)) {
      $reqResult = $conn->query("SELECT i.* FROM images i JOIN image_depicts d ON i.iid=d.iid WHERE d.gid=" . $jsonObject["gid"]);
      while ($row = $reqResult->fetch_assoc()) {
        $finalRequestResult[] = $row;
      }
    }
    // get all (active) images
    if (!array_key_exists("kid", $jsonObject) && !array_key_exists("gid", $jsonObject)) {
      $reqResult = $conn->query("SELECT * FROM images WHERE active=1");
      while ($row = $reqResult->fetch_assoc()) {
        $finalRequestResult[] = $row;
      }
    }
    break;
  // getCurrentAssignees : -
  case 'getCurrentAssignees':
    // will return all kids that are currently assigned to any group
    $reqResult = $conn->query("SELECT a.* FROM group_assigned_to a JOIN kids k ON a.kid=k.kid JOIN groups g ON a.gid=g.gid WHERE a.end IS NULL");
    while ($row = $reqResult->fetch_assoc()) {
      $finalRequestResult[] = $row;
    }
    break;
  // getCurrentLeaders : -
  case 'getCurrentLeaders':
    // will return all teachers that are currently leading a group
    $reqResult = $conn->query("SELECT l.* FROM group_led_by l JOIN teachers t ON l.tid=t.tid JOIN groups g ON l.gid=g.gid WHERE l.end IS NULL");
    while ($row = $reqResult->fetch_assoc()) {
      $finalRequestResult[] = $row;
    }
    break;
  // getAssignments : start,end
  case 'getAssignments':
    // will return all kids that were assigned in a certain time frame (if 'gid' is given only a specific group is searched)
    if (array_key_exists("start", $jsonObject) && array_key_exists("end", $jsonObject)) {
      // assignment either surrounds 'start' or 'end' .. or lies completely between them .. or is still open and began before 'end'
      $assignmentStartSurrounded = "((a.start <= STR_TO_DATE('" . $jsonObject["start"] . "', '%Y-%m-%d %H:%i:%s')) AND (a.end >= STR_TO_DATE('" . $jsonObject["start"] . "', '%Y-%m-%d %H:%i:%s')))";
      $assignmentEndSurrounded = "((a.start <= STR_TO_DATE('" . $jsonObject["end"] . "', '%Y-%m-%d %H:%i:%s')) AND (a.end >= STR_TO_DATE('" . $jsonObject["end"] . "', '%Y-%m-%d %H:%i:%s')))";
      $assignmentIsSurrounded = "((a.start >= STR_TO_DATE('" . $jsonObject["start"] . "', '%Y-%m-%d %H:%i:%s')) AND (a.end <= STR_TO_DATE('" . $jsonObject["end"] . "', '%Y-%m-%d %H:%i:%s')))";
      $assignmentStartedInside = "((a.start <= STR_TO_DATE('" . $jsonObject["end"] . "', '%Y-%m-%d %H:%i:%s')) AND a.end IS NULL)";
      $reqResult = $conn->query("SELECT a.* FROM group_assigned_to a JOIN kids k ON a.kid=k.kid JOIN groups g ON a.gid=g.gid WHERE (" . $assignmentStartSurrounded . " OR " . $assignmentEndSurrounded
        . " OR " . $assignmentIsSurrounded . " OR " . $assignmentStartedInside . ")" . (array_key_exists("gid", $jsonObject) ? (" AND g.gid=" . $jsonObject["gid"]) : ""));
      while ($row = $reqResult->fetch_assoc()) {
        $finalRequestResult[] = $row;
      }
    } else {
      $finalRequestResult = array("error" => "Parameters 'start' and 'end' are mandatory for 'getAssignment' requests.");
    }
    break;
  // getEmptyGroups : -
  case 'getEmptyGroups':
    $reqResult = $conn->query("SELECT g.* FROM groups g LEFT OUTER JOIN group_assigned_to a ON g.gid=a.gid WHERE a.gid IS NULL");
    while ($row = $reqResult->fetch_assoc()) {
      $finalRequestResult[] = $row;
    }
    break;
  // getHeadlessGroups : -
  case 'getHeadlessGroups':
    $reqResult = $conn->query("SELECT g.* FROM groups g LEFT OUTER JOIN group_led_by l ON g.gid=l.gid WHERE l.gid IS NULL");
    while ($row = $reqResult->fetch_assoc()) {
      $finalRequestResult[] = $row;
    }
    break;
  // getUnassignedKids : -
  case 'getUnassignedKids':
    $reqResult = $conn->query("SELECT k.* FROM kids k LEFT OUTER JOIN group_assigned_to a ON k.kid=a.kid WHERE a.kid IS NULL");
    while ($row = $reqResult->fetch_assoc()) {
      $finalRequestResult[] = $row;
    }
    break;
  // getBoredTeachers : -
  case 'getBoredTeachers':
    $reqResult = $conn->query("SELECT t.* FROM teachers t LEFT OUTER JOIN group_led_by l ON t.tid=l.tid WHERE t.kid IS NULL");
    while ($row = $reqResult->fetch_assoc()) {
      $finalRequestResult[] = $row;
    }
    break;
  // getUndescribedImages : -
  case 'getUndescribedImages':
    $reqResult = $conn->query("SELECT i.* FROM images i LEFT OUTER JOIN image_depicts d ON i.iid=d.iid WHERE d.kid IS NULL AND d.gid IS NULL");
    while ($row = $reqResult->fetch_assoc()) {
      $finalRequestResult[] = $row;
    }
    break;
  // createGroup : name, comment
  case 'createGroup':
    // TODO: from here; add image; change image type in DB etc
    if (array_key_exists("name", $jsonObject) && array_key_exists("comment", $jsonObject)) {
      if ($conn->query("INSERT INTO groups (name, comment) VALUES ('" . $jsonObject["name"] . "', '" . $jsonObject["comment"] . "')")) {
        $finalRequestResult = array("success" => "Group successfully added.");
      } else {
        $finalRequestResult = array("error" => "Group could not be added. Reason: " . $conn->error);
      }
    } else {
      $finalRequestResult = array("error" => "Group not created because of missing details.");
    }
    break;
  // updateGroup : gid, name, comment
  case 'updateGroup':
    if (array_key_exists("gid", $jsonObject) && array_key_exists("name", $jsonObject) && array_key_exists("comment", $jsonObject)) {
      if ($conn->query("UPDATE groups SET name='" . $jsonObject["name"] . "', comment='" . $jsonObject["comment"] . "' WHERE gid=" . $jsonObject["gid"])) {
        $finalRequestResult = array("success" => "Group successfully updated.");
      } else {
        $finalRequestResult = array("error" => "Group could not be updated. Reason: " . $conn->error);
      }
    } else {
      $finalRequestResult = array("error" => "Group not updated because of missing details.");
    }
    break;
  // deleteGroup : gid
  case 'deleteGroup':
    if (array_key_exists("gid", $jsonObject)) {
      if ($conn->query("UPDATE groups SET active=0 WHERE gid=" . $jsonObject["gid"])) {
        $finalRequestResult = array("success" => "Group successfully removed.");
      } else {
        $finalRequestResult = array("error" => "Group could not be removed. Reason: " . $conn->error);
      }
    } else {
      $finalRequestResult = array("error" => "Group not removed because of missing details.");
    }
    break;
  // createKid : name, comment
  case 'createKid':
    if (array_key_exists("name", $jsonObject) && array_key_exists("comment", $jsonObject)) {
      if ($conn->query("INSERT INTO kids (name, comment) VALUES ('" . $jsonObject["name"] . "', '" . $jsonObject["comment"] . "')")) {
        $finalRequestResult = array("success" => "Kid successfully added.");
      } else {
        $finalRequestResult = array("error" => "Kid could not be added. Reason: " . $conn->error);
      }
    } else {
      $finalRequestResult = array("error" => "Kid not created because of missing details.");
    }
    break;
  // updateKid : kid, name, comment
  case 'updateKid':
    if (array_key_exists("kid", $jsonObject) && array_key_exists("name", $jsonObject) && array_key_exists("comment", $jsonObject)) {
      if ($conn->query("UPDATE kids SET name='" . $jsonObject["name"] . "', comment='" . $jsonObject["comment"] . "' WHERE kid=" . $jsonObject["kid"])) {
        $finalRequestResult = array("success" => "Kid successfully updated.");
      } else {
        $finalRequestResult = array("error" => "Kid could not be updated. Reason: " . $conn->error);
      }
    } else {
      $finalRequestResult = array("error" => "Kid not updated because of missing details.");
    }
    break;
  // deleteKid : kid
  case 'deleteKid':
    if (array_key_exists("kid", $jsonObject)) {
      if ($conn->query("UPDATE kids SET active=0 WHERE kid=" . $jsonObject["kid"])) {
        $finalRequestResult = array("success" => "Kid successfully removed.");
      } else {
        $finalRequestResult = array("error" => "Kid could not be removed. Reason: " . $conn->error);
      }
    } else {
      $finalRequestResult = array("error" => "Kid not removed because of missing details.");
    }
    break;
  // createTeacher : name, comment
  case 'createTeacher':
    if (array_key_exists("name", $jsonObject) && array_key_exists("comment", $jsonObject)) {
      if ($conn->query("INSERT INTO teachers (name, comment) VALUES ('" . $jsonObject["name"] . "', '" . $jsonObject["comment"] . "')")) {
        $finalRequestResult = array("success" => "Teacher successfully added.");
      } else {
        $finalRequestResult = array("error" => "Teacher could not be added. Reason: " . $conn->error);
      }
    } else {
      $finalRequestResult = array("error" => "Teacher not created because of missing details.");
    }
    break;
  // updateTeacher : tid, name, comment
  case 'updateTeacher':
    if (array_key_exists("tid", $jsonObject) && array_key_exists("name", $jsonObject) && array_key_exists("comment", $jsonObject)) {
      if ($conn->query("UPDATE teachers SET name='" . $jsonObject["name"] . "', comment='" . $jsonObject["comment"] . "' WHERE tid=" . $jsonObject["tid"])) {
        $finalRequestResult = array("success" => "Teacher successfully updated.");
      } else {
        $finalRequestResult = array("error" => "Teacher could not be updated. Reason: " . $conn->error);
      }
    } else {
      $finalRequestResult = array("error" => "Teacher not updated because of missing details.");
    }
    break;
  // deleteTeacher : tid
  case 'deleteTeacher':
    if (array_key_exists("tid", $jsonObject)) {
      if ($conn->query("UPDATE teachers SET active=0 WHERE tid=" . $jsonObject["tid"])) {
        $finalRequestResult = array("success" => "Teacher successfully removed.");
      } else {
        $finalRequestResult = array("error" => "Teacher could not be removed. Reason: " . $conn->error);
      }
    } else {
      $finalRequestResult = array("error" => "Teacher not removed because of missing details.");
    }
    break;
  // assignKid : kid, gid
  case 'assignKid':
    if (array_key_exists("kid", $jsonObject) && array_key_exists("gid", $jsonObject)) {
      // close old entries and create a new one
      if ($conn->query("UPDATE group_assigned_to SET end=CURRENT_TIME() WHERE kid=" . $jsonObject["kid"] . " AND end IS NULL") && $conn->query("INSERT INTO group_assigned_to (kid, gid, start) VALUES ("
        . $jsonObject["kid"] . ", " . $jsonObject["gid"]) . ", CURRENT_TIME())") {
        $finalRequestResult = array("success" => "Kid successfully assigned.");
      } else {
        $finalRequestResult = array("error" => "Kid could not be assigned. Reason: " . $conn->error);
      }
    } else {
      $finalRequestResult = array("error" => "Kid not assigned because of missing details.");
    }
    break;
  // assignTeacher : tid, gid
  case 'assignTeacher':
    if (array_key_exists("tid", $jsonObject) && array_key_exists("gid", $jsonObject)) {
      // close old entries and create a new one
      if ($conn->query("UPDATE group_led_by SET end=CURRENT_TIME() WHERE tid=" . $jsonObject["tid"] . " AND end IS NULL") && $conn->query("INSERT INTO group_led_by (tid, gid, start) VALUES ("
        . $jsonObject["tid"] . ", " . $jsonObject["gid"]) . ", CURRENT_TIME())") {
        $finalRequestResult = array("success" => "Teacher successfully assigned.");
      } else {
        $finalRequestResult = array("error" => "Teacher could not be assigned. Reason: " . $conn->error);
      }
    } else {
      $finalRequestResult = array("error" => "Teacher not assigned because of missing details.");
    }
    break;
  // default: unknown action
  default:
    $finalRequestResult = array("error" => "Unknown action.");
    break;
}

// clear result if present
if (isset($reqResult) && ($reqResult !== NULL) && (gettype($reqResult) == "object")) {
  $reqResult->close();
}

if ($finalRequestResult !== NULL) {
  echo (json_encode($finalRequestResult));
} else {
  echo (json_encode(array("error" => "Request returned no result.")));
}
  
// end database connection
$conn->close();
?>
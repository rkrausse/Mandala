<?php
  // http://localhost/playground/index.php?foo=bar&data=c29tZSBkYXRhIQ==

  // data = 'data=' + JSON.stringify(data).replace(/,\"\$\$hashKey\":\"[A-Za-z0-9:]*\"/ig, ''); 
  //  $http.post(url, data, { 'headers' : $list.headers }).then(
  //    function(success) { $scope.payments.splice(success.data.data._key, 1); },
  //    function(error) { $scope.pageSettings.error = error.data.error; });

  define("DEBUG", FALSE);

  function debugOutput(string $line){
    if (DEBUG) {
      echo $line;
    }
  }

  function debugVarDump($var, string $add = "") {
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
    die("Could not decoded parameters.");
  }

  // try to read JSON object
  $jsonObject = json_decode($jsonString, TRUE);
  if ($jsonObject == NULL) {
    die("Could not decode JSON object.");
  }
  debugVarDump($jsonObject);
  if (!array_key_exists("action", $jsonObject) || ($jsonObject["action"] == NULL)) {
    die("Wrong structure of JSON object.");
  }

  $dbHost = "localhost";
  $dbUser = "mandala";
  $dbPassword = "fooBar!1024";
  $dbName = "kita";

  // build database connection
  $conn = new mysqli($dbHost, $dbUser, $dbPassword, $dbName);
  if ($conn->connect_error) {
    die("ERROR: Unable to connect: " . $conn->connect_error);
  } 
  debugOutput("Connected to the database.<br>");

  // REQUESTS
  $finalRequestResult = [];
  switch ($jsonObject["action"]) {
    // getGroups : [kid/gid]
    case 'getGroups':
      if (array_key_exists("kid", $jsonObject)) {
        $reqResult = $conn->query("SELECT g.* FROM groups g JOIN group_assignments a ON g.gid=a.gid JOIN kids k ON a.kid=k.kid WHERE k.kid=" . $conn->real_escape_string($jsonObject["kid"]));
        $finalRequestResult = $reqResult->fetch_assoc();
      }
      if (array_key_exists("gid", $jsonObject)) {
        $reqResult = $conn->query("SELECT * FROM groups WHERE gid=" . $conn->real_escape_string($jsonObject["gid"]));
        $finalRequestResult = $reqResult->fetch_assoc();
      }
      if (!array_key_exists("kid", $jsonObject) && !array_key_exists("gid", $jsonObject)) {
        $reqResult = $conn->query("SELECT * FROM groups" . (array_key_exists("all", $jsonObject) && strcasecmp($jsonObject["all"], "true")) ? "" : " WHERE active=1");
        while ($row = $reqResult->fetch_assoc()) {
          $finalRequestResult[] = $row;
        }
      }
      break;
    // getKids : [kid/gid]
    case 'getKids':
      if (array_key_exists("gid", $jsonObject)) {
        $reqResult = $conn->query("SELECT k.* FROM groups g JOIN group_assignments a ON g.gid=a.gid JOIN kids k ON a.kid=k.kid WHERE g.gid=" . $conn->real_escape_string($jsonObject["gid"]));
        $finalRequestResult = $reqResult->fetch_assoc();
      }
      if (array_key_exists("kid", $jsonObject)) {
        $reqResult  = $conn->query("SELECT * FROM kids WHERE kid=" . $conn->real_escape_string($jsonObject["kid"]));
        $finalRequestResult = $reqResult->fetch_assoc();
      }
      if (!array_key_exists("kid", $jsonObject) && !array_key_exists("gid", $jsonObject)) {
        $reqResult = $conn->query("SELECT * FROM kids" . (array_key_exists("all", $jsonObject) && strcasecmp($jsonObject["all"], "true")) ? "" : " WHERE active=1");
        while ($row = $reqResult->fetch_assoc()) {
          $finalRequestResult[] = $row;
        }
      }
      break;
    // getCurrentAssignments : -
    case 'getCurrentAssignments':
      $reqResult = $conn->query("SELECT a.* FROM group_assignments a JOIN kids k ON a.kid=k.kid JOIN groups g ON a.gid=g.gid WHERE a.end IS NULL");
      while ($row = $reqResult->fetch_assoc()) {
        $finalRequestResult[] = $row;
      }
      break;
    // getAssignments
    case 'getAssignments':
      if (array_key_exists("start", $jsonObject) && array_key_exists("end", $jsonObject)) {
        // WHERE expires_at <= STR_TO_DATE('2010-10-15 10:00:00', '%Y-%m-%d %H:%i:%s')
        $saneStart = $conn->real_escape_string($jsonObject["start"]);
        $saneEnd = $conn->real_escape_string($jsonObject["end"]);
        // assignment either envelops 'start' or 'end' .. or lies completely between them .. or is still open and began before 'end'
        $reqResult = $conn->query("SELECT a.* FROM group_assignments a JOIN kids k ON a.kid=k.kid JOIN groups g ON a.gid=g.gid WHERE ((a.start <= STR_TO_DATE('" . $saneStart . "', '%Y-%m-%d %H:%i:%s')) AND (a.end >= STR_TO_DATE('" . $saneStart . "', '%Y-%m-%d %H:%i:%s'))) OR ((a.start <= STR_TO_DATE('" . $saneEnd . "', '%Y-%m-%d %H:%i:%s')) AND (a.end >= STR_TO_DATE('" . $saneEnd . "', '%Y-%m-%d %H:%i:%s'))) OR ((a.start >= STR_TO_DATE('" . $saneStart . "', '%Y-%m-%d %H:%i:%s')) AND (a.end <= STR_TO_DATE('" . $saneEnd . "', '%Y-%m-%d %H:%i:%s'))) OR ((a.start <= STR_TO_DATE('" . $saneEnd . "', '%Y-%m-%d %H:%i:%s')) AND a.end IS NULL)");
        while ($row = $reqResult->fetch_assoc()) {
          $finalRequestResult[] = $row;
        }
      } else {
        $finalRequestResult = array("error" => "Parameters 'start' and 'end' are mandatory for 'getAssignment' requests.");
      }
      break;
    // getEmptyGroups : -
    case 'getEmptyGroups':
      $reqResult = $conn->query("SELECT g.gid FROM groups g LEFT OUTER JOIN group_assignments a ON g.gid=a.gid WHERE a.gid IS NULL");
      while ($row = $reqResult->fetch_assoc()) {
        $finalRequestResult[] = $row;
      }
      break;
    case 'getUnassignedKids':
      $reqResult = $conn->query("SELECT k.kid FROM kids k LEFT OUTER JOIN group_assignments a ON k.kid=a.kid WHERE a.kid IS NULL");
      while ($row = $reqResult->fetch_assoc()) {
        $finalRequestResult[] = $row;
      }
      break;
    // createGroup : auth, name, comment
    case 'createGroup':
      // check auth
      if (!array_key_exists("auth", $jsonObject) || ($conn->query("SELECT * FROM auth WHERE role='adult' AND password='" . $conn->real_escape_string($jsonObject["auth"]) . "'")->num_rows == 0)) {
        $finalRequestResult = array("error" => "Authorisation failed.");
      } else {
        // request
        if (array_key_exists("name", $jsonObject) && array_key_exists("comment", $jsonObject)) {
          if ($conn->query("INSERT INTO groups (name, comment) VALUES ('" . $conn->real_escape_string($jsonObject["name"]) . "', '" . $conn->real_escape_string($jsonObject["comment"]) . "')")) {
            $finalRequestResult = array("success" => "Group successfully added.");
          } else {
            $finalRequestResult = array("error" => "Group could not be added. Reason: " . $conn->error);
          }
        } else {
          $finalRequestResult = array("error" => "Could not create group because of missing details.");
        }
      }
      break;
    // updateGroup : auth, gid, name, comment
    case 'updateGroup':
      // check auth
      if (!array_key_exists("auth", $jsonObject) || ($conn->query("SELECT * FROM auth WHERE role='adult' AND password='" . $conn->real_escape_string($jsonObject["auth"]) . "'")->num_rows == 0)) {
        $finalRequestResult = array("error" => "Authorisation failed.");
      } else {
        // request
        if (array_key_exists("gid", $jsonObject) && array_key_exists("name", $jsonObject) && array_key_exists("comment", $jsonObject)) {
          if ($conn->query("UPDATE groups SET name='" . $conn->real_escape_string($jsonObject["name"]) . "', comment='" . $conn->real_escape_string($jsonObject["comment"]) . "' WHERE gid=" . $conn->real_escape_string($jsonObject["gid"]))) {
            $finalRequestResult = array("success" => "Group successfully updated.");
          } else {
            $finalRequestResult = array("error" => "Group could not be updated. Reason: " . $conn->error);
          }
        } else {
          $finalRequestResult = array("error" => "Could not update group because of missing details.");
        }
      }
      break;
    // deleteGroup : auth, gid
    case 'deleteGroup':
      // check auth
      if (!array_key_exists("auth", $jsonObject) || ($conn->query("SELECT * FROM auth WHERE role='adult' AND password='" . $conn->real_escape_string($jsonObject["auth"]) . "'")->num_rows == 0)) {
        $finalRequestResult = array("error" => "Authorisation failed.");
      } else {
        // request
        if (array_key_exists("gid", $jsonObject)) {
          if ($conn->query("UPDATE groups SET active=0 WHERE gid=" . $conn->real_escape_string($jsonObject["gid"]))) {
            $finalRequestResult = array("success" => "Group successfully removed.");
          } else {
            $finalRequestResult = array("error" => "Group could not be removed. Reason: " . $conn->error);
          }
        } else {
          $finalRequestResult = array("error" => "Could not delete group because of missing details.");
        }
      }
      break;
    // createKid : auth, name, comment
    case 'createKid':
      // check auth
      if (!array_key_exists("auth", $jsonObject) || ($conn->query("SELECT * FROM auth WHERE role='adult' AND password='" . $conn->real_escape_string($jsonObject["auth"]) . "'")->num_rows == 0)) {
        $finalRequestResult = array("error" => "Authorisation failed.");
      } else {
        // request
        if (array_key_exists("name", $jsonObject) && array_key_exists("comment", $jsonObject)) {
          if ($conn->query("INSERT INTO kids (name, comment) VALUES ('" . $conn->real_escape_string($jsonObject["name"]) . "', '" . $conn->real_escape_string($jsonObject["comment"]) . "')")) {
            $finalRequestResult = array("success" => "Kid successfully added.");
          } else {
            $finalRequestResult = array("error" => "Kid could not be added. Reason: " . $conn->error);
          }
        } else {
          $finalRequestResult = array("error" => "Kid not create group because of missing details.");
        }
      }
      break;
    // updateKid : auth, kid, name, comment
    case 'updateKid':
      // check auth
      if (!array_key_exists("auth", $jsonObject) || ($conn->query("SELECT * FROM auth WHERE role='adult' AND password='" . $conn->real_escape_string($jsonObject["auth"]) . "'")->num_rows == 0)) {
        $finalRequestResult = array("error" => "Authorisation failed.");
      } else {
        // request
        if (array_key_exists("kid", $jsonObject) && array_key_exists("name", $jsonObject) && array_key_exists("comment", $jsonObject)) {
          if ($conn->query("UPDATE kids SET name='" . $conn->real_escape_string($jsonObject["name"]) . "', comment='" . $conn->real_escape_string($jsonObject["comment"]) . "' WHERE kid=" . $conn->real_escape_string($jsonObject["kid"]))) {
            $finalRequestResult = array("success" => "Kid successfully updated.");
          } else {
            $finalRequestResult = array("error" => "Kid could not be updated. Reason: " . $conn->error);
          }
        } else {
          $finalRequestResult = array("error" => "Could not update kid because of missing details.");
        }
      }
      break;
    // deleteKid : auth, kid
    case 'deleteKid':
      // check auth
      if (!array_key_exists("auth", $jsonObject) || ($conn->query("SELECT * FROM auth WHERE role='adult' AND password='" . $conn->real_escape_string($jsonObject["auth"]) . "'")->num_rows == 0)) {
        $finalRequestResult = array("error" => "Authorisation failed.");
      } else {
        // request
        if (array_key_exists("kid", $jsonObject)) {
          if ($conn->query("UPDATE kids SET active=0 WHERE kid=" . $conn->real_escape_string($jsonObject["kid"]))) {
            $finalRequestResult = array("success" => "Kid successfully removed.");
          } else {
            $finalRequestResult = array("error" => "Kid could not be removed. Reason: " . $conn->error);
          }
        } else {
          $finalRequestResult = array("error" => "Could not delete kid because of missing details.");
        }
      }
      break;
    // moveKid : auth, kid, gid
    case 'moveKid':
      if (array_key_exists("kid", $jsonObject) && array_key_exists("gid", $jsonObject)) {
        // close old entries and create a new one
        if ($conn->query("UPDATE group_assignments SET end=CURRENT_TIME() WHERE kid=" . $conn->real_escape_string($jsonObject["kid"]) . " AND end IS NULL") && $conn->query("INSERT INTO group_assignments (kid, gid, start) VALUES (" . $conn->real_escape_string($jsonObject["kid"]) . ", " . $conn->real_escape_string($jsonObject["gid"]) . ", CURRENT_TIME())")) {
          $finalRequestResult = array("success" => "Kid successfully moved.");
        } else {
          $finalRequestResult = array("error" => "Kid could not be moved. Reason: " . $conn->error);
        }
      } else {
        $finalRequestResult = array("error" => "Could not move kid because of missing details.");
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
    echo(json_encode($finalRequestResult));
  } else {
    echo(json_encode(array("error" => "Request returned no result.")));
  }
  
  // end database connection
  $conn->close();
?>
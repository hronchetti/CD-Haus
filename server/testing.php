<?php

require_once('config/setEnv.php');
require_once('classes/recordSet.class.php');
require_once('classes/pdoDB.class.php');
require_once('classes/session.class.php');

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : null;
$genre = isset($_REQUEST['genre']) ? $_REQUEST['genre'] : null;
$term = isset($_REQUEST['term']) ? $_REQUEST['term'] : null;
$showTracks = isset($_REQUEST['showTracks']) ? $_REQUEST['showTracks'] : null;

if (empty($action)) {
    if ((($_SERVER['REQUEST_METHOD'] == 'POST') ||
         ($_SERVER['REQUEST_METHOD'] == 'PUT') ||
         ($_SERVER['REQUEST_METHOD'] == 'DELETE')) &&
         (strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false)) {

        $input = json_decode(file_get_contents('php://input'), true);

        $action = isset($input['action']) ? $input['action'] : null;
        $subject = isset($input['subject']) ? $input['subject'] : null;
        $data = isset($input['data']) ? $input['data'] : null;
    }
}

// Connecting to the database
$db = pdoDB::getConnection();
// Connecting to session
$session = Session::getInstance();
//
switch ($action) {
    case 'loginUser':
        // Getting user id and password from the form input values
        $user_id = isset($_REQUEST['user_id']) ? $_REQUEST['user_id'] : null;
        $password = isset($_REQUEST['password']) ? $_REQUEST['password'] : null;
        // Preparing a statement for selecting a users password from DB
        $stmt = $db->prepare("SELECT password FROM i_user WHERE user_id = :user_id");
        // Executing the prepared statement and storing the result as an object in an array
        $stmt->execute(array(':user_id' => $user_id));
        // Fetching the object whilst looping through the array of results returned by the statement above
        while($hash = $stmt->fetchObject()){
            // Verifying the given $password is the un-hashed version of the password row in the database
            if (password_verify($password, $hash->password)) {
                // Correct password, user logged in
                $session->setProperty('loggedIn', true);
                $session->setProperty('user_id', $user_id);
                $session->setProperty('password', $password);

                echo ('Correct password');

            } else{
                // Incorrect password, user not logged in. Error message sent back
                $errorMessage = 'Incorrect password';
                echo ($errorMessage);
            }
        }

        break;

    default:
        break;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>PHP Testing Page</title>
</head>
<body>
    <form method="post" action="login.php">
        <input type="text" name="user_id">
        <input type="password" name="password">
        <input type="submit" value="Log in">
    </form>
</body>
</html>

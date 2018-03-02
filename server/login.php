<?php

require_once('config/setEnv.php');
require_once('classes/pdoDB.class.php');
require_once('classes/session.class.php');

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
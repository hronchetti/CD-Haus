<?php

require_once('config/setEnv.php');
require_once('classes/recordSet.class.php');
require_once('classes/session.class.php');
require_once('classes/pdoDB.class.php');
/*
 *  ESSENTIAL COMPONENTS ------------------------------------------------------
 */
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : null;

if (empty($action)) {
    if ((($_SERVER['REQUEST_METHOD'] == 'POST') ||
            ($_SERVER['REQUEST_METHOD'] == 'PUT') ||
            ($_SERVER['REQUEST_METHOD'] == 'DELETE')) &&
        (strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false)) {

        $input = json_decode(file_get_contents('php://input'), true);

        $action = isset($input['action']) ? $input['action'] : null;
    }
}
// Connecting to the database
$db = pdoDB::getConnection();
// Connecting to session
$session = Session::getSession();
// Setting the header type to JSON because everything returned to this page will be in that format
header("Content-Type: application/json");
/*
 *  RE-USE VARIABLES ----------------------------------------------------------
 */
//
$cd = isset($_REQUEST['cd']) ? $_REQUEST['cd'] : null;
// If CD is clicked on my user then implement, if not set cd (album_id) to 1
if (!isset($cd)) {
    // No album_id set by user, show tracks for album_id=1
    $cd = 1;
}
//
$userSession_id = $session->getProperty('user_id');
$currentTime = ;
/*
 *  ACTIONS -------------------------------------------------------------------
 */
switch ($action) {

    case 'loginUser':
        // Getting user id and password from the form input values
        $user_id = isset($_REQUEST['user_id']) ? $_REQUEST['user_id'] : null;
        $password = isset($_REQUEST['password']) ? $_REQUEST['password'] : null;
        // If user id AND password are both not empty then
        if (!empty($user_id) && !empty($password)) {
            // Preparing a statement for selecting a users password from DB
            $stmt = $db->prepare("SELECT user_id, password, username
                                  FROM i_user 
                                  WHERE user_id = :user_id");
            // Executing the prepared statement and storing the result as an object in an array
            $stmt->execute(array(':user_id' => $user_id));
            // Fetching the object whilst looping through the array of results returned by the statement above
            while ($hash = $stmt->fetchObject()) {
                // Verifying the given $password is the un-hashed version of the password row in the database
                if (password_verify($password, $hash->password)) {
                    // Correct password, user logged in
                    $session->setProperty('loggedIn', true);
                    $session->setProperty('user_id', $user_id);
                    $session->setProperty('password', $password);

                    echo('Signed in');

                } else {
                    // Incorrect password, user not logged in. Error message sent back
                    $errorMessage = 'Incorrect password or username';
                    echo($errorMessage);
                }
            }
        } else {
            echo '{"status":{"error":"error", "text":"Username and Password are required."}}';
        }

        break;

    case 'logoutUser':

        // Clear session data

        break;

    case 'search':
        //
        $criteria = isset($_REQUEST['criteria']) ? $_REQUEST['criteria'] : null;
        $ordering = isset($_REQUEST['ordering']) ? $_REQUEST['ordering'] : null;
        // If search entries or genre filters are set by the user then implement, if not show all albums
        if (!isset($criteria)) {
            // Nothing set by user, show all CDs
            $criteria = 1;
        }
        // If ordering filters are set by the user then implement, if not order by album name
        if (!isset($ordering)) {
            // Nothing set by user, order by album name (ascending)
            $ordering = 'i_album.name ASC';
        }
        // SQL query that gets all field names from genre, album, album track, track and artist tables that match given criteria
        $searchSLQ = "SELECT *
                      FROM i_genre 
                          INNER JOIN i_album ON (i_genre.genre_id = i_album.genre_id)
                          INNER JOIN i_album_track ON (i_album.album_id = i_album_track.album_id)
                          INNER JOIN i_track ON (i_album_track.track_id = i_track.track_id)
                          INNER JOIN i_artist ON (i_track.artist_id = i_artist.artist_id)
                      WHERE i_album.name LIKE '%$criteria%'
                          OR i_album.composer LIKE '%$criteria%'
                          OR i_album.year LIKE '%$criteria%'
                          OR i_artist.name LIKE '%$criteria%'
                          OR i_genre.name LIKE '%$criteria%'
                          OR i_track.name LIKE '%$criteria%'
                      ORDER BY $ordering";
        // Creating a new instance of the JSON_RecordSet class
        $rs = new JSON_RecordSet();
        //
        $retrieval = $rs->getRecordSet($searchSLQ);
        // Printing the results on the page
        echo $retrieval;

        break;

    case 'showTracks':

        // SQL to retrieve all information about tracks related to a given album (from the artist, track, album track and album tables)
        $showTracksSQL = "SELECT *
                          FROM i_artist
                             INNER JOIN i_track ON (i_artist.artist_id = i_track.artist_id)
                             INNER JOIN i_album_track ON (i_track.track_id = i_album_track.track_id)
                             INNER JOIN i_album ON (i_album_track.album_id = i_album.album_id)
                          WHERE i_album_track.album_id = $cd
                          ORDER BY i_album_track.track_number";

        $rs = new JSON_RecordSet();
        $retrieval = $rs->getRecordSet($showTracksSQL);
        echo $retrieval;

        break;

    case 'showNotes':

        $showNotesSQL = "SELECT *
                         FROM i_notes
                         WHERE i_notes.album_id = $cd
                         ORDER BY lastupdate";

        $rs = new JSON_RecordSet();
        $retrieval = $rs->getRecordSet($showNotesSQL);
        echo $retrieval;

        break;

    case 'newNote':

        $newNoteText = '';

        // generate current time

        $newNoteSQL = "INSERT INTO i_notes (album_id, userID, note, lastupdate) 
                       VALUES (:album_id, :user_id, :note, :lastupdate)";

        $rs = new JSON_RecordSet();
        $retrieval = $rs->getRecordSet($newNoteSQL,
            'ResultSet'
            array(':album_id' => $cd,
                  ':user_id' => $userSession_id,
                  ':note' => $newNoteText,
                  ':lastupdate' => $currentTime));

        break;

    case 'updateNote':

        $updateNoteSQL = "";

        break;

    case 'deleteNote':

        $deleteNoteSQL = "";

        break;

    default:
        echo '{"status":"error", "message":{"text": "default no action taken"}}';
        break;
}
<?php

require_once('classes/session.class.php');
require_once('config/setEnv.php');
require_once('classes/recordSet.class.php');
require_once('classes/pdoDB.class.php');

// Connecting to the database & current session
$db = pdoDB::getConnection();
$session = Session::getSession();

// Setting the header type to JSON because everything returned to this page will be in that format
header("Content-Type: application/json");

/*  ---------------------------------------------
 *  ESSENTIAL COMPONENTS
 *  ------------------------------------------ */

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : null;
$album = isset($_REQUEST['album']) ? $_REQUEST['album'] : null;
$userID = isset($_REQUEST['userID']) ? $_REQUEST['userID'] : null;
$note = isset($_REQUEST['note']) ? $_REQUEST['note'] : null;
$password = isset($_REQUEST['password']) ? $_REQUEST['password'] : null;
// Search Variables
$criteria = isset($_REQUEST['criteria']) ? $_REQUEST['criteria'] : null;
$genre = isset($_REQUEST['genre']) ? $_REQUEST['genre'] : null;
$ordering = isset($_REQUEST['ordering']) ? $_REQUEST['ordering'] : null;

if (empty($action)) {
    // Angular doesn't put post/put/delete method in the request stream so if the request method is post || put || delete then get
    if ((($_SERVER['REQUEST_METHOD'] == 'POST') ||
            ($_SERVER['REQUEST_METHOD'] == 'PUT') ||
            ($_SERVER['REQUEST_METHOD'] == 'DELETE')) &&
        (strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false)) {
        //
        $input = json_decode(file_get_contents('php://input'), true);
        // Putting the
        $action = isset($input['action']) ? $input['action'] : null;
        $album = isset($input['album']) ? $input['album'] : null;
        $userID = isset($input['userID']) ? $input['userID'] : null;
        $note = isset($input['note']) ? $input['note'] : null;
        $password = isset($input['password']) ? $input['password'] : null;
        $criteria = isset($input['criteria']) ? $input['criteria'] : null;
        $genre = isset($input['genre']) ? $input['genre'] : null;
        $ordering = isset($input['ordering']) ? $input['ordering'] : null;
        // Returned from angular (POST)
        $data = isset($input['data']) ? $input['data'] : null;
    }
}

/*  ---------------------------------------------
 *  VARIABLE CONTROL
 *  ------------------------------------------ */

$loggedInStatus = $session->getProperty('loggedIn');
$sessionUserID = $session->getProperty('user_id');
// Implement some checking for session ID matching ID that comes with data

/*  ---------------------------------------------
 *  ACTIONS
 *  -----------------------------------------  */

switch ($action) {
    case 'loginUser':
        // password and userID retrieved at top of page in 'ESSENTIAL COMPONENTS'
        if (!empty($userID) && !empty($password)) {
            // Preparing a statement for selecting a users password from DB
            $stmt = $db->prepare("SELECT user_id, password
                                  FROM i_user 
                                  WHERE user_id = :user_id");
            // Executing the prepared statement and storing the result as an object in an array
            $stmt->execute(array(':user_id' => $userID));
            // Fetching the object whilst looping through the array of results returned by the statement above
            while ($hash = $stmt->fetchObject()) {
                // Verifying the given $password is the un-hashed version of the password row in the database
                if (password_verify($password, $hash->password)) {
                    // Correct password, user logged in
                    $session->setProperty('loggedIn', true);
                    $session->setProperty('user_id', $userID);

                    echo '{"status":"ok", "message":{"text": "Sign in successful"}}';

                } else {
                    // Incorrect password, user not logged in. Error message sent back
                    echo '{"status":"error", "message":{"text": "Password incorrect"}}';
                }
            }
        } else {
            echo '{"status":"error", "message":{"text": "Username and password required"}}';
        }

        break;

    case 'logoutUser':

        $session->clearSession();

        echo '{"status":"ok","message":{"text":"Sign out successful"}}';

        break;

    case 'search':

        // If search entries or genre filters are set by the user then implement, if not show all albums
        if((!empty($genre)) && (!empty($criteria))){
            // Filter based on user input of genre AND search text
            $filters = "i_album.genre_id = $genre
                        AND (Album LIKE '%$criteria%'
                        OR i_artist.name LIKE '%$criteria%')";

        } else if ((!empty($genre)) && (empty($criteria))){
            // Filter just by genre
            $filters = "i_album.genre_id = $genre";

        } else if ((empty($genre)) && (!empty($criteria))){
            // Filter just by search text
            $filters = "Album LIKE '%$criteria%'
                        OR i_artist.name LIKE '%$criteria%'";
        } else{
            // Show all results
            $filters = 1;
        }
        // If ordering filters are set by the user then implement, if not order by album name
        if (empty($ordering)) {
            // Nothing set by user, order by album name (ascending)
            $ordering = 'i_album.name ASC';
        }
        //
        $searchSLQ = "SELECT i_album.artwork AS Artwork, i_album.album_id AS Album_ID, i_album.name AS Album, GROUP_CONCAT(DISTINCT i_artist.name) AS Artists, i_album.genre_id AS Genre, i_album.year AS Year, TIME(SUM(i_track.total_time)/1000, 'unixepoch') AS Duration
                      FROM i_album
                          LEFT JOIN i_album_track ON (i_album.album_id = i_album_track.album_id)
                          LEFT JOIN i_track ON (i_album_track.track_id = i_track.track_id)
                          LEFT JOIN i_artist ON (i_track.artist_id = i_artist.artist_id)
                      WHERE $filters AND genre_id IS NULL
                      GROUP BY i_album.album_id
                      UNION ALL
                      SELECT i_album.artwork AS Artwork, i_album.album_id AS Album_ID, i_album.name AS Album, GROUP_CONCAT(DISTINCT i_artist.name) AS Artists, i_genre.name AS Genre, i_album.year AS Year, TIME(SUM(i_track.total_time)/1000, 'unixepoch') AS Duration
                      FROM i_genre
                          INNER JOIN i_album ON (i_genre.genre_id = i_album.genre_id) 
                          LEFT JOIN i_album_track ON (i_album.album_id = i_album_track.album_id)
                          LEFT JOIN i_track ON (i_album_track.track_id = i_track.track_id)
                          LEFT JOIN i_artist ON (i_track.artist_id = i_artist.artist_id)
                      WHERE $filters
                      GROUP BY i_album.album_id
                      ORDER BY $ordering";
        // Creating a new instance of the JSON_RecordSet class
        $rs = new JSON_RecordSet();
        //
        $retrieval = $rs->getRecordSet($searchSLQ);
        // Printing the results on the page
        echo $retrieval;

        break;

    case 'showTracks':

        if(!empty($album)) {
            // SQL to retrieve all information about tracks related to a given album (from the artist, track, album track and album tables)
            $showTracksSQL = "SELECT i_album_track.track_number, i_track.name AS Track, i_artist.name AS Artist, TIME(i_track.total_time /1000, 'unixepoch') AS Duration, CAST(i_track.size*1.0/1000000 AS STRING) || ' MB' AS Size
                          FROM i_artist
                             INNER JOIN i_track ON (i_artist.artist_id = i_track.artist_id)
                             INNER JOIN i_album_track ON (i_track.track_id = i_album_track.track_id)
                             INNER JOIN i_album ON (i_album_track.album_id = i_album.album_id)
                             INNER JOIN i_genre ON (i_album.genre_id = i_genre.genre_id)
                          WHERE i_album_track.album_id = $album
                          ORDER BY i_album_track.track_number";

            $rs = new JSON_RecordSet();
            $retrieval = $rs->getRecordSet($showTracksSQL);
            echo $retrieval;

        } else{
            echo '{"status":"error", "message":{"text": "No album chosen"}}';
        }
        break;

    case 'showNote':
        // userID retrieved at top of page in 'ESSENTIAL COMPONENTS'
        if((!empty($userID)) && (!empty($album))){

            $showNoteSQL = "SELECT *
                            FROM i_notes
                            WHERE i_notes.album_id = $album AND i_notes.userID = '$userID'";

            $rs = new JSON_RecordSet();
            $retrieval = $rs->getRecordSet($showNoteSQL);
            echo $retrieval;

        } else{
            echo '{"status":"error", "message":{"text": "Sign in to show notes"}}';
        }

        break;

    case 'newNote':
        // note, album and userID retrieved at top of page in 'ESSENTIAL COMPONENTS'
        if (((!empty($note)) && (!empty($userID))) && (!empty($album))){

            $newNoteSQL = "INSERT INTO i_notes (album_id, userID, note) 
                           VALUES (:album_id, :user_id, :note)";

            $rs = new JSON_RecordSet();
            $retrieval = $rs->getRecordSet($newNoteSQL,
                'ResultSet',
                array(':album_id' => $album,
                    ':user_id' => $userID,
                    ':note' => $note));

            echo '{"status":"ok", "message":{"text": "New note added"}}';

        } else {
            echo '{"status":"error", "message":{"text": "New note not added. Sign in required OR note text and album not provided"}}';
        }

        break;

    case 'updateNote':

        // note, album and userID retrieved at top of page in 'ESSENTIAL COMPONENTS'
        if (((!empty($note)) && (!empty($userID))) && (!empty($album))){

            $updateNoteSQL = "UPDATE i_notes
                              SET note=:note
                              WHERE i_notes.album_id=:album_id AND i_notes.userID=:userID";

            $rs = new JSON_RecordSet();
            $retrieval = $rs->getRecordSet($updateNoteSQL,
                'ResultSet',
                array(':note' => $note,
                    ':album_id' => $album,
                    ':userID' => $userID));

            echo '{"status":"ok", "message":{"text": "Note updated"}}';

        } else {
            echo '{"status":"error", "message":{"text": "Note not updated. Sign in required OR note text and album not provided"}}';
        }

        break;

    case 'deleteNote':

        if ((!empty($album)) && (!empty($userID))){
            // SQL to delete record from database entirely
            $deleteNoteSQL = "DELETE FROM i_notes
                              WHERE i_notes.album_id=:album_id AND i_notes.userID=:userID";
            //
            $rs = new JSON_RecordSet();
            $retrieval = $rs->getRecordSet($deleteNoteSQL,
                'ResultSet',
                array(':album_id' => $album,
                    ':userID' => $userID));

            echo '{"status":"ok", "message":{"text": "Note deleted"}}';

        } else {
            echo '{"status":"error", "message":{"text": "Note not deleted. Sign in required OR album not provided"}}';
        }

        break;

    case 'showGenres':
        // case to make all the genres accessible so filtering is dynamically populated from the database
        $genreSQL = "SELECT *
                     FROM i_genre
                     ORDER BY name";

        $rs = new JSON_RecordSet();
        //
        $retrieval = $rs->getRecordSet($genreSQL);
        // Printing the results on the page
        echo $retrieval;

        break;
    case 'showAlbumInfo':

        if(!empty($album)) {
            $albumInfoSQL = "SELECT i_album.artwork AS Artwork, i_album.album_id AS Album_ID, i_album.name AS Album, i_artist.name AS Artists, i_genre.name AS Genre, i_album.year AS Year, TIME(SUM(i_track.total_time)/1000, 'unixepoch') AS Duration
                          FROM i_genre
                              INNER JOIN i_album ON (i_genre.genre_id = i_album.genre_id) 
                              LEFT JOIN i_album_track ON (i_album.album_id = i_album_track.album_id)
                              LEFT JOIN i_track ON (i_album_track.track_id = i_track.track_id)
                              LEFT JOIN i_artist ON (i_track.artist_id = i_artist.artist_id)
                          WHERE i_album.album_id = $album
                          GROUP BY i_album.album_id";

            $rs = new JSON_RecordSet();
            //
            $retrieval = $rs->getRecordSet($albumInfoSQL);
            // Printing the results on the page
            echo $retrieval;
        } else{
            echo '{"status":"error", "message":{"text": "No album chosen"}}';
        }
        break;

    default:
        echo '{"status":"error", "message":{"text": "(default) no action taken"}}';
        break;
}
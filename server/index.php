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
$data = isset($_REQUEST['data']) ? $_REQUEST['data'] : null;
$criteria = isset($_REQUEST['criteria']) ? $_REQUEST['criteria'] : null;
$genre = isset($_REQUEST['genre']) ? $_REQUEST['genre'] : null;
$ordering = isset($_REQUEST['ordering']) ? $_REQUEST['ordering'] : null;

if (empty($action)) {

    /* Angular doesn't put post/put/delete method in the request stream
     * if the request method is post || put || delete then get php://input
     * if any of the variables in php://input are named the same as the
     * variables we need then fill the php variable with its value
     */

    if ((($_SERVER['REQUEST_METHOD'] == 'POST') ||
            ($_SERVER['REQUEST_METHOD'] == 'PUT') ||
            ($_SERVER['REQUEST_METHOD'] == 'DELETE')) &&
        (strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false)) {
        // input is what is returned from angular, which is neither of the request methods
        $input = json_decode(file_get_contents('php://input'), true);
        // Putting the values of the php://input variables into the local php variables for use
        $action = isset($input['action']) ? $input['action'] : null;
        $album = isset($input['album']) ? $input['album'] : null;
        $userID = isset($input['userID']) ? $input['userID'] : null;
        $note = isset($input['note']) ? $input['note'] : null;
        $password = isset($input['password']) ? $input['password'] : null;
        $criteria = isset($input['criteria']) ? $input['criteria'] : null;
        $genre = isset($input['genre']) ? $input['genre'] : null;
        $ordering = isset($input['ordering']) ? $input['ordering'] : null;
        $data = isset($input['data']) ? $input['data'] : null;
    }
}

/*  ---------------------------------------------
 *  ACTIONS
 *  -----------------------------------------  */

switch ($action) {
    case 'loginUser':
        // password and userID retrieved at top of page in 'ESSENTIAL COMPONENTS'
        if (!empty($userID) && !empty($password)) {

            // Check if user exists in the database
            $userCheck = $db->prepare("SELECT user_id
                                       FROM i_user
                                       WHERE user_id = :user_id");
            $userCheck->execute(array(':user_id' => $userID));

            // If fetchObject returns a row it means the user_id entered exists in the database (step into if)
            if($userCheck->fetchObject()){

                $stmt = $db->prepare("SELECT user_id, password
                                      FROM i_user 
                                      WHERE user_id = :user_id");
                $stmt->execute(array(':user_id' => $userID));
                // Fetching the object whilst looping through the array of results returned by the statement above
                while ($hash = $stmt->fetchObject()) {
                    // Verifying the given $password is the un-hashed version of the password row in the database
                    if (password_verify($password, $hash->password)) {

                        $session->setProperty('signedIn', true);
                        $session->setProperty('user_id', $userID);

                        // Password entered matches the user_id entered in the database (logged in)
                        echo '{"status":"ok", "message":{"text": "Sign in successful"}}';

                    } else {
                        // Incorrect password, user not logged in
                        echo '{"status":"error", "message":{"text": "Password incorrect"}}';
                    }
                }
            } else{
                // fetchObject() did not return any rows, user_id entered is not in the database
                echo '{"status":"error", "message":{"text": "User incorrect"}}';
            }

        } else {
            // Either user or password (or both) were empty
            echo '{"status":"error", "message":{"text": "User and password required"}}';
        }

        break;

    case 'logoutUser':

        // Some error checking needed in here (if I have time)
        $session->clearSession();

        echo '{"status":"ok","message":{"text":"Sign out successful"}}';

        break;

    case 'getSessionData':

        if(!empty($data)){

            // echo the session property and its value returned
            echo '{"status":"ok","message":{"'. $data . '":"' . $session->getProperty($data) .'"}}';

        } else{
            // data (property) was not received
            echo '{"status":"error","message":{"text":"Not property given"}}';
        }

        break;

    case 'search':

        // If search entries or genre filters are set by the user then implement, if not show all albums
        if((!empty($genre)) && (!empty($criteria))){
            // Filter results by search criteria and genre
            $filters = "i_album.genre_id = $genre
                        AND (Album LIKE '%$criteria%'
                        OR i_artist.name LIKE '%$criteria%')";

        } else if ((!empty($genre)) && (empty($criteria))){
            // Filter just by genre
            $filters = "i_album.genre_id = $genre";

        } else if ((empty($genre)) && (!empty($criteria))){
            // Filter just by search criteria
            $filters = "Album LIKE '%$criteria%'
                        OR i_artist.name LIKE '%$criteria%'";
        } else{
            // Show all results, no filtering
            $filters = 1;
        }

        // If ordering filters are set by the user then implement, if not order by album name
        if (empty($ordering)) {
            // Nothing set by user, order by album name (ascending)
            $ordering = 'i_album.name ASC';
        }

        /* UNION was used to bind 2 SQL statements together
         * i_album must be the initial table selected from
         * to get all rows because RIGHT JOIN is not supported
         * in SQLite
         */

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

        $rs = new JSON_RecordSet();
        $retrieval = $rs->getRecordSet($searchSLQ);
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
            // Album id was empty
            echo '{"status":"error", "message":{"text": "No album chosen"}}';
        }
        break;

    case 'showNote':

        if((!empty($userID)) && (!empty($album))){

            $showNoteSQL = "SELECT *
                            FROM i_notes
                            WHERE i_notes.album_id = $album AND i_notes.userID = '$userID'";

            $rs = new JSON_RecordSet();
            $retrieval = $rs->getRecordSet($showNoteSQL);
            echo $retrieval;

        } else if((empty($userID)) && (!empty($album))){
            echo '{"status":"error", "message":{"text": "Sign in to show notes"}}';

        } else if((!empty($userID)) && (empty($album))){
            echo '{"status":"error", "message":{"text": "No album chosen"}}';

        } else {
            echo '{"status":"error", "message":{"text": "Sign in to show notes"}}';
        }

        break;

    case 'newNote':

        if (((!empty($note)) && (!empty($userID))) && (!empty($album))){

            $noteCheck = $db->prepare("SELECT note
                                       FROM i_notes
                                       WHERE userID = :userID AND album_id = :album_id");

            $noteCheck->execute(array(':userID' => $userID,
                                      ':album_id' => $album));
            if($noteCheck->fetchObject()){
                // Note already in the database for this album & user do not proceed
                echo '{"status":"error", "message":{"text": "Note already exists for this album"}}';

            } else{
                // No note in database so proceed with adding new note
                $newNoteSQL = "INSERT INTO i_notes (album_id, userID, note) 
                               VALUES (:album_id, :userID, :note)";

                $rs = new JSON_RecordSet();
                $retrieval = $rs->getRecordSet($newNoteSQL,
                    'ResultSet',
                    array(':album_id' => $album,
                        ':userID' => $userID,
                        ':note' => $note));

                echo '{"status":"ok", "message":{"text": "New note added"}}';
            }

        } else if(((empty($note)) && (!empty($userID))) && (!empty($album))){
            // Note was empty, no point in trying to add it to the database
            echo '{"status":"error", "message":{"text": "New note not added. Note was empty"}}';
        } else {
            // Either note, userID or album was empty no point trying to add note to database
            echo '{"status":"error", "message":{"text": "New note not added. Sign in required OR note text and album not provided"}}';
        }

        break;

    case 'updateNote':

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
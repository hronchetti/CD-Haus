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
// Getting all the potential variables from the request stream
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : null;
$album = isset($_REQUEST['album']) ? $_REQUEST['album'] : null;
$userID = isset($_REQUEST['userID']) ? $_REQUEST['userID'] : null;
$note = isset($_REQUEST['note']) ? $_REQUEST['note'] : null;
$password = isset($_REQUEST['password']) ? $_REQUEST['password'] : null;
$data = isset($_REQUEST['data']) ? $_REQUEST['data'] : null;
$criteria = isset($_REQUEST['criteria']) ? $_REQUEST['criteria'] : null;
$genre = isset($_REQUEST['genre']) ? $_REQUEST['genre'] : null;
$ordering = isset($_REQUEST['ordering']) ? $_REQUEST['ordering'] : null;

// If action variable not in request stream but index.php still referenced there may still be some data sent by angular
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

        if (!empty($userID) && !empty($password)) {
            // Both userID and password were provided

            // Check if user exists in the database
            $userCheck = $db->prepare("SELECT user_id
                                       FROM i_user
                                       WHERE user_id = :user_id");
            $userCheck->execute(array(':user_id' => $userID));

            // If fetchObject returns a row it means the user_id entered exists in the database (step into)
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

        // Empty the session using the session class
        $session->clearSession();

        echo '{"status":"ok","message":{"text":"Sign out successful"}}';

        break;

    case 'getSessionData':

        if(!empty($data)){

            // Echo the session property and its value returned, accessed by method in session class
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

        /* Selecting from the last album required an traveling from i_artist -> i_genre
         * because RIGHT JOIN is not supported in Sqlite therefore cannot travel
         * i_genre -> i_artist AND include all albums with missing genres...
         * Seeing as every album has an artist this method works!
         */

        $searchSLQ = "SELECT i_album.artwork AS Artwork, i_album.album_id AS Album_ID, i_album.name AS Album, GROUP_CONCAT(DISTINCT i_artist.name) AS Artists, i_genre.name AS Genre, i_album.year AS Year, TIME(SUM(i_track.total_time)/1000, 'unixepoch') AS Duration
                      FROM i_artist
                          INNER JOIN i_track ON (i_track.artist_id = i_artist.artist_id)
                          INNER JOIN i_album_track ON (i_album_track.track_id = i_track.track_id)
                          INNER JOIN i_album ON (i_album.album_id = i_album_track.album_id)
                          LEFT JOIN i_genre ON (i_genre.genre_id = i_album.genre_id)
                      GROUP BY i_album.album_id
                      HAVING $filters
                      ORDER BY $ordering";

        // Creating a new instance of JSON_RecordSet class and applying SQL to the getRecordSet method
        $rs = new JSON_RecordSet();
        $retrieval = $rs->getRecordSet($searchSLQ);
        // Echoing the JSON returned from the getRecordSet method, which, used the SQL provided
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

            // Both userID and album provided so run SQL to see if notes exist
            $showNoteSQL = "SELECT *
                            FROM i_notes
                            WHERE i_notes.album_id = $album AND i_notes.userID = '$userID'";

            $rs = new JSON_RecordSet();
            $retrieval = $rs->getRecordSet($showNoteSQL);
            echo $retrieval;

        } else if((empty($userID)) && (!empty($album))){
            // UserID not provided, likely user is not signed in
            echo '{"status":"error", "message":{"text": "Sign in to show notes"}}';

        } else if((!empty($userID)) && (empty($album))){
            // Album id not provided, notify user
            echo '{"status":"error", "message":{"text": "No album chosen"}}';

        } else {
            // Both userID and album were empty
            echo '{"status":"error", "message":{"text": "Sign in to show notes"}}';
        }

        break;

    case 'newNote':

        if (((!empty($note)) && (!empty($userID))) && (!empty($album))){

            // All necessary data provided but first check if note already exists before trying to add a new one
            $noteCheck = $db->prepare("SELECT note
                                       FROM i_notes
                                       WHERE userID = :userID AND album_id = :album_id");
            $noteCheck->execute(array(':userID' => $userID,
                                      ':album_id' => $album));

            if($noteCheck->fetchObject()){
                // Note already in the database for this album & user do not proceed
                echo '{"status":"error", "message":{"text": "Note already exists for this album"}}';

            } else{
                // No note in database, proceed with adding new note
                $newNoteSQL = "INSERT INTO i_notes (album_id, userID, note) 
                               VALUES (:album_id, :userID, :note)";

                $rs = new JSON_RecordSet();
                $retrieval = $rs->getRecordSet($newNoteSQL,
                    'ResultSet',
                    array(':album_id' => $album,
                        ':userID' => $userID,
                        ':note' => $note));

                echo '{"status":"ok", "message":{"text": "New note added: '. $note .'"}}';
            }

        } else if(((empty($note)) && (!empty($userID))) && (!empty($album))){
            // Note was empty, no point in trying to add it to the database
            echo '{"status":"error", "message":{"text": "New note field required, note not added"}}';
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

            // All necessary data provided, but first check note actually exists in database before trying to delete it
            $noteCheck = $db->prepare("SELECT note
                                       FROM i_notes
                                       WHERE userID = :userID AND album_id = :album_id");
            $noteCheck->execute(array(':userID' => $userID,
                                      ':album_id' => $album));

            if($noteCheck->fetchObject()){
                // Note does exist, proceed with deleting note
                $deleteNoteSQL = "DELETE FROM i_notes
                                  WHERE i_notes.album_id=:album_id AND i_notes.userID=:userID";
                $rs = new JSON_RecordSet();
                $retrieval = $rs->getRecordSet($deleteNoteSQL,
                'ResultSet',
                array(':album_id' => $album,
                      ':userID' => $userID));

                echo '{"status":"ok", "message":{"text": "Note deleted"}}';
            } else{
                // Note did not exist for the album and userID provided, don't try and delete because nothing to delete
                echo '{"status":"error", "message":{"text": "Note cannot be deleted, note does not exist"}}';
            }
        } else {
            // Note or userID not provided, no point trying to delete from database
            echo '{"status":"error", "message":{"text": "Note not deleted. Sign in required OR album not provided"}}';
        }

        break;

    case 'showGenres':
        // Case to make all the genres accessible so filtering is dynamically populated from the database
        $genreSQL = "SELECT *
                     FROM i_genre
                     ORDER BY name";

        $rs = new JSON_RecordSet();
        $retrieval = $rs->getRecordSet($genreSQL);
        echo $retrieval;

        break;
    case 'showAlbumInfo':

        // Get the information for a given album, necessary because angular page changes controller and 'search' case does not retrieve the right information
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
            $retrieval = $rs->getRecordSet($albumInfoSQL);
            echo $retrieval;

        } else{
            echo '{"status":"error", "message":{"text": "No album chosen"}}';
        }

        break;
    default:
        // $action provided did not match any cases
        echo '{"status":"error", "message":{"text": "(default) no action taken"}}';
        break;
}
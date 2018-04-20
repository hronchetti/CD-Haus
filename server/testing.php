<?php

require_once('classes/session.class.php');
require_once('config/setEnv.php');
require_once('classes/webpage.class.php');
require_once('classes/pdoDB.class.php');
require_once('classes/getAlbums.class.php');

$session = Session::getSession();
/* Creating a new instance of the webpage class and passing in a title and stylesheet
   (not making use of array but testing page doesn't need many styles right!?) */

$page = new Webpage( "CD Haus | Testing page", array( "../resources/style/testing.css" ) );
$albumList = new getAlbums();

$URLBase = 'index.php?action=';

// Add new text to page body
$page->addToBody( "\t<h1>CD Haus Testing Page</h1>\n
                        \t<a href='../index.html'>Angular App</a>" );

/*  ---------------------------------------------
 *  USER FORMS
 *  ------------------------------------------ */

$loggedInStatus = $session->getProperty('loggedIn');
$sessionUserID = $session->getProperty('user_id');

/* If user is logged in change the form button to sign out, form action to logout and show user_id
   (will save time when trying to figure out if signed in for note functionality) */
if ($loggedInStatus === true){
    $userID = $sessionUserID;
    $userFormAction = '<input type="hidden" name="action" value="logoutUser"/>';
    $userFormButton = '<input type="submit" value="SIGN OUT">';
} else{
    $userID = '';
    $userFormAction = '<input type="hidden" name="action" value="loginUser"/>';
    $userFormButton = '<input type="submit" value="SIGN IN">';
}

$loginUser = <<<FORM

    <form action="$URLBase" method="get">
        <fieldset>
            $userFormAction
            <legend>Login/Logout User</legend>
            <label>
                User ID: 
                <input type="text" name="userID" value="$userID">
            </label>
            <label>
                Password: 
                <input type="password" name="password">
            </label>
            $userFormButton
        </fieldset>
    </form>
FORM;

/*  ---------------------------------------------
 *  SEARCH FORM
 *  ------------------------------------------ */

// Storing returned data in another variable because functions cannot be executed in a heredoc (for some reason)
$genres = $albumList->getAllGenres();

$searchAlbums = <<<FORM

    <form action="$URLBase" method="get">
        <fieldset>
            <input type="hidden" name="action" value="search"/>
            <legend>Search Albums</legend>
            <label>
                Search criteria:
                <input type="search" name="criteria">
            </label>
            <label>
                Genre:
                <select name="genre">
                    <option selected value="0">All Genres</option>
                    <!-- ALL GENRES -->
                    $genres
                    <!-- END ALL GENRES -->
                </select>   
            </label>
            <label>
                Ordering: 
                <select name="ordering">
                    <option selected value="Album ASC">Album (A - Z)</option>
                    <option value="Album DESC">Album (Z - A)</option>
                    <option value="Artists ASC">Artist (A - Z)</option>
                    <option value="Artists DESC">Artist (Z - A)</option>
                    <option value="Genre ASC">Genre (A - Z)</option>
                    <option value="Genre DESC">Genre (Z - A)</option>
                    <option value="Year DESC">Year (Newest)</option>
                    <option value="Year ASC">Year (Oldest)</option>
                    <option value="Duration DESC">Duration (Longest)</option>
                    <option value="Duration ASC">Duration (Shortest)</option>
                </select>
            </label>
            <input type="submit" value="SEARCH">
        </fieldset>
    </form>
FORM;

/*  ---------------------------------------------
 *  SHOW TRACKS FORM
 *  ------------------------------------------ */

// Storing returned data in another variable because functions cannot be executed in a heredoc (for some reason)
$albums = $albumList->getAllAlbums();

$showAlbumTracks = <<<FORM

    <form action="$URLBase" method="get">
        <fieldset>
            <input type="hidden" name="action" value="showTracks"/>
            <legend>Show Tracks</legend>
            <label>
                For Album: 
                <select name="album">
                    <!-- ALL ALBUMS -->
                    $albums
                    <!-- END ALL ALBUMS -->
                </select>
            </label>
            <input type="submit" value="SHOW TRACKS">
        </fieldset>
    </form>
FORM;

/*  ---------------------------------------------
 *  SHOW NOTE FORM
 *  ------------------------------------------ */


// Storing returned data in another variable because functions cannot be executed in a heredoc (for some reason)
$albumsWithANote = $albumList->getAlbumsWithANote();

$showNote = <<<FORM

    <form action="$URLBase" method="get">
        <fieldset>
            <input type="hidden" name="action" value="showNote"/>
            <legend>Show Note</legend>
            <label>
                On Album: 
                <select name="album">
                    <!-- ALBUMS WITH A NOTE -->
                    $albumsWithANote
                    <!-- END ALBUMS WITH A NOTE -->
                </select>
            </label>
            <input type="hidden" name="userID" value="$sessionUserID">
            <input type="submit" value="SHOW NOTE">
        </fieldset>
    </form>
FORM;

/*  ---------------------------------------------
 *  NEW NOTE FORM
 *  ------------------------------------------ */

// Storing returned data in another variable because functions cannot be executed in a heredoc (for some reason)
$albumsWithNoNote = $albumList->getAlbumsWithNoNote();

$newNote = <<<FORM
    
    <form action="$URLBase" method="get">
        <fieldset>
            <input type="hidden" name="action" value="newNote"/>
            <legend>New Note</legend>
            <label>
                On Album: 
                <select name="album">
                    <!-- ALBUMS WITH NO NOTE -->
                    $albumsWithNoNote
                    <!-- END ALBUMS WITH NO NOTE -->
                </select>
            </label>
            <label>
                Note: 
                <textarea name="note" rows="2"></textarea>
            </label>
            <input type="hidden" name="userID" value="$sessionUserID">
            <input type="submit" value="ADD NOTE">
        </fieldset>
    </form>
FORM;

/*  ---------------------------------------------
 *  UPDATE & DELETE NOTE FORMS
 *  ------------------------------------------ */
$updateNote = <<<FORM
    
    <form action="$URLBase" method="get">
        <fieldset>
            <input type="hidden" name="action" value="updateNote"/>
            <legend>Edit Note</legend>
            <label>
                On Album: 
                <select name="album">
                    <!-- ALBUMS WITH A NOTE -->
                    $albumsWithANote
                    <!-- END ALBUMS WITH A NOTE -->
                </select>
            </label>
            <label>
                New Text: 
                <textarea name="note" rows="2"></textarea>
            </label>
            <input type="hidden" name="userID" value="$sessionUserID">
            <input type="submit" value="UPDATE NOTE">
        </fieldset>
    </form>
FORM;
//
$deleteNote = <<<FORM
    
    <form action="$URLBase" method="get">
        <fieldset>
            <input type="hidden" name="action" value="deleteNote"/>
            <legend>Delete Note</legend>
            <label>
                On Album: 
                <select name="album">
                    <!-- ALBUMS WITH A NOTE -->
                    $albumsWithANote
                    <!-- END ALBUMS WITH A NOTE -->
                </select>
            </label>
            <input type="hidden" name="userID" value="$sessionUserID">
            <input type="submit" value="DELETE NOTE">
        </fieldset>
    </form>
FORM;

// Forms added to page in order listed above
$page->addToBody($loginUser);
$page->addToBody($searchAlbums);
$page->addToBody($showAlbumTracks);
$page->addToBody($showNote);
$page->addToBody($newNote);
$page->addToBody($updateNote);
$page->addToBody($deleteNote);
// Echo whole page back to the browser
echo $page->getPage();
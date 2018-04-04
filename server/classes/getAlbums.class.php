<?php
// A class to store all the album lists used on the testing page
class getAlbums
{
    protected $db;

    function __construct()
    {
        $this->db = pdoDB::getConnection();
    }
    // Making a function to get all the albums in the database
    function getAllAlbums(){
        // Creating an empty variable to add to
        $albums = '';
        // SQL for retrieving all albums from database
        $albumDropDownSQL = "SELECT * 
                         FROM i_album
                         ORDER BY name ASC";
        // Executing the SQL query
        $stmt = $this->db->query($albumDropDownSQL);
        // Storing each genre in a variable
        while($album = $stmt->fetchObject()){
            $albums .= "<option value='{$album->album_id}'>{$album->name}</option>\n\t\t\t\t\t";
        }
        // Returning all genres for use outside the function
        return $albums;
    }

    function getAlbumsWithANote(){

        $albumsWithANote = '';

        $albumsWithANoteSQL = "SELECT *
                           FROM i_album 
                              INNER JOIN i_notes ON (i_album.album_id = i_notes.album_id)
                           ORDER BY i_album.name ASC";

        $stmt = $this->db->query($albumsWithANoteSQL);
        while($album = $stmt->fetchObject()){
            $albumsWithANote .= "<option value='{$album->album_id}'>{$album->name}</option>\n\t\t\t\t\t";
        }
        return $albumsWithANote;
    }

    function getAlbumsWithNoNote(){

        $albumsWithNoNote = '';

        $albumsWithNoNoteSQL = "SELECT i_album.album_id AS Album, i_album.name
                            FROM i_album
                              LEFT JOIN i_notes ON (i_album.album_id = i_notes.album_id)
                            WHERE note IS NULL
                            ORDER BY i_album.name ASC";

        $stmt = $this->db->query($albumsWithNoNoteSQL);
        while($album = $stmt->fetchObject()){
            $albumsWithNoNote .= "<option value='{$album->Album}'>{$album->name}</option>\n\t\t\t\t\t";
        }
        return $albumsWithNoNote;
    }

    function getAllGenres(){

        $genres = '';

        $genreDropDownSQL = "SELECT * 
                         FROM i_genre
                         ORDER BY name ASC";

        $stmt = $this->db->query($genreDropDownSQL);

        while($genre = $stmt->fetchObject()){
            $genres .= "<option value='{$genre->genre_id}'>{$genre->name}</option>\n\t\t\t\t\t";
        }

        return $genres;
    }
}
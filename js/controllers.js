/**
 * Controllers to process the data and manipulate Views
 */
(function () {

    "use strict";  // Using javascript strict syntax mode to facilitate quality syntax

    angular.module('CDHaus').controller('IndexController',
        [
            '$scope',
            'dataService',

            /* Dependencies
             * ------------
             * $scope binds HTML and JS together by delivering data to the view from the controller (from the model)
             *
             * dataService is our custom built service that contains several methods for accessing data from the
             * model using http requests
            */

            function ($scope, dataService) {

                // Function gets user session data from $_SESSION so session persists in front-end when page is refreshed
                var getSessionProperty = function(property){
                    dataService.sessionService(property).then(
                        // Promise is returned
                        function (response) {
                            // Promise is fulfilled
                            if(property === 'signedIn'){
                                // SignedIn variable is being checked
                                if(response.data.signedIn === '1'){
                                    // SignedIn Session variable is true (session variable = '1' when true)
                                    $scope.signedIn = true;
                                    $scope.buttonText = 'Sign Out';

                                } else{
                                    // SignedIn Session variable is not true
                                    $scope.signedIn = false;
                                    $scope.buttonText = 'Sign In';
                                }
                            } else if(property === 'user_id'){
                                // User_id variable is being checked
                                if(response.data.user_id.length > 1){
                                    // User_id exists so change scope variable to its value
                                    $scope.userID = response.data.user_id
                                } else{
                                    // User_id not in session
                                    $scope.userID = '';
                                }
                            } else{
                                console.log('Session property does not exist');
                                $scope.buttonText = 'Sign In';
                                $scope.userID = '';
                            }
                        }, function (err) {
                            // Promise not fulfilled
                            console.log(err);

                        }, function (notify) {
                            // Notify status of promise fulfillment
                            console.log(notify);
                        }
                    );
                };

                // Function for logging user in
                var loginUser = function (user_id, password) {
                    dataService.loginUser(user_id, password).then(
                        // Promise is returned
                        function (response) {
                            // Promise is fulfilled
                            if (response.data === 'Sign in successful') {
                                // Login success
                                $scope.feedback = '';
                                $scope.userID = user_id;
                                $scope.signedIn = true;
                                $scope.buttonText = 'Sign Out';

                            } else {
                                // Login failed, feedback why to user
                                $scope.feedback = response.data;
                            }
                        }, function (err) {
                            // Promise not fulfilled
                            console.log(err);

                        }, function (notify) {
                            // Notify status of promise fulfillment
                            console.log(notify);
                        }
                    );
                };

                // Function for logging user out
                var logoutUser = function () {
                    dataService.logoutUser().then(
                        // Promise is returned
                        function (response) {
                            // Promise is fulfilled
                            if (response.data === 'Sign out successful') {
                                // Logout success
                                $scope.feedback = '';
                                $scope.signedIn = false;
                                $scope.buttonText = 'Sign In';
                                $scope.userID = '';
                            }
                        }, function (err) {
                            // Promise not fulfilled
                            console.log(err);

                        }, function (notify) {
                            // Notify status of promise fulfillment
                            console.log(notify);
                        }
                    );
                };

                /* Method to decide which login method is used
                 * If buttonText is 'Sign In' the user is not signed in
                 * therefore, use sign in function. Otherwise sign out
                 */

                $scope.loginHandler = function ($event, user_id, password) {

                    if ($scope.signedIn === false) {
                        loginUser(user_id, password);

                    } else {
                        logoutUser();
                    }
                };

                //
                getSessionProperty('signedIn');
                getSessionProperty('user_id');
            }
        ]
    ).controller('AlbumsController',
        [
            '$scope',
            'dataService',
            'applicationData',
            '$location',

            function ($scope, dataService, applicationData, $location) {

                var getGenres = function () {
                    dataService.getGenres().then(
                        function (response) {
                            $scope.genres = response.data;
                        }, function (err) {
                            console.log(err);
                        }, function (notify) {
                            console.log(notify);
                        }
                    );
                };

                $scope.getAlbums = function (selectedGenre, searchCriteria) {
                    dataService.getAlbums(selectedGenre, searchCriteria).then(
                        function (response) {
                            $scope.albumCount = response.rowCount + ' Albums';
                            $scope.albums = response.data;
                        }, function (err) {
                            console.log(err);
                        }, function (notify) {
                            console.log(notify);
                        }
                    );
                };

                // Creating an empty object for error prevention
                applicationData.publishInfo('album', {});

                $scope.selectAlbum = function ($event, album) {
                    /* Changing the URL path to be current + '/album/' + the album_id of the album clicked on
                     * This will trigger a controller change (see app.js) */
                    $location.path('/album/' + album.Album_ID);
                    applicationData.publishInfo('album', album);
                };

                // Executing the function just created to it runs when the controller loads
                getGenres();
            }
        ]
    ).controller('TracksController',
        [
            '$scope',
            'dataService',
            '$routeParams',
            '$window',

            function ($scope, dataService, $routeParams, $window) {

                // Defining the getTracks function for use
                var getTracks = function (Album_ID) {
                    dataService.getTracks(Album_ID).then(
                        function (response) {
                            $scope.tracks = response.data;
                        }, function (err) {
                            console.log(err);
                        }, function (notify) {
                            console.log(notify);
                        }
                    );
                };

                /*
                 * Populating album info from URL because it will clear on refresh / link access
                 * if it used object passed through click $event
                */

                var getAlbumInfo = function (Album_ID) {
                    dataService.getAlbumInfo(Album_ID).then(
                        function (response) {
                            $scope.chosenAlbum = response.data[0];
                        }, function (err) {
                            console.log(err);
                        }, function (notify) {
                            console.log(notify);
                        }
                    );
                };

                if ($routeParams && $routeParams.Album_ID) {
                    // Scroll to top of page (Because its a single page app the scroll does not reset to 0,0)
                    $window.scrollTo(0, 0);
                    // Just to prove single page, all console logs will remain when changing between albums
                    console.log('Viewed album: ' + $routeParams.Album_ID);
                    // Using getTracks function and passing in the Album_ID retrieved from URL
                    getAlbumInfo($routeParams.Album_ID);
                    // Using getTracks function and passing in the Album_ID retrieved from URL
                    getTracks($routeParams.Album_ID);
                }
            }
        ]
    ).controller('NotesController',
        [
            '$scope',
            '$routeParams',
            'dataService',

            function ($scope, $routeParams, dataService) {

                var showNote = function(action, userID, album) {
                    dataService.noteService(action, userID, album).then(
                        function (response) {

                            if(response.data.ResultSet){
                                // If more than one row returned a  note exists
                                if(response.data.ResultSet.Result[0].note){
                                    // Note exists
                                    $scope.albumNote = response.data.ResultSet.Result[0].note;
                                    $scope.albumHasNotes = true;
                                } else{
                                    // No note on album
                                    $scope.albumNote = 'No note';
                                    $scope.albumHasNotes = false;
                                }
                            } else{
                                // Error with retrieving notes (not signed in or album_id not sent)
                                $scope.albumNote = 'Not signed in or album not given';
                            }
                        }, function (err) {
                            console.log(err);
                        }, function (notify) {
                            console.log(notify);
                        }
                    );
                };

                $scope.newNote = function($event, note){
                    dataService.noteServiceWithText('newNote', $scope.userID, $routeParams.Album_ID, note).then(
                        function (response) {
                            //
                            if(response.data === 'New note added'){
                                $scope.albumHasNotes = true;
                                $scope.noteFeedback = response.data;
                                $scope.albumNote = note;

                            } else{
                                $scope.noteFeedback = response.data;
                            }

                        }, function (err) {
                            console.log(err);
                        }, function (notify) {
                            console.log(notify);
                        }
                    );
                };

                $scope.deleteNote = function($event){
                    dataService.noteService('deleteNote', $scope.userID, $routeParams.Album_ID).then(
                        function (response) {
                            //
                            if(response.data.status === 'ok'){
                                $scope.albumHasNotes = false;
                                $scope.noteFeedback = response.data.message.text;
                                $scope.albumNote = '';
                            } else{
                                $scope.noteFeedback = response.data.message.text;
                            }

                        }, function (err) {
                            console.log(err);
                        }, function (notify) {
                            console.log(notify);
                        }
                    );
                };

                /* userID is inherited because NotesController is child of IndexController
                 * if Album_ID is present in URL (routeParams) and userId is bigger than one (exists)
                 * run the showNote method to see if a note exists -> showNote() deals with updates to
                 * the view
                 */

                if(($routeParams && $routeParams.Album_ID) && ($scope.signedIn && $scope.userID.length > 1)){
                    showNote('showNote', $scope.userID, $routeParams.Album_ID);
                } else{
                    $scope.albumNote = 'Not signed in';
                }
            }
        ]
    );
}());
/*
 * Using Immediately-Invoked Function Expression (IIFE) that executes straight away and doesn't unnecessarily dirty
 * scope by creating global properties
 */
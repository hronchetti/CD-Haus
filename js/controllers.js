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

                // Function for logging user in
                var loginUser = function(user_id, password){
                    dataService.loginUser(user_id, password).then(
                        // Promise is returned
                        function (response) {
                            // Promise is fulfilled
                            if(response.data === 'Sign in successful'){
                                // Login success
                                $scope.buttonText = 'Sign Out';
                                $scope.feedback = '';
                            } else{
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
                var logoutUser = function(){
                    dataService.logoutUser().then(
                        // Promise is returned
                        function (response) {
                            // Promise is fulfilled
                            if(response.data === 'Sign out successful'){
                                // Logout success
                                $scope.buttonText = 'Sign In';
                                $scope.feedback = '';
                            } else{
                                // Logout failed, feedback why to user
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

                $scope.loginHandler = function($event, user_id, password){

                    if($scope.buttonText === 'Sign In'){

                        loginUser(user_id, password);

                    } else{
                        logoutUser();
                    }

                };
            }
        ]
    ).controller('AlbumsController',
        [
            // Dependencies
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

                applicationData.publishInfo('album', {});

                $scope.selectAlbum = function ($event, album) {
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
                    console.log('Viewed album: ' + $routeParams.Album_ID);
                    // Using getTracks function and passing in the Album_ID retrieved from URL
                    getAlbumInfo($routeParams.Album_ID);
                    // Using getTracks function and passing in the Album_ID retrieved from URL
                    getTracks($routeParams.Album_ID);
                }
            }
        ]
    );
}());
/*
 * Using Immediately-Invoked Function Expression (IIFE) that executes straight away and doesn't unnecessarily dirty
 * scope by creating global properties
 */
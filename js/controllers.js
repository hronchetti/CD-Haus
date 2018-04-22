/**
 * Controllers to process the data and manipulate Views
 */
(function () {

    "use strict";  // Using javascript strict syntax mode to facilitate quality syntax

    angular.module('CDHaus').controller('IndexController',
        [
            // Scope dependency binds HTML and JS together by delivering data from the model
            '$scope',
            'dataService',

            function ($scope, dataService) {

                $scope.title = 'Sign in';
                $scope.userID = '';
                $scope.buttonText = 'Sign In';
            }
            // Do login here so album and track controllers can inherit (they are inside in HTML)
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
                        // Promise is returned
                        function (response) {
                            // Promise is fulfilled
                            $scope.genres = response.data;

                        }, function (err) {
                            // Promise not fulfilled
                            console.log(err);

                        }, function (notify) {
                            //
                            console.log(notify);
                        }
                    );
                };

                $scope.getAlbums = function (selectedGenre, searchCriteria) {
                    dataService.getAlbums(selectedGenre, searchCriteria).then(
                        // Promise is returned
                        function (response) {
                            // Promise is fulfilled
                            $scope.albumCount = response.rowCount + ' Albums';
                            $scope.albums = response.data;

                        }, function (err) {
                            // Promise not fulfilled
                            console.log(err);

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
                var getTracks = function(Album_ID){
                    dataService.getTracks(Album_ID).then(
                      function (response) {
                          $scope.tracks = response.data;

                      }, function (err) {
                            // Promise not fulfilled
                            console.log(err);

                        }
                    );
                };

                var getAlbumInfo = function(Album_ID){
                    dataService.getAlbumInfo(Album_ID).then(
                        function (response) {
                            $scope.chosenAlbum = response.data[0];

                        }, function (err) {
                            // Promise not fulfilled
                            console.log(err);

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
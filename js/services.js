/**
 * Services available for the CD Haus App, used in Controllers (but independent of Views)
 */
(function () {

    "use strict";  // Using javascript strict syntax mode to facilitate quality syntax

    angular.module('CDHaus').service('dataService',
        [
            '$q', '$http',

            /* Dependencies
             * ------------
             * $q is a built in AngularJS service for promises
             *
             * $http is a built in AngularJS service for dealing with http requests,
             * using $http makes it possible to  send data to and from the server without page refresh.
             * This is how we achieve an AJAX single page application
            */

            function ($q, $http) {
                // Making a variable for the URL to the data model to save re-writing it multiple times
                var urlBase = 'server/index.php';

                /*
                 * Service function for signing in users,
                 * uses $http built in service to send user input data to index.php with necessary action
                 */

                this.loginUser = function (userID, password) {
                    var defer = $q.defer(),
                        data = {

                            action: 'loginUser',
                            userID: userID,
                            password: password
                        };

                    $http.get(urlBase, {params: data, cache: true}).
                    // $http service promise abstraction success/error instead of normal promise
                    success(function (response) {
                        defer.resolve({
                            // Promise returned successfully
                            // JSON object will be returned, add message text to data for access in controllers
                            data: response.message.text
                        });
                    }).error(function (err) {
                        // Promise not returned
                        defer.reject(err);
                    });

                    return defer.promise;
                };

                /*
                 *
                 * Service function for signing out users, using necessary PHP action
                 *
                 */

                this.logoutUser = function (){
                    var defer = $q.defer(),
                        data = {
                            action: 'logoutUser'
                        };

                    $http.get(urlBase, {params: data, cache: true}).
                    success(function (response) {
                        defer.resolve({
                            data: response.message.text
                        });
                    }).error(function (err) {
                        defer.reject(err);
                    });

                    return defer.promise;
                };

                /*
                 *
                 * Service function for getting albums, re-used when genre or search changed
                 *
                 */

                this.getAlbums = function (genre, criteria) {
                    var defer = $q.defer(),
                        data = {
                            action: 'search',
                            genre: genre,
                            criteria: criteria
                        };
                    $http.get(urlBase, {params: data, cache: true}).
                    success(function (response) {
                        defer.resolve({
                            data: response.ResultSet.Result,
                            rowCount: response.ResultSet.RowCount
                        });
                    }).error(function (err) {
                        defer.reject(err);
                    });

                    return defer.promise;
                };

                /*
                 *
                 * Service function for getting tracks of an album using given album_id
                 *
                 */

                this.getTracks = function (Album_ID) {
                    var defer = $q.defer(),
                        data = {
                            action: 'showTracks',
                            album: Album_ID
                        };
                    $http.get(urlBase, {params: data, cache: true}).
                    success(function (response) {
                        defer.resolve({
                            data: response.ResultSet.Result
                        });
                    }).error(function (err) {
                        defer.reject(err);
                    });

                    return defer.promise;
                };

                /*
                 *
                 * Service function for getting genres to populate the genre select options
                 *
                 */

                this.getGenres = function () {
                    var defer = $q.defer(),
                        data = {
                            action: 'showGenres'
                        };

                    $http.get(urlBase, {params: data, cache: true}).
                    success(function (response) {
                        defer.resolve({
                            data: response.ResultSet.Result,
                            rowCount: response.ResultSet.RowCount
                        });
                    }).error(function (err) {
                        defer.reject(err);
                    });

                    return defer.promise;
                };

                /*
                 *
                 * Service function for getting the album info when an album is clicked on
                 *
                 */

                this.getAlbumInfo = function (Album_ID) {
                    var defer = $q.defer(),
                        data = {
                            action: 'showAlbumInfo',
                            album: Album_ID
                        };

                    $http.get(urlBase, {params: data, cache: true}).// $http service promise abstraction
                    success(function (response) {
                        defer.resolve({
                            data: response.ResultSet.Result
                        });
                    }).error(function (err) {
                        defer.reject(err);
                    });

                    return defer.promise;
                };


                /*
                 *
                 * Service function for note functions not requiring note message parameters
                 * re-used by sending action with SQL data to determine which PHP case to use
                 *
                 */

                this.noteService = function (action, userID, album){
                    var defer = $q.defer(),
                        data = {
                            action: action,
                            userID: userID,
                            album: album
                        };

                    $http.get(urlBase, {params: data, cache: true}).// $http service promise abstraction
                    success(function (response) {
                        defer.resolve({
                            data: response
                        });
                    }).error(function (err) {
                        defer.reject(err);
                    });

                    return defer.promise;
                };

                /*
                 *
                 * Service function for note functions requiring note message parameters
                 *
                 */

                this.noteServiceWithText = function (action, userID, album, note){
                    var defer = $q.defer(),
                        data = {
                            action: action,
                            userID: userID,
                            album: album,
                            note: note
                        };

                    $http.get(urlBase, {params: data, cache: true}).// $http service promise abstraction
                    success(function (response) {
                        defer.resolve({
                            data: response
                        });
                    }).error(function (err) {
                        defer.reject(err);
                    });

                    return defer.promise;
                };

                /*
                 *
                 * Re-usable service function for retrieving information from PHP Session
                 * gets value of a given property by name
                 *
                 */

                this.sessionService = function (property){
                    var defer = $q.defer(),
                        data = {
                            action: 'getSessionData',
                            data: property
                        };

                    $http.get(urlBase, {params: data, cache: true}).// $http service promise abstraction
                    success(function (response) {
                        defer.resolve({
                            data: response.message
                        });
                    }).error(function (err) {
                        defer.reject(err);
                    });

                    return defer.promise;
                };
            }
        ]
    ).service('applicationData',

        /*
         *
         * Service function for broadcasting changes in $scope from highest level to lowest
         *
         */

        // Access $scope from highest level (root)
        function ($rootScope) {
            // sharedService is an object because without being so changes will not be received in child all controllers
            var sharedService = {};
            sharedService.info = {};

            sharedService.publishInfo = function (key, obj) {
                this.info[key] = obj;
                $rootScope.$broadcast('systemInfo_' + key, obj);
            };

            return sharedService;
        }
    );
}());
/*
 * Using Immediately-Invoked Function Expression (IIFE) that executes straight away and doesn't unnecessarily dirty
 * scope by creating global properties
 */
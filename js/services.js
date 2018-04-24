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
                 *
                 * Service function for signing in users
                 *
                 */

                this.loginUser = function (userID, password) {
                    var defer = $q.defer(),
                        data = {

                            action: 'loginUser',
                            userID: userID,
                            password: password
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
                 * Service function for signing out users
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
                 * Service function for getting albums, re-used when genre || search || ordering changed
                 *
                 */

                this.getAlbums = function (genre, criteria) {
                    var defer = $q.defer(),
                        data = {
                            action: 'search',
                            genre: genre,
                            criteria: criteria
                        };
                    // $http service promise abstraction success/error instead of normal promise
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
                 * Service function for getting track from an album
                 *
                 */

                this.getTracks = function (Album_ID) {
                    var defer = $q.defer(),
                        data = {
                            action: 'showTracks',
                            album: Album_ID
                        };
                    // $http service promise abstraction success/error instead of normal promise
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
                 * Service function for getting genres to populate the genre option box
                 *
                 */

                this.getGenres = function () {
                    var defer = $q.defer(),
                        data = {
                            action: 'showGenres'
                        };

                    $http.get(urlBase, {params: data, cache: true}).// $http service promise abstraction
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
                            data: response.message.text
                        });
                    }).error(function (err) {
                        defer.reject(err);
                    });

                    return defer.promise;
                };

                /*
                 *
                 * Re-usable service function for retrieving information from PHP Session
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
/**
 * Controllers to process the data and manipulate Views
 */
(function () {

    "use strict";  // Using javascript strict syntax mode to facilitate quality syntax

    angular.module('CDHaus').controller('IndexController',
        [
            // Scope dependency binds HTML and JS together by delivering data from the model
            '$scope',

            function ($scope) {
                $scope.title = 'Sign in';
                $scope.userID = '';
                $scope.buttonText = 'Sign In';
            }
            // Do login here so album and track controllers can inherit (they are inside in HTML)
        ]).controller('AlbumsController',
        [
            // Dependencies
            '$scope',
            'dataService',

            function ($scope, dataService) {

                var getAlbums = function (criteria) {
                    dataService.getAlbums().then(
                        // Promise is returned
                        function (response) {
                            // Promise is fulfilled
                            $scope.albumCount = response.rowCount + ' Albums';
                            $scope.albums = response.data;

                        }, function (err) {
                            // Promise not fulfilled
                            console.log(err);

                        }, function (notify) {
                            //
                            console.log(notify);
                        }
                    );
                };
                // Executing the function just created
                getAlbums();
            }
        ]).controller('GenreController',
        [
            '$scope',

            function ($scope) {

            }
        ]
    );
}());
/*
 * Using Immediately-Invoked Function Expression (IIFE) that executes straight away and doesn't unnecessarily dirty
 * scope by creating global properties
 */
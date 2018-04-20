/**
 * Services available for the CD Haus App, used in Controllers (but independent of Views)
 */
(function () {

    "use strict";  // Using javascript strict syntax mode to facilitate quality syntax
    angular.module('CDHaus').service('dataService',
        [
            '$q', '$http',
            function ($q, $http) {
                // Making a variable for the URL to the data model to save re-writing it multiple times
                var urlBase = 'server/index.php';

                this.getAlbums = function (criteria) {
                    var defer = $q.defer(),
                        data = {
                            action: 'search',
                            criteria: criteria
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

                this.getGenres = function () {

                }
            }
        ]);
}());
/*
 * Using Immediately-Invoked Function Expression (IIFE) that executes straight away and doesn't unnecessarily dirty
 * scope by creating global properties
 */
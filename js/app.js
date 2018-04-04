/*
 * Using Immediately-Invoked Function Expression (IIFE) that executes straight away and doesn't unnecessarily dirty
 * scope by creating global properties
 */
/**
 * 
 */
(function () {

    "use strict";  // Using javascript strict syntax mode to facilitate quality syntax

    angular.module("CDHaus",
        [
            'ngRoute'   // Dependencies
        ]
    ).config(
        [

        ]
    );
})();
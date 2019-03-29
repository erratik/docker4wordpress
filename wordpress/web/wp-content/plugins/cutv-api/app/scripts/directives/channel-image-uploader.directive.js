'use strict';

/**
 * @ngdoc directive
 * @name cutvApiAdminApp.directive:channelUploader
 * @description
 * # channelUploader
 */
angular.module('cutvApiAdminApp')
    .directive('channelImageUploader', function($templateRequest, $compile, $http) {
        return {
            restrict: 'E',
            replace: true,
            template: '<div class="flex vertical three wide column uploader-content"><div class="ui active inverted dimmer"><div class="ui loader"></div></div></div>',
            scope: true,
            controllerAs: 'childCtrl',
            link: function(scope, element) {

                scope.updated = false;
                if (!!scope.$flow) {

                    $templateRequest('/wp-content/plugins/cutv-api/app/templates/upload-channel-image.html').then(function(html) {
                        var template = angular.element(html);
                        element.html(template);
                        $compile(template)(scope);
                    });

                }

                scope.$on('flow::fileAdded', (event, $flow, flowFile) => {
                    scope.filename = flowFile.error ? 'http://via.placeholder.com/100/C00D0D/000?text=oops.' : flowFile.name;
                    scope.$emit('channelImageUploaded');
                });

                scope.$on('flow::fileSuccess', (event, $flow, flowFile) => {

                    scope.$emit('channelImageUpdated');
                    scope.updated = true;
                    $flow.files = [];
                });

            }
        };
    });
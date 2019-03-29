'use strict';

// require '../services/channel.svc';

/**
 * @ngdoc directive
 * @name cutvApiAdminApp.directive:manageSources
 * @description
 * # manageChannel
 */
angular.module('cutvApiAdminApp')
    .directive('manageSources', (ChannelService) => {
        return {
            restrict: 'E',
            // replace: true,
            scope: {
                channel: '=',
                sources: '='
            },
            templateUrl: '/wp-content/plugins/cutv-api/app/templates/manage-sources.html',
            link: function(scope, element, attrs) {

                scope.query = '';
                scope.updateSuccess = false;

                const selectedSources = new Set(scope.sources.selected);
                const availableSources = new Set(scope.sources.available);

                const sourcesToArrays = (() => {
                    scope.sources = {
                        selected: Array.from(selectedSources),
                        available: Array.from(availableSources)
                    }
                });

                scope.selectSource = (source) => {
                    if (source.selected) {
                        selectedSources.add(source);
                        availableSources.delete(source);
                    } else {
                        availableSources.add(source);
                        selectedSources.delete(source);
                    }
                    sourcesToArrays();
                };

                scope.updateChannelSources = () => {

                    var data = {
                        action: 'cutv_update_channel_sources',
                        sources: scope.sources.selected.map(({
                            source_id
                        }) => source_id).join(','),
                        channel: scope.channel.pid,
                        move_videos: true
                    };

                    ChannelService.handlePluginAction(data).then(res => {
                        scope.updateSuccess = true;
                        // todo: validate the results
                        // return res.map(r => Number(r));
                    });

                };



                function makeSourceObj(updatingSources) {

                    var channel_id = scope.channel.pid;
                    scope.channel['sources'] = scope.sources.filter((src) => {
                        return src.selected
                    });
                    debugger;
                }


            }
        };
    })
    .filter('orderObjectBy', function() {
        return function(input, attribute) {
            if (!angular.isObject(input)) return input;

            var array = [];
            for (var objectKey in input) {
                array.push(input[objectKey]);
            }
            console.log();
            array.sort(function(a, b) {
                a = a[attribute];
                b = b[attribute];
                return a - b;
            });
            return array;
        }
    });
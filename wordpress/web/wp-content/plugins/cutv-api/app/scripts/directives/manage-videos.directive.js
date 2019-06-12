'use strict';

// require '../services/channel.svc';

/**
 * @ngdoc directive
 * @name cutvApiAdminApp.directive:manageSources
 * @description
 * # manageChannel
 */
angular.module('cutvApiAdminApp')
    .directive('manageVideos', ($http, $routeParams, ChannelService) => {
        return {
            restrict: 'E',
            // replace: true,
            scope: {
                channel: '=',
                sources: '='
            },
            templateUrl: '/wp-content/plugins/cutv-api/app/templates/manage-videos.html',

            controller: async function($scope, $element, $attrs) {

                $scope.options = [{
                        label: 'Publish',
                        state: 'publish',
                        color: 'green'
                    },
                    {
                        label: 'Unpublish',
                        state: 'pending',
                        color: 'purple'
                    },
                    {
                        label: 'Trash',
                        state: 'draft',
                        color: 'grey'
                    }
                ];
                $scope.selectedAction = $scope.options[0];
                $scope.toggles = {};
                $scope.isLoading = true;

                $scope.loaders = {};
                $scope.videos = {};
                $scope.selected = new Set();
                $scope.channel.sources.forEach(data => {
                    const source = data.source.wpvr_source_name;
                    const pending = data.videos.pending;
                    const publish = data.videos.publish;
                    const draft = data.videos.draft;
                    $scope.videos[source] = {
                        pending,
                        publish,
                        draft,
                        all: pending.concat(publish, draft),
                        total: pending.length + publish.length + draft.length
                    };
                    // $scope.videos.all = $scope.videos.all.concat($scope.videos[source].all);
                    $scope.loaders[source] = false;
                    $scope.toggles[source] = {};
                });

                $scope.query = '';

                $scope.update = () => {
                    $scope.$emit('videosUpdated');
                };

                $scope.$on('reload', () => {
                    $scope.isLoading = false;
                })

            },
            link: function(scope, element, attrs) {
                scope.isLoading = false;
                scope.toggleVideoSelected = (video) => {
                    if (scope.selected.has(video.ID)) {
                        scope.selected.delete(video.ID);
                    } else {
                        scope.selected.add(video.ID);
                    }
                    video.selected = !video.selected;
                    scope.selectedVideos = Array.from(scope.selected);
                };
                scope.isVideoSelected = (id) => scope.selected.has(id);

                scope.toggleSourceVideos = (source, state) => {
                    scope.toggles[source.name][state] = !scope.toggles[source.name][state];
                    scope.videos[source.name][state].forEach(id => {
                        let video = scope.videos[source.name].all.find(vid => id === vid);
                        scope.toggleVideoSelected(video);
                    });
                };

                scope.updateVideos = (method) => {
                    scope.isLoading = true;
                    const videoString = Array.from(scope.selected).join(',');
                    var data = {
                        method: method.state,
                        action: 'cutv_convert_snaptube',
                        video_ids: videoString,
                    };

                    ChannelService.handlePluginAction(data).then(() => {
                        Array.prototype.concat.apply([],
                                Object.keys(scope.videos).map(sourceName => scope.videos[sourceName].all))
                            .filter(v => videoString.includes(v.ID)).forEach(video => {
                                scope.toggleVideoSelected(video);
                                video.post_status = method.state;
                            });
                    });

                };

                scope.openSourceDialog = (source, action) => {

                    switch (action) {
                        case 'edit':
                            $http.get(`/wp-admin/post.php?post=${source.source_id}&action=edit`).then((res) => {
                                console.log(JSON.parse(res))
                            });
                            $(`#${action}Sources_${source.source_id}`).find('.content').html(`<iframe src="/wp-admin/post.php?post=${source.source_id}&action=edit"></iframe>`);
                            break;
                        case 'run':
                            $http.get(`/wp-admin/admin.php?page=wpvr&run_sources&ids=${source.source_id}`).then((res) => {
                                var el = document.createElement('html');
                                el.innerHTML = res.data;
                                $(`#${action}Sources_${source.source_id}`).find('.content').html($(el.getElementsByClassName('wpvr_source_insights')).html());
                            });
                            break;
                        default:

                    }
                    $(`#${action}Sources_${source.source_id}`).modal('show');


                };
                scope.closeSourceDialog = (source, action) => {
                    // todo: refetch channel videos
                    $(`#${action}Sources_${source.source_id}`).modal('hide');
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
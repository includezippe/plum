var app = angular.module('main', ['ui.router']);

    app.config(function ($stateProvider, $locationProvider, $urlRouterProvider) {

        $urlRouterProvider.otherwise(function($injector, $location){
            $injector.invoke(['$state', function($state) {
                $state.go('404');
            }]);
        });

        $stateProvider
            .state('root',{
                url: '',
                views: {
                    'nav': {
                        templateUrl: 'view/nav.html',
                        controller: 'Nav'
                    }
                }
            })
            .state('404', {
                views: {
                    'container@': {
                        templateUrl: 'view/404.html'
                    },
                    'nav@': {}
                }
            })
            .state('root.Login', {
                url: '/',
                views: {
                    'container@': {
                        templateUrl: 'view/login.html',
                        controller: 'Login'
                    },
                    'nav@': {}
                }
            })
            .state('root.scan', {
                url: '/scan',
                views: {
                    'container@': {
                        templateUrl: 'view/scan.html',
                        controller: 'Scan'
                    }
                }
            })
            .state('root.view_profile', {
                    url: '/id{id:[0-9]+}',
                    views: {
                        'container@': {
                            templateUrl: 'view/view_profile.html',
                            controller: 'ViewProfile'
                        }
                    }
            })
            .state('root.join', {
                url: '/join',
                view: {
                    'contaioner@': {
                        templateUrl: 'view/edit_profile.html',
                        controller: 'FillProfile'
                    }
                }
            })
            .state('root.touch', {
                url: '/touch',
                view: {
                    'contaioner@': {
                        templateUrl: 'view/touch.html',
                        controller: 'Touch'
                    }
                }
            })
            .state('root.favorite', {
                url: '/fav',
                view: {
                    'contaioner@': {
                        templateUrl: 'view/favorites.html',
                        controller: 'Favorites'
                    }
                }
            });
        $locationProvider.html5Mode(true);
    });

    app.run(function($rootScope, $location){


    });

    app.factory('actions', function($http) {
        var actions = {};
        $http.defaults.headers.post["Content-Type"] = "application/x-www-form-urlencoded";

        function object_to_params(obj) {
            var p = [];
            for (var key in obj) {
                p.push(key + '=' + obj[key]);
            }
            return p.join('&');
        }

        actions.send_data = function(url,data) {
            return $http({
                method: 'POST',
                url: url,
                data: object_to_params(data)
            })
        };

        actions.get_data = function(url) {
            return $http({
                method: 'GET',
                url: url
            })
        };

        actions.isEmpty = function (obj) {
            for(var key in obj) {
                if(obj.hasOwnProperty(key))
                    return false;
            }
            return true;
        };

        return actions;
    });

    app.controller('Nav', function($scope,$http,$location) {

    });

    app.controller('Login', function($scope,$http,$location) {

    });

    app.controller('FillProfile', function(actions, $scope, $http, $location ) {

        //+++get profile data from database
        $scope.profile = {sex: 0};

        $scope.set_profile = function () {
           actions.send_data('api/update_profile',this.profile);
           $location.path('/touch');
        }
    });

    app.controller('Touch', function(actions, $scope, $http, $location ) {
        var prev = null;
        var ready = false;
        var results = [];
        var slides = [];
        var selected_slides = [];
        var characters = [];
        var points = [0,0,0,0];

        $scope.touch_1_complete = false;

        $scope.init = function() {
            actions.get_data('api/get_touch_slides').success(function (data) {
                slides = data;
                var rand = Math.floor(Math.random() * slides.length);
                $scope.slides = slides;
                $scope.slides[rand].selected = true;
                prev = rand;
                ready = true;
            });
        };

        $scope.next = function (value) {
            if(ready) {
                results.push([slides[prev].CharacterID,value]);
                if(parseInt(value)==1) {
                    selected_slides.push(slides[prev]);
                }
                if($scope.slides.length>1) {
                    $scope.slides.splice(prev,1);
                    rand = Math.floor(Math.random() * $scope.slides.length);;
                    $scope.slides[rand].selected = true;
                    prev = rand;
                } else
                    $scope.show_next_scene();
            }
        };

        $scope.show_next_scene = function () {
            var group_0 = [1,2,3,4];
            var group_1 = [5,6,7,8];
            var group_2 = [9,10,11,12];
            var group_3 = [13,14,15,16];
            var selected = [];

            $scope.to_select_num = 3;

            $scope.touch_1_complete = true;

            for(i=0;i<results.length;i++) {
                if(results[i][1] == 1) selected.push(parseInt(results[i][0]));
            }

            for(i=0;i<selected.length;i++) {
                for(g=0;g<=3;g++) {
                    if(eval( 'group_' + g).indexOf(parseInt(selected[i]))>-1) {
                        points[g]++;
                        break;
                    }
                }
            }
            $scope.selected_slides = selected_slides;
        };

        $scope.choice = function(index) {
            if($scope.to_select_num>0) {
                characters.push(selected_slides[index].CharacterID);
                $scope.selected_slides.splice(index,1);
                $scope.to_select_num--;
                $scope.next = '';
            }
        };

        $scope.send_data = function() {
            actions.send_data('api/send_characters',[points,characters]);
            $location.path('/scan');
        }
    });

    app.controller('Scan', function($scope, $http, actions, $stateParams) {
        $scope.sub = $stateParams.sub;
        actions.get_data('api/get_users').success(function (data) {
            console.log(data);
            $scope.users = data;
        });
    });

    app.controller('ViewProfile', function($scope, $http, actions, $stateParams, $location) {
        actions.get_data('api/get_profile?id='+$stateParams.id).success(function (data) {
            if(!actions.isEmpty(data)) {
                $scope.user = data;
            } else {
                $location.path('/404');
            }
        });
    });

    app.controller('Favorites', function($scope, $http, actions) {
        actions.get_data('api/get_favorites').success(function (data) {
            if(!actions.isEmpty(data)) {
                $scope.users = data;
            }
        });
    });

'use strict';

function ListCtrl($scope, Items, Data) {
    $scope.items = Items.query(function(data){
        var i = 0;
        angular.forEach(data, function(v,k) { data[k]._id = i++; });
    });
    $scope.categories = Data('categories');
    $scope.answerers  = Data('answerers');

    $scope.tablehead = [
        {name:'title',    title:"Заголовок"},
        {name:'category', title:"Категория",  list:$scope.categories},
        {name:'answerer', title:"Кому задан", list:$scope.answerers},
        {name:'author',   title:"Автор"},
        {name:'created',  title:"Задан"},
        {name:'answered', title:"Отвечен"},
        {name:'shown',    title:"Опубликован"}
    ];

    $scope.disableItem = function() {
        var item = this.item;
        Items.toggle({id:item.id}, function() { if (data.ok) item.shown = item.shown>0?0:1; });
    };
}

function EditCtrl($scope, $routeParams, $location, Items, Data) {
    $scope.item = Items.get({id:$routeParams.id});
    $scope.categories = Data('categories');
    $scope.answerers  = Data('answerers');
    $scope.save = function() {
        $scope.item.$save({id:$scope.item.id}, function(){ $location.path('/list'); });
    };
}

function NewCtrl($scope, $location, Items, Data) {
    $scope.item = {id:0,category:'',answerer:'',title:'',text:'',answer:'',author:''};
    $scope.categories = Data('categories');
    $scope.answerers  = Data('answerers');
    $scope.save = function() {
        Items.create($scope.item, function(){ $location.path('/list'); });
    };
}
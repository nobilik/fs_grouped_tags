(function () {
    var module_routes = [
    {
        "uri": "grouped-tags\/attach",
        "name": "grouped-tags.attach"
    },
    {
        "uri": "grouped-tags\/detach",
        "name": "grouped-tags.detach"
    },
    {
        "uri": "grouped-tags\/tag\/store",
        "name": "grouped-tags.tag.store"
    },
    {
        "uri": "grouped-tags\/{group}\/destroy",
        "name": "grouped-tags.destroy"
    }
];

    if (typeof(laroute) != "undefined") {
        laroute.add_routes(module_routes);
    } else {
        contole.log('laroute not initialized, can not add module routes:');
        contole.log(module_routes);
    }
})();
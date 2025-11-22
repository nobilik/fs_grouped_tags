<?php

// Этот файл должен находиться в Modules\NobilikGroupedTags\routes\web.php

Route::group([
    'middleware' => 'web', 
    'prefix' => \Helper::getSubdirectory(), 
    'namespace' => 'Modules\NobilikGroupedTags\Http\Controllers' 
], function()
{

    Route::any('/grouped-tags/check', [
        'uses' => 'GroupedTagsController@check', 
        'middleware' => ['auth', 'roles'], 
        'roles' => ['admin', 'user'], 
        'laroute' => true
    ])->name('grouped-tags.check');
    

    Route::any('/grouped-tags/attach-tag', [
        'uses' => 'GroupedTagsController@attachTagToConversation', 
        'middleware' => ['auth', 'roles'], 
        'roles' => ['admin', 'user'], 
        'laroute' => true
    ])->name('grouped-tags.attach-tag');

        // 4. Прикрепление тега (POST/ANY)
    Route::any('/grouped-tags/attach', [
        'uses' => 'GroupedTagsController@attachTag', 
        'middleware' => ['auth', 'roles'], 
        'roles' => ['admin'], 
        'laroute' => true
    ])->name('grouped-tags.attach');

    // 5. Открепление тега (POST/ANY)
    Route::any('/grouped-tags/detach', [
        'uses' => 'GroupedTagsController@detachTag', 
        'middleware' => ['auth', 'roles'], 
        'roles' => ['admin'], 
        'laroute' => true
    ])->name('grouped-tags.detach');

    Route::any('/grouped-tags/tag/store', [
        'uses' => 'GroupedTagsController@storeTag', 
        'middleware' => ['auth', 'roles'], 
        'roles' => ['admin'], 
        'laroute' => true
    ])->name('grouped-tags.tag.store');
    // --- Страницы (GET) ---

    // Страница настроек. ИСПРАВЛЕНО: Вызываем метод 'index' или 'settings' в контроллере. 
    // Если вы уверены, что метод называется settings, оставьте его. Если нет, используйте index.
    Route::get('/grouped-tags/settings', [
        // Я оставляю 'settings', так как мы пытались его использовать. 
        // Если ошибка "Method [settings] does not exist" повторится, измените на 'index'.
        'uses' => 'GroupedTagsController@index', 
        'middleware' => ['auth', 'roles'], 
        'roles' => ['admin']
    ])->name('grouped-tags.settings');
    
    // --- AJAX-маршруты (Route::any с laroute: true) ---

    // 1. Удаление группы (DELETE/ANY). 
    // URL: /grouped-tags/{group}/destroy - чтобы избежать конфликта с update/store.
    Route::any('/grouped-tags/{group}/destroy', [ 
        'uses' => 'GroupedTagsController@destroy', 
        'middleware' => ['auth', 'roles'], 
        'roles' => ['admin'], 
        'laroute' => true 
    ])->name('grouped-tags.destroy'); // Имя, используемое в module.js

    // 2. Обновление группы (PUT/PATCH/ANY)
    Route::any('/grouped-tags/{group}', [
        'uses' => 'GroupedTagsController@update', 
        'middleware' => ['auth', 'roles'], 
        'roles' => ['admin']
    ])->name('grouped-tags.update'); // Имя, используемое в Blade (edit_group_modal)

    // 3. Создание новой группы (POST/ANY)
    Route::any('/grouped-tags', [
        'uses' => 'GroupedTagsController@store', 
        'middleware' => ['auth', 'roles'], 
        'roles' => ['admin']
    ])->name('grouped-tags.store'); // Имя, используемое в Blade (add_group_modal)

});
// Используем полную форму jQuery, чтобы избежать конфликтов
(function(jQuery, window, document) {
    'use strict';
    
    // Проверяем наличие fsAjax
    if (typeof fsAjax === 'undefined') {
        console.error('FATAL ERROR: fsAjax function is not defined. Cannot perform AJAX operations securely.');
    }

    /**
     * Обработчик удаления группы (использует fsAjax для DELETE-запроса).
     * URL берется из data-delete-url в Blade.
     */
    function handleDeleteGroup(e) {
        e.preventDefault();
        e.stopPropagation();
        
        if (typeof fsAjax === 'undefined') {
             alert('Cannot proceed: fsAjax is missing. Ensure Freescout core functions are loaded.');
             return;
        }
        
        var $button = jQuery(this); 
        var groupId = $button.data('group-id');
        var groupName = $button.data('group-name');
        
        // --- ОБХОДНОЙ ПУТЬ: URL берется из data-атрибута BLADE-шаблона ---
        var url = $button.data('delete-url');
        // ------------------------------------------------------------------

        var $groupPanel = $button.closest('.panel')
        
        var message = 'Are you sure you want to delete tag group "' + groupName + '"? All tags will be unassigned.';

        if (confirm(message)) {
            
            if (!url) {
                console.error('FATAL ERROR: data-delete-url is missing. Check settings.blade.php.');
                alert('Ошибка маршрутизации: Невозможно сгенерировать URL для удаления. Проверьте шаблон Blade.');
                return; 
            }


            // =======================================================


            // Данные для отправки: указываем метод DELETE
            var data = { 
                _method: 'DELETE' // Спуфинг метода
            };
            
            // Запускаем AJAX-запрос. Браузер выполнит 302, что подтвердит удаление на сервере.
            fsAjax(data, 
                url, 
                function(response) {
                    $groupPanel.fadeOut(300, function() {
                        jQuery(this).remove();
                    });
                    
                    // Опционально: Обновляем счетчик групп на странице
                    var $heading = jQuery('h3:contains("Tag Groups")');
                    var match = $heading.text().match(/\((\d+)\)/);
                    var currentCount = match ? parseInt(match[1], 10) : 0;
                    if (currentCount > 0) {
                        $heading.html($heading.html().replace('('+currentCount+')', '('+(currentCount-1)+')'));
                    }
                },
                true // loaderShow: true
            );
        }
    }

    /**
     * Обработчик отвязки тега (использует fsAjax для POST-запроса).
     * URL берется из глобальной переменной NobilikGroupedTags.urls.detach.
     */
    function handleDetachTag(e) {
        e.preventDefault();
        
        if (typeof fsAjax === 'undefined') {
             alert('Cannot proceed: fsAjax is missing. Ensure Freescout core functions are loaded.');
             return;
        }

        var $button = jQuery(this);
        var groupId = $button.data('group-id');
        var tagId = $button.data('tag-id');
        var groupName = $button.data('group-name');
        var tagName = $button.data('tag-name');
        var url = $button.data('detach-url');

        console.log('GroupedTags: handleDetachTag called for Tag ID:', tagId, 'Group ID:', groupId); 

        var message = 'Are you sure you want to remove tag "' + tagName + '" from group "' + groupName + '"?';

        if (confirm(message)) { 
            
            if (typeof url === 'undefined' || !url) {
                console.error('GroupedTags: URL for detach is missing. Check settings.blade.php.');
                alert('Ошибка маршрутизации: Невозможно сгенерировать URL для открепления тега.');
                return; 
            }

            var data = { 
                group_id: groupId, 
                tag_id: tagId
            };
            
            fsAjax(data, 
                url, 
                function(response) {
                    if (typeof(response.status) !== 'undefined' && response.status === 'success') {
                        window.location.reload();
                    } else {
                        if (typeof showAjaxError !== 'undefined') {
                            showAjaxError(response);
                        } else {
                            alert('Error detaching tag: ' + (response.message || 'Unknown error'));
                        }
                    }
                },
                true 
            );
        }
    }


    /**
     * Обработчик привязки тега (использует fsAjax для POST-запроса).
     * URL берется из глобальной переменной NobilikGroupedTags.urls.attach.
     */
    function handleAttachTag(e) {
        e.preventDefault();
        
        if (typeof fsAjax === 'undefined') {
             alert('Cannot proceed: fsAjax is missing. Ensure Freescout core functions are loaded.');
             return;
        }
        
        var $link = jQuery(this);
        var groupId = $link.data('group-id');
        var tagId = $link.data('tag-id');

        console.log('GroupedTags: handleAttachTag called for Tag ID:', tagId, 'Group ID:', groupId); 
        
        // --- КРИТИЧЕСКОЕ ИЗМЕНЕНИЕ: Используем глобальную переменную ---
        var url = $link.data('attach-url');
        // -------------------------------------------------------------
        
        if (typeof url === 'undefined' || !url) {
            console.error('GroupedTags: URL for attach is missing. Check settings.blade.php.');
            alert('Ошибка маршрутизации: Невозможно сгенерировать URL для прикрепления тега.');
            return; 
        }

        var data = { 
            group_id: groupId, 
            tag_id: tagId
        };
        
        fsAjax(data, 
            url, 
            function(response) {
                if (typeof(response.status) !== 'undefined' && response.status === 'success') {
                    window.location.reload();
                } else {
                    if (typeof showAjaxError !== 'undefined') {
                        showAjaxError(response);
                    } else {
                        var errorMsg = 'Error attaching tag: ' + (response.message || 'Unknown error or limit reached.');
                        alert(errorMsg);
                    }
                }
            },
            true 
        );
    }

    /**
     * Инициализация обработчиков событий
     */
    function initGroupedTagsSettings() {
        // Делегирование событий
        jQuery(document).on('click', '.js-delete-group', handleDeleteGroup);
        jQuery(document).on('click', '.js-detach-tag', handleDetachTag);
        jQuery(document).on('click', '.js-attach-tag', handleAttachTag);
        
        console.log('GroupedTags: Event listeners initialized.');
    }

    // Запускаем инициализацию после полной загрузки DOM
    jQuery(document).ready(initGroupedTagsSettings);
    
})(jQuery, window, document);
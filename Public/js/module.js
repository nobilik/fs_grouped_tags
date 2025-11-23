(function($, window, document) {
    'use strict';

    if (typeof fsAjax === 'undefined') {
        console.error('FATAL ERROR: fsAjax is not defined.');
    }

    // ============================
    // УДАЛЕНИЕ ГРУППЫ
    // ============================
    function handleDeleteGroup(e) {
        e.preventDefault();
        e.stopPropagation();

        if (typeof fsAjax === 'undefined') {
            alert('Cannot proceed: fsAjax missing.');
            return;
        }

        var $button = $(this);
        var groupName = $button.data('group-name');
        var url = $button.data('delete-url');
        var $groupPanel = $button.closest('.panel');

        if (!url) {
            alert('Ошибка маршрутизации: нет data-delete-url');
            return;
        }

        if (confirm('Are you sure you want to delete tag group "' + groupName + '"?')) {
            var data = { _method: 'DELETE' };
            fsAjax(data, url, function() {
                $groupPanel.fadeOut(300, function() { $(this).remove(); });
            }, true);
        }
    }

    // ============================
    // ОТВЯЗКА ТЕГА
    // ============================
    function handleDetachTag(e) {
        e.preventDefault();

        if (typeof fsAjax === 'undefined') {
            alert('fsAjax missing');
            return;
        }

        var $button = $(this);
        var groupId = $button.data('group-id');
        var tagId = $button.data('tag-id');
        var url = $button.data('detach-url');

        if (!url) {
            alert('Ошибка маршрутизации: нет data-detach-url');
            return;
        }

        if (confirm('Are you sure you want to remove tag "' + $button.data('tag-name') +
            '" from group "' + $button.data('group-name') + '"?')) {

            var data = { group_id: groupId, tag_id: tagId };

            fsAjax(data, url, function(response) {
                if (response.status === 'success') {
                    window.location.reload();
                } else {
                    alert(response.message || 'Unknown error');
                }
            }, true);
        }
    }

    // ============================
    // ПРИВЯЗКА ТЕГА
    // ============================
    function handleAttachTag(e) {
        e.preventDefault();

        if (typeof fsAjax === 'undefined') {
            alert('fsAjax missing');
            return;
        }

        var $link = $(this);
        var groupId = $link.data('group-id');
        var tagId = $link.data('tag-id');
        var url = $link.data('attach-url');

        if (!url) {
            alert('Ошибка маршрутизации: нет data-attach-url');
            return;
        }

        var data = { group_id: groupId, tag_id: tagId };

        fsAjax(data, url, function(response) {
            if (response.status === 'success') {
                window.location.reload();
            } else {
                alert(response.message || 'Attach failed');
            }
        }, true);
    }

    // ============================
    // ИНИЦИАЛИЗАЦИЯ
    // ============================
    function init() {
        $(document).on('click', '.js-delete-group', handleDeleteGroup);
        $(document).on('click', '.js-detach-tag', handleDetachTag);
        $(document).on('click', '.js-attach-tag', handleAttachTag);

        console.log('GroupedTags: handlers loaded');
    }

    $(document).ready(init);

})(jQuery, window, document);

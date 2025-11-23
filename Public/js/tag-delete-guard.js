$(document).on('click', '.tag-delete-forever', function(e) {
    var $btn = $(this);

    // Если уже разрешено удаление — не делаем проверку повторно
    if ($btn.data('tag-delete-checked')) {
        $btn.data('tag-delete-checked', false); // сброс флага
        return; // продолжаем стандартный обработчик Freescout
    }

    var $form = $btn.closest('form');
    var tagId = $form.data('tag-id');
    var url = '/grouped-tags/check-tag-delete';

    // Блокируем стандартный обработчик Freescout до проверки
    e.preventDefault();
    e.stopImmediatePropagation();

    fsAjax({ tag_id: tagId }, url, function(res) {
        if (!res.allowed) {
            // Запрещено удаление → флеш предупреждение
            if (typeof showFloatingAlert !== 'undefined') {
                showFloatingAlert('error', res.message);
            }

            $('.modal:visible').modal('hide');
            return;
        }

        // Разрешено → помечаем флаг, чтобы следующий клик прошел без проверки
        $btn.data('tag-delete-checked', true);

        // Вызываем стандартный Freescout клик
        var evt = new MouseEvent('click', { bubbles: true, cancelable: true });
        $btn[0].dispatchEvent(evt);
    }, true);
});

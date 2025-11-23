/**
 * Tag Manager для Freescout
 * - проверяет обязательные группы тегов
 * - блокирует сабмит, если есть пропущенные группы
 * - открывает модал
 * - после выбора всех тегов продолжает сабмит
 */

const tagManager = (() => {
    let currentGroups = [];
    let currentConversationId = null;
    let pendingButton = null; // кнопка, сабмит которой заблокирован

    function openTagModal(groups, conversationId, button) {
        currentGroups = groups;
        currentConversationId = conversationId;
        pendingButton = button;

        renderTagGroups();
        setupListeners();

        const status = document.getElementById('status-message');
        status.classList.add('hidden');
        status.innerHTML = '';

        document.getElementById('tag-modal-overlay').classList.add('open');
    }

    function closeTagModal() {
        document.getElementById('tag-modal-overlay').classList.remove('open');
        pendingButton = null;
    }

    function renderTagGroups() {
        const container = document.getElementById('tag-groups-container');
        container.innerHTML = '';

        currentGroups.forEach(group => {
            const limitText = group.max_tags_for_conversation ? ` (лимит: ${group.max_tags_for_conversation})` : '';

            const tagsHtml = group.tags.map(tag => `
                <label class="tag-item">
                    <input type="checkbox" class="tag-checkbox" data-group-id="${group.id}" value="${tag.id}">
                    <span>${tag.name}</span>
                </label>
            `).join('');

            container.insertAdjacentHTML('beforeend', `
                <section class="tag-group" data-group-id="${group.id}">
                    <h3>${group.name}${limitText}</h3>
                    <div class="tag-group__tags">${tagsHtml}</div>
                </section>
            `);
        });
    }

    function setupListeners() {
        document.querySelectorAll('.tag-checkbox').forEach(ch => {
            ch.addEventListener('change', () => {
                applyLimit(parseInt(ch.dataset.groupId));
            });
        });

        currentGroups.forEach(g => applyLimit(g.id));
    }

    function applyLimit(groupId) {
        const group = currentGroups.find(g => g.id === groupId);
        if (!group || group.max_tags_for_conversation === 0) return;

        const boxes = [...document.querySelectorAll(`input[data-group-id="${groupId}"]`)];
        const count = boxes.filter(b => b.checked).length;

        boxes.forEach(b => {
            b.disabled = count >= group.max_tags_for_conversation && !b.checked;
        });
    }

    async function handleTagAttachment() {
        const btn = document.getElementById('submit-btn');
        const status = document.getElementById('status-message');

        btn.disabled = true;
        btn.textContent = 'Обработка...';
        status.classList.add('hidden');
        status.innerHTML = '';

        const results = [];

        for (const group of currentGroups) {
            const selected = [...document.querySelectorAll(`input[data-group-id="${group.id}"]:checked`)]
                .map(b => parseInt(b.value));

            if (!selected.length) {
                results.push({ group: group.name, ok: false, message: 'Нет выбранных тегов' });
                continue;
            }

            try {
                const res = await fetch('/grouped-tags/attach-tag', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        conversation_id: currentConversationId,
                        tag_ids: selected,
                        group_id: group.id
                    }),
                    credentials: 'same-origin'
                });

                const data = await res.json();

                if (!res.ok || data.status !== 'ok') {
                    const msg = data.message || 'Ошибка сервера';
                    results.push({ group: group.name, ok: false, message: msg });
                } else {
                    results.push({ group: group.name, ok: true, message: data.message || '' });
                }
            } catch (e) {
                results.push({ group: group.name, ok: false, message: e.message });
            }
        }

        status.innerHTML = results.map(r =>
            `<div>${r.ok ? '✔️' : '❌'} <strong>${r.group}</strong>${r.message ? ' — ' + r.message : ''}</div>`
        ).join('');

        status.classList.remove('hidden');
        const errors = results.filter(r => !r.ok).length;
        status.className = errors ? 'tag-modal__status error' : 'tag-modal__status success';

        btn.disabled = false;
        btn.textContent = 'Прикрепить Теги и Продолжить';

        // Проверка на сервере: есть ли еще обязательные группы
        try {
            const checkRes = await fetch('/grouped-tags/check', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ conversation_id: currentConversationId }),
                credentials: 'same-origin'
            });

            const checkData = await checkRes.json();

            if (checkRes.ok && (!checkData.missing_groups || checkData.missing_groups.length === 0)) {
                closeTagModal();

                // Продолжаем исходный сабмит Freescout
                if (pendingButton) {
                    pendingButton.click();
                    pendingButton = null;
                }
            } else if (checkRes.ok && checkData.missing_groups?.length) {
                // обновляем UI с пропущенными группами
                currentGroups = checkData.missing_groups;
                renderTagGroups();
                setupListeners();
                status.innerHTML += `<div class="mt-2">Остались обязательные группы: <strong>${checkData.missing_groups.map(g => g.name).join(', ')}</strong></div>`;
                status.className = 'tag-modal__status error';
                status.classList.remove('hidden');
            }
        } catch (e) {
            console.error('Final check failed', e);
            status.innerHTML += `<div class="mt-2">Не удалось проверить текущее состояние: ${e.message}</div>`;
            status.className = 'tag-modal__status error';
            status.classList.remove('hidden');
        }
    }

    return { openTagModal, closeTagModal, handleTagAttachment };
})();

// --- Инжекция в фильтр Freescout ---
document.addEventListener('DOMContentLoaded', function() {
    if (typeof window.fsAddFilter === 'function') {
        fsAddFilter('conversation.can_submit', function(button, form) {
            const conversationId = getConversationId();
            if (!conversationId) return true;

            // Проверяем на сервере обязательные группы (синхронно через XHR)
            let xhr = new XMLHttpRequest();
            xhr.open('POST', '/grouped-tags/check', false); // синхронный запрос
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]').content);
            xhr.send(JSON.stringify({ conversation_id: conversationId }));

            if (xhr.status !== 200) return true;

            const resp = JSON.parse(xhr.responseText);

            if (resp.missing_groups?.length) {
                tagManager.openTagModal(resp.missing_groups, conversationId, button);
                return false; // блокируем сабмит до заполнения
            }

            return true; // можно сабмитить
        });
    }
});

// --- Вспомогательная функция ---
function getConversationId() {
    return getGlobalAttr('conversation_id')
        || document.querySelector('input[name="conversation_id"]')?.value
        || document.querySelector('#form-create input[name="id"]')?.value
        || null;
}

// --- Слушатели модала ---
document.getElementById('submit-btn').addEventListener('click', tagManager.handleTagAttachment);
document.querySelector('.tag-modal__close-btn').addEventListener('click', tagManager.closeTagModal);

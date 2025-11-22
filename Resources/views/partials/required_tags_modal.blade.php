<div id="tag-modal-overlay" class="tag-modal-overlay">
    <div id="tag-modal" class="tag-modal">
        <div class="tag-modal__content">

            <header class="tag-modal__header">
                <h2 class="tag-modal__title">Требуется Выбор Обязательных Тегов</h2>
                <button id="tag-modal-close" class="tag-modal__close-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" class="tag-modal__close-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </header>

            <div class="tag-modal__body">
                <p class="tag-modal__warning">
                    ❗️ Контроллер требует прикрепить теги для следующих обязательных групп:
                </p>

                <div id="tag-groups-container" class="tag-modal__groups"></div>
            </div>

            <footer class="tag-modal__footer">
                <div id="status-message" class="tag-modal__status hidden"></div>

                <button id="submit-btn" class="tag-modal__submit-btn">
                    Прикрепить Теги и Продолжить
                </button>
            </footer>

        </div>
    </div>
</div>

{extends file="layout.tpl"}

{block name="title"}403 — Доступ ограничен{/block}

{block name="content"}
    <div class="error-container">
        <!-- Цвет #dc3545 (красный) для обозначения запрета доступа -->
        <p class="error-code" style="color: #dc3545;">403</p>
        <h2 style="margin-top: 10px; color: #222;">Доступ ограничен</h2>

        <p style="color: #666; max-width: 500px; margin: 15px auto 30px auto;">
            {$message|default:"У вас нет прав для просмотра этой страницы. Возможно, вам необходимо авторизоваться под учетной записью администратора."}
        </p>

        <div style="display: flex; gap: 10px; justify-content: center;">
            <a href="/" class="view-all-link">На главную</a>
            <button onclick="history.back()" class="view-all-link" style="background: #6c757d; border: none; cursor: pointer;">Назад</button>
        </div>
    </div>
{/block}

{extends file="layout.tpl"}

{block name="title"}401 — Необходима авторизация{/block}

{block name="content"}
    <div class="error-container">
        <!-- Цвет #ffc107 (предупреждающий оранжевый) для обозначения необходимости входа -->
        <p class="error-code" style="color: #ffc107;">401</p>
        <h2 style="margin-top: 10px; color: #222;">Необходима авторизация</h2>

        <p style="color: #666; max-width: 500px; margin: 15px auto 30px auto;">
            {$message|default:"Для просмотра этой страницы необходимо войти в систему. Пожалуйста, авторизуйтесь под своей учетной записью."}
        </p>

        <div style="display: flex; gap: 10px; justify-content: center;">
            <a href="/login" class="view-all-link" style="background: #ffc107; color: #000;">Войти в аккаунт</a>
            <a href="/" class="view-all-link">На главную</a>
            <button onclick="history.back()" class="view-all-link" style="background: #6c757d; border: none; cursor: pointer;">Назад</button>
        </div>
    </div>
{/block}

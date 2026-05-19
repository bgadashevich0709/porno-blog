{extends file="layout.tpl"}

{block name="title"}404 — Страница не найдена{/block}

{block name="content"}
    <div class="error-container">
        <p class="error-code">404</p>
        <h2 style="margin-top: 10px; color: #222;">Упс! Страница не найдена</h2>

        <p style="color: #666; max-width: 500px; margin: 15px auto 30px auto;">
            {$message|default:"Возможно, адрес был введен неверно или страница была удалена авторами проекта."}
        </p>

        <div style="display: flex; gap: 10px; justify-content: center;">
            <a href="/" class="view-all-link">На главную</a>
            <button onclick="history.back()" class="view-all-link" style="background: #6c757d; border: none; cursor: pointer;">Назад</button>
        </div>
    </div>
{/block}

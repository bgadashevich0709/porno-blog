{extends file="layout.tpl"}

{block name="title"}{$title}{/block}

{block name="content"}
    <section class="category-block" style="max-width: 800px; margin: 0 auto;">
        <div class="category-header">
            <div>
                <h2 class="category-title" style="color: #28a745;">🔒 Доступ разрешен</h2>
                <p class="category-desc">Добро пожаловать в закрытый клуб, бро! Наша мидлваря успешно проверила твой токен.</p>
            </div>
            <a href="/" class="view-all-link">&larr; На главную</a>
        </div>

        <div class="posts-grid" style="grid-template-columns: 1fr; margin-top: 20px;">
            <article class="post-card" style="padding: 25px; background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                <h3 style="margin-bottom: 15px; color: #222;">Информация о твоей сессии:</h3>

                {if isset($user)}
                    <ul style="list-style: none; padding: 0; margin: 0; line-height: 1.8; color: #444;">
                        <li><strong>ID пользователя:</strong> {$user.user_id}</li>
                        <li><strong>Электронная почта:</strong> {$user.email}</li>
                        <li><strong>Твои роли в системе:</strong>
                            {foreach $user.roles as $role}
                                <span class="tag" style="background: #e9ecef; padding: 2px 8px; border-radius: 4px; font-size: 0.85em; margin-right: 5px;">{$role}</span>
                            {/foreach}
                        </li>
                    </ul>
                {else}
                    <p style="color: #666;">Данные токена пусты или не были переданы в шаблон.</p>
                {/if}
            </article>
        </div>
    </section>
{/block}

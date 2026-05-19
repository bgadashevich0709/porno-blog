{extends file="layout.tpl"}

{block name="title"}{$title}{/block}

{block name="content"}
    <style>
        .full-post { background: #fff; border: 1px solid #e1e1e1; border-radius: 8px; padding: 30px; margin-bottom: 40px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .full-post-image { width: 100%; max-height: 500px; object-fit: cover; border-radius: 6px; margin-bottom: 25px; }
        .full-post-meta { display: flex; gap: 20px; color: #777; font-size: 0.9rem; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 15px; }
        .full-post-desc { font-size: 1.2rem; color: #555; line-height: 1.6; font-style: italic; margin-bottom: 25px; padding-left: 15px; border-left: 4px solid #007bff; }
        .full-post-body { font-size: 1.1rem; line-height: 1.8; color: #222; }
        .similar-heading { font-size: 1.8rem; margin: 40px 0 20px 0; padding-bottom: 10px; border-bottom: 2px solid #333; }
    </style>

    <article class="full-post">
        {* Подключаем хлебные крошки над статьей поста *}
        {include file="partials/breadcrumbs.tpl" breadcrumbs=$data->breadcrumbs}
        {* Исправлено: обращение к image идет через $data->post *}
        {if !empty($data->post->image)}
            <img src="{$data->post->image}" alt="{$data->post->title}" class="full-post-image">
        {/if}

        <div class="full-post-meta">
            {* Исправлено: обращение идет через $data->post *}
            <span>👁 {$data->post->viewsCount}</span>
            <span>📅 {$data->post->createdAt->format('d.m.Y')}</span>
        </div>

        {* Исправлено: обращение идет через $data->post *}
        {if !empty($data->post->description)}
            <div class="full-post-desc">
                {$data->post->description}
            </div>
        {/if}

        <div class="full-post-body">
            {* Исправлено: обращение идет через $data->post *}
            {$data->post->content|nl2br}
        </div>
    </article>

    {* Исправлено: обращение к похожим материалам идет через $data->similarPosts *}
    {if !empty($data->similarPosts)}
        <section class="similar-section">
            <h2 class="similar-heading">Похожие материалы</h2>

            <div class="posts-grid">
                {foreach $data->similarPosts as $similarPost}
                    {include file="partials/post_article.tpl" post=$similarPost}
                {/foreach}
            </div>
        </section>
    {/if}
{/block}

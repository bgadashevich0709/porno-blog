{extends file="layout.tpl"}

{block name="title"}{$title}{/block}

{block name="content"}
    <div class="category-block">
        {* Подключаем хлебные крошки над заголовком категории *}
        {include file="partials/breadcrumbs.tpl" breadcrumbs=$data->breadcrumbs}

        <div class="category-header">
            <div>
                <h1 class="category-title">{$data->category->title}</h1>
                {if !empty($data->category->description)}
                    <p class="category-desc">{$data->category->description}</p>
                {/if}
            </div>
            <div class="view-all-link" style="background: #28a745;">
                Всего публикаций: {$data->postsData->totalItems}
            </div>
        </div>

        {include file="partials/sort_panel.tpl"}

        {if empty($data->postsData->items)}
            <div class="empty-state">
                <p>В этой категории пока нет доступных публикаций.</p>
            </div>
        {else}
            <div class="posts-grid">
                {foreach $data->postsData->items as $post}
                    {include file="partials/post_article.tpl" post=$post}
                {/foreach}
            </div>

            {include file="partials/pagination.tpl"
            pager=$data->pager
            postsData=$data->postsData
            limitControl=$data->limitControl
            }
        {/if}
    </div>
{/block}

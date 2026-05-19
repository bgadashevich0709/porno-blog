<article class="post-card">
    {if !empty($post->image)}
        <img src="{$post->image}" alt="{$post->title}" class="post-image" loading="lazy">
    {/if}

    <div class="post-content">
        <h3 class="post-title">
            <a href="{$post->link}">{$post->title}</a>
        </h3>
        <p class="post-desc">{$post->description}</p>

        <div class="post-footer">
            <span class="post-views">👁 {$post->viewsCount}</span>
            <span class="post-date">📅 {$post->createdAt->format('d.m.Y')}</span>
        </div>
    </div>
</article>

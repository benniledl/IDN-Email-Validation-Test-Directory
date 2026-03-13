<?php
/** @var array<string, mixed> $comment */
/** @var string $deletePath */
/** @var bool $adminMode */
/** @var string $adminCsrfToken */

$commentAuthor = trim((string)($comment['author_name'] ?? 'Anonymous'));
$commentInitial = function_exists('mb_substr') ? (string)mb_substr($commentAuthor, 0, 1, 'UTF-8') : substr($commentAuthor, 0, 1);
$commentInitial = function_exists('mb_strtoupper') ? (string)mb_strtoupper($commentInitial, 'UTF-8') : strtoupper($commentInitial);
if ($commentInitial === '') {
    $commentInitial = 'A';
}
?>
<article class="rounded-box border border-base-300 bg-base-100 p-3">
    <div class="flex items-start gap-3">
        <div class="comment-avatar flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-base-300 text-base-content">
            <span class="text-xs font-semibold uppercase leading-none"><?= htmlspecialchars($commentInitial, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <div class="min-w-0 flex-1">
            <div class="mb-1 flex flex-wrap items-center gap-2 text-sm">
                <span class="font-semibold text-base-content"><?= htmlspecialchars($commentAuthor, ENT_QUOTES, 'UTF-8') ?></span>
                <time class="text-base-content/60" title="<?= htmlspecialchars((string)$comment['created_at'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(View::timeAgo((string)$comment['created_at']), ENT_QUOTES, 'UTF-8') ?></time>
                <?php if ((int)($comment['is_admin_solution'] ?? 0) === 1): ?><span class="badge badge-success badge-sm">Official</span><?php endif; ?>
            </div>
            <p class="whitespace-pre-wrap text-sm text-base-content/85"><?= htmlspecialchars((string)$comment['comment'], ENT_QUOTES, 'UTF-8') ?></p>
            <?php if ($adminMode): ?>
                <form method="post" action="<?= htmlspecialchars($deletePath, ENT_QUOTES, 'UTF-8') ?>" data-confirm="Delete this comment?" class="mt-3">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($adminCsrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <button class="btn btn-xs btn-error btn-outline" type="submit" title="Delete comment">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2m-8 0l1 12a1 1 0 001 1h6a1 1 0 001-1l1-12"/></svg>
                        Delete
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</article>

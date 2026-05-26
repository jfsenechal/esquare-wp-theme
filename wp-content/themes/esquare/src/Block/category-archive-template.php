<?php

/**
 * @var \WP_Term $term  Provided by Esquare\Theme\Block\CategoryArchive::render().
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

use Esquare\Theme\Block\CategoryArchive;

$parent_term = $term->parent ? get_term($term->parent, $term->taxonomy) : null;
$parent_label = $parent_term instanceof WP_Term ? $parent_term->name : __('Actualités', 'esquare');

$description_raw = trim((string) $term->description);
$has_description = $description_raw !== '';

$children = get_terms([
    'taxonomy'   => $term->taxonomy,
    'parent'     => $term->term_id,
    'hide_empty' => false,
    'orderby'    => 'name',
    'order'      => 'ASC',
]);
$children = is_wp_error($children) ? [] : $children;

$descendant_ids = get_term_children($term->term_id, $term->taxonomy);
$descendant_ids = is_wp_error($descendant_ids) ? [] : $descendant_ids;
$branch_ids = array_values(array_unique(array_merge([$term->term_id], $descendant_ids)));

$child_id_set = [];
$child_counts = [];
$slug_to_id = ['__all__' => 0];
foreach ($children as $child) {
    $child_id_set[$child->term_id] = true;
    $child_counts[$child->term_id] = 0;
    $slug_to_id[$child->slug] = $child->term_id;
}

$filter_slug = isset($_GET['filter']) ? sanitize_title(wp_unslash((string) $_GET['filter'])) : '';
$initial_term_id = $slug_to_id[$filter_slug] ?? 0;
$initial_filter_active = $initial_term_id !== 0;

$posts_query = new WP_Query([
    'post_type'           => 'post',
    'category__in'        => $branch_ids,
    'posts_per_page'      => CategoryArchive::POSTS_PER_PAGE,
    'orderby'             => 'date',
    'order'               => 'DESC',
    'ignore_sticky_posts' => true,
    'no_found_rows'       => true,
]);

if (function_exists('wp_interactivity_state')) {
    wp_interactivity_state(CategoryArchive::STORE_NAMESPACE, [
        'activeTermId' => $initial_term_id,
        'slugToId'     => $slug_to_id,
    ]);
}

$context_for = static fn (array $payload): string => esc_attr(wp_json_encode($payload));
$is_chip_active = static fn (int $term_id): bool => $term_id === $initial_term_id;

$post_cards = [];
$total_displayed = 0;

while ($posts_query->have_posts()) {
    $posts_query->the_post();
    $post_id = (int) get_the_ID();
    $post_terms = wp_get_post_terms($post_id, $term->taxonomy, ['fields' => 'ids']);
    if (is_wp_error($post_terms)) {
        $post_terms = [];
    }
    $post_terms = array_map('intval', $post_terms);
    $relevant_terms = array_values(array_intersect($post_terms, $branch_ids));

    foreach ($post_terms as $tid) {
        if (isset($child_id_set[$tid])) {
            $child_counts[$tid] = ($child_counts[$tid] ?? 0) + 1;
        }
    }

    $primary_sub = null;
    foreach ($post_terms as $tid) {
        if (isset($child_id_set[$tid])) {
            $candidate = get_term($tid, $term->taxonomy);
            if ($candidate instanceof WP_Term) {
                $primary_sub = $candidate;
                break;
            }
        }
    }

    $hidden = $initial_filter_active && ! in_array($initial_term_id, $relevant_terms, true);

    $post_cards[] = [
        'id'             => $post_id,
        'permalink'      => get_the_permalink($post_id),
        'title'          => get_the_title($post_id),
        'has_excerpt'    => has_excerpt($post_id),
        'excerpt'        => get_the_excerpt($post_id),
        'date_iso'       => get_the_date('c', $post_id),
        'date_human'     => get_the_date('j F Y', $post_id),
        'has_thumbnail'  => has_post_thumbnail($post_id),
        'thumbnail_html' => has_post_thumbnail($post_id)
            ? get_the_post_thumbnail($post_id, 'medium_large', ['loading' => 'lazy', 'class' => 'esq-card__image', 'alt' => '']) //phpcs:ignore
            : '',
        'primary_sub'    => $primary_sub,
        'relevant_terms' => $relevant_terms,
        'hidden'         => $hidden,
    ];

    if (! $hidden) {
        $total_displayed++;
    }
}
wp_reset_postdata();

$all_total = count($post_cards);
$initial_all_hidden = $initial_filter_active && $all_total > 0 && $total_displayed === 0;
?>

<main id="contenu" class="esq-archive">

    <header class="esq-archive-header reveal">
        <p class="article-eyebrow">
            <?php echo esc_html(strtoupper($parent_label)); ?>
            <span aria-hidden="true">·</span>
            <?php esc_html_e('Rubrique', 'esquare'); ?>
        </p>

        <h1 class="article-title esq-archive-title"><?php echo esc_html($term->name); ?></h1>

        <?php if ($has_description): ?>
            <div class="article-standfirst esq-archive-description">
                <?php echo wp_kses_post(wpautop($description_raw)); ?>
            </div>
        <?php endif; ?>
    </header>

    <?php if (! empty($children)): ?>
        <section
            class="esq-filter-section reveal reveal-2"
            data-wp-interactive="<?php echo esc_attr(CategoryArchive::STORE_NAMESPACE); ?>"
            data-wp-init="callbacks.syncFromUrl"
            aria-label="<?php echo esc_attr__('Filtrer par sous-rubrique', 'esquare'); ?>"
        >
            <p class="esq-filter-meta"><?php esc_html_e('Filtrer', 'esquare'); ?></p>
            <div class="esq-filter-rail" role="toolbar" aria-label="<?php echo esc_attr__('Sous-rubriques', 'esquare'); ?>">
                <a
                    href="<?php echo esc_url(get_term_link($term)); ?>"
                    class="article-tag<?php echo $is_chip_active(0) ? ' is-active' : ''; ?>"
                    data-wp-class--is-active="state.isAllActive"
                    data-wp-on--click="actions.setFilter"
                    data-wp-context="<?php echo $context_for(['termId' => 0, 'termSlug' => '__all__']); ?>"
                    aria-current="<?php echo $is_chip_active(0) ? 'true' : 'false'; ?>"
                >
                    <span><?php esc_html_e('Tout', 'esquare'); ?></span>
                    <span class="article-tag__count" aria-hidden="true"><?php echo esc_html((string) $all_total); ?></span>
                    <span class="sr-only">
                        <?php
                        /* translators: %d: number of articles. */
                        printf(esc_html(_n('%d article', '%d articles', $all_total, 'esquare')), (int) $all_total);
                        ?>
                    </span>
                </a>
                <?php foreach ($children as $child):
                    $count = $child_counts[$child->term_id] ?? 0;
                    $active = $is_chip_active($child->term_id);
                    ?>
                    <a
                        href="<?php echo esc_url(get_term_link($child)); ?>"
                        class="article-tag<?php echo $active ? ' is-active' : ''; ?>"
                        data-wp-class--is-active="state.isChipActive"
                        data-wp-on--click="actions.setFilter"
                        data-wp-context="<?php echo $context_for(['termId' => (int) $child->term_id, 'termSlug' => $child->slug]); ?>"
                        aria-current="<?php echo $active ? 'true' : 'false'; ?>"
                    >
                        <span><?php echo esc_html($child->name); ?></span>
                        <span class="article-tag__count" aria-hidden="true"><?php echo esc_html((string) $count); ?></span>
                        <span class="sr-only">
                            <?php
                            /* translators: %d: number of articles in this sub-category. */
                            printf(esc_html(_n('%d article', '%d articles', $count, 'esquare')), (int) $count);
                            ?>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <section
        class="esq-archive-section reveal reveal-3"
        data-wp-interactive="<?php echo esc_attr(CategoryArchive::STORE_NAMESPACE); ?>"
    >
        <?php if (! empty($post_cards)): ?>
            <div class="esq-archive-grid">
                <?php foreach ($post_cards as $card):
                    $sub_name = $card['primary_sub'] instanceof WP_Term ? $card['primary_sub']->name : '';
                    ?>
                    <article
                        class="esq-card<?php echo $card['hidden'] ? ' esq-hidden' : ''; ?>"
                        data-wp-class--esq-hidden="state.isCardHidden"
                        data-wp-context="<?php echo $context_for(['terms' => $card['relevant_terms']]); ?>"
                    >
                        <a href="<?php echo esc_url($card['permalink']); ?>" class="esq-card__media-link" tabindex="-1" aria-hidden="true">
                            <?php if ($card['has_thumbnail']): ?>
                                <span class="esq-card__media">
                                    <?php echo $card['thumbnail_html']; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-generated. ?>
                                </span>
                            <?php else: ?>
                                <span class="esq-card__media esq-card__placeholder">
                                    <?php if ($sub_name !== ''): ?>
                                        <span class="esq-card__placeholder-stamp"><?php echo esc_html($sub_name); ?></span>
                                    <?php endif; ?>
                                </span>
                            <?php endif; ?>
                        </a>

                        <p class="esq-card__meta">
                            <time datetime="<?php echo esc_attr($card['date_iso']); ?>"><?php echo esc_html($card['date_human']); ?></time>
                            <?php if ($sub_name !== ''): ?>
                                <span class="esq-card__meta-sep" aria-hidden="true">·</span>
                                <span class="esq-card__meta-kind"><?php echo esc_html($sub_name); ?></span>
                            <?php endif; ?>
                        </p>

                        <h3 class="esq-card__title">
                            <a href="<?php echo esc_url($card['permalink']); ?>"><?php echo esc_html($card['title']); ?></a>
                        </h3>

                        <?php if ($card['has_excerpt']): ?>
                            <p class="esq-card__excerpt"><?php echo esc_html($card['excerpt']); ?></p>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
            <p
                class="esq-archive-empty<?php echo $initial_all_hidden ? ' is-visible' : ''; ?>"
                data-wp-class--is-visible="state.isAllHidden"
                role="status"
                aria-live="polite"
            >
                <?php esc_html_e('Aucun article dans cette sélection. Choisissez une autre sous-rubrique.', 'esquare'); ?>
            </p>
        <?php else: ?>
            <p class="esq-archive-empty is-visible">
                <?php esc_html_e('Aucun article dans cette rubrique pour le moment.', 'esquare'); ?>
            </p>
        <?php endif; ?>
    </section>

</main>

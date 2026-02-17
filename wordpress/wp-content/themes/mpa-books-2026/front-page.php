<?php
get_header();
$genres = get_terms([
    'taxonomy' => 'mpa_genre',
    'hide_empty' => true,
]);
?>
<main class="mpa-wrap mpa-layout">
  <?php if (function_exists('mpa_render_sidebar_panel')) {
      mpa_render_sidebar_panel();
  } ?>

  <section class="mpa-content">
    <header class="mpa-hero">
      <h1><?php esc_html_e('Browse by Genre', 'mpa-books-2026'); ?></h1>
      <p><?php esc_html_e('Pick a genre to explore titles in your selected language.', 'mpa-books-2026'); ?></p>
    </header>

    <?php if (!empty($genres) && !is_wp_error($genres)) : ?>
      <ul class="mpa-genre-list" role="list">
        <?php foreach ($genres as $genre) : ?>
          <li class="mpa-genre-list__item">
            <a href="<?php echo esc_url(get_term_link($genre)); ?>">
              <span class="mpa-genre-list__name"><?php echo esc_html($genre->name); ?></span>
              <span class="mpa-genre-list__count"><?php echo esc_html(sprintf(_n('%d book', '%d books', (int) $genre->count, 'mpa-books-2026'), (int) $genre->count)); ?></span>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php else : ?>
      <p><?php esc_html_e('No genres found yet.', 'mpa-books-2026'); ?></p>
    <?php endif; ?>
  </section>
</main>
<?php
get_footer();

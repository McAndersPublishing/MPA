<?php
get_header();
$term = get_queried_object();
?>
<main class="mpa-wrap mpa-layout">
  <?php if (function_exists('mpa_render_sidebar_panel')) {
      mpa_render_sidebar_panel();
  } ?>

  <section class="mpa-content">
    <header class="mpa-hero">
      <h1><?php echo esc_html($term->name ?? __('Genre', 'mpa-books-2026')); ?></h1>
      <?php if (!empty($term->description)) : ?>
        <p><?php echo esc_html($term->description); ?></p>
      <?php endif; ?>
    </header>

    <div class="mpa-series-grid">
      <?php if (have_posts()) : ?>
        <?php while (have_posts()) : the_post(); ?>
          <?php get_template_part('template-parts/book', 'card'); ?>
        <?php endwhile; ?>
      <?php else : ?>
        <p><?php esc_html_e('No books found in this genre.', 'mpa-books-2026'); ?></p>
      <?php endif; ?>
    </div>
  </section>
</main>
<?php
get_footer();

<?php
get_header();
?>
<main class="mpa-wrap mpa-layout">
  <?php if (function_exists('mpa_render_sidebar_panel')) {
      mpa_render_sidebar_panel();
  } ?>

  <section class="mpa-content">
    <header>
      <h1><?php post_type_archive_title(); ?></h1>
    </header>

    <div class="mpa-series-grid">
      <?php if (have_posts()) : ?>
        <?php while (have_posts()) : the_post(); ?>
          <?php get_template_part('template-parts/book', 'card'); ?>
        <?php endwhile; ?>
      <?php else : ?>
        <p><?php esc_html_e('No books found.', 'mpa-books-2026'); ?></p>
      <?php endif; ?>
    </div>
  </section>
</main>
<?php
get_footer();

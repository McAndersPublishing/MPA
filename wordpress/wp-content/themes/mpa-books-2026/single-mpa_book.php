<?php
get_header();
?>
<main class="mpa-wrap mpa-layout">
  <?php if (function_exists('mpa_render_sidebar_panel')) {
      mpa_render_sidebar_panel();
  } ?>

  <section class="mpa-content">
    <?php while (have_posts()) : the_post(); ?>
      <?php get_template_part('template-parts/book', 'card'); ?>
      <article id="preview">
        <h2><?php esc_html_e('Preview', 'mpa-books-2026'); ?></h2>
        <?php the_content(); ?>
      </article>
    <?php endwhile; ?>
  </section>
</main>
<?php
get_footer();

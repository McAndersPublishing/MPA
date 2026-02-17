<?php
$book_id = get_the_ID();
$cover_url = (string) get_post_meta($book_id, 'book_cover_image_url', true);
$book_language = sanitize_key((string) get_post_meta($book_id, 'book_language', true));
$formats = function_exists('mpa_get_book_formats') ? mpa_get_book_formats($book_id) : [];
$buy_url = function_exists('mpa_get_buy_url') ? mpa_get_buy_url($book_id) : '';
$language_label = function_exists('mpa_get_language_label') && $book_language !== ''
    ? mpa_get_language_label($book_language)
    : '';
?>
<article class="mpa-book-card">
  <?php if ($cover_url !== '') : ?>
    <img class="mpa-book-card__image" src="<?php echo esc_url($cover_url); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" loading="lazy" />
  <?php endif; ?>

  <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>

  <?php if ($language_label !== '') : ?>
    <p class="mpa-book-card__formats">
      <?php echo esc_html__('Language: ', 'mpa-books-2026') . esc_html($language_label); ?>
    </p>
  <?php endif; ?>

  <p><?php echo esc_html(get_the_excerpt()); ?></p>

  <?php if (!empty($formats)) : ?>
    <p class="mpa-book-card__formats">
      <?php echo esc_html__('Available formats: ', 'mpa-books-2026') . esc_html(implode(', ', $formats)); ?>
    </p>
  <?php endif; ?>

  <div class="mpa-actions">
    <a class="mpa-btn mpa-btn--preview" href="<?php the_permalink(); ?>#preview"><?php esc_html_e('Preview', 'mpa-books-2026'); ?></a>
    <?php if ($buy_url !== '') : ?>
      <a class="mpa-btn mpa-btn--buy" href="<?php echo esc_url($buy_url); ?>"><?php esc_html_e('Buy', 'mpa-books-2026'); ?></a>
    <?php endif; ?>
  </div>
</article>

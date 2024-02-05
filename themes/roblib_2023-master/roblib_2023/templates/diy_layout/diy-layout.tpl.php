<?php
/**
 * @file
 * Template for a 1 column panel layout.
 *
 * This template provides a very simple "one column" panel display layout.
 *
 * Variables:
 * - $css_id: An optional CSS ID.
 * - $id_attribute: The whole id="$css_id" string.
 * - $content: An array of content, each item in the array is keyed to one
 *   panel of the layout. This layout supports the following sections:
 *   $content['middle']: The only panel in the layout.
 */
?>
<section class="grid-x <?php print $panel_classes; ?>"<?php print $panel_id; ?>>
  <div class="cell medium-4 diy_side">
	  <?php print $content['side']; ?>
  </div>
<div class="cell medium-8 diy_content">
	<?php print $content['content']; ?>
</div>
</section>

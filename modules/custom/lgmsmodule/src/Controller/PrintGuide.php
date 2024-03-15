<?php

namespace Drupal\lgmsmodule\Controller;

use DOMDocument;
use Dompdf\Dompdf;
use Dompdf\Options;
use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\Response;

class PrintGuide extends ControllerBase {

  public function downloadGuide(NodeInterface $node) {
    // Check if the node is of type 'guide'
    if ($node->getType() === 'guide') {
      // Initialize HTML content with the guide title
      $html = '<h1>' . $node->getTitle() . '</h1><hr>';

      // Prepare base URL for absolute path conversion
      $baseUrl = \Drupal::request()->getSchemeAndHttpHost();

      // Add the guide's description if it exists
      if ($node->hasField('field_description') && !$node->get('field_description')->isEmpty()) {
        $descriptionValue = $node->get('field_description')->value;
        $html .= '<div>' . $descriptionValue . '</div>'; // Assuming the description contains safe HTML
      } else {
        $html .= "<div>No description content available.</div>";
      }



      // Log the start of processing the guide
      \Drupal::logger('lgmsmodule')->notice('Starting to process guide with ID: @id', ['@id' => $node->id()]);

      // Check if the guide has child boxes and add them to the HTML
      if ($node->hasField('field_child_boxes') && !$node->get('field_child_boxes')->isEmpty()) {
        $guideBoxes = $node->get('field_child_boxes')->referencedEntities();

        // Log the count of guide boxes found
        \Drupal::logger('lgmsmodule')->notice('Found @count guide boxes for guide ID: @id', ['@count' => count($guideBoxes), '@id' => $node->id()]);

        foreach ($guideBoxes as $box) {
          $html .= '<h2>' . $box->getTitle() . '</h2>'; // Guide box title
          \Drupal::logger('lgmsmodule')->notice('Processing guide box with ID: @id, Title: @title', ['@id' => $box->id(), '@title' => $box->getTitle()]);

          // Check and add items list for each guide box
          if ($box->hasField('field_box_items') && !$box->get('field_box_items')->isEmpty()) {
            $itemEntities = $box->get('field_box_items')->referencedEntities();

            // Log the count of item entities found for this box
            \Drupal::logger('lgmsmodule')->notice('Found @count item entities for guide box ID: @id', ['@count' => count($itemEntities), '@id' => $box->id()]);

            $html .= '<ul>';
            foreach ($itemEntities as $itemEntity) {
              // Log each guide box item being processed
              \Drupal::logger('lgmsmodule')->notice('Processing guide box item with ID: @id', ['@id' => $itemEntity->id()]);

              // Check for HTML Box Items
              if ($itemEntity->hasField('field_html_item') && !$itemEntity->get('field_html_item')->isEmpty()) {
                $htmlBoxItems = $itemEntity->get('field_html_item')->referencedEntities();

                // Log how many HTML box items were found
                \Drupal::logger('lgmsmodule')->notice('Found @count HTML box items for guide box item ID: @id', ['@count' => count($htmlBoxItems), '@id' => $itemEntity->id()]);

                foreach ($htmlBoxItems as $htmlBoxItem) {
                  if ($htmlBoxItem->hasField('field_text_box_item2') && !$htmlBoxItem->get('field_text_box_item2')->isEmpty()) {
                    $rawHtmlContent = $htmlBoxItem->get('field_text_box_item2')->value;
                    $processedHtmlContent = $this->prepareHtmlContent($rawHtmlContent, $baseUrl);

                    // Initialize DOMDocument and load processed HTML content
                    $doc = new DOMDocument();
                    @$doc->loadHTML(mb_convert_encoding($processedHtmlContent, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

                    $tags = $doc->getElementsByTagName('img');

                    foreach ($tags as $tag) {
                      $src = $tag->getAttribute('src');
                      $alt = $tag->getAttribute('alt') ?: 'No alternative text';

                      // Create an anchor element wrapping around the alt text
                      $a = $doc->createElement('a', $alt);
                      $a->setAttribute('href', $src);

                      // Replace the img tag with the anchor tag in the DOM
                      $tag->parentNode->replaceChild($a, $tag);
                    }

                    // Save the modified HTML from DOMDocument back to a string
                    $htmlContent = $doc->saveHTML();

                    // Append the modified content, which includes the rest of the HTML elements
                    $html .= '<li>' . $htmlContent . '</li>';
                  }
                }

              } else {
                // Log when no HTML box items are found for a guide box item
                \Drupal::logger('lgmsmodule')->notice('No HTML box items found for guide box item ID: @id', ['@id' => $itemEntity->id()]);
              }

              // Check for Database Link Items
              if ($itemEntity->hasField('field_database_item') && !$itemEntity->get('field_database_item')->isEmpty()) {
                $databaseLinkItems = $itemEntity->get('field_database_item')->referencedEntities();

                // Log how many database link items were found
                \Drupal::logger('lgmsmodule')->notice('Found @count database link items for guide box item ID: @id', ['@count' => count($databaseLinkItems), '@id' => $itemEntity->id()]);

                foreach ($databaseLinkItems as $databaseLinkItem) {
                  if ($databaseLinkItem->hasField('field_database_link') && !$databaseLinkItem->get('field_database_link')->isEmpty()) {
                    $linkValue = $databaseLinkItem->get('field_database_link')->first()->getUrl()->toString();
                    $html .= '<li><a href="' . $linkValue . '">' . $linkValue . '</a></li>';

                    // Log the successful processing of a database link item
                    \Drupal::logger('lgmsmodule')->notice('Processed database link item with link value: @value for database link item ID: @id', ['@value' => $linkValue, '@id' => $databaseLinkItem->id()]);
                  }
                }
              } else {
                // Log when no database link items are found for a guide box item
                \Drupal::logger('lgmsmodule')->notice('No database link items found for guide box item ID: @id', ['@id' => $itemEntity->id()]);
              }
            }
            $html .= '</ul>';
          } else {
            $html .= "<div>No items available.</div>";
            // Log that no item entities were found for this box
            \Drupal::logger('lgmsmodule')->notice('No item entities found for guide box ID: @id', ['@id' => $box->id()]);
          }
        }
      } else {
        $html .= "<div>No child boxes content available.</div>";
        // Log that no guide boxes were found for the guide
        \Drupal::logger('lgmsmodule')->notice('No guide boxes found for guide ID: @id', ['@id' => $node->id()]);
      }

      $options = new Options();
      $options->setIsRemoteEnabled(true);
      // Load dompdf and setup
      $dompdf = new Dompdf($options);

      $dompdf->loadHtml($html);
      // Log final HTML content before rendering to PDF
      \Drupal::logger('lgmsmodule')->debug('Final HTML content before PDF generation: @html', ['@html' => $html]);

      $dompdf->setPaper('A4', 'landscape');
      $dompdf->render();
      $dompdf->stream($node->getTitle() . ".pdf", ["Attachment" => true]);

      // Prevent further Drupal processing by returning a response
      return new Response('', 200);
    }

    // Access denied for non-guide nodes
    return $this->createAccessDeniedResponse();
  }

  /**
   * Prepares HTML content for PDF rendering.
   * Converts relative paths to absolute paths for resources like images.
   *
   * @param string $htmlContent The original HTML content.
   * @param string $baseUrl The base URL of the site for absolute path conversion.
   * @return string The processed HTML content with converted paths.
   */
  protected function prepareHtmlContent($htmlContent, $baseUrl) {
    // Log the original HTML content for debugging
    \Drupal::logger('lgmsmodule')->debug('Original HTML content: @html', ['@html' => $htmlContent]);

    // Corrected regex pattern to match src attributes more reliably and capture the path
    $pattern = '/src="\/([^"]+)"/';

    // Convert relative src attributes to absolute
    $processedHtml = preg_replace_callback($pattern, function ($matches) use ($baseUrl) {
      // Log each match found
      \Drupal::logger('lgmsmodule')->debug('Match found: @match', ['@match' => print_r($matches, TRUE)]);

      // Construct and return the replacement string with an absolute URL
      if (isset($matches[1])) {
        $replacement = 'src="' . $baseUrl . '/' . $matches[1] . '"';
        \Drupal::logger('lgmsmodule')->debug('Replacement: @replacement', ['@replacement' => $replacement]);
        return $replacement;
      }
      // If the capture group is not set, return the original match without modification
      return $matches[0];
    }, $htmlContent);

    // Log the processed HTML content to verify the outcome
    \Drupal::logger('lgmsmodule')->debug('Processed HTML content: @html', ['@html' => $processedHtml]);

    return $processedHtml;
  }



}

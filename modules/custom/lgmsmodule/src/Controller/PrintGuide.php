<?php

namespace Drupal\lgmsmodule\Controller;

use DOMDocument;
use DOMException;
use Dompdf\Dompdf;
use Dompdf\Options;
use Drupal;
use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Controller for generating and downloading PDF versions of guide nodes.
 *
 * This controller supports transforming guide node content and its related
 * entities into a formatted PDF document. It leverages Dompdf for PDF generation
 * and includes functionality for processing various field types and media.
 */
class PrintGuide extends ControllerBase {

  /**
   * Generates a PDF for a given node of type 'guide'.
   *
   * @param NodeInterface $node The guide node entity.
   *
   * @return RedirectResponse|Response Redirects to the node page if the node
   *                                  is not of type 'guide', or initiates a PDF
   *                                  download response.
   * @throws DOMException Thrown if there's an error in processing HTML content.
   * @throws EntityMalformedException Thrown if there's an issue with entity data.
   */
  public function downloadGuide(NodeInterface $node): RedirectResponse|Response
  {
    if ($node->getType() !== 'guide') {
      // Access denied for non-guide nodes
      $this->messenger()->addError('There was an error printing your guide.');
      $url = $node->toUrl()->toString();
      return new RedirectResponse($url);
    } else {

      $html = '<h1>' . htmlspecialchars($node->getTitle()) . '</h1><hr>';
      $baseUrl = Drupal::request()->getSchemeAndHttpHost();

      $descriptionField = $node->hasField('field_description') && !$node->get('field_description')->isEmpty() ? $node->get('field_description')->value : "No description content available.";
      $html .= '<div>' . $this->prepareHtmlContent($descriptionField, $baseUrl) . '</div>';

      $guideBoxes = $node->hasField('field_child_boxes') ? $node->get('field_child_boxes')->referencedEntities() : [];

      foreach ($guideBoxes as $box) {
        $html .= $this->processBox($box, $baseUrl);
      }

      $guidePages = $node->hasField('field_child_pages') ? $node->get('field_child_pages')->referencedEntities() : [];

      foreach ($guidePages as $page) {
        $html .= $this->processPage($page, $baseUrl);
      }

      $this->generatePdf($html, $node->getTitle());

      // Prevent further Drupal processing by returning a response
      return new Response('', 200);
    }
  }

  /**
   * Processes and formats box entities associated with a guide for PDF output.
   *
   * @param $box The box entity to process.
   * @param string $baseUrl The base URL of the site for absolute links.
   *
   * @return string Formatted HTML content for the box.
   * @throws DOMException If there's an error in HTML content manipulation.
   */
  protected function processBox($box, $baseUrl): string {
    $boxHtml = '<h3>' . htmlspecialchars($box->getTitle()) . '</h3>';

    $itemEntities = $box->hasField('field_box_items') && !$box->get('field_box_items')->isEmpty() ? $box->get('field_box_items')->referencedEntities() : [];

    foreach ($itemEntities as $itemEntity) {
      if ($itemEntity->hasField('field_html_item') && !$itemEntity->get('field_html_item')->isEmpty()) {
        $htmlBoxItems = $itemEntity->get('field_html_item')->referencedEntities();
        foreach ($htmlBoxItems as $htmlBoxItem) {
          if ($htmlBoxItem->hasField('field_text_box_item2') && !$htmlBoxItem->get('field_text_box_item2')->isEmpty()) {
            $rawHtmlContent = $htmlBoxItem->get('field_text_box_item2')->value;
            $boxHtml .= $this->modifyHtmlContent($rawHtmlContent, $baseUrl);
          }
        }
      } else if ($itemEntity->hasField('field_book_item') && !$itemEntity->get('field_book_item')->isEmpty()) {
        $bookItems = $itemEntity->get('field_book_item')->referencedEntities();
        foreach ($bookItems as $book) {
          $boxHtml .= $this->bookDisplayForPDF($book, true, $baseUrl);
        }
      } else if ($itemEntity->hasField('field_media_image') && !$itemEntity->get('field_media_image')->isEmpty()) {
        $mediaItems = $itemEntity->get('field_media_image')->referencedEntities();
        foreach ($mediaItems as $mediaItem) {
          $boxHtml .= $this->processMediaItem($mediaItem, $baseUrl);
        }
      }
    }

    return $boxHtml;
  }

  /**
   * Processes media entities for inclusion in the PDF, adjusting links and formats.
   *
   * @param $mediaItem The media entity to process.
   * @param string $baseUrl The base URL of the site for absolute links.
   *
   * @return string Formatted HTML content for the media item.
   */
  protected function processMediaItem($mediaItem, $baseUrl): string {
    $boxHtml = '';
    $fileUrlGenerator = Drupal::service('file_url_generator');

    switch ($mediaItem->bundle()) {
      case 'image':
        $file = $mediaItem->field_media_image->entity;
        $imageUrl = $fileUrlGenerator->generateAbsoluteString($file->getFileUri());
        $altText = $mediaItem->field_media_image->alt ?? 'Image';
        $boxHtml .= "Image: <a href=\"{$imageUrl}\">{$altText}</a><br>";
        break;
      case 'remote_video':
        $videoUrl = $mediaItem->field_media_oembed_video->value;
        $boxHtml .= "Video URL: <a href=\"{$videoUrl}\">Watch Video</a><br>";
        break;
      case 'document':
      case 'audio':
      case 'video':
        $field = $this->getFieldNameByMediaType($mediaItem->bundle());
        $file = $mediaItem->{$field}->entity;
        $fileUrl = $fileUrlGenerator->generateAbsoluteString($file->getFileUri());
        $title = $mediaItem->getName();
        $mediaType = ucfirst($mediaItem->bundle());
        $boxHtml .= "$mediaType: <a href=\"{$fileUrl}\">{$title}</a><br>";
        break;
    }

    return $boxHtml;
  }

  /**
   * Helper method to determine the appropriate field name based on media type.
   *
   * @param string $mediaType The bundle of the media entity.
   *
   * @return string The field name associated with the media file.
   */
  protected function getFieldNameByMediaType(string $mediaType): string {
    return match ($mediaType) {
      'document' => 'field_media_document',
      'audio' => 'field_media_audio_file',
      'video' => 'field_media_video_file',
      default => '',
    };
  }

  /**
   * Formats and displays book information for inclusion in the PDF.
   *
   * @param EntityInterface $entity The book entity.
   * @param bool $includeTitle Whether to include the book title in the output.
   *
   * @return string Formatted HTML content for the book.
   */
  function bookDisplayForPDF(EntityInterface $entity, bool $includeTitle = true): string
  {
    // Initialize the HTML with the container for the book details
    $html = '<div class="book-container">';

    // Start the book details section
    $html .= '<div class="book-details">';

    // Book title
    $html .= '<strong>' . htmlspecialchars($entity->get('title')->value) . '</strong>';

    // Author or Editor
    $authorEditor = htmlspecialchars($entity->get('field_book_author_or_editor')->value);
    $html .= "<div>by $authorEditor</div>";

    // Publisher, Edition, Year
    $publisher = htmlspecialchars($entity->get('field_book_publisher')->value);
    $edition = htmlspecialchars($entity->get('field_book_edition')->value);
    $year = htmlspecialchars($entity->get('field_book_year')->value);
    $html .= "<p><i>Publisher: $publisher, Edition: $edition, Year: $year</i></p>";

    // Description
    $description = $entity->get('field_book_description')->getValue();
    $renderableDescription = [
      '#type' => 'processed_text',
      '#text' => $description[0]['value'],
      '#format' => $description[0]['format'],
    ];

    $renderedDescription = \Drupal::service('renderer')->renderRoot($renderableDescription);
    $html .= $renderedDescription;


    // Type-specific information: 'print' or others
    $bookTypeName = $entity->get('field_book_type')->value;

    if ($bookTypeName === 'print') {
      // Location and Call Number for print books
      $location = htmlspecialchars($entity->get('field_book_location')->value);
      $callNumber = htmlspecialchars($entity->get('field_book_call_number')->value);
      $html .= "<div>Location: $location</div>";
      $html .= "<div>Call Number: $callNumber</div>";

      // Catalog Record
      $catRecordUrl = htmlspecialchars($entity->get('field_book_cat_record')->uri);
      $catRecordTitle = htmlspecialchars($entity->get('field_book_cat_record')->title);
      $html .= "<div>Cat Record: <a href='$catRecordUrl'>$catRecordTitle</a></div>";
    } else {
      // Publication Finder for non-print books
      $pubFinderUrl = htmlspecialchars($entity->get('field_book_pub_finder')->uri);
      $pubFinderTitle = htmlspecialchars($entity->get('field_book_pub_finder')->title);
      $html .= "<div>Pub Finder: <a href='$pubFinderUrl'>$pubFinderTitle</a></div>";
    }

    // Close the book details and container divs
    $html .= '</div></div>';

    return $html;
  }


  /**
   * Processes page entities associated with a guide for PDF output.
   *
   * @param $page The page entity to process.
   * @param string $baseUrl The base URL of the site for absolute links.
   *
   * @return string Formatted HTML content for the page.
   * @throws DOMException If there's an error in HTML content manipulation.
   */
  protected function processPage($page, $baseUrl): string
  {

    $pageHtml = '<h3>' . htmlspecialchars($page->getTitle()) . '</h3>';

    // Check the field_hide_description boolean field before adding the description
    $hideDescription = $page->hasField('field_hide_description') && !$page->get('field_hide_description')->isEmpty() ? $page->get('field_hide_description')->value : false;

    if (!$hideDescription) {
      if ($page->hasField('field_description') && !$page->get('field_description')->isEmpty()) {
        $description = $page->get('field_description')->value;
        $pageHtml .= '<div>' . $this->prepareHtmlContent($description, $baseUrl) . '</div>';
      }
    }

    // Process child boxes within the page
    if ($page->hasField('field_child_boxes') && !$page->get('field_child_boxes')->isEmpty()) {
      $childBoxes = $page->get('field_child_boxes')->referencedEntities();
      foreach ($childBoxes as $box) {
        $pageHtml .= $this->processBox($box, $baseUrl);
      }
    }

    // Recursively process child pages within the page, if any
    if ($page->hasField('field_child_pages') && !$page->get('field_child_pages')->isEmpty()) {
      $childPages = $page->get('field_child_pages')->referencedEntities();
      foreach ($childPages as $childPage) {
        $pageHtml .= $this->processPage($childPage, $baseUrl);
      }
    }

    return $pageHtml;
  }


  /**
   * Modifies HTML content, converting image tags into clickable links.
   *
   * @param string $htmlContent The original HTML content.
   * @param string $baseUrl The base URL of the site for absolute links.
   *
   * @return false|string The modified HTML content.
   * @throws DOMException If there's an error loading or saving HTML.
   */
  protected function modifyHtmlContent($htmlContent, $baseUrl): false|string
  {
    $doc = new DOMDocument();
    @$doc->loadHTML(mb_convert_encoding($htmlContent, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    $tags = $doc->getElementsByTagName('img');

    foreach ($tags as $tag) {
      $src = $tag->getAttribute('src');
      $alt = $tag->getAttribute('alt') ?: 'No alternative text';
      $a = $doc->createElement('a', htmlspecialchars($alt));
      $a->setAttribute('href', htmlspecialchars($src));
      $tag->parentNode->replaceChild($a, $tag);
    }

    return $doc->saveHTML();
  }

  /**
   * Prepares HTML content for PDF rendering, adjusting image paths.
   *
   * @param string $htmlContent The original HTML content.
   * @param string $baseUrl The base URL of the site for absolute links.
   *
   * @return array|string|null The prepared HTML content.
   */
  protected function prepareHtmlContent($htmlContent, $baseUrl): array|string|null
  {
    $pattern = '/src="\/([^"]+)"/';
    $processedHtml = preg_replace_callback($pattern, function ($matches) use ($baseUrl) {
      return 'src="' . $baseUrl . '/' . $matches[1] . '"';
    }, $htmlContent);
    return $processedHtml;
  }

  /**
   * Generates and streams a PDF document from HTML content.
   *
   * @param string $html The HTML content to convert into a PDF.
   * @param string $title The title for the PDF document.
   *
   * @throws DOMException If there's an error during PDF generation.
   */
  protected function generatePdf($html, $title): void
  {
    $cssStyles = "<style>
        body { font-family: 'Helvetica', sans-serif; margin: 24px; }
        h1 { color: #333366; margin-bottom: 20px; }
        h2, h3 { color: #336633; margin-top: 18px; margin-bottom: 10px; }
        div { margin-bottom: 12px; }
        li { margin-bottom: 5px; }
        a { text-decoration: none; color: #003399; }
        a:hover { text-decoration: underline; }
        ul { list-style-type: square; margin-left: 20px; }
        </style>";

    $options = new Options();
    $options->setIsRemoteEnabled(true);
    $dompdf = new Dompdf($options);

    // Prepend CSS styles to the HTML content
    $htmlWithStyles = $cssStyles . $html;
    $dompdf->loadHtml($htmlWithStyles);

    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream($title . ".pdf", ["Attachment" => true]);
  }
}

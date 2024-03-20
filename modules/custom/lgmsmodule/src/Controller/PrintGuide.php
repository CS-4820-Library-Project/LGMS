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
    if ($node->getType() !== 'guide') {
      // Access denied for non-guide nodes
      return $this->createAccessDeniedResponse();
    }

    $html = '<h1>' . htmlspecialchars($node->getTitle()) . '</h1><hr>';
    $baseUrl = \Drupal::request()->getSchemeAndHttpHost();

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

  protected function processBox($box, $baseUrl) {
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
      }
    }

    return $boxHtml;
  }


  protected function processPage($page, $baseUrl) {

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



  protected function modifyHtmlContent($htmlContent, $baseUrl) {
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

  protected function prepareHtmlContent($htmlContent, $baseUrl) {
    $pattern = '/src="\/([^"]+)"/';
    $processedHtml = preg_replace_callback($pattern, function ($matches) use ($baseUrl) {
      return 'src="' . $baseUrl . '/' . $matches[1] . '"';
    }, $htmlContent);
    return $processedHtml;
  }

  protected function generatePdf($html, $title) {
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

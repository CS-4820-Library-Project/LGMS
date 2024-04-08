<?php

namespace Drupal\lgmsmodule\Controller;


use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\node\Entity\Node;

/**
 * Provides a controller for help guide nodes.
 *
 * This controller class includes methods for creating and managing various
 * types of help guide content, including guide pages, guide boxes, and their items.
 */
class HelpGuide extends ControllerBase {

  /**
   * Creates a new node of the specified type with given fields.
   *
   * @param string $type The machine name of the content type.
   * @param string $title The title of the node.
   * @param array $fields Additional fields to be attached to the node.
   * @return Node The newly created node.
   * @throws EntityStorageException Throws exception if unable to save the node.
   */

  function createNodeOfType($type, $title, array $fields) {
    $node = Node::create(array_merge([
      'type' => $type,
      'title' => $title,
    ], $fields));

    $node->set('promote', 0);
    $node->save();
    return $node;
  }

  /**
   * Creates a new help guide node.
   *
   * @return ContentEntityBase|EntityInterface|Node|EntityBase The newly created help guide node.
   *   The newly created help guide node.
   * @throws EntityStorageException Throws exception if unable to save the node.
   */
  function createHelpGuideNode(): ContentEntityBase|EntityInterface|Node|EntityBase
  {
    return $this->createNodeOfType('guide', 'Help Guide', [
      'field_description' => [
        'value' => 'This is a helper guide to assist users in creating guides and using the module.',
        'format' => 'full_html',
      ],
      'field_hide_description' => FALSE,
    ]);
  }

  /**
   * Creates a new guide page node associated with a help guide.
   *
   * @param int $guideNodeId The ID of the guide node to which this page belongs.
   * @param string $title The title of the guide page.
   * @param string $description The description of the guide page.
   * @return ContentEntityBase|EntityInterface|Node|EntityBase The newly created guide page node.
   *   The newly created guide page node.
   * @throws EntityStorageException Throws exception if unable to save the node.
   */
  function createGuidePageNode($guideNodeId, $title, $description): ContentEntityBase|EntityInterface|Node|EntityBase
  {
    return $this->createNodeOfType('guide_page', $title, [
      'field_description' => [
        'value' => $description,
        'format' => 'full_html',
      ],
      'field_parent_guide' => [
        'target_id' => $guideNodeId,
      ],
      'field_hide_description' => TRUE,
    ]);
  }

  /**
   * Creates a new guide box node associated with a guide page.
   *
   * @param int $guidePageId The ID of the guide page to which this box belongs.
   * @param string $title The title of the guide box.
   * @return ContentEntityBase|EntityInterface|Node|EntityBase The newly created guide box node.
   *   The newly created guide box node.
   * @throws EntityStorageException Throws exception if unable to save the node.
   */
  function createGuideBoxNode($guidePageId, $title): ContentEntityBase|EntityInterface|Node|EntityBase
  {
    return $this->createNodeOfType('guide_box', $title, [
      'field_parent_node' => [
        'target_id' => $guidePageId,
      ],
    ]);
  }

  /**
   * Creates a new guide box item node associated with a guide box.
   *
   * @param int $guideBoxId The ID of the guide box to which this item belongs.
   * @param string $title The title of the guide box item.
   * @return ContentEntityBase|EntityInterface|Node|EntityBase The newly created guide box item node.
   *   The newly created guide box item node.
   * @throws EntityStorageException Throws exception if unable to save the node.
   */
  function createGuideBoxItemNode($guideBoxId, $title): ContentEntityBase|EntityInterface|Node|EntityBase
  {
    return $this->createNodeOfType('guide_item', $title, [
      'field_parent_box' => [
        'target_id' => $guideBoxId,
      ],
    ]);
  }

  /**
   * Creates a new HTML item node associated with a guide box item.
   *
   * @param int $guideBoxItemId The ID of the guide box item to which this HTML item belongs.
   * @param string $title The title of the HTML item.
   * @param string $html The HTML content.
   * @return ContentEntityBase|EntityInterface|Node|EntityBase The newly created HTML item node.
   *   The newly created HTML item node.
   * @throws EntityStorageException Throws exception if unable to save the node.
   */
  function createHtmlItemNode($guideBoxItemId, $title, $html): ContentEntityBase|EntityInterface|Node|EntityBase
  {
    return $this->createNodeOfType('guide_html_item', $title, [
      'field_text_box_item2' => [
        'value' => $html,
        'format' => 'full_html',
      ],
      'field_parent_item' => [
        'target_id' => $guideBoxItemId,
      ],
    ]);
  }

  /**
   * Attaches child nodes to a parent node.
   *
   * @param EntityInterface $parentNode The parent node to attach children to.
   * @param string $field_name The field name on the parent entity where children are referenced.
   * @param array $childNodeIds Array of child node IDs to attach.
   * @throws EntityStorageException Throws exception if unable to save the parent node.
   */
  function attachChildNodes($parentNode, $field_name, $childNodeIds): void
  {
    foreach ($childNodeIds as $childNodeId) {
      $parentNode->{$field_name}[] = ['target_id' => $childNodeId];
    }
    $parentNode->save();
  }


  /**
   * @param ContentEntityBase|EntityInterface|Node|EntityBase $html_guide_box
   * @param ContentEntityBase|EntityInterface|Node|EntityBase $html_box_item
   * @param ContentEntityBase|EntityInterface|Node|EntityBase $book_guide_box
   * @param ContentEntityBase|EntityInterface|Node|EntityBase $book_box_item
   * @param ContentEntityBase|EntityInterface|Node|EntityBase $database_guide_box
   * @param ContentEntityBase|EntityInterface|Node|EntityBase $database_box_item
   * @param ContentEntityBase|EntityInterface|Node|EntityBase $media_guide_box
   * @param ContentEntityBase|EntityInterface|Node|EntityBase $media_box_item
   * @param ContentEntityBase|EntityInterface|Node|EntityBase $create_box_guide_box
   * @param ContentEntityBase|EntityInterface|Node|EntityBase $create_box_box_item
   * @param ContentEntityBase|EntityInterface|Node|EntityBase $create_guide_box
   * @param ContentEntityBase|EntityInterface|Node|EntityBase $create_guide_box_item
   * @return void
   * @throws EntityStorageException
   */
  public function attachChildBoxItems(ContentEntityBase|EntityInterface|Node|EntityBase $html_guide_box, ContentEntityBase|EntityInterface|Node|EntityBase $html_box_item, ContentEntityBase|EntityInterface|Node|EntityBase $book_guide_box, ContentEntityBase|EntityInterface|Node|EntityBase $book_box_item, ContentEntityBase|EntityInterface|Node|EntityBase $database_guide_box, ContentEntityBase|EntityInterface|Node|EntityBase $database_box_item, ContentEntityBase|EntityInterface|Node|EntityBase $media_guide_box, ContentEntityBase|EntityInterface|Node|EntityBase $media_box_item, ContentEntityBase|EntityInterface|Node|EntityBase $create_box_guide_box, ContentEntityBase|EntityInterface|Node|EntityBase $create_box_box_item, ContentEntityBase|EntityInterface|Node|EntityBase $create_guide_box, ContentEntityBase|EntityInterface|Node|EntityBase $create_guide_box_item): void
  {
    $this->attachChildNodes($html_guide_box, 'field_box_items', [$html_box_item->id()]);
    $this->attachChildNodes($book_guide_box, 'field_box_items', [$book_box_item->id()]);
    $this->attachChildNodes($database_guide_box, 'field_box_items', [$database_box_item->id()]);
    $this->attachChildNodes($media_guide_box, 'field_box_items', [$media_box_item->id()]);
    $this->attachChildNodes($create_box_guide_box, 'field_box_items', [$create_box_box_item->id()]);
    $this->attachChildNodes($create_guide_box, 'field_box_items', [$create_guide_box_item->id()]);
  }

  /**
   * @param ContentEntityBase|EntityInterface|Node|EntityBase $html_box_item
   * @param ContentEntityBase|EntityInterface|Node|EntityBase $the_html_content
   * @param ContentEntityBase|EntityInterface|Node|EntityBase $book_box_item
   * @param ContentEntityBase|EntityInterface|Node|EntityBase $book_html_content
   * @param ContentEntityBase|EntityInterface|Node|EntityBase $database_box_item
   * @param ContentEntityBase|EntityInterface|Node|EntityBase $database_html_content
   * @param ContentEntityBase|EntityInterface|Node|EntityBase $media_box_item
   * @param ContentEntityBase|EntityInterface|Node|EntityBase $media_html_content
   * @param ContentEntityBase|EntityInterface|Node|EntityBase $create_box_box_item
   * @param ContentEntityBase|EntityInterface|Node|EntityBase $create_box_html_content
   * @param ContentEntityBase|EntityInterface|Node|EntityBase $create_guide_box_item
   * @param ContentEntityBase|EntityInterface|Node|EntityBase $create_guide_html_content
   * @return void
   * @throws EntityStorageException
   */
  public function attachHTMLItemsToBoxItems(ContentEntityBase|EntityInterface|Node|EntityBase $html_box_item, ContentEntityBase|EntityInterface|Node|EntityBase $the_html_content, ContentEntityBase|EntityInterface|Node|EntityBase $book_box_item, ContentEntityBase|EntityInterface|Node|EntityBase $book_html_content, ContentEntityBase|EntityInterface|Node|EntityBase $database_box_item, ContentEntityBase|EntityInterface|Node|EntityBase $database_html_content, ContentEntityBase|EntityInterface|Node|EntityBase $media_box_item, ContentEntityBase|EntityInterface|Node|EntityBase $media_html_content, ContentEntityBase|EntityInterface|Node|EntityBase $create_box_box_item, ContentEntityBase|EntityInterface|Node|EntityBase $create_box_html_content, ContentEntityBase|EntityInterface|Node|EntityBase $create_guide_box_item, ContentEntityBase|EntityInterface|Node|EntityBase $create_guide_html_content): void
  {
    $this->attachChildNodes($html_box_item, 'field_html_item', [$the_html_content->id()]);
    $this->attachChildNodes($book_box_item, 'field_html_item', [$book_html_content->id()]);
    $this->attachChildNodes($database_box_item, 'field_html_item', [$database_html_content->id()]);
    $this->attachChildNodes($media_box_item, 'field_html_item', [$media_html_content->id()]);
    $this->attachChildNodes($create_box_box_item, 'field_html_item', [$create_box_html_content->id()]);
    $this->attachChildNodes($create_guide_box_item, 'field_html_item', [$create_guide_html_content->id()]);
  }

  /**
   * @param ContentEntityBase|EntityInterface|Node|EntityBase $html_page
   * @param ContentEntityBase|EntityInterface|Node|EntityBase $html_guide_box
   * @param ContentEntityBase|EntityInterface|Node|EntityBase $reuse_html_guide_box
   * @param ContentEntityBase|EntityInterface|Node|EntityBase $book_page
   * @param ContentEntityBase|EntityInterface|Node|EntityBase $book_guide_box
   * @param ContentEntityBase|EntityInterface|Node|EntityBase $reuse_book_guide_box
   * @param ContentEntityBase|EntityInterface|Node|EntityBase $database_page
   * @param ContentEntityBase|EntityInterface|Node|EntityBase $database_guide_box
   * @param ContentEntityBase|EntityInterface|Node|EntityBase $reuse_database_guide_box
   * @return void
   * @throws EntityStorageException
   */
  public function attachChildBoxes(ContentEntityBase|EntityInterface|Node|EntityBase $html_page, ContentEntityBase|EntityInterface|Node|EntityBase $html_guide_box, ContentEntityBase|EntityInterface|Node|EntityBase $reuse_html_guide_box, ContentEntityBase|EntityInterface|Node|EntityBase $book_page, ContentEntityBase|EntityInterface|Node|EntityBase $book_guide_box, ContentEntityBase|EntityInterface|Node|EntityBase $reuse_book_guide_box, ContentEntityBase|EntityInterface|Node|EntityBase $database_page, ContentEntityBase|EntityInterface|Node|EntityBase $database_guide_box, ContentEntityBase|EntityInterface|Node|EntityBase $reuse_database_guide_box): void
  {
    $this->attachChildNodes($html_page, 'field_child_boxes', [$html_guide_box->id()]);
    $this->attachChildNodes($html_page, 'field_child_boxes', [$reuse_html_guide_box->id()]);

    $this->attachChildNodes($book_page, 'field_child_boxes', [$book_guide_box->id()]);
    $this->attachChildNodes($book_page, 'field_child_boxes', [$reuse_book_guide_box->id()]);

    $this->attachChildNodes($database_page, 'field_child_boxes', [$database_guide_box->id()]);
    $this->attachChildNodes($database_page, 'field_child_boxes', [$reuse_database_guide_box->id()]);
  }

  /**
   * Orchestrates the creation of a complete help guide structure.
   *
   * This includes creating the main help guide node, associated guide pages, guide boxes,
   * and various items within those boxes. Each step in the guide creation process is handled
   * by dedicated methods within this class.
   *
   * @return array An array of created node entities, keyed by their respective roles in the guide structure.
   * @throws EntityStorageException Throws exception if unable to save any of the entities.
   */
  function createHelpGuide(): array {
    // Create main help guide node
    $help_guide = $this->createHelpGuideNode();

    // Create guide pages
    $guide_page = $this->createGuidePageNode($help_guide->id(), 'Guide', 'Tutorial for guide creation and reuse.');
    $guide_page_page = $this->createGuidePageNode($help_guide->id(), 'Guide Page', 'Tutorial for guide page creation and reuse.');
    $guide_box_page = $this->createGuidePageNode($help_guide->id(), 'Guide Box', 'Tutorial for guide box creation and reuse.');
    $html_page = $this->createGuidePageNode($help_guide->id(), 'HTML', 'Tutorial for HTML creation and reuse.');
    $book_page = $this->createGuidePageNode($help_guide->id(), 'Book', 'Tutorial for book creation and reuse.');
    $database_page = $this->createGuidePageNode($help_guide->id(), 'Database', 'Tutorial for database creation and reuse.');
    $media_page = $this->createGuidePageNode($help_guide->id(), 'Media', 'Tutorial for media creation.');


    // Create guide boxes
    $create_guide_box = $this->createGuideBoxNode($guide_page->id(), 'Create Guide Box');
    $reuse_guide_box = $this->createGuideBoxNode($guide_page->id(), 'Reuse Guide Box');

    $create_page_guide_box = $this->createGuideBoxNode($guide_page_page->id(), 'Create Page Guide Box');
    $reuse_page_guide_box = $this->createGuideBoxNode($guide_page_page->id(), 'Reuse Page Guide Box');

    $create_box_guide_box = $this->createGuideBoxNode($guide_box_page->id(), 'Create a Guide Box');
    $reuse_box_guide_box = $this->createGuideBoxNode($guide_box_page->id(), 'Reuse a Guide Box');

    $html_guide_box = $this->createGuideBoxNode($html_page->id(), 'HTML Guide Box');
    $reuse_html_guide_box = $this->createGuideBoxNode($html_page->id(), 'Reuse HTML Guide Box');

    $book_guide_box = $this->createGuideBoxNode($book_page->id(), 'Book Guide Box');
    $reuse_book_guide_box = $this->createGuideBoxNode($book_page->id(), 'Reuse Book Guide Box');

    $database_guide_box = $this->createGuideBoxNode($database_page->id(), 'Database Guide Box');
    $reuse_database_guide_box = $this->createGuideBoxNode($database_page->id(), 'Reuse Database Guide Box');

    $media_guide_box = $this->createGuideBoxNode($media_page->id(), 'Media Guide Box');

    // Attach guide boxes to pages
    $this->attachChildBoxes($guide_page, $create_guide_box, $reuse_guide_box, $guide_page_page, $create_page_guide_box, $reuse_page_guide_box, $guide_box_page, $create_box_guide_box, $reuse_box_guide_box);

    $this->attachChildBoxes($html_page, $html_guide_box, $reuse_html_guide_box, $book_page, $book_guide_box, $reuse_book_guide_box, $database_page, $database_guide_box, $reuse_database_guide_box);

    $this->attachChildNodes($media_page, 'field_child_boxes', [$media_guide_box->id()]);

    // Create guide box items
    $html_box_item = $this->createGuideBoxItemNode($html_guide_box->id(), 'HTML Item');
    $book_box_item = $this->createGuideBoxItemNode($book_guide_box->id(), 'Book Item');
    $database_box_item = $this->createGuideBoxItemNode($database_guide_box->id(), 'Database Item');
    $media_box_item = $this->createGuideBoxItemNode($media_guide_box->id(), 'Media Item');
    $create_box_box_item = $this->createGuideBoxItemNode($create_box_guide_box->id(), 'Create Box Item');
    $create_guide_box_item = $this->createGuideBoxItemNode($create_guide_box->id(), 'Create Guide Item');
    $create_page_box_item = $this->createGuideBoxItemNode($create_page_guide_box->id(), 'Create Page Item');
    $reuse_guide_box_item = $this->createGuideBoxItemNode($reuse_guide_box->id(), 'Reuse Guide Item');
    $reuse_html_box_item = $this->createGuideBoxItemNode($reuse_html_guide_box->id(), 'Reuse HTML Item');
    $reuse_page_box_item = $this->createGuideBoxItemNode($reuse_page_guide_box->id(), 'Reuse Page Item');
    $reuse_database_box_item = $this->createGuideBoxItemNode($reuse_database_guide_box->id(), 'Reuse Database Item');
    $reuse_book_box_item = $this->createGuideBoxItemNode($reuse_book_guide_box->id(), 'Reuse Book Item');

    // Create HTML items

    $add_html = '<h1><a href="https://app.tango.us/app/workflow/80425ed0-4b37-44c7-8f41-bbe19d009ed3?utm_source=magicCopy&utm_medium=magicCopy&utm_campaign=workflow%20export%20links" target="_blank">Adding HTML/ Text Content to Your Box</a></h1>
<div><b>Creation Date:</b> March 31, 2024</div>
<div><b>Created By:</b> Mohammed Amaan</div>
<div><a href="https://app.tango.us/app/workflow/80425ed0-4b37-44c7-8f41-bbe19d009ed3?utm_source=magicCopy&utm_medium=magicCopy&utm_campaign=workflow%20export%20links" target="_blank">View most recent version on Tango.us</a></div>
<div style="height: 24px">&#8203;</div>
<hr />
<div style="height: 24px">&#8203;</div>


<div><h2><a href="http://localhost/LGMS/psychology/finding-books-psychology"># Navigate to the required Page and then the box</a></h2></div>

<div><h3>1. This is what the Box will Look like without any content in it</h3>
<img src="https://images.tango.us/workflows/80425ed0-4b37-44c7-8f41-bbe19d009ed3/steps/c0b98ecd-f9f8-43aa-a971-63d35b97feb7/82e7c59b-c95f-4e20-8745-c66e6e414c96.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.6214&fp-y=0.6066&fp-z=1.4224&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=46&mark-y=303&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz0xMTA4Jmg9Mzc0JmZpdD1jcm9wJmNvcm5lci1yYWRpdXM9MTA%3D" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="This is what the Box will Look like without any content in it" />
</div>

<div><h3>2. Click on Add/Reuse HTML to add Plain text or HTML content </h3>
<img src="https://images.tango.us/workflows/80425ed0-4b37-44c7-8f41-bbe19d009ed3/steps/bee8f840-00d2-4541-83b3-5520fa5ca621/7933b6a5-2776-450d-983d-1e2864e3509d.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.3932&fp-y=0.5976&fp-z=2.3651&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=426&mark-y=455&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz0zNDkmaD03MCZmaXQ9Y3JvcCZjb3JuZXItcmFkaXVzPTEw" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Click on Add/Reuse HTML to add Plain text or HTML content " />
</div>

<div><h3>3. Add Title </h3>
<img src="https://images.tango.us/workflows/80425ed0-4b37-44c7-8f41-bbe19d009ed3/steps/bdc2bb58-3fbc-4c8e-bc3c-8bd4c63ed45a/8015f4ef-4dbc-456a-832e-181d9466b4ec.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.5005&fp-y=0.2576&fp-z=1.1706&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=102&mark-y=265&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz05OTcmaD02MSZmaXQ9Y3JvcCZjb3JuZXItcmFkaXVzPTEw" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Add Title " />
</div>

<div><h3>4. In the Body text box is where you will add the content.</h3>
<p>By Default Basic HTML is selected</p>
<img src="https://images.tango.us/workflows/80425ed0-4b37-44c7-8f41-bbe19d009ed3/steps/097a09ac-c363-42af-835b-242b6cebfbe2/1a01e8b3-83b1-4848-8a3d-18d1be3f366d.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.5005&fp-y=0.4826&fp-z=1.1706&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=102&mark-y=356&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz05OTcmaD0yNjgmZml0PWNyb3AmY29ybmVyLXJhZGl1cz0xMA%3D%3D" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="In the Body text box is where you will add the content." />
</div>

<div><h3>5. You have the option to change it to full HTML or Restricted HTML</h3>
<img src="https://images.tango.us/workflows/80425ed0-4b37-44c7-8f41-bbe19d009ed3/steps/464ed90c-35f9-4900-b93a-f26c88470c42/6ca23239-c171-44dd-8e4a-54967b3c48b8.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.5005&fp-y=0.5932&fp-z=1.1706&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=102&mark-y=483&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz05OTcmaD02MSZmaXQ9Y3JvcCZjb3JuZXItcmFkaXVzPTEw" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="You have the option to change it to full HTML or Restricted HTML" />
</div>

<div><h3>6. Click on Save</h3>
<img src="https://images.tango.us/workflows/80425ed0-4b37-44c7-8f41-bbe19d009ed3/steps/086224cf-3251-45c6-a184-d20bf7e4800e/735bf073-ed2a-4656-938c-e7f063f4ec14.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.1962&fp-y=0.8692&fp-z=2.6918&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=485&mark-y=557&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz0yMzEmaD0xNTcmZml0PWNyb3AmY29ybmVyLXJhZGl1cz0xMA%3D%3D" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Click on Save" />
</div>

<br/>
<hr/>
<div>
</div>';

    $add_book = '<h1><a href="https://app.tango.us/app/workflow/e33598e0-bfe8-49e0-83f0-fa057bbb015f?utm_source=magicCopy&utm_medium=magicCopy&utm_campaign=workflow%20export%20links" target="_blank">Adding a Book to a Box</a></h1>
<div><b>Creation Date:</b> March 31, 2024</div>
<div><b>Created By:</b> Mohammed Amaan</div>
<div><a href="https://app.tango.us/app/workflow/e33598e0-bfe8-49e0-83f0-fa057bbb015f?utm_source=magicCopy&utm_medium=magicCopy&utm_campaign=workflow%20export%20links" target="_blank">View most recent version on Tango.us</a></div>
<div style="height: 24px">&#8203;</div>
<hr />
<div style="height: 24px">&#8203;</div>
<div><h2><a href="http://localhost/LGMS/psychology/finding-books-psychology"># Navigate to the required box</a></h2></div>

<div><h3>1. Click on Add/Reuse Book</h3>
<img src="https://images.tango.us/workflows/e33598e0-bfe8-49e0-83f0-fa057bbb015f/steps/07da3f50-9fc4-4084-a4f5-7b7bda143000/ca18dfb2-b084-46b7-90a5-af2ed85157ab.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.3900&fp-y=0.7626&fp-z=2.4015&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=432&mark-y=454&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz0zMzUmaD03MSZmaXQ9Y3JvcCZjb3JuZXItcmFkaXVzPTEw" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Click on Add/Reuse Book" />
</div>

<div><h3>2. Enter the Title</h3>
<img src="https://images.tango.us/workflows/e33598e0-bfe8-49e0-83f0-fa057bbb015f/steps/caa24488-411d-4c2b-9d91-3a2ea2e71931/e2bb6d54-1032-4ff9-b44b-ed2eddc6defa.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.5005&fp-y=0.2565&fp-z=1.1706&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=102&mark-y=264&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz05OTcmaD02MSZmaXQ9Y3JvcCZjb3JuZXItcmFkaXVzPTEw" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Enter the Title" />
</div>

<div><h3>3. Enter the  Author/Editor</h3>
<img src="https://images.tango.us/workflows/e33598e0-bfe8-49e0-83f0-fa057bbb015f/steps/3d96fff4-6d47-472a-8f59-528fb0fb8bbf/16b8d8a0-9f8b-4a9f-a512-ae33bcc3de3f.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.5005&fp-y=0.3462&fp-z=1.1706&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=102&mark-y=367&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz05OTcmaD02MSZmaXQ9Y3JvcCZjb3JuZXItcmFkaXVzPTEw" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Enter the  Author/Editor" />
</div>

<div><h3>4. Enter the Publisher</h3>
<img src="https://images.tango.us/workflows/e33598e0-bfe8-49e0-83f0-fa057bbb015f/steps/8e95a740-ba8b-41a5-907a-55cd531ee7bd/fd37f8b0-8cad-4cd8-be31-4f72b6fdc689.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.5005&fp-y=0.4360&fp-z=1.1706&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=102&mark-y=460&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz05OTcmaD02MSZmaXQ9Y3JvcCZjb3JuZXItcmFkaXVzPTEw" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Enter the Publisher" />
</div>

<div><h3>5. Enter the Year</h3>
<img src="https://images.tango.us/workflows/e33598e0-bfe8-49e0-83f0-fa057bbb015f/steps/38d38619-9e38-4d0e-8134-fa733154ce9b/ec01bd18-092e-45e6-a562-5e2cf55ea75e.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.5005&fp-y=0.5258&fp-z=1.1706&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=102&mark-y=460&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz05OTcmaD02MSZmaXQ9Y3JvcCZjb3JuZXItcmFkaXVzPTEw" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Enter the Year" />
</div>

<div><h3>6. Enter the Edition</h3>
<img src="https://images.tango.us/workflows/e33598e0-bfe8-49e0-83f0-fa057bbb015f/steps/25ea8b9c-2371-40a2-90ce-cc41bfc4323f/44b06cf2-fa64-40f7-8200-7b2764f90f17.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.5005&fp-y=0.4360&fp-z=1.1706&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=102&mark-y=460&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz05OTcmaD02MSZmaXQ9Y3JvcCZjb3JuZXItcmFkaXVzPTEw" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Enter the Edition" />
</div>

<div><h3>7. Click on Cover Picture to add a cover picture ( optional )</h3>
<img src="https://images.tango.us/workflows/e33598e0-bfe8-49e0-83f0-fa057bbb015f/steps/f7806d55-30dc-437b-a4de-bc9d201e19fa/0db5112d-40de-4745-8ec8-29ff452d103b.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.5005&fp-y=0.3805&fp-z=1.1706&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=102&mark-y=416&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz05OTcmaD00MSZmaXQ9Y3JvcCZjb3JuZXItcmFkaXVzPTEw" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Click on Cover Picture to add a cover picture ( optional )" />
</div>

<div><h3>8. Enter a discription of the book</h3>
<img src="https://images.tango.us/workflows/e33598e0-bfe8-49e0-83f0-fa057bbb015f/steps/b229e287-b7e7-40f0-9f42-763541029b40/455c7d25-aec1-426e-aff3-72ec76a5ef8b.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.5005&fp-y=0.4175&fp-z=1.1706&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=102&mark-y=345&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz05OTcmaD0yNjgmZml0PWNyb3AmY29ybmVyLXJhZGl1cz0xMA%3D%3D" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Enter a discription of the book" />
</div>

<div><h2>🖨️ If the type is Print follow the instructions below</h2></div>

<div><h3>9. Enter the Call Number</h3>
<img src="https://images.tango.us/workflows/e33598e0-bfe8-49e0-83f0-fa057bbb015f/steps/c460dbf7-2587-4390-915a-40b69b33b5e1/3b455d73-3dce-485e-a3eb-adbe6273fef4.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.5005&fp-y=0.3844&fp-z=1.1706&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=102&mark-y=411&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz05OTcmaD02MSZmaXQ9Y3JvcCZjb3JuZXItcmFkaXVzPTEw" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Enter the Call Number" />
</div>

<div><h3>10. Enter the Location</h3>
<img src="https://images.tango.us/workflows/e33598e0-bfe8-49e0-83f0-fa057bbb015f/steps/87976202-c905-436b-841b-640ca9706531/918cd941-02c1-4ba0-ac83-05778521f4ee.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.5005&fp-y=0.4742&fp-z=1.1706&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=102&mark-y=460&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz05OTcmaD02MSZmaXQ9Y3JvcCZjb3JuZXItcmFkaXVzPTEw" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Enter the Location" />
</div>

<div><h3>11. Enter the Link Link Text</h3>
<img src="https://images.tango.us/workflows/e33598e0-bfe8-49e0-83f0-fa057bbb015f/steps/d650afb6-3ea3-4dc2-bb78-ea5ea8ce8881/7dbaa728-5356-4b07-9577-5ef8954727d1.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.5005&fp-y=0.6190&fp-z=1.1706&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=102&mark-y=513&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz05OTcmaD02MSZmaXQ9Y3JvcCZjb3JuZXItcmFkaXVzPTEw" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Enter the Link Link Text" />
</div>

<div><h3>12. Enter the URL</h3>
<img src="https://images.tango.us/workflows/e33598e0-bfe8-49e0-83f0-fa057bbb015f/steps/91b3fd2d-e36a-42be-9fa3-3eef35ef7f15/b6d52c58-abe4-4d25-8380-f5fc7233a80c.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.5005&fp-y=0.7088&fp-z=1.1706&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=102&mark-y=616&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz05OTcmaD02MSZmaXQ9Y3JvcCZjb3JuZXItcmFkaXVzPTEw" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Enter the URL" />
</div>

<div><h2>📔 If the type is an Ebook, follow the instructions below.</h2></div>

<div><h3>13. Select eBook from Type</h3>
<img src="https://images.tango.us/workflows/e33598e0-bfe8-49e0-83f0-fa057bbb015f/steps/9df83f36-beff-4488-975b-d624f9daa7c5/8f1058fa-322e-470d-9281-5afddff28550.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.5005&fp-y=0.4574&fp-z=1.1706&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=102&mark-y=460&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz05OTcmaD02MSZmaXQ9Y3JvcCZjb3JuZXItcmFkaXVzPTEw" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Select eBook from Type" />
</div>

<div><h3>14. Enter the  Link Text</h3>
<img src="https://images.tango.us/workflows/e33598e0-bfe8-49e0-83f0-fa057bbb015f/steps/ab459363-f65c-4414-9ca5-6a930fb43906/949646a7-49e8-4d8d-ac27-d2bd3552a8fa.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.5005&fp-y=0.6190&fp-z=1.1706&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=102&mark-y=513&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz05OTcmaD02MSZmaXQ9Y3JvcCZjb3JuZXItcmFkaXVzPTEw" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Enter the  Link Text" />
</div>

<div><h3>15. Enter the URL to the Book</h3>
<img src="https://images.tango.us/workflows/e33598e0-bfe8-49e0-83f0-fa057bbb015f/steps/a0137618-881e-407c-a2f5-4423235a3b86/b0194519-0e3c-4952-bb4a-a04833cb8bcd.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.5005&fp-y=0.7088&fp-z=1.1706&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=102&mark-y=616&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz05OTcmaD02MSZmaXQ9Y3JvcCZjb3JuZXItcmFkaXVzPTEw" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Enter the URL to the Book" />
</div>

<div><h3>16. Click on Save to add the book to your box.</h3>
<img src="https://images.tango.us/workflows/e33598e0-bfe8-49e0-83f0-fa057bbb015f/steps/18a01da9-d8e7-49d7-9b6b-398214fe418b/b70d1a09-8d43-4fdb-870a-8e2fa0ef7fc1.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.1962&fp-y=0.8704&fp-z=2.6918&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=485&mark-y=560&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz0yMzEmaD0xNTcmZml0PWNyb3AmY29ybmVyLXJhZGl1cz0xMA%3D%3D" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Click on Save to add the book to your box." />
</div>

<br/>
<hr/>
<div>
<span>Created with </span><a href="https://tango.us?utm_source=magicCopy&utm_medium=magicCopy&utm_campaign=workflow%20export%20links" target="_blank" style="color: #256EFF">Tango.us
    </a>
</div>';

    $reuse_book = '<h1><a href="https://app.tango.us/app/workflow/6da44a15-c065-4681-84b7-74fe6cd3b91f?utm_source=magicCopy&utm_medium=magicCopy&utm_campaign=workflow%20export%20links" target="_blank">Reuse A book</a></h1>
<div><b>Creation Date:</b> April 6, 2024</div>
<div><b>Created By:</b> Mohammed Amaan</div>
<div><a href="https://app.tango.us/app/workflow/6da44a15-c065-4681-84b7-74fe6cd3b91f?utm_source=magicCopy&utm_medium=magicCopy&utm_campaign=workflow%20export%20links" target="_blank">View most recent version on Tango.us</a></div>
<div style="height: 24px">&#8203;</div>
<hr />
<div style="height: 24px">&#8203;</div>


<div><h2><a href="http://localhost/LGMS/psychology"># Navigate to the Required section</a></h2></div>

<div><h3>1. Click on Add/Reuse Book</h3>
<img src="https://images.tango.us/workflows/6da44a15-c065-4681-84b7-74fe6cd3b91f/steps/511defd6-5c4f-4925-ac02-cdd76e61b9c4/b3c498c3-eb3e-489b-b2bf-d545f8e862dc.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.3900&fp-y=0.6246&fp-z=2.4015&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=432&mark-y=454&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz0zMzUmaD03MSZmaXQ9Y3JvcCZjb3JuZXItcmFkaXVzPTEw" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Click on Add/Reuse Book" />
</div>

<div><h3>2. Click on Reuse Book</h3>
<img src="https://images.tango.us/workflows/6da44a15-c065-4681-84b7-74fe6cd3b91f/steps/22db2bc8-2aec-4802-9866-be971d197265/435d0d45-ed51-493f-9777-18437e3741c0.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.2864&fp-y=0.1661&fp-z=2.4445&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=440&mark-y=336&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz0zMjAmaD0xMjQmZml0PWNyb3AmY29ybmVyLXJhZGl1cz0xMA%3D%3D" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Click on Reuse Book" />
</div>

<div><h3>3. Select the book item from the drop down</h3>
<img src="https://images.tango.us/workflows/6da44a15-c065-4681-84b7-74fe6cd3b91f/steps/d7b76074-7363-4de8-be06-0bb708525ed1/952c84b9-2dfd-4617-a2c5-9d5e21403f2e.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.5005&fp-y=0.2565&fp-z=1.1706&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=102&mark-y=264&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz05OTcmaD02MSZmaXQ9Y3JvcCZjb3JuZXItcmFkaXVzPTEw" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Select the book item from the drop down" />
</div>

<div><h3>4. Click on Save to add the book to your box</h3>
<img src="https://images.tango.us/workflows/6da44a15-c065-4681-84b7-74fe6cd3b91f/steps/e0732c17-c2f1-49c7-887c-40df8795614f/e494fd73-3fec-457a-bcfd-4e26b5312ca9.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.1962&fp-y=0.8704&fp-z=2.6918&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=485&mark-y=560&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz0yMzEmaD0xNTcmZml0PWNyb3AmY29ybmVyLXJhZGl1cz0xMA%3D%3D" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Click on Save to add the book to your box" />
</div>

<br/>
<hr/>
<div>
<span>Created with </span><a href="https://tango.us?utm_source=magicCopy&utm_medium=magicCopy&utm_campaign=workflow%20export%20links" target="_blank" style="color: #256EFF">Tango.us
    </a>
</div>';

    $add_database = '<h1><a href="https://app.tango.us/app/workflow/1c359512-d3ef-4064-ac76-b549ee754a93?utm_source=magicCopy&utm_medium=magicCopy&utm_campaign=workflow%20export%20links" target="_blank">Adding a DataBase to the Box</a></h1>
<div><b>Creation Date:</b> March 31, 2024</div>
<div><b>Created By:</b> Mohammed Amaan</div>
<div><a href="https://app.tango.us/app/workflow/1c359512-d3ef-4064-ac76-b549ee754a93?utm_source=magicCopy&utm_medium=magicCopy&utm_campaign=workflow%20export%20links" target="_blank">View most recent version on Tango.us</a></div>
<div style="height: 24px">&#8203;</div>
<hr />
<div style="height: 24px">&#8203;</div>


<div><h2><a href="http://localhost/LGMS/psychology/finding-books-psychology"># Navigate to the Box where the database is to be added </a></h2></div>

<div><h3>1. Click on Add/Reuse Database</h3>
<img src="https://images.tango.us/workflows/1c359512-d3ef-4064-ac76-b549ee754a93/steps/6f935eaf-3cdf-4aa1-9a3d-facef694bec6/70c2d897-1595-43b5-a909-65cd11866854.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.4042&fp-y=0.6515&fp-z=2.2481&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=405&mark-y=457&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz0zOTEmaD02NyZmaXQ9Y3JvcCZjb3JuZXItcmFkaXVzPTEw" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Click on Add/Reuse Database" />
</div>

<div><h3>2. Click on Database Title: </h3>
<p>Enter the Title</p>
<img src="https://images.tango.us/workflows/1c359512-d3ef-4064-ac76-b549ee754a93/steps/7a1b7889-c4f1-4bfd-b190-77998ce095a4/2db26591-ae26-4ee8-be9b-e49ea23fd239.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.5005&fp-y=0.2565&fp-z=1.1706&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=102&mark-y=264&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz05OTcmaD02MSZmaXQ9Y3JvcCZjb3JuZXItcmFkaXVzPTEw" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Click on Database Title: " />
</div>

<div><h3>3. Click on Link Text:</h3>
<p>Enter the Link text</p>
<img src="https://images.tango.us/workflows/1c359512-d3ef-4064-ac76-b549ee754a93/steps/08de9ad7-7656-4906-9766-4ae9a9d55800/abba0851-38e0-4a6d-9c45-0fe7dbec801a.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.5005&fp-y=0.3462&fp-z=1.1706&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=102&mark-y=367&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz05OTcmaD02MSZmaXQ9Y3JvcCZjb3JuZXItcmFkaXVzPTEw" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Click on Link Text:" />
</div>

<div><h3>4. Click on Database Link</h3>
<p>Enter the Database Link</p>
<img src="https://images.tango.us/workflows/1c359512-d3ef-4064-ac76-b549ee754a93/steps/d8cacaaa-fef5-4042-9fe2-8fe4444939a2/3c98833e-cf75-4b3d-811d-cfc95cc57a11.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.5005&fp-y=0.4360&fp-z=1.1706&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=102&mark-y=460&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz05OTcmaD02MSZmaXQ9Y3JvcCZjb3JuZXItcmFkaXVzPTEw" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Click on Database Link" />
</div>

<div><h3>5. Check Include Proxy</h3>
<p>Proxy gives the you the ability to add a url proxy before being redirected to the database.</p>
<img src="https://images.tango.us/workflows/1c359512-d3ef-4064-ac76-b549ee754a93/steps/fdaf0d51-477b-4401-be0c-dd6e949f807b/e02ddcaf-bfaf-4dcd-b102-f560a462454d.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.1554&fp-y=0.5292&fp-z=3.0905&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=540&mark-y=454&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz03MSZoPTcxJmZpdD1jcm9wJmNvcm5lci1yYWRpdXM9MTA%3D" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Check Include Proxy" />
</div>

<div><h3>6. Click on Brief Description</h3>
<p>Enter a Description</p>
<img src="https://images.tango.us/workflows/1c359512-d3ef-4064-ac76-b549ee754a93/steps/71ddb844-ffe3-4eb4-93f3-65036c7b8f5a/96dac1a6-d441-4e56-afa9-5d9d48347a19.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.5005&fp-y=0.5651&fp-z=1.1706&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=102&mark-y=460&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz05OTcmaD02MSZmaXQ9Y3JvcCZjb3JuZXItcmFkaXVzPTEw" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Click on Brief Description" />
</div>

<div><h3>7. Click on Editor editing area</h3>
<p>Here you can add additional instructions or details about the database</p>
<img src="https://images.tango.us/workflows/1c359512-d3ef-4064-ac76-b549ee754a93/steps/12b80f61-7823-416e-b780-492f425f60fe/ea707a1c-4b41-48f5-8a7c-a0228fd42a7e.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.5005&fp-y=0.6229&fp-z=1.1706&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=102&mark-y=413&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz05OTcmaD0yNjgmZml0PWNyb3AmY29ybmVyLXJhZGl1cz0xMA%3D%3D" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Click on Editor editing area" />
</div>

<div><h3>8. Click on Save to add the guide to your box</h3>
<img src="https://images.tango.us/workflows/1c359512-d3ef-4064-ac76-b549ee754a93/steps/3390ce20-f74b-4c65-aea3-a41ba36cd732/06e5e58b-b44a-4e45-993c-2ca409b8af09.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.1962&fp-y=0.8704&fp-z=2.6918&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=485&mark-y=560&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz0yMzEmaD0xNTcmZml0PWNyb3AmY29ybmVyLXJhZGl1cz0xMA%3D%3D" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Click on Save to add the guide to your box" />
</div>

<br/>
<hr/>
<div>
<span>Created with </span><a href="https://tango.us?utm_source=magicCopy&utm_medium=magicCopy&utm_campaign=workflow%20export%20links" target="_blank" style="color: #256EFF">Tango.us
    </a>
</div>';

    $reuse_database = '<h1><a href="https://app.tango.us/app/workflow/83387a65-9c51-46cf-8b06-236e0217b73d?utm_source=magicCopy&utm_medium=magicCopy&utm_campaign=workflow%20export%20links" target="_blank">Reuse Database</a></h1>
<div><p>Allows one to Reuse and existing database content type from a box</p></div>
<div><b>Creation Date:</b> March 31, 2024</div>
<div><b>Created By:</b> Mohammed Amaan</div>
<div><a href="https://app.tango.us/app/workflow/83387a65-9c51-46cf-8b06-236e0217b73d?utm_source=magicCopy&utm_medium=magicCopy&utm_campaign=workflow%20export%20links" target="_blank">View most recent version on Tango.us</a></div>
<div style="height: 24px">&#8203;</div>
<hr />
<div style="height: 24px">&#8203;</div>


<div><h2><a href="http://localhost/LGMS/business-administration/finding-journal-articles-business-administration"># Navigate to the required box.</a></h2></div>

<div><h3>1. Click on Add/Reuse Database</h3>
<img src="https://images.tango.us/workflows/83387a65-9c51-46cf-8b06-236e0217b73d/steps/77f3babc-86eb-4a6b-8041-bc94518459c2/2d9326be-af1d-4922-9bd3-9022393744bd.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.4042&fp-y=0.6515&fp-z=2.2481&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=405&mark-y=457&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz0zOTEmaD02NyZmaXQ9Y3JvcCZjb3JuZXItcmFkaXVzPTEw" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Click on Add/Reuse Database" />
</div>

<div><h3>2. Click on Reuse Database</h3>
<img src="https://images.tango.us/workflows/83387a65-9c51-46cf-8b06-236e0217b73d/steps/a797e346-ff25-40bf-b640-bce414d4bc07/cd901e96-1d97-4579-9df9-8dbd559800c6.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.3304&fp-y=0.1661&fp-z=2.2810&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=411&mark-y=314&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz0zNzkmaD0xMTUmZml0PWNyb3AmY29ybmVyLXJhZGl1cz0xMA%3D%3D" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Click on Reuse Database" />
</div>

<div><h3>3. Select the desired Database</h3>
<img src="https://images.tango.us/workflows/83387a65-9c51-46cf-8b06-236e0217b73d/steps/974de293-1d00-4c4f-bdb1-0cd60dce2953/b0ac0663-6d83-4f40-93ee-be8d193a4da0.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.5005&fp-y=0.2565&fp-z=1.1706&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=102&mark-y=264&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz05OTcmaD02MSZmaXQ9Y3JvcCZjb3JuZXItcmFkaXVzPTEw" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Select the desired Database" />
</div>

<div><h3>4. Click on Save</h3>
<img src="https://images.tango.us/workflows/83387a65-9c51-46cf-8b06-236e0217b73d/steps/ed00fcca-8f9e-4927-a1e8-3300a0c70d6f/fe8d172e-8f15-4f90-abdf-a9ec9b9d75fb.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.1962&fp-y=0.8704&fp-z=2.6918&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=485&mark-y=560&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz0yMzEmaD0xNTcmZml0PWNyb3AmY29ybmVyLXJhZGl1cz0xMA%3D%3D" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Click on Save" />
</div>

<br/>
<hr/>
<div>
<span>Created with </span><a href="https://tango.us?utm_source=magicCopy&utm_medium=magicCopy&utm_campaign=workflow%20export%20links" target="_blank" style="color: #256EFF">Tango.us
    </a>
</div>';

    $add_media = '<h1><a href="https://app.tango.us/app/workflow/9fea3130-e2fc-4ed2-9d52-fbf31825c9f5?utm_source=magicCopy&utm_medium=magicCopy&utm_campaign=workflow%20export%20links" target="_blank">Adding Media</a></h1>
<div><p>The Adding Media uses another module to add libraries so the instructions on how to found on their page.</p></div>
<div><b>Creation Date:</b> March 31, 2024</div>
<div><b>Created By:</b> Mohammed Amaan</div>
<div><a href="https://app.tango.us/app/workflow/9fea3130-e2fc-4ed2-9d52-fbf31825c9f5?utm_source=magicCopy&utm_medium=magicCopy&utm_campaign=workflow%20export%20links" target="_blank">View most recent version on Tango.us</a></div>
<div style="height: 24px">&#8203;</div>
<hr />
<div style="height: 24px">&#8203;</div>


<div><h2><a href="http://localhost/LGMS/psychology/finding-books-psychology"># Navigate to the required box</a></h2></div>

<div><h3>1. Click on Add Media</h3>
<img src="https://images.tango.us/workflows/9fea3130-e2fc-4ed2-9d52-fbf31825c9f5/steps/b7ade9c2-35ac-4a75-894f-17eb917be732/c1ad845a-0c14-4434-adb9-603115414357.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.3708&fp-y=0.7637&fp-z=2.6461&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=476&mark-y=451&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz0yNDcmaD03OSZmaXQ9Y3JvcCZjb3JuZXItcmFkaXVzPTEw" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Click on Add Media" />
</div>

<div><h3>2. Select the media you would like to upload by name</h3>
<p>Additionally if you do not have any media uploaded click on create new media and then the box the says " + add new media"</p><p></p><p>The Media can be of the type </p><ul><li><p> <a target="_blank" rel="noopener noreferrer nofollow" class="admin-item__link" href="http://localhost/LGMS/media/add/audio"><strong>Audio </strong></a> A locally hosted audio file.</p></li><li><p><a target="_blank" rel="noopener noreferrer nofollow" class="admin-item__link" href="http://localhost/LGMS/media/add/document"><strong>Document </strong></a>An uploaded file or document, such as a PDF.</p></li><li><p><a target="_blank" rel="noopener noreferrer nofollow" class="admin-item__link" href="http://localhost/LGMS/media/add/image"><strong>Image </strong></a>Use local images for reusable media.</p></li><li><p><a target="_blank" rel="noopener noreferrer nofollow" class="admin-item__link" href="http://localhost/LGMS/media/add/remote_video"><strong>Remote video </strong></a>A remotely hosted video from YouTube or Vimeo.</p></li><li><p><a target="_blank" rel="noopener noreferrer nofollow" class="admin-item__link" href="http://localhost/LGMS/media/add/video"><strong>Video </strong></a>A locally hosted video file.</p></li></ul>
<img src="https://images.tango.us/workflows/9fea3130-e2fc-4ed2-9d52-fbf31825c9f5/steps/4e604438-3a05-4f8e-b075-fde56e527e87/b00e41e1-0c52-41eb-bcca-7a1a1423f3f9.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.5005&fp-y=0.4091&fp-z=1.1706&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=102&mark-y=439&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz05OTcmaD02MSZmaXQ9Y3JvcCZjb3JuZXItcmFkaXVzPTEw" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Select the media you would like to upload by name" />
</div>

<div><h3>3. Uncheck Use Media Default name</h3>
<img src="https://images.tango.us/workflows/9fea3130-e2fc-4ed2-9d52-fbf31825c9f5/steps/dce3754c-485c-4091-b85f-dc1bee1113f3/8e3bb108-5ecb-4dde-a0df-c361d28879a4.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.1554&fp-y=0.5022&fp-z=3.0905&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=540&mark-y=454&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz03MSZoPTcxJmZpdD1jcm9wJmNvcm5lci1yYWRpdXM9MTA%3D" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Uncheck Use Media Default name" />
</div>

<div><h3>4. Add a different tittle if you wish to</h3>
<img src="https://images.tango.us/workflows/9fea3130-e2fc-4ed2-9d52-fbf31825c9f5/steps/1ed70726-9407-41a5-89fa-25887d833df5/5e3d27a3-2be5-4f8c-80a0-cbaa4f7ae66a.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.5005&fp-y=0.5954&fp-z=1.1706&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=102&mark-y=486&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz05OTcmaD02MSZmaXQ9Y3JvcCZjb3JuZXItcmFkaXVzPTEw" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Add a different tittle if you wish to" />
</div>

<div><h3>5. Click on Save</h3>
<img src="https://images.tango.us/workflows/9fea3130-e2fc-4ed2-9d52-fbf31825c9f5/steps/8ca8087f-0fdd-448c-abe1-7bee4ffa5ecc/8e2dd2cd-c544-4e48-8be7-9a14122ac3bb.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.8130&fp-y=0.7637&fp-z=3.7595&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=439&mark-y=380&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz0zMjMmaD0yMTkmZml0PWNyb3AmY29ybmVyLXJhZGl1cz0xMA%3D%3D" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Click on Save" />
</div>

<br/>
<hr/>
<div>
<span>Created with </span><a href="https://tango.us?utm_source=magicCopy&utm_medium=magicCopy&utm_campaign=workflow%20export%20links" target="_blank" style="color: #256EFF">Tango.us
    </a>
</div>';

    $add_guide = '<h1><a href="https://app.tango.us/app/workflow/2433334b-218b-449e-ac47-cad62f893575?utm_source=magicCopy&utm_medium=magicCopy&utm_campaign=workflow%20export%20links" target="_blank">Creating a Guide</a></h1>
<div><b>Creation Date:</b> March 25, 2024</div>
<div><b>Created By:</b> Mohammed Amaan</div>
<div><a href="https://app.tango.us/app/workflow/2433334b-218b-449e-ac47-cad62f893575?utm_source=magicCopy&utm_medium=magicCopy&utm_campaign=workflow%20export%20links" target="_blank">View most recent version on Tango.us</a></div>
<div style="height: 24px">&#8203;</div>
<hr />
<div style="height: 24px">&#8203;</div>


<div><h2><a href="http://localhost/LGMS/lgms"># LGMS Module Landing Page | lgms</a></h2><p>Welcome to Library Guide Management System Setting up a new guide Help. Follow the steps below to create your first guide. </p>
</div>

<div><h3>1. Click on My Dashboard</h3>
<img src="https://images.tango.us/workflows/2433334b-218b-449e-ac47-cad62f893575/steps/d9e4bfb7-83b3-4425-94e2-5f60d9776602/a1194031-e14f-473b-ba43-431915d1ca37.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.8818&fp-y=0.3743&fp-z=2.8169&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=614&mark-y=414&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz0zNzImaD0xNTImZml0PWNyb3AmY29ybmVyLXJhZGl1cz0xMA%3D%3D" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Click on My Dashboard" />
</div>

<div><h3>2. Click on New</h3>
<img src="https://images.tango.us/workflows/2433334b-218b-449e-ac47-cad62f893575/steps/705953b7-6adc-4787-b923-6b1b050d6398/973e0e93-ff3e-484f-ad14-86bcb0b18772.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.1329&fp-y=0.2593&fp-z=2.7599&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=337&mark-y=429&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz0yMDYmaD0xMjEmZml0PWNyb3AmY29ybmVyLXJhZGl1cz0xMA%3D%3D" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Click on New" />
</div>

<div><h3>3. Type the name of the Subject in our case we will add Psychology </h3>
<img src="https://images.tango.us/workflows/2433334b-218b-449e-ac47-cad62f893575/steps/893b7b96-7bf7-4994-aca4-fbc4f5fc230a/bdd969e3-9a31-477c-85a5-444c3662c3bf.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.5000&fp-y=0.4731&fp-z=1.0521&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=31&mark-y=461&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz0xMTM4Jmg9NTQmZml0PWNyb3AmY29ybmVyLXJhZGl1cz0xMA%3D%3D" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Type the name of the Subject in our case we will add Psychology " />
</div>

<div><h3>4. Type or Paste selected text into element</h3>
<img src="https://images.tango.us/workflows/2433334b-218b-449e-ac47-cad62f893575/steps/50972923-a3cd-4d80-a107-371bd3cb1905/6532afde-cae7-4c6d-bd47-5ef34f5ae694.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.5000&fp-y=0.5039&fp-z=1.0521&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=31&mark-y=370&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz0xMTM4Jmg9MjQxJmZpdD1jcm9wJmNvcm5lci1yYWRpdXM9MTA%3D" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Type or Paste selected text into element" />
</div>

<div><h3>5. Check The Relevant subjects Psychology. Do not remember all you ? No problem you can add them later in the Edit tab.</h3>
<img src="https://images.tango.us/workflows/2433334b-218b-449e-ac47-cad62f893575/steps/23f88a2d-c6d1-400f-9baa-c46979dd5724/cc1a8d57-37ba-43dc-b81d-b16c48ab1414.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.0591&fp-y=0.4618&fp-z=3.0905&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=184&mark-y=454&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz03MSZoPTcxJmZpdD1jcm9wJmNvcm5lci1yYWRpdXM9MTA%3D" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Check The Relevant subjects Psychology. Do not remember all you ? No problem you can add them later in the Edit tab." />
</div>

<div><h3>6. Check Economics</h3>
<img src="https://images.tango.us/workflows/2433334b-218b-449e-ac47-cad62f893575/steps/175cc0f7-1f45-4e3b-bb50-53f706637d9a/b0ee66dc-38a7-4d77-8e87-4004aa1c354b.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.0591&fp-y=0.2250&fp-z=3.0905&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=184&mark-y=454&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz03MSZoPTcxJmZpdD1jcm9wJmNvcm5lci1yYWRpdXM9MTA%3D" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Check Economics" />
</div>

<div><h3>7. Select The Type of the guide</h3>
<p>There are 4 options here to choose from </p><ul><li><p>Course Guide </p></li><li><p>General Purpose Guide</p></li><li><p>Subject Guide </p></li><li><p>Topic Guide </p></li></ul><p>These are not mandatory but helpful.</p>
<img src="https://images.tango.us/workflows/2433334b-218b-449e-ac47-cad62f893575/steps/255f65fd-8186-4858-9f66-d1c2853f1f5f/381f1d88-d933-4e27-8065-c334c623eb70.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.5000&fp-y=0.4304&fp-z=1.0521&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=31&mark-y=417&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz0xMTM4Jmg9NTQmZml0PWNyb3AmY29ybmVyLXJhZGl1cz0xMA%3D%3D" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Select The Type of the guide" />
</div>

<div><h3>8. You can Also add them to a group for better organization</h3>
<p>You will have the option to choose from Group 1-4</p>
<img src="https://images.tango.us/workflows/2433334b-218b-449e-ac47-cad62f893575/steps/09f63e99-e410-4446-90f5-f252bc0c1862/a83770b5-b6f2-4455-a2b6-ef9258c9fc0c.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.5000&fp-y=0.5382&fp-z=1.0521&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=31&mark-y=477&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz0xMTM4Jmg9NTQmZml0PWNyb3AmY29ybmVyLXJhZGl1cz0xMA%3D%3D" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="You can Also add them to a group for better organization" />
</div>

<div><h3>9. Lastly click save to publish the guide</h3>
<img src="https://images.tango.us/workflows/2433334b-218b-449e-ac47-cad62f893575/steps/9ad58dab-5350-47b8-ade9-9496a64d7a40/2ba9f428-b133-4ea6-9f08-8c0789f00b25.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.0797&fp-y=0.8075&fp-z=2.7740&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=165&mark-y=415&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz0yMDEmaD0xNTAmZml0PWNyb3AmY29ybmVyLXJhZGl1cz0xMA%3D%3D" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Lastly click save to publish the guide" />
</div>

<br/>
<hr/>
<div>
<span>Created with </span><a href="https://tango.us?utm_source=magicCopy&utm_medium=magicCopy&utm_campaign=workflow%20export%20links" target="_blank" style="color: #256EFF">Tango.us
    </a>
</div>';

    $reuse_guide = '<h1><a href="https://app.tango.us/app/workflow/1215df59-c463-41b0-8598-f61e3c9ebf86?utm_source=magicCopy&utm_medium=magicCopy&utm_campaign=workflow%20export%20links" target="_blank">Reuse a Guide</a></h1>
<div><b>Creation Date:</b> March 31, 2024</div>
<div><b>Created By:</b> Mohammed Amaan</div>
<div><a href="https://app.tango.us/app/workflow/1215df59-c463-41b0-8598-f61e3c9ebf86?utm_source=magicCopy&utm_medium=magicCopy&utm_campaign=workflow%20export%20links" target="_blank">View most recent version on Tango.us</a></div>
<div style="height: 24px">&#8203;</div>
<hr />
<div style="height: 24px">&#8203;</div>


<div><h2><a href="http://localhost/LGMS/lgms/dashboard"># Navigate to the Dashboard Overview</a></h2></div>

<div><h3>1. Click on Reuse Guide</h3>
<img src="https://images.tango.us/workflows/1215df59-c463-41b0-8598-f61e3c9ebf86/steps/99096531-94fe-4499-b913-b68bca5c73c6/a9a64162-3459-467a-bd4c-f3b745675475.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.2191&fp-y=0.2593&fp-z=2.4500&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=441&mark-y=436&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz0zMTgmaD0xMDgmZml0PWNyb3AmY29ybmVyLXJhZGl1cz0xMA%3D%3D" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Click on Reuse Guide" />
</div>

<div><h3>2. Select the guide to be reused</h3>
<img src="https://images.tango.us/workflows/1215df59-c463-41b0-8598-f61e3c9ebf86/steps/fd1b5b98-905d-464b-b24f-bff1aee2f7ad/3ec04f52-0b86-4156-b61d-e69222e3ad33.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.5000&fp-y=0.4035&fp-z=1.0521&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=31&mark-y=389&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz0xMTM4Jmg9NTQmZml0PWNyb3AmY29ybmVyLXJhZGl1cz0xMA%3D%3D" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Select the guide to be reused" />
</div>

<div><h3>3. Click on Reuse Guide</h3>
<img src="https://images.tango.us/workflows/1215df59-c463-41b0-8598-f61e3c9ebf86/steps/4810fb0e-bed3-4bc9-91f2-656df3eba043/28cf6125-1c84-4507-bf09-b9df17770514.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.1123&fp-y=0.4843&fp-z=2.5006&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=187&mark-y=423&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz0zMDAmaD0xMzUmZml0PWNyb3AmY29ybmVyLXJhZGl1cz0xMA%3D%3D" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Click on Reuse Guide" />
</div>

<div><h3>4. Here you can change the title as well as description of the guide before reusing it.</h3>
<img src="https://images.tango.us/workflows/1215df59-c463-41b0-8598-f61e3c9ebf86/steps/4ef2a40c-72bf-4df9-a483-840adec33a2e/803cc487-8c30-4b2c-ab5a-0f6e6d0f5a4c.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.5000&fp-y=0.3406&fp-z=1.0521&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=31&mark-y=324&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz0xMTM4Jmg9NTQmZml0PWNyb3AmY29ybmVyLXJhZGl1cz0xMA%3D%3D" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Here you can change the title as well as description of the guide before reusing it." />
</div>

<div><h3>5. Check or uncheck subjects that are related to the guide</h3>
<img src="https://images.tango.us/workflows/1215df59-c463-41b0-8598-f61e3c9ebf86/steps/a1a0171a-d478-4885-b923-788791eb3d51/35481831-914a-4abb-aeef-9dd4771b6c07.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.0591&fp-y=0.3114&fp-z=3.0905&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=184&mark-y=454&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz03MSZoPTcxJmZpdD1jcm9wJmNvcm5lci1yYWRpdXM9MTA%3D" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Check or uncheck subjects that are related to the guide" />
</div>

<div><h3>6. Click on URL alias</h3>
<img src="https://images.tango.us/workflows/1215df59-c463-41b0-8598-f61e3c9ebf86/steps/30b4d61e-e7fa-4bdc-8734-3f3ca7ebf448/bb7bf288-e6c7-4d29-8c01-962135fe58ec.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.1641&fp-y=0.4826&fp-z=1.9030&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=117&mark-y=398&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz01MTUmaD0xODQmZml0PWNyb3AmY29ybmVyLXJhZGl1cz0xMA%3D%3D" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Click on URL alias" />
</div>

<div><h3>7. Type new url alias as the previous one is in use by the original guide</h3>
<img src="https://images.tango.us/workflows/1215df59-c463-41b0-8598-f61e3c9ebf86/steps/d2e2f1b7-ced9-4882-8ac5-d0a3bf741e28/2b0f411b-042e-41a2-92a5-fa6afeb32a9e.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.6100&fp-y=0.3283&fp-z=1.4132&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=61&mark-y=418&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz0xMDc3Jmg9NzMmZml0PWNyb3AmY29ybmVyLXJhZGl1cz0xMA%3D%3D" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Type new url alias as the previous one is in use by the original guide" />
</div>

<div><h3>8. Click on Save to publish the guide.</h3>
<img src="https://images.tango.us/workflows/1215df59-c463-41b0-8598-f61e3c9ebf86/steps/bbc293e9-d200-4581-9ed4-8b30306667ca/5dd39d3c-b242-48fe-a25a-144f6872227f.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.0797&fp-y=0.7626&fp-z=2.7740&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=165&mark-y=415&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz0yMDEmaD0xNTAmZml0PWNyb3AmY29ybmVyLXJhZGl1cz0xMA%3D%3D" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Click on Save to publish the guide." />
</div>

<br/>
<hr/>
<div>
<span>Created with </span><a href="https://tango.us?utm_source=magicCopy&utm_medium=magicCopy&utm_campaign=workflow%20export%20links" target="_blank" style="color: #256EFF">Tango.us
    </a>
</div>';

    $add_page = '<h1><a href="https://app.tango.us/app/workflow/718b2df1-f17d-40cc-8359-44062a5011d8?utm_source=magicCopy&utm_medium=magicCopy&utm_campaign=workflow%20export%20links" target="_blank">How to Add a Page To a Guide</a></h1>
<div><b>Creation Date:</b> March 31, 2024</div>
<div><b>Created By:</b> Mohammed Amaan</div>
<div><a href="https://app.tango.us/app/workflow/718b2df1-f17d-40cc-8359-44062a5011d8?utm_source=magicCopy&utm_medium=magicCopy&utm_campaign=workflow%20export%20links" target="_blank">View most recent version on Tango.us</a></div>
<div style="height: 24px">&#8203;</div>
<hr />
<div style="height: 24px">&#8203;</div>


<div><h2><a href="http://localhost/LGMS/psychology"># Navigate to the Guide from the Dashboard or Create a new guide</a></h2></div>

<div><h3>1. Click on Create/Reuse Guide Page</h3>
<img src="https://images.tango.us/workflows/718b2df1-f17d-40cc-8359-44062a5011d8/steps/8b7fe742-3586-4071-a7ba-93e05b2bb710/ad7725ff-6685-4be4-bc3a-217deb2d92d6.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.1425&fp-y=0.3827&fp-z=1.9754&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=93&mark-y=455&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz00ODkmaD03MCZmaXQ9Y3JvcCZjb3JuZXItcmFkaXVzPTEw" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Click on Create/Reuse Guide Page" />
</div>

<div><h3>2. Input your title</h3>
<img src="https://images.tango.us/workflows/718b2df1-f17d-40cc-8359-44062a5011d8/steps/5581320d-59d1-4609-9c5f-15d1aabc60ba/c8dbbd8f-1137-4b8f-8083-7b01a4a14b7a.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.5005&fp-y=0.3013&fp-z=1.1706&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=102&mark-y=315&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz05OTcmaD02MSZmaXQ9Y3JvcCZjb3JuZXItcmFkaXVzPTEw" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Input your title" />
</div>

<div><h3>3. Input the Description</h3>
<p>If you would like no description for the page select the Hide Description option.</p>
<img src="https://images.tango.us/workflows/718b2df1-f17d-40cc-8359-44062a5011d8/steps/9b4377ca-aa3b-440f-8246-68f6ab18a1eb/9b75dae1-d207-43f6-9d18-8b5c3a428c19.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.5005&fp-y=0.5814&fp-z=1.1706&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=102&mark-y=366&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz05OTcmaD0yNjgmZml0PWNyb3AmY29ybmVyLXJhZGl1cz0xMA%3D%3D" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Input the Description" />
</div>

<div><h3>4. Click on Create Page to Have your Page Created</h3>
<img src="https://images.tango.us/workflows/718b2df1-f17d-40cc-8359-44062a5011d8/steps/d387f0dd-6d40-4b87-a65d-af8a4b1130b0/c756c83f-c8ec-4213-a5b6-31961f2883d3.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.2209&fp-y=0.8704&fp-z=2.3754&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=428&mark-y=609&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz0zNDUmaD0xMzgmZml0PWNyb3AmY29ybmVyLXJhZGl1cz0xMA%3D%3D" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Click on Create Page to Have your Page Created" />
</div>

<div><h3>5. Navigation to Recently Created Page </h3>
<p>To navigate to the recently created page click on the the page title to be redirected to the page.</p>
<img src="https://images.tango.us/workflows/718b2df1-f17d-40cc-8359-44062a5011d8/steps/7713af79-8b1b-4b3a-b7be-87e7d171033e/e1c585a9-1865-4271-91d5-8f76ee54bc4d.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.1370&fp-y=0.6033&fp-z=2.1634&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=145&mark-y=458&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz00MjEmaD02NCZmaXQ9Y3JvcCZjb3JuZXItcmFkaXVzPTEw" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Navigation to Recently Created Page " />
</div>

<div><h3>6. Here is what the Page looks Like after creation</h3>
<p>You can start adding More Content by adding boxes. </p>
<img src="https://images.tango.us/workflows/718b2df1-f17d-40cc-8359-44062a5011d8/steps/ad66da39-5d27-4803-9a5d-f56bc2bcce1c/178159ef-3e67-431a-96a4-9b47bc9a053d.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.5000&fp-y=0.5982&fp-z=1.0037&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=2&mark-y=285&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz0xMTk2Jmg9NjAxJmZpdD1jcm9wJmNvcm5lci1yYWRpdXM9MTA%3D" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Here is what the Page looks Like after creation" />
</div>

<br/>
<hr/>
<div>
<span>Created with </span><a href="https://tango.us?utm_source=magicCopy&utm_medium=magicCopy&utm_campaign=workflow%20export%20links" target="_blank" style="color: #256EFF">Tango.us
    </a>
</div>';

    $reuse_page = '<h1><a href="https://app.tango.us/app/workflow/a964e0e7-53e1-4f98-929a-13ffbcc002e2?utm_source=magicCopy&utm_medium=magicCopy&utm_campaign=workflow%20export%20links" target="_blank">Reuse a Page</a></h1>
<div><p>A page that is existing in some other guide can be reused again by doing the steps below. <br>Reusing the Page gives you the option to import all the exiting content of a page.</p></div>
<div><b>Creation Date:</b> March 31, 2024</div>
<div><b>Created By:</b> Mohammed Amaan</div>
<div><a href="https://app.tango.us/app/workflow/a964e0e7-53e1-4f98-929a-13ffbcc002e2?utm_source=magicCopy&utm_medium=magicCopy&utm_campaign=workflow%20export%20links" target="_blank">View most recent version on Tango.us</a></div>
<div style="height: 24px">&#8203;</div>
<hr />
<div style="height: 24px">&#8203;</div>


<div><h2><a href="http://localhost/LGMS/psychology"># Navigate to the Required guide</a></h2></div>

<div><h3>1. Click on Create/Reuse Guide Page</h3>
<img src="https://images.tango.us/workflows/a964e0e7-53e1-4f98-929a-13ffbcc002e2/steps/b2924888-5eb4-4bb6-9a77-95c87a377eea/3fadcaba-127c-4cea-b6f9-90973f6a1049.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.1425&fp-y=0.6947&fp-z=1.9754&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=93&mark-y=455&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz00ODkmaD03MCZmaXQ9Y3JvcCZjb3JuZXItcmFkaXVzPTEw" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Click on Create/Reuse Guide Page" />
</div>

<div><h3>2. Click on Reuse Guide Page</h3>
<img src="https://images.tango.us/workflows/a964e0e7-53e1-4f98-929a-13ffbcc002e2/steps/fec62b3f-32ea-422b-a5c8-ecd889f7e64f/ff76b24e-a179-4272-9fe4-092a13d3fc4f.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.3698&fp-y=0.1661&fp-z=2.2072&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=397&mark-y=303&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz00MDUmaD0xMTImZml0PWNyb3AmY29ybmVyLXJhZGl1cz0xMA%3D%3D" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Click on Reuse Guide Page" />
</div>

<div><h3>3. Select The page to reused from existing guides</h3>
<img src="https://images.tango.us/workflows/a964e0e7-53e1-4f98-929a-13ffbcc002e2/steps/83ac1753-1633-481d-874d-587e6092ea0d/00e4f53e-c52e-4f71-af74-f23f8658fec7.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.5005&fp-y=0.2565&fp-z=1.1706&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=102&mark-y=264&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz05OTcmaD02MSZmaXQ9Y3JvcCZjb3JuZXItcmFkaXVzPTEw" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Select The page to reused from existing guides" />
</div>

<div><h3>4. check Include Subpages if you would also like to import the subpages of the page</h3>
<p>Note: This option will not be available if the importing page is a subpage already.</p>
<img src="https://images.tango.us/workflows/a964e0e7-53e1-4f98-929a-13ffbcc002e2/steps/3b9137fe-0d10-44b1-96b6-0d5c1d0e4091/6b9546db-e22e-41d1-9e92-441aee6878a0.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.1554&fp-y=0.4371&fp-z=3.0905&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=540&mark-y=454&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz03MSZoPTcxJmZpdD1jcm9wJmNvcm5lci1yYWRpdXM9MTA%3D" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="check Include Subpages if you would also like to import the subpages of the page" />
</div>

<div><h3>5. Select Link: By selecting this, a link to the HTML item will be created. it will be un-editable from this box</h3>
<img src="https://images.tango.us/workflows/a964e0e7-53e1-4f98-929a-13ffbcc002e2/steps/0d1fdfe0-f036-4562-8870-2794e06568d9/bd7831df-380e-4c2e-8997-0992380921ac.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.1554&fp-y=0.4933&fp-z=3.0905&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=540&mark-y=454&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz03MSZoPTcxJmZpdD1jcm9wJmNvcm5lci1yYWRpdXM9MTA%3D" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Select Link: By selecting this, a link to the HTML item will be created. it will be un-editable from this box" />
</div>

<div><h3>6. Enter a Title </h3>
<img src="https://images.tango.us/workflows/a964e0e7-53e1-4f98-929a-13ffbcc002e2/steps/686aaef5-7263-412c-a615-50cfce8ebebb/2e8b41ef-2c8d-40ac-b306-4cfe3d68062b.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.5005&fp-y=0.5864&fp-z=1.1706&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=102&mark-y=475&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz05OTcmaD02MSZmaXQ9Y3JvcCZjb3JuZXItcmFkaXVzPTEw" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Enter a Title " />
</div>

<div><h3>7. Use Position to determine if it is a top level page or if it goes under an existing page.</h3>
<img src="https://images.tango.us/workflows/a964e0e7-53e1-4f98-929a-13ffbcc002e2/steps/02ab8089-0e69-43ba-97bd-87eb4bae54c6/9db85022-2f86-4f55-966c-5674a95f97d3.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.5000&fp-y=0.5000&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Use Position to determine if it is a top level page or if it goes under an existing page." />
</div>

<div><h3>8. Click on Reuse Guide Page</h3>
<img src="https://images.tango.us/workflows/a964e0e7-53e1-4f98-929a-13ffbcc002e2/steps/2d103856-4f4f-4d16-9b20-08f28797827a/b7995999-1441-49fe-a8c0-04cebb285a2a.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.2415&fp-y=0.7593&fp-z=2.1634&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=389&mark-y=427&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz00MjEmaD0xMjYmZml0PWNyb3AmY29ybmVyLXJhZGl1cz0xMA%3D%3D" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Click on Reuse Guide Page" />
</div>

<br/>
<hr/>
<div>
<span>Created with </span><a href="https://tango.us?utm_source=magicCopy&utm_medium=magicCopy&utm_campaign=workflow%20export%20links" target="_blank" style="color: #256EFF">Tango.us
    </a>
</div>';

    $add_box = '<h1><a href="https://app.tango.us/app/workflow/a7aa6b7d-ab80-4419-ad9f-6463322ad59f?utm_source=magicCopy&utm_medium=magicCopy&utm_campaign=workflow%20export%20links" target="_blank">Creating A Box</a></h1>
<div><b>Creation Date:</b> March 31, 2024</div>
<div><b>Created By:</b> Mohammed Amaan</div>
<div><a href="https://app.tango.us/app/workflow/a7aa6b7d-ab80-4419-ad9f-6463322ad59f?utm_source=magicCopy&utm_medium=magicCopy&utm_campaign=workflow%20export%20links" target="_blank">View most recent version on Tango.us</a></div>
<div style="height: 24px">&#8203;</div>
<hr />
<div style="height: 24px">&#8203;</div>


<div><h2><a href="http://localhost/LGMS/psychology/finding-books-psychology"># Navigate to the Page where you would like to add the box </a></h2></div>

<div><h3>1. Click on Create/Reuse Guide Box</h3>
<img src="https://images.tango.us/workflows/a7aa6b7d-ab80-4419-ad9f-6463322ad59f/steps/079255f6-0e9f-4e02-8bc2-8182bf4035c5/3932ac41-b021-4cff-bf65-79a90b26c61e.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.3799&fp-y=0.6919&fp-z=2.1464&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=386&mark-y=458&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz00MjcmaD02NCZmaXQ9Y3JvcCZjb3JuZXItcmFkaXVzPTEw" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Click on Create/Reuse Guide Box" />
</div>

<div><h3>2. Enter Your Title</h3>
<img src="https://images.tango.us/workflows/a7aa6b7d-ab80-4419-ad9f-6463322ad59f/steps/a102b58f-4d40-47b1-9ad0-2c7c56cb0c29/9fb9745b-ec03-471c-97da-63e41182e860.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.5005&fp-y=0.4910&fp-z=1.1706&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=102&mark-y=460&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz05OTcmaD02MSZmaXQ9Y3JvcCZjb3JuZXItcmFkaXVzPTEw" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Enter Your Title" />
</div>

<div><h3>3. Click on Save</h3>
<img src="https://images.tango.us/workflows/a7aa6b7d-ab80-4419-ad9f-6463322ad59f/steps/46454f15-21e6-4e36-ae18-b073fda169be/c3bc9739-3f8a-4113-a962-e35790afc6c4.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.1962&fp-y=0.6347&fp-z=2.6918&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=485&mark-y=412&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz0yMzEmaD0xNTcmZml0PWNyb3AmY29ybmVyLXJhZGl1cz0xMA%3D%3D" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Click on Save" />
</div>

<br/>
<hr/>
<div>
<span>Created with </span><a href="https://tango.us?utm_source=magicCopy&utm_medium=magicCopy&utm_campaign=workflow%20export%20links" target="_blank" style="color: #256EFF">Tango.us
    </a>
</div>';

    $reuse_html = '<h1><a href="https://app.tango.us/app/workflow/196d5909-5060-4564-b332-cfb65402a66e?utm_source=magicCopy&utm_medium=magicCopy&utm_campaign=workflow%20export%20links" target="_blank">Reuse an HTML</a></h1>
<div><p>This is the place where text block or HTML blocks can be reused from the same guide or some other guide</p></div>
<div><b>Creation Date:</b> March 31, 2024</div>
<div><b>Created By:</b> Mohammed Amaan</div>
<div><a href="https://app.tango.us/app/workflow/196d5909-5060-4564-b332-cfb65402a66e?utm_source=magicCopy&utm_medium=magicCopy&utm_campaign=workflow%20export%20links" target="_blank">View most recent version on Tango.us</a></div>
<div style="height: 24px">&#8203;</div>
<hr />
<div style="height: 24px">&#8203;</div>


<div><h2><a href="http://localhost/LGMS/business-administration/finding-journal-articles-business-administration"># Navigate to the box that is going to reuse the content</a></h2></div>

<div><h3>1. Click on Add/Reuse HTML</h3>
<img src="https://images.tango.us/workflows/196d5909-5060-4564-b332-cfb65402a66e/steps/de79b58d-8260-44bf-a7ff-e34dcb308b7d/ef79f322-1097-46b3-8bc1-bd576499a772.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.3932&fp-y=0.6470&fp-z=2.3651&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=426&mark-y=455&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz0zNDkmaD03MCZmaXQ9Y3JvcCZjb3JuZXItcmFkaXVzPTEw" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Click on Add/Reuse HTML" />
</div>

<div><h3>2. Click on Reuse HTML</h3>
<img src="https://images.tango.us/workflows/196d5909-5060-4564-b332-cfb65402a66e/steps/adbf34b6-94fb-43c4-9c60-ab8b99d59864/db7f8c99-558f-4d9a-90e2-d5c00c2c846f.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.2961&fp-y=0.1672&fp-z=2.4068&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=433&mark-y=334&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz0zMzQmaD0xMjImZml0PWNyb3AmY29ybmVyLXJhZGl1cz0xMA%3D%3D" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Click on Reuse HTML" />
</div>

<div><h3>3. Select the required block from Select HTML Item</h3>
<img src="https://images.tango.us/workflows/196d5909-5060-4564-b332-cfb65402a66e/steps/8b801791-daba-4836-bc28-829ea0311d5b/fae176e5-57f5-40c5-a332-14b02cb6f2b8.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.5005&fp-y=0.2576&fp-z=1.1706&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=102&mark-y=265&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz05OTcmaD02MSZmaXQ9Y3JvcCZjb3JuZXItcmFkaXVzPTEw" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Select the required block from Select HTML Item" />
</div>

<div><h3>4. Click on Save</h3>
<img src="https://images.tango.us/workflows/196d5909-5060-4564-b332-cfb65402a66e/steps/505fb517-5c70-47f9-b546-01dc0dd85435/73023423-ec38-4a6f-8cb5-21d92aa779ef.png?fm=png&crop=focalpoint&fit=crop&fp-x=0.1962&fp-y=0.8861&fp-z=2.6918&w=1200&border=2%2CF4F2F7&border-radius=8%2C8%2C8%2C8&border-radius-inner=8%2C8%2C8%2C8&blend-align=bottom&blend-mode=normal&blend-x=0&blend-w=1200&blend64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL21hZGUtd2l0aC10YW5nby13YXRlcm1hcmstdjIucG5n&mark-x=485&mark-y=601&m64=aHR0cHM6Ly9pbWFnZXMudGFuZ28udXMvc3RhdGljL2JsYW5rLnBuZz9tYXNrPWNvcm5lcnMmYm9yZGVyPTYlMkNGRjc0NDImdz0yMzEmaD0xNTcmZml0PWNyb3AmY29ybmVyLXJhZGl1cz0xMA%3D%3D" style="border-radius: 8px; border: 1px solid #F4F2F7;" width="600" alt="Click on Save" />
</div>

<br/>
<hr/>
<div>
<span>Created with </span><a href="https://tango.us?utm_source=magicCopy&utm_medium=magicCopy&utm_campaign=workflow%20export%20links" target="_blank" style="color: #256EFF">Tango.us
    </a>
</div>';

    $the_html_content_node = $this->createHtmlItemNode($html_box_item->id(), 'Add HTML Content', $add_html);
    $book_html_content_node = $this->createHtmlItemNode($book_box_item->id(), 'Add Book HTML Content', $add_book);
    $database_html_content_node = $this->createHtmlItemNode($database_box_item->id(), 'Add Database HTML Content', $add_database);
    $media_html_content_node = $this->createHtmlItemNode($media_box_item->id(), 'Add Media HTML Content', $add_media);
    $create_box_html_content_node = $this->createHtmlItemNode($create_box_box_item->id(), 'Create Box HTML Content', $add_box);
    $create_guide_html_content_node = $this->createHtmlItemNode($create_guide_box_item->id(), 'Create Guide HTML Content', $add_guide);
    $create_page_html_content_node = $this->createHtmlItemNode($create_page_box_item->id(), 'Create Page HTML Content', $add_page);
    $reuse_guide_html_content_node = $this->createHtmlItemNode($reuse_guide_box_item->id(), 'Reuse Guide HTML Content', $reuse_guide);
    $reuse_html_html_content_node = $this->createHtmlItemNode($reuse_html_box_item->id(), 'Reuse HTML HTML Content', $reuse_html);
    $reuse_page_html_content_node = $this->createHtmlItemNode($reuse_page_box_item->id(), 'Reuse Page HTML Content', $reuse_page);
    $reuse_database_html_content_node = $this->createHtmlItemNode($reuse_database_box_item->id(), 'Reuse Database HTML Content', $reuse_database);
    $reuse_book_html_content_node = $this->createHtmlItemNode($reuse_book_box_item->id(), 'Reuse Book HTML Content', $reuse_book);


    $add_html_node = $the_html_content_node->id();
    $reuse_html_node = $reuse_html_html_content_node->id();
    $add_book_node = $book_html_content_node->id();
    $reuse_book_node = $reuse_book_html_content_node->id();
    $add_database_node = $database_html_content_node->id();
    $reuse_database_node = $reuse_database_html_content_node->id();
    $add_media_node = $media_html_content_node->id();
    $add_guide_node = $create_guide_html_content_node->id();
    $reuse_guide_node = $reuse_guide_html_content_node->id();
    $add_guide_page_node = $create_page_html_content_node->id();
    $reuse_guide_page_node = $reuse_page_html_content_node->id();
    $add_box_node = $create_box_html_content_node->id();
    // reuse box needed left

    $the_html_content = $this->createHtmlItemNode($html_box_item->id(), 'HTML Content', '<p><a href="/node/' . $add_html_node . '">"Add an HTML" tutorial</a></p>');
    $book_html_content = $this->createHtmlItemNode($book_box_item->id(), 'Book HTML Content', '<p><a href="/node/' . $add_book_node . '">"Add a Book" tutorial</a></p>');
    $database_html_content = $this->createHtmlItemNode($database_box_item->id(), 'Database HTML Content', '<p><a href="/node/' . $add_database_node . '">"Add a Database" tutorial</a></p>');
    $media_html_content = $this->createHtmlItemNode($media_box_item->id(), 'Media HTML Content', '<p><a href="/node/' . $add_media_node . '">"Add a Media" tutorial</a></p>');
    $create_box_html_content = $this->createHtmlItemNode($create_box_box_item->id(), 'Create Box HTML Content', '<p><a href="/node/' . $add_box_node . '">"Add a Box" tutorial</a></p>' );
    $create_guide_html_content = $this->createHtmlItemNode($create_guide_box_item->id(), 'Create Guide HTML Content', '<p><a href="/node/' . $add_guide_node . '">"Add a Guide" tutorial</a></p>');
    $create_page_html_content = $this->createHtmlItemNode($create_page_box_item->id(), 'Create Page HTML Content', '<p><a href="/node/' . $add_guide_page_node . '">"Add a Page" tutorial</a></p>');
    $reuse_guide_html_content = $this->createHtmlItemNode($reuse_guide_box_item->id(), 'Reuse Guide HTML Content', '<p><a href="/node/' . $reuse_guide_node . '">"Reuse a Guide" tutorial</a></p>');
    $reuse_html_html_content = $this->createHtmlItemNode($reuse_html_box_item->id(), 'Reuse HTML HTML Content', '<p><a href="/node/' . $reuse_html_node . '">"Reuse an HTML" tutorial</a></p>');
    $reuse_page_html_content = $this->createHtmlItemNode($reuse_page_box_item->id(), 'Reuse Page HTML Content', '<p><a href="/node/' . $reuse_guide_page_node . '">"Reuse a Page" tutorial</a></p>');
    $reuse_database_html_content = $this->createHtmlItemNode($reuse_database_box_item->id(), 'Reuse Database HTML Content', '<p><a href="/node/' . $reuse_database_node . '">"Reuse a Database" tutorial</a></p>');
    $reuse_book_html_content = $this->createHtmlItemNode($reuse_book_box_item->id(), 'Reuse Book HTML Content', '<p><a href="/node/' . $reuse_book_node . '">"Reuse a Book" tutorial</a></p>');

    // Attach HTML items to box items
    $this->attachHTMLItemsToBoxItems($html_box_item, $the_html_content, $book_box_item, $book_html_content, $database_box_item, $database_html_content, $media_box_item, $media_html_content, $create_box_box_item, $create_box_html_content, $create_guide_box_item, $create_guide_html_content);
    $this->attachHTMLItemsToBoxItems($create_page_box_item, $create_page_html_content, $reuse_guide_box_item, $reuse_guide_html_content, $reuse_html_box_item, $reuse_html_html_content, $reuse_page_box_item, $reuse_page_html_content, $reuse_database_box_item, $reuse_database_html_content, $reuse_book_box_item, $reuse_book_html_content);


    // Update 'Guide Box' with the newly created 'Guide Box Items'
    $this->attachChildBoxItems($html_guide_box, $html_box_item, $book_guide_box, $book_box_item, $database_guide_box, $database_box_item, $media_guide_box, $media_box_item, $create_box_guide_box, $create_box_box_item, $create_guide_box, $create_guide_box_item);
    $this->attachChildBoxItems($create_page_guide_box, $create_page_box_item, $reuse_guide_box, $reuse_guide_box_item, $reuse_html_guide_box, $reuse_html_box_item, $reuse_page_guide_box, $reuse_page_box_item, $reuse_database_guide_box, $reuse_database_box_item, $reuse_book_guide_box, $reuse_book_box_item);


    // Update the 'Help Guide' with the new guide pages
    $this->attachChildNodes($help_guide, 'field_child_pages', [
      $guide_page->id(),
      $guide_page_page->id(),
      $guide_box_page->id(),
      $html_page->id(),
      $book_page->id(),
      $database_page->id(),
      $media_page->id(),
    ]);

    $help_guide->set('status', 0);
    $help_guide->save();

    return [
      'help_guide_id' => $help_guide->id(),
      'add_html_page_id' => $html_page->id(),
      'html_guide_box_id' => $html_guide_box->id(),
      'html_box_item_id' => $html_box_item->id(),
      'html_content_id' => $the_html_content->id(),
      'add_book_page_id' => $book_page->id(),
      'book_guide_box_id' => $book_guide_box->id(),
      'book_box_item_id' => $book_box_item->id(),
      'book_html_content_id' => $book_html_content->id(),
      'add_database_page_id' => $book_page->id(),
      'database_guide_box_id' => $database_guide_box->id(),
      'database_box_item_id' => $database_box_item->id(),
      'database_html_content_id' => $database_html_content->id(),
      'add_media_page_id' => $media_page->id(),
      'media_guide_box_id' => $media_guide_box->id(),
      'media_box_item_id' => $media_box_item->id(),
      'media_html_content_id' => $media_html_content->id(),
      'create_box_page_id' => $guide_box_page->id(),
      'create_box_guide_box_id' => $create_box_guide_box->id(),
      'create_box_box_item_id' => $create_box_box_item->id(),
      'create_box_html_content_id' => $create_box_html_content->id(),
      'create_guide_page_id' => $guide_page->id(),
      'create_guide_box_id' => $create_guide_box->id(),
      'create_guide_box_item_id' => $create_guide_box_item->id(),
      'create_guide_html_content_id' => $create_guide_html_content->id(),
      'create_page_page_id' => $guide_page_page->id(),
      'create_page_guide_box_id' => $create_page_guide_box->id(),
      'create_page_box_item_id' => $create_page_box_item->id(),
      'create_page_html_content_id' => $create_page_html_content->id(),
      'reuse_guide_page_id' => $guide_page->id(),
      'reuse_guide_box_id' => $reuse_guide_box->id(),
      'reuse_guide_box_item_id' => $reuse_guide_box_item->id(),
      'reuse_guide_html_content_id' => $reuse_guide_html_content->id(),
      'reuse_html_page_id' => $html_page->id(),
      'reuse_html_guide_box_id' => $reuse_html_guide_box->id(),
      'reuse_html_box_item_id' => $reuse_html_box_item->id(),
      'reuse_html_html_content_id' => $reuse_html_html_content->id(),
      'reuse_page_page_id' => $guide_page_page->id(),
      'reuse_page_guide_box_id' => $reuse_page_guide_box->id(),
      'reuse_page_box_item_id' => $reuse_page_box_item->id(),
      'reuse_page_html_content_id' => $reuse_page_html_content->id(),
      'reuse_database_page_id' => $database_page->id(),
      'reuse_database_guide_box_id' => $reuse_database_guide_box->id(),
      'reuse_database_box_item_id' => $reuse_database_box_item->id(),
      'reuse_database_html_content_id' => $reuse_database_html_content->id(),
      'reuse_book_page_id' => $book_page->id(),
      'reuse_book_guide_box_id' => $reuse_book_guide_box->id(),
      'reuse_book_box_item_id' => $reuse_book_box_item->id(),
      'reuse_book_html_content_id' => $reuse_book_html_content->id(),
    ];
  }
}

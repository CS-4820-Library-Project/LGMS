<?php


namespace Drupal\lgmsmodule\Controller;

use Drupal\Core\Controller\ControllerBase;

class FirstController extends ControllerBase {


	public function simpleContent() {

		return [
			'#type' => 'markup',
			'#markup' => t('Hello LGMS Module. This is a First test of installation.'),
		];
	}
}

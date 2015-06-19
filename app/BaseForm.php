<?php

namespace App;

use Nette\Application\UI\Form;

class BaseForm extends Form {

	public function __construct() {
		parent::__construct();
		$this->getRenderer()->wrappers['label']['suffix'] = ':';
	}

	public function addError($message) {
		$this->valid = FALSE;

		if ($message !== NULL) {
			$messagePresent = FALSE;
			foreach ($this->parent->template->flashes as $value) {
				if ($message == $value->message) {
					$messagePresent = TRUE;
				}
			}

			if (!$messagePresent) {
				$this->parent->flashMessage($message, "error");
				if ($this->parent->isAjax()) {
					$this->parent->payload->success = 0;
					$this->parent->invalidateControl('flash');
				}
			}
		}
	}

}

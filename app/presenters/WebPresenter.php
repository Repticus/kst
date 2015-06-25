<?php

namespace App\Presenters;

use Nette,
	 App\BaseForm,
	 Nette\Utils\Finder,
	 Nette\Mail\Message,
	 Nette\Mail\SendmailMailer;

//use Nette\Application\Responses\JsonResponse;

class WebPresenter extends Nette\Application\UI\Presenter {

	/** persistent */
	public $client = false;
	public $courses;

	public function beforeRender() {
		$this->template->google = $this->context->parameters['google'];
	}

	public function actionKontaktyTerapeutu() {
		$this->template->therapists = $this->context->parameters['therapist'];
		$this->template->cities = $this->context->parameters['city'];
	}

	public function actionKurzyKraniosakralniTerapie() {
		$this->courses = $this->context->parameters['courses'];
		$now = strtotime(date("d.m.Y"));
		foreach ($this->courses as $key => $course) {
			$date = strtotime(date("d.m.Y", strtotime($course['dates']['0']['date'])));
			if ($now >= $date) {
				$this->courses[$key]['expiration'] = true;
			} else {
				$this->courses[$key]['expiration'] = false;
			}
		}
		$this->template->courses = $this->courses;
		$this->template->addFilter('days', 'WebPresenter::dayFormat');
	}

	public function actionFotoVideo() {
		$dir = 'img/glr/';
		foreach (Finder::findFiles('*.jpg')->in($dir) as $file) {
			$images[] = $dir . $file->getBasename();
		}
		arsort($images);
		$this->template->images = $images;
	}

	public function createVariable() {
		$char = array(1, 2, 3, 4, 5, 6, 7, 8, 9);
		$variable = $char[array_rand($char)];
		array_push($char, 0);
		for ($i = 0; $i < 5; $i++) {
			$variable .= $char[array_rand($char)];
		}
		return $variable;
	}

	public function dayFormat($day) {
		switch ($day) {
			case 1:
				return $day . " den";
			case 2:
			case 3:
			case 4:
				return $day . " dny";
			default:
				return $day . " dnů";
		}
	}

	public function generateCourseDates() {
		foreach ($this->courses as $key => $course) {
			$timestamp = strtotime($course['dates']['0']['date']);
			$date = date("d.m.Y", $timestamp);
			$dates[$key] = $date;
		}
		return $dates;
	}

	public function setPersonalData($form) {
		$fields = array("phone", "city", "profession", "note");
		$personalData['name']['caption'] = 'Jméno a příjmení';
		$personalData['name']['value'] = $form['firstname']->value . " " . $form['lastname']->value;
		foreach ($fields as $fieldName) {
			$field['caption'] = $form[$fieldName]->caption;
			$field['value'] = $form[$fieldName]->value;
			if ($field['value']) {
				$personalData[$fieldName] = $field;
			}
		}
		return $personalData;
	}

	public function setCourseData($courseId) {
		$courseInfo['placeName']['caption'] = 'Místo konání';
		$courseInfo['placeName']['value'] = $this->context->parameters['courses'][$courseId]['place']['name'];
		if (isset($this->context->parameters['courses'][$courseId]['place']['url'])) {
			$courseInfo['placeName']['url'] = $this->context->parameters['courses'][$courseId]['place']['url'];
		}
		$courseInfo['placeStreet']['caption'] = 'Ulice';
		$courseInfo['placeStreet']['value'] = $this->context->parameters['courses'][$courseId]['place']['street'];
		if (isset($this->context->parameters['courses'][$courseId]['place']['map'])) {
			$courseInfo['placeStreet']['url'] = $this->context->parameters['courses'][$courseId]['place']['map'];
		}
		$courseInfo['placeCity']['caption'] = 'Město';
		$courseInfo['placeCity']['value'] = $this->context->parameters['courses'][$courseId]['place']['city'];
		$courseInfo['price']['caption'] = 'Cena kurzu';
		$courseInfo['price']['value'] = $this->context->parameters['courses'][$courseId]['price'] . ' Kč';
		return $courseInfo;
	}

	public function setCourseDays($courseId) {
		foreach ($this->courses[$courseId]['dates'] as $day) {
			$interval['start'] = $day['date'];
			$interval['end'] = $day['end'];
			$days[] = $interval;
		}
		return $days;
	}

	public function setBankData($courseId) {
		$bank = $this->context->parameters['bank'];
		$bankInfo['name']['caption'] = 'Název účtu';
		$bankInfo['name']['value'] = $bank['name'];
		$bankInfo['number']['caption'] = 'Číslo účtu';
		$bankInfo['number']['value'] = $bank['number'];
		$bankInfo['currency']['caption'] = 'Měna';
		$bankInfo['currency']['value'] = $bank['currency'];
		$bankInfo['iban']['caption'] = 'IBAN';
		$bankInfo['iban']['value'] = $bank['iban'];
		$bankInfo['swift']['caption'] = 'BIC(SWIFT)';
		$bankInfo['swift']['value'] = $bank['swift'];
		$bankInfo['variable']['caption'] = 'Variabilní symbol';
		$bankInfo['variable']['value'] = $this->createVariable();
		$bankInfo['deposit']['caption'] = 'Částka k úhradě';
		$bankInfo['deposit']['value'] = $this->context->parameters['courses'][$courseId]['deposit'] . ' Kč';
		return $bankInfo;
	}

	public function setInvoiceData($form) {
		if ($form['invoice']->value) {
			$invoiceData['firm']['caption'] = $form['firm']->caption;
			$invoiceData['firm']['value'] = $form['firm']->value;
			$invoiceData['street']['caption'] = $form['street']->caption;
			$invoiceData['street']['value'] = $form['street']->value;
			$invoiceData['place']['caption'] = 'PSČ a město';
			$invoiceData['place']['value'] = $form['psc']->value . " " . $form['place']->value;

			$fields = array("ic", "dic");
			foreach ($fields as $fieldName) {
				$field['caption'] = $form[$fieldName]->caption;
				$field['value'] = $form[$fieldName]->value;
				if ($field['value']) {
					$invoiceData[$fieldName] = $field;
				}
			}
			return $invoiceData;
		} else {
			return null;
		}
	}

	public function sendReservation($form) {
		$clientMail = $form['email']->value;
		$ownerMail = $this->context->parameters['owner']['mail'];
		$ownerName = $this->context->parameters['owner']['name'];
		$template = $this->createTemplate();
		$template->setFile(__DIR__ . "/../templates/Mail/clientCourseReservation.latte");
		$template->personalData = $this->setPersonalData($form);
		$template->courseData = $this->setCourseData($form['course']->value);
		$template->courseDays = $this->setCourseDays($form['course']->value);
		$template->bankData = $this->setBankData($form['course']->value);
		$template->invoiceData = $this->setInvoiceData($form);
		$mail = new Message;
		$mail->setFrom($ownerMail, $ownerName)
				  ->addTo($clientMail)
				  ->setHtmlBody($template);

		$mailer = new SendmailMailer;
		$mailer->send($mail);

		$template->setFile(__DIR__ . "/../templates/Mail/ownerCourseReservation.latte");
		$mail->setFrom($clientMail)
				  ->clearHeader('To')
				  ->addTo($ownerMail, $ownerName);
		$mailer->send($mail);
	}

	protected function createComponentAuthentication() {
		$form = new BaseForm();
		$form->addText('password', 'Heslo', NULL, 11)
				  ->setAttribute('placeholder', 'heslo')
				  ->setRequired("Pro přehrání videa zadejte heslo.");
		$form->addSubmit('send', 'Přehrát video');
		$form->onSuccess[] = array($this, 'submitAuthentication');

		return $form;
	}

	public function submitAuthentication($form) {
		if ($form['password']->value == "kraniovideo") {
			$this->client = true;
			$flashMessage = "Nyní je možné video přehrát.";
			$this->flashMessage($flashMessage, 'success');
			$this->invalidateControl('video');
			$this->invalidateControl('flash');
		} else {
			$flashMessage = "Heslo není správné.";
			$this->flashMessage($flashMessage, 'error');
			$this->invalidateControl('flash');
		}
	}

	protected function createComponentCourseReservation2() {
		$form = new BaseForm();
		$form->addText('firstname', 'Jméno', NULL, 30)
				  ->setAttribute('placeholder', 'Jan')
				  ->setRequired('%label musí být vyplněno.');
		$form->addText('lastname', 'Příjmení', NULL, 30)
				  ->setAttribute('placeholder', 'Novák')
				  ->setRequired('%label musí být vyplněno.');
		$form->addText('phone', 'Telefon', NULL, 14)
				  ->setAttribute('placeholder', '606111222')
				  ->setRequired('%label musí být vyplněn.')
				  ->addRule(BaseForm::PATTERN, '%label může být buď v místním formátu bez mezer anebo s mezinárodní předvolbou oddělenou mezerou.', '(\+[1-9][0-9]{1,2} )?[1-9][0-9]{8}');
		$form->addText('email', 'Email', NULL, 50)
				  ->setAttribute('placeholder', 'novak@seznam.cz')
				  ->setRequired('%label musí být vyplněn.')
				  ->addRule(BaseForm::EMAIL, '%label není správně vyplněn.');
		$form->addText('city', 'Město', NULL, 30)
				  ->setAttribute('placeholder', 'Praha');
		$form->addText('profession', 'Profese', NULL, 50)
				  ->setAttribute('placeholder', 'Fyzioterapeut');
		$form->addTextArea('note', 'Poznámka', NULL, NULL)
				  ->setAttribute('placeholder', 'Vaše motivace k účasti na kurzu nebo jakýkoli dotaz.')
				  ->addRule(BaseForm::MAX_LENGTH, 'Poznámka je příliš dlouhá', 1000);
		$form->addCheckbox('invoice', 'Chci vystavit fakturu.');
		$form->addText('firm', 'Firma')
				  ->setAttribute('placeholder', 'Název firmy s.r.o.')
				  ->addConditionOn($form['invoice'], BaseForm::EQUAL, TRUE)
				  ->addRule(BaseForm::FILLED, 'Pro vystavení faktury je nutné uvést název firmy.');
		$form->addText('street', 'Ulice')
				  ->setAttribute('placeholder', 'Vratislavská 323/5')
				  ->addConditionOn($form['invoice'], BaseForm::EQUAL, TRUE)
				  ->addRule(BaseForm::FILLED, 'Pro vystavení faktury je nutné uvést ulici sídla firmy.');
		$form->addText('psc', 'PSČ')
				  ->setAttribute('placeholder', '184 04')
				  ->addConditionOn($form['invoice'], BaseForm::EQUAL, TRUE)
				  ->addRule(BaseForm::FILLED, 'Pro vystavení faktury je nutné uvést PSČ sídla firmy.');
		$form->addText('place', 'Město')
				  ->setAttribute('placeholder', 'Praha 5')
				  ->addConditionOn($form['invoice'], BaseForm::EQUAL, TRUE)
				  ->addRule(BaseForm::FILLED, 'Pro vystavení faktury je nutné uvést město sídla firmy.');
		$form->addText('ic', 'IČ')
				  ->setAttribute('placeholder', '11122333')
				  ->addConditionOn($form['invoice'], BaseForm::EQUAL, TRUE)
				  ->addRule(BaseForm::FILLED, 'Pro vystavení faktury je nutné uvést %label.');
		$form->addText('dic', 'DIČ')
				  ->setAttribute('placeholder', 'CZ11122333');
		$form->addHidden('course');
		$form->addSubmit('send', 'Rezervovat');
		$form->onSuccess[] = array($this, 'submitCourseReservation');
		$form->onValidate[] = array($this, 'validateCourseReservation');
		return $form;
	}

	public function validateCourseReservation($form) {
		if ($form['course']->value > 30) {
			$form->addError('Rezervace nebyla odeslána z důvodu nekompatibilty vašeho prohlížeče, kontaktujte prosím provozovatele kurzů.');
		}
	}

	public function submitCourseReservation($form) {
		$this->sendReservation($form);
		$flashMessage = "Děkujeme, Vaše rezervace byla úspěšně přijata. Zadané údaje a platební pokyny Vám byly odeslány na Váš email. Ujistěte se prosím, že Vám byl tento email doručen.";
		$this->flashMessage($flashMessage, 'success');
		if ($this->isAjax()) {
			$this->payload->success = 1;
			$this->invalidateControl('flash');
		}
	}

}

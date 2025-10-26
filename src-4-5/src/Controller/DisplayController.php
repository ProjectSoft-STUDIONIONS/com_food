<?php

namespace ProjectSoft\Component\Food\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;

/**
 * @package     Joomla.Administrator
 * @subpackage  com_food
 *
 * @copyright   Copyright (C) 2008 ProjectSoft. All rights reserved.
 * @license     MIT Lecense; see LICENSE
 */
class DisplayController extends BaseController {
	/**
	 * Представление по умолчанию для метода отображения.
	 *
	 * @var string
	 */
	protected $default_view = 'food';

	public function display($cachable = false, $urlparams = array()) {
		// Установка локали
		setlocale(LC_NUMERIC, 'C');
		return parent::display($cachable, $urlparams);
	}
}

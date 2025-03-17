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

/**
 * Контроллер по умолчанию компонента Food
 *
 * @package     Joomla.Administrator
 * @subpackage  com_food
 */
class DisplayController extends BaseController {
	/**
	 * Представление по умолчанию для метода отображения.
	 *
	 * @var string
	 */
	protected $default_view = 'food';

	public function display($cachable = false, $urlparams = array()) {
		return parent::display($cachable, $urlparams);
	}
}
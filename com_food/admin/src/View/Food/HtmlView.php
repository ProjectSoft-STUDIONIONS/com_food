<?php

namespace ProjectSoft\Component\Food\Administrator\View\Food;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Toolbar\Button\DropdownButton;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
/**
 * @package     Joomla.Administrator
 * @subpackage  com_food
 *
 * @copyright   Copyright (C) 2008 ProjectSoft. All rights reserved.
 * @license     MIT Lecense; see LICENSE
 */

/**
 * Основной вид в админке "Food" 
 */
class HtmlView extends BaseHtmlView {
	
	public $stats = array();
	/**
	 * Отображение основного вида "Food" 
	 *
	 * @param   string  $tpl  Имя файла шаблона для анализа; автоматический поиск путей к шаблону.
	 * @return  void
	 */
	public function display($tpl = null) {
		$this->stats = $this->getStats();
		$this->addToolbar();
		$doc = Factory::getDocument();
		$doc->addScript("/administrator/components/com_food/assets/js/jquery.min.js", array("version" => "auto"), array());
		$doc->addScript("/administrator/components/com_food/assets/js/main.min.js", array("version" => "auto"), array());
		$doc->addStyleSheet("/administrator/components/com_food/assets/css/main.min.css", array("version" => "auto"));
		parent::display($tpl);
	}

	public function getSize($file) {

		$sizes = array('Tb' => 1099511627776, 'Gb' => 1073741824, 'Mb' => 1048576, 'Kb' => 1024, 'b' => 1);
		$precisions = count($sizes) - 1;
		$size = filesize($file);
		foreach ($sizes as $unit => $bytes) {
			if ($size >= $bytes) {
				return number_format($size / $bytes, $precisions) . ' ' . $unit;
			}
			$precisions--;
		}
		return '0 b';
	}

	/**
	 * Вывод времени в определённом формате
	 */
	public function toDateFormat( $timestamp = 0 )
	{
		$dateFormat = 'd-m-Y H:i:s';
		$strTime = date($dateFormat, $timestamp);
		return $strTime;
	}

	/**
	 * Кнопка настройки компонента
	 */
	protected function addToolbar() {
		$ch   = ContentHelper::getActions('com_food');
		$toolbar = Toolbar::getInstance();
		if ($ch->get('core.admin') || $ch->get('core.options')) {
			$toolbar->preferences('com_food');
		}
	}

	/**
	 * Настройки
	 */
	private function getStats() {
		$input = Factory::getApplication()->getInput();
		// Получаем параметры
		$option   = $input->get('option',   'com_food');
		$dir      = $input->get('dir',      '');
		$mode     = $input->get('mode',     '');
		$file     = $input->get('file',     '');
		$new_file = $input->get('new_file', '');
		// Параметры директорий
		$params   = ComponentHelper::getParams('com_food');
		$folders  = $params->get('food_folders', 'food');
		$folders  = preg_split('/[\s,;]+/', $folders);
		$food     = array("food");
		$folders  = array_filter(array_unique(array_merge($food, $folders)));
		sort($folders);
		$stats    = array(
			"option" => $option,
			"dir" => $dir,
			"mode" => $mode,
			"file" => $file,
			"new_file" => $new_file,
			"folders" => $folders,
			"files" => array()
		);
		if($dir):
			// Собрать файлы
		endif;
		return $stats;
	}

	/**
	 * Получение пути файла в правильном формате
	 */
	private function realPath($path = "") {
		$path = rtrim($path, "\\/");
		return str_replace('\\', '/', $path);
	}
}
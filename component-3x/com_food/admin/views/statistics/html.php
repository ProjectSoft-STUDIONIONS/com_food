<?php

defined( '_JEXEC' ) or die( 'Restricted access' ); 

class FoodViewsStatisticsHtml extends JViewHtml
{
	public function render()
	{
		$model = new FoodModelsStatistics();
		$this->stats = $model->getStats();
		$this->addScripts();
		$this->addToolbar();
		return parent::render();
	} 

	protected function addToolbar()
	{
		$app = JFactory::getApplication();
		$canDo  = FoodHelpersFood::getActions();
		$bar = JToolBar::getInstance('toolbar');
		JToolbarHelper::title(JText::_('COM_FOOD_TITLE'), 'folder-open food');
		if ($canDo->get('core.admin'))
		{
			JToolbarHelper::custom(
				'food.github',
				'link',
				'link',
				'GitHub',
				false
			);
			JToolbarHelper::custom(
				'food.cancel',
				'cancel',
				'cancel',
				'Выход',
				false
			);
			JToolbarHelper::divider();
			JToolbarHelper::preferences('com_food');
		}
	}

	// Добавляем свои переменные языка для JS
	private function addScripts(){
		JText::script('COM_FOOD_TITLE');
		JText::script('COM_FOOD_ERROR_MAX_UPLOAD');
		JText::script('COM_FOOD_ERROR_TYPE_UPLOAD');
		JText::script('COM_FOOD_RENAME_QUAERE');
		JText::script('COM_FOOD_RENAME_ERROR');
		JText::script('COM_FOOD_DELETE_QUAERE');
		JText::script('COM_FOOD_EXPORT_XLSX');
		JText::script('COM_FOOD_EXPORT_TO_XLSX');
		JText::script('COM_FOOD_EXPORT_PDF');
		JText::script('COM_FOOD_EXPORT_TO_PDF');
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
	 * Получение пути файла в правильном формате
	 */
	public function realPath($path = "") {
		$path = rtrim($path, "\\/");
		return str_replace('\\', '/', $path);
	}
}
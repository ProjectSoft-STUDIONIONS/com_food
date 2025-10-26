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
		//$app->input->set('hidemainmenu', true);
		$canDo  = FoodHelpersFood::getActions();
		$toolbar = JToolBar::getInstance('toolbar');
		JToolbarHelper::title(JText::_('COM_FOOD_TITLE'), 'folder-open food');
		if ($canDo->get('core.admin'))
		{
			JToolbarHelper::custom(
				'food.cancel',
				'cancel',
				'cancel',
				'Выход',
				false
			);
			JToolbarHelper::divider();
			JToolbarHelper::preferences('com_food');
			// Если $this->stats->update не false
			if($this->stats["update"]):
				$bar = JToolbar::getInstance('toolbar');
				$html = '<button onclick="window.open(\'' . $this->stats["update"] . '\'); return false;" class="btn btn-small"><span class="icon-options" aria-hidden="true"></span>' . JText::_('COM_FOOD_UPDATE') . '</button>';
  				$toolbar->appendButton('Custom', $html);
			endif;
		}
	}

	// Добавляем свои переменные языка для JS
	private function addScripts(){
		$doc = JFactory::getDocument();
		// Стили
		$styles = array(
			"/viewer/app.min.css",
			"/administrator/components/com_food/assets/css/main.min.css"
		);

		// Add langs
		JText::script('COM_FOOD_TITLE');
		JText::script('COM_FOOD_ERROR_MAX_UPLOAD');
		JText::script('COM_FOOD_ERROR_TYPE_UPLOAD');
		JText::script('COM_FOOD_RENAME_QUAERE');
		JText::script('COM_FOOD_RENAME_ERROR');
		JText::script('COM_FOOD_DELETE_QUAERE');
		JText::script('COM_FOOD_EXPORT');
		JText::script('COM_FOOD_EXPORT_XLSX');
		JText::script('COM_FOOD_EXPORT_TO_XLSX');
		JText::script('COM_FOOD_EXPORT_PDF');
		JText::script('COM_FOOD_EXPORT_TO_PDF');
		JText::script('COM_FOOD_DIRECTORY');
		JText::script('COM_FOOD_SELECT_FILES');
		JText::script('COM_FOOD_FILES_UPLOAD');
		JText::script('COM_FOOD_TITLE_DRAG');
		JText::script('COM_FOOD_TITLE_DRAG_BEFORE');
		JText::script('COM_FOOD_TOOLS');
		JText::script('COM_FFOD_TXT_FILES_SELECT');
		JText::script('COM_FOOD_TXT_FILES_ONE');
		JText::script('COM_FOOD_TXT_FILES_TWO');
		JText::script('COM_FOOD_TXT_FILES_THREE');

		// Добавляем стили
		foreach ($styles as $key => $value):
			if(is_file(JPATH_ROOT . $value)):
				$version = filemtime(JPATH_ROOT . $value);
				$doc->addStyleSheet($value, array('version' => $version));
			endif;
		endforeach;
	}

	/**
	 * Получение пути файла в правильном формате
	 */
	public function realPath($path = "") {
		$path = rtrim($path, "\\/");
		return str_replace('\\', '/', $path);
	}
}

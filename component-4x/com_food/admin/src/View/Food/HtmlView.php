<?php

namespace ProjectSoft\Component\Food\Administrator\View\Food;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\LanguageHelper;
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
	 * Разрещённые расширения
	 */
	public $exts = array("xlsx", "pdf");
	/**
	 * Отображение основного вида "Food" 
	 *
	 * @param   string  $tpl  Имя файла шаблона для анализа; автоматический поиск путей к шаблону.
	 * @return  void
	 */

	private $dateTime;

	private $timezone = 0;

	public function display($tpl = null) {
		$this->timezone = Factory::getUser()->getTimezone();
		$this->dateTime = new \DateTime('now', $this->timezone);
		// Всё, что нужно
		$this->stats = $this->getStats();
		// Кнопки
		$this->addToolbar();
		$doc = Factory::getDocument();
		// Добавляем стили
		$doc->addStyleSheet("/viewer/app.min.css", array("version" => "auto"));
		$doc->addStyleSheet("/administrator/components/com_food/assets/css/main.min.css", array("version" => "auto"));
		// Добавляем JS
		$this->addScripts();
		parent::display($tpl);
	}

	public function getSize($file) {

		$sizes      = array('Tb' => 1099511627776, 'Gb' => 1073741824, 'Mb' => 1048576, 'Kb' => 1024, 'b' => 1);
		$precisions = count($sizes) - 1;
		$size       = filesize($file);
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
		$this->dateTime->setTimestamp($timestamp);
		return $this->dateTime->format('d-m-Y H:i:s');
	}

	/**
	 * Получение пути файла в правильном формате
	 */
	public function realPath($path = "") {
		$path = rtrim($path, "\\/");
		return str_replace('\\', '/', $path);
	}

	/**
	 * Кнопка настройки компонента
	 */
	protected function addToolbar() {
		Factory::getApplication()->getInput()->set('hidemainmenu', true);
		$ch      = ContentHelper::getActions('com_food');
		$toolbar = Toolbar::getInstance();
		if ($ch->get('core.admin') || $ch->get('core.options')) {
			ToolbarHelper::custom(
				'food.cancel',
				'cancel',
				'cancel',
				Text::_('COM_FOOD_CLOSE'),
				false
			);
			//$toolbar->cancel('food.cancel');
			$toolbar->divider();
			$toolbar->preferences('com_food');
			// Если $this->stats->update не false
			if($this->stats["update"]):
				// вывести кнопку на скачивание новой версии
				$btn = $toolbar->standardButton('nrecords');
				$btn->icon('fa fa-github');
				$btn->text(Text::_('COM_FOOD_UPDATE'));
				$btn->task('');
				$btn->onclick("window.open('" . $this->stats["update"] . "'); return false;");
				$btn->listCheck(false);
			endif;
		}
	}

	// Добавляем свои переменные языка для JS
	private function addScripts(){
		//Factory::getLanguage();
		Text::script('COM_FOOD_TITLE');
		Text::script('COM_FOOD_ERROR_MAX_UPLOAD');
		Text::script('COM_FOOD_ERROR_TYPE_UPLOAD');
		Text::script('COM_FOOD_RENAME_QUAERE');
		Text::script('COM_FOOD_RENAME_ERROR');
		Text::script('COM_FOOD_DELETE_QUAERE');
		Text::script('COM_FOOD_EXPORT_XLSX');
		Text::script('COM_FOOD_EXPORT_TO_XLSX');
		Text::script('COM_FOOD_EXPORT_PDF');
		Text::script('COM_FOOD_EXPORT_TO_PDF');
		Text::script('COM_FOOD_DIRECTORY');
	}

	// Объединение директорий
	private function path_join() {
		$paths = array();
		foreach (func_get_args() as $arg) {
			if ($arg !== '') { $paths[] = $arg; }
		}
		return preg_replace('#/+#','/', join('/', $paths));
	}

	/**
	 * Настройки
	 */
	private function getStats() {
		$application = Factory::getApplication();
		$input = $application->getInput();
		// Получаем параметры
		$option   = $input->get('option',   'com_food');
		$dir      = $input->get('dir',      '');
		$mode     = $input->get('mode',     '');
		$file     = $input->get('file',     '');
		$new_file = $input->get('new_file', '');
		// Параметры директорий
		$params   = ComponentHelper::getParams('com_food');

		$autodelete  = intval($params->get('food_auto_delete', '0'));
		$autodelete_year  = intval($params->get('food_auto_year', '5'));

		$folders  = $params->get('food_folders', 'food');
		$folders  = preg_split('/[\s,;]+/', $folders);
		$food     = array("food");
		$folders  = array_filter(array_unique(array_merge($food, $folders)));
		sort($folders);
		// Language
		$lang = Factory::getLanguage();
		$re = '/-/';
		$str = $lang->get('tag');
		$subst = "_";
		// Tabs
		$stats    = array(
			"option" => $option,
			"dir" => $dir,
			"mode" => $mode,
			"file" => $file,
			"new_file" => $new_file,
			"folders" => $folders,
			"files" => array(),
			"update" => $this->getUpdate(),
			"lang" => preg_replace($re, $subst, $str)
		);

		if($dir):
			// Поиск файлов в директории
			$files_path = join("/", array(
				$this->realPath(JPATH_ROOT),
				$dir
			));
			$iterators = new \DirectoryIterator($files_path);
			foreach ($iterators as $fileinfo):
				// Если это файл
				if($fileinfo->isFile()):
					$ext = strtolower($fileinfo->getExtension());
					if(in_array($ext, $this->exts)):
						// Проверить дату (год) в имени файла
						$name = $fileinfo->getFilename();
						$re = '/^(?:[\w]+)?(\d{4})/';
						preg_match($re, $name, $matches);
						// Если есть 4 цифры в имени файла и включено автоудаление
						if($matches && $autodelete == 1):
							// Год сейчас
							$year = intval(date("Y", time()));
							// Год в имени файла
							$file_year = intval($matches[1]);
							// Если разница лет больше autodelete_year.
							if($year - $file_year > $autodelete_year):
								// Удаляем файл
								$file_absolute = $this->path_join($files_path, $name);
								@unlink($file_absolute);
								$application->enqueueMessage(Text::sprintf('COM_FOOD_AUTODELETE_FILE', $name), 'message');
							else:
								// Добавляем файл в отображение
								$stats["files"][] = $name;
							endif;
						else:
							// Добавляем файл в отображение
							$stats["files"][] = $name;
						endif;
					endif;
				endif;
			endforeach;
			natsort($stats["files"]);
			$stats["files"] = array_reverse($stats["files"], false);
		endif;
		return $stats;
	}

	private function getUpdate() {
		// Загружаем манифест
		$path = $this->realPath(JPATH_ROOT);
		$manifest = join("/", array(
			$path,
			'administrator/components/com_food/food.xml'
		));
		$manifest = file_get_contents($manifest);
		$manifestData = new \SimpleXMLElement($manifest);
		// Получаем версию компонента
		$componentVersion = (string) $manifestData->version;
		// Временные данные
		$data = new \stdClass;
		// Дата на прошедшие сутки
		$data->date = date( "Y-m-d", time() - 3600 );
		// Тякущая версия компонента
		$data->version = $componentVersion;
		$update = join("/", array(
			$path,
			'administrator/components/com_food/.update.json'
		));

		// Если нет файла запишем данные
		if(!is_file($update)):
			file_put_contents($update, json_encode($data));
		endif;

		// Получить данные из json.
		$data = @file_get_contents($update);
		$data = json_decode($data);
		// Если есть версия, дата, обновление
		if(isset($data->date) && isset($data->version)):
			$version = $data->version;
			$date = $data->date;
			// Сравниваем дату
			//if(is_object($temp_date)):
				// Если дата в файле больше текущей даты на 7 дней
				$temp_time = strtotime("+1 week", strtotime($date . ' 00:00:00'));
				//$temp_time = strtotime($date . ' 00:00:00') + 3600 * 7;
				// Текущая дата
				$time = strtotime(date('Y-m-d 00:00:00', time()));
				if($temp_time < $time):
					// cURL
					try {
						$url = 'https://api.github.com/repos/ProjectSoft-STUDIONIONS/com_food/releases/latest';
						$headers = array(
							'cache-control: max-age=0',
							'upgrade-insecure-requests: 1',
							'user-agent: Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/78.0.3904.97 Safari/537.36',
							'sec-fetch-user: ?1',
							'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3',
									'x-compress: null',
									'sec-fetch-site: none',
									'sec-fetch-mode: navigate',
									'accept-encoding: deflate, br',
									'accept-language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
						);
						$CurlOptions = array(
							CURLOPT_URL 		   => $url,
							CURLOPT_RETURNTRANSFER => true,
							CURLOPT_FOLLOWLOCATION => true,
							CURLOPT_SSL_VERIFYPEER => false,
							CURLOPT_HEADER         => false,
							CURLOPT_HTTPHEADER     => $headers
						);
						$ch = curl_init();
						curl_setopt_array( $ch, $CurlOptions );
						$json = curl_exec($ch);
						curl_close($ch);
						$json = json_decode($json);
						// compare
						$github = $json->tag_name;
						if( version_compare($componentVersion, $github, '<') ):
							// Новая версия
							$vals = false;
							if(is_array($json->assets)):
								foreach($json->assets as $key => $value ):
									if($value->name == 'com_food-4.x-5.x.zip'):
										$vals = $value->browser_download_url;
									endif;
								endforeach;
							endif;
							// Вернём $vals (по сути должен быть линк на скачивание)
							return $vals;
						else:
							// Если версии одинаковые/или? - пишем в файл новую дату
							$data->version = $componentVersion;
							$data->date = date('Y-m-d', time());
							file_put_contents($update, json_encode($data));
							// Возвращяем false
							return false;
						endif;
					} catch (\Exception $e) {
						return false;
					}
				endif;
				return false;
			//endif;
			//return $temp_date;
		else:
			$data = new \stdClass;
			// Дата на прошедшие сутки
			$data->date = date( "Y-m-d", time() - 3600 );
			// Текущая версия компонента
			$data->version = $componentVersion;
			file_put_contents($update, json_encode($data));
			return false;
		endif;
		return false;
	}
}
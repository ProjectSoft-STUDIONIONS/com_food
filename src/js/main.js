/**
 * 
 * COM_FOOD_ERROR_MAX_UPLOAD
 * COM_FOOD_ERROR_TYPE_UPLOAD
 * COM_FOOD_RENAME_QUAERE
 * COM_FOOD_RENAME_ERROR
 * COM_FOOD_DELETE_QUAERE
 * COM_FOOD_EXPORT_XLSX
 * COM_FOOD_EXPORT_TO_XLSX
 * COM_FOOD_EXPORT_PDF
 * COM_FOOD_EXPORT_TO_PDF
 * COM_FOOD_DIRECTORY
 * COM_FOOD_TITLE
 * 
 * 
 **/
!(function($){
	const jq = $.noConflict(true);
	let search = location.search.replace(/\?/g, '');
	let search_api = search.split('&').map((item, index, array) => {
		let param = item.split('=');
		return param;
	});
	const searchAPI = Object.fromEntries(search_api);
	// Translate from Joomla text
	const Translate = {
		translate: (key) => Joomla.Text._(key, key),
		sprintf: (string, ...args) => {
			const newString = Translate.translate(string);
			let i = 0;
			return newString.replace(/%((%)|s|d)/g, (m) => {
				let val = args[i];
				if (m === '%d') {
					val = parseFloat(val);
					if (Number.isNaN(val)) {
						val = 0;
					}
				}
				i += 1;
				return val;
			});
		}
	};
	// Добавить максимальное количество файлов
	const maxCountFile = parseInt(window.MAX_COUNT_FILE),
		// Reset формы модификации
		modeFormReset = function() {
			let form = document.form_mode,
				input_mode = jq('input[name=mode]', form)[0],
				input_file = jq('input[name=file]', form)[0],
				input_new_file = jq('input[name=new_file]', form)[0];
			input_mode.value = "";
			input_file.value = "";
			input_new_file.value = "";
			form.reset();
		},
		getDateTime = function(timestamp = 0) {
			let time = new Date(timestamp),
				date = time.getDate(),
				month = time.getMonth() + 1,
				year = time.getFullYear(),
				hour = time.getHours(),
				minute = time.getMinutes(),
				second = time.getSeconds(),
				arrDate = [
					leftPad(date,  2, '0'),
					leftPad(month, 2, '0'),
					String(year)
				],
				arrTime = [
					leftPad(hour,   2, '0'),
					leftPad(minute, 2, '0'),
					leftPad(second, 2, '0')
				];
			return arrDate.join('-') + ' ' + arrTime.join(':');

		},
		leftPad = function (str, len, ch) {
			str = String(str);
			let i = -1;
			if (!ch && ch !== 0) ch = ' ';
			len = len - str.length;
			while (++i < len) {
				str = ch + str;
			}
			return str;
		};

	// Обрабатываем таски
	Joomla.submitbutton = function(task) {
		console.log(task);
		switch(task) {
			case "food.cancel":
				// Закрыть
				// Переход на главную страницу админки
				window.location.href = window.location.origin + window.location.pathname;
				break;
			case "food.github":
				// Открываем последний реализ плагина
				window.open("https://github.com/ProjectSoft-STUDIONIONS/com_food/releases/latest");
				break;
			default:
				break;
		}
	}
	// Загрузка файлов
	window.uploadFiles = function(el) {
		let p = jq("#p_uploads"),
			files = [...el.files],
			out = [], str = "";
		if(files.length > maxCountFile) {
			alert(Translate.sprintf('COM_FOOD_ERROR_MAX_UPLOAD',  maxCountFile));
			document.upload.reset();
			return !1;
		}
		for (let a of files){
			const regex = /[^.]+$/;
			let m;
			if ((m = regex.exec(a.name)) !== null) {
				let ex = m[0].toLowerCase();
				if(ex == "xlsx" || ex == "pdf"){
					out.push(`<code>${a.name}</code>`);
				}else{
					p.html("");
					alert(Translate.sprintf('COM_FOOD_ERROR_TYPE_UPLOAD', a.name, a.type));
					document.upload.reset();
					return !1;
				}
			}
		}
		p.html(out.join(""));
		return !1;
	}
	// Переименование файла. Удаление файла.
	window.modeFile = function(el) {
		let mode = jq(el).data('mode'),
			file = jq(el).data('file'),
			form = document.form_mode,
			input_mode = jq('input[name=mode]', form)[0],
			input_file = jq('input[name=file]', form)[0],
			input_new_file = jq('input[name=new_file]', form)[0];
		switch(mode) {
			case 'rename':
				// На переименование вывести только имя файла
				const [...segments] = file.split('.');
				const fileExtension = segments.pop();
				let fileName = segments.join('.');
				let nwfile = prompt(Translate.sprintf('COM_FOOD_RENAME_QUAERE', file), fileName);
				if(!nwfile) {
					modeFormReset();
					return !1
				}
				const regex = /[^A-z0-9-._]+/;
				if(regex.test(nwfile)){
					alert(Translate.sprintf('COM_FOOD_RENAME_ERROR') + `!`);
					modeFormReset();
					return !1;
				}
				input_mode.value = mode;
				input_file.value = file;
				input_new_file.value = nwfile + `.${fileExtension}`;
				if(input_file.value != input_new_file.value){
					form.submit();
				}else{
					modeFormReset();
				}
				break;
			case 'delete':
				if(confirm(Translate.sprintf('COM_FOOD_DELETE_QUAERE', file))){
					input_mode.value = mode;
					input_file.value = file;
					form.submit();
				}
				break;
		}
	}
	// Если находимся в директории.
	if(searchAPI.dir) {
		const url = `${location.origin}/${searchAPI.dir}/`;
		jq.extend(true, DataTable.Buttons.defaults, {
			dom: {
				container: {
					className: 'dt-buttons btn-group flex-wrap'
				},
				button: {
					className: 'btn btn-secondary text-uppercase'
				}
			}
		});
		let table = new DataTable('.food-table .table', {
			// Колонки
			columns: [
				{
					name: 'file'
				},
				{
					name: 'permission'
				},
				{
					name: 'date'
				},
				{
					name: 'size'
				},
				{
					name: 'actions'
				}
			],
			// Настройки по колонкам
			columnDefs : [
				// Разрешено для первой колонки поиск, сортировка
				{ 
					'searchable'    : !0, 
					'targets'       : [0],
					'orderable'     : !0
				},
				// Запрещено для последующих колонок поиск, сортировка
				{ 
					'searchable'    : !1, 
					'targets'       : [1,2,3,4],
					'orderable'     : !1
				},
			],
			// Разрешена сортировка
			ordering: !0,
			// Фиксируем сортировку (по умолчанию)
			order: {
				name: "file",
				dir: ""
			},
			// Разрешаем запоминание всех свойств
			stateSave: !0,
			// Сохранение свойств определённой таблицы директории
			stateSaveCallback: function (settings, data) {
				// Удаляем сортировку
				delete data.order;
				// Удаляем данные о столбцах
				delete data.columns;
				// Удаляем данные о поиске
				delete data.search;
				// Запоминаем данные о кол-ве отражения файлов
				let length = data.length;
				// Данные о кол-ве отражения файлов
				localStorage.setItem('DataTablesLength', length);
				// Данные о состоянии данной таблице
				// Вид имени таблицы - 'DataTables_сштые_food' - таблица директории food
				localStorage.setItem(
					'DataTables_' + settings.sInstance + '_' + searchAPI.dir,
					JSON.stringify(data)
				);
			},
			// Загружаем свойства для определённой таблицы
			stateLoadCallback: function (settings) {
				// Данные по дефолту
				let tempData = {
					time: (new Date()).getTime(),
					start: 0,
					length: 10,
					childRows: []
				};
				// Получаем данные таблицы директории даже если она null
				let data = JSON.parse(localStorage.getItem('DataTables_' + settings.sInstance + '_' + searchAPI.dir));
				// Получаем данные о кол-ве отражения файлов
				let length = parseInt(localStorage.getItem('DataTablesLength'));
				// Если null присваиваем дефолтное 10
				length = isNaN(length) ? 10 : length;
				// Указываем дефолтному объекту настроек кол-во отражения файлов
				tempData.length = length;
				// Объединяем полученные данные таблицы с дефолтными данными с перезаписью.
				data = jq.extend({}, data, tempData);
				// Теперь вид таблиц не будет отличаться.
				return data;
			},
			// Меню вывода кол-ва файлов
			lengthMenu: [
				[10, 25, 50, 100, -1],
				['по 10', 'по 25', 'по 50', 'по 100', 'Все']
			],
			// Контейнеры
			layout: {
				// Контейнер слева: Меню вывода кол-ва файлов
				topStart: [
					'pageLength',
					'search'
				],
				// Контейнер справа: кнопки экспорта XLSX, PDF
				topEnd: {
					buttons: [
						// Кнопка экспорта XLSX
						{
							extend: 'excel',
							text: Translate.sprintf('COM_FOOD_EXPORT_XLSX'),
							download: '',
							filename: Translate.sprintf('COM_FOOD_EXPORT_TO_XLSX', searchAPI.dir),
							title: Translate.sprintf('COM_FOOD_DIRECTORY', url),
							sheetName: `${searchAPI.dir}`,
							customize: function (xlsx) {
								let date = new Date();
								let dateISO = date.toISOString();
								// Создаём xml файлы для свойств документа (метатеги)
								xlsx["_rels"] = {};
								xlsx["_rels"][".rels"] = jq.parseXML(`<?xml version="1.0" encoding="UTF-8" standalone="yes"?>` +
									`<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">` +
										`<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>` +
										`<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>` +
										`<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>` +
									`</Relationships>`);
								xlsx["docProps"] = {};
								// Общая конфигурация
								xlsx["docProps"]["core.xml"] = jq.parseXML(`<?xml version="1.0" encoding="UTF-8" standalone="yes"?>` +
									`<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">` +
										// Заголовок
										`<dc:title>` + Translate.sprintf('COM_FOOD_DIRECTORY', url) + `</dc:title>` +
										// Тема
										`<dc:subject>` + Translate.sprintf('COM_FOOD_DIRECTORY', url) + `</dc:subject>` +
										// Создатель
										`<dc:creator>${componentName}</dc:creator>` +
										// Теги
										`<cp:keywords />` +
										// Описание
										`<dc:description>${componentName}</dc:description>` +
										// Последнее изменение
										`<cp:lastModifiedBy>${componentName}</cp:lastModifiedBy>` +
										// Дата создания - время создания
										`<dcterms:created xsi:type="dcterms:W3CDTF">${dateISO}</dcterms:created>` +
										// Дата изменеия - время создания
										`<dcterms:modified xsi:type="dcterms:W3CDTF">${dateISO}</dcterms:modified>` +
										// Категория
										`<cp:category>${searchAPI.dir}</cp:category>` +
									`</cp:coreProperties>`);
								// Конфигурация приложения
								xlsx["docProps"]["app.xml"] = jq.parseXML(
									`<?xml version="1.0" encoding="UTF-8" standalone="yes"?>` +
									`<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">` +
										`<Application>Microsoft Excel</Application>` +
										`<DocSecurity>0</DocSecurity>` +
										`<ScaleCrop>false</ScaleCrop>` +
										`<HeadingPairs>` +
											`<vt:vector size="2" baseType="variant">` +
												`<vt:variant>` +
													`<vt:lpstr>Листы</vt:lpstr>` +
												`</vt:variant>` +
												`<vt:variant>` +
													`<vt:i4>1</vt:i4>` +
												`</vt:variant>` +
											`</vt:vector>` +
										`</HeadingPairs>` +
										`<TitlesOfParts>` +
											`<vt:vector size="1" baseType="lpstr">` +
												`<vt:lpstr>${searchAPI.dir}</vt:lpstr>` +
											`</vt:vector>` +
										`</TitlesOfParts>` +
										// Руководитель - автор компонента
										`<Manager>${Developer}</Manager>` +
										// Организация - автор компонента
										`<Company>${Developer}</Company>` +
										`<LinksUpToDate>false</LinksUpToDate>` +
										`<SharedDoc>false</SharedDoc>` +
										`<HyperlinkBase>${url}</HyperlinkBase>` +
										`<HyperlinksChanged>false</HyperlinksChanged>` +
										`<AppVersion>16.0300</AppVersion>` +
									`</Properties>`
								);
								// Вставляем данные в Content_Types
								let contentType = xlsx["[Content_Types].xml"];
								let Types = contentType.querySelector('Types');
								// Общая конфигурация
								let Core = contentType.createElement('Override');
								Core.setAttribute("PartName", "/docProps/core.xml");
								Core.setAttribute("ContentType", "application/vnd.openxmlformats-package.core-properties+xml");
								Types.append(Core);
								// Конфигурация приложения
								let App = contentType.createElement('Override');
								App.setAttribute("PartName", "/docProps/app.xml");
								App.setAttribute("ContentType", "application/vnd.openxmlformats-officedocument.extended-properties+xml");
								Types.append(App);
								// Присваиваем
								xlsx["[Content_Types].xml"] = contentType;
							},
							action: function (e, dt, node, config, cb) {
								DataTable.ext.buttons.excelHtml5.action.call(
									this,
									e,
									dt,
									node,
									config,
									cb
								);
							}
						},
						// Кнопка экспорта PDF
						{
							extend: 'pdf',
							text: Translate.sprintf('COM_FOOD_EXPORT_PDF'),
							download: '',
							filename: Translate.sprintf('COM_FOOD_EXPORT_TO_PDF', searchAPI.dir),
							title: Translate.sprintf('COM_FOOD_DIRECTORY', url),
							// Кастомизируем вывод
							customize: function (doc) {
								let date = new Date();
								let dateISO = date.toISOString();
								let title = [
									Translate.sprintf('COM_FOOD_TITLE'),
									Translate.sprintf('COM_FOOD_DIRECTORY', url)
								];
								// Используемый язык экспорта
								doc.language = 'ru-RU';
								// Метатеги экспорта
								doc.info = {
									title: title.join(' '),
									author: componentName,
									subject: title.join(' '),
									keywords: title.join(' '),
									creator: `${componentName}`,
									producer: `${Developer}`,
									modDate: `${dateISO}`
								};
								// Колонтитулы
								// Верхний
								doc.header = {
	   								columns: [
	   									{
	   										text: `${url}`,
	   										margin: [15, 15, 15, 15],
	   										alignment: 'left'
	    								},
	    								{
	    									text: getDateTime((new Date()).getTime()),
	    									margin: [15, 15, 15, 15],
	   										alignment: 'right'
	   									}
									]
								};
								// Нижний
								doc.footer = function(currentPage, pageCount) {
									return [
										{
	    									text: currentPage.toString() + ' из ' + pageCount,
	    									margin: [15, 15, 15, 15],
	    									alignment: 'center'
	   									}
									];
								};
								// Текст контента.
								doc.content[0].text = title.join('\r\n');
							},
							action: function (e, dt, node, config, cb) {
								DataTable.ext.buttons.pdfHtml5.action.call(
									this,
									e,
									dt,
									node,
									config,
									cb
								);
							}
						}
					]
				}
			},
			// Загружаем язык
			// Нужно сделать определение и загрузка нужного языка панели.
			language: {
				url: '/administrator/components/com_food/assets/js/ru_RU.json',
			}
		});
	}
}(jQuery));

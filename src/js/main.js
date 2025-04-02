!(function($){
	const jq = $.noConflict(true);
	let search = location.search.replace(/\?/g, '');
	let search_api = search.split('&').map((item, index, array) => {
		let param = item.split('=');
		return param;
	});
	const searchAPI = Object.fromEntries(search_api);
	// Добавить максимальное количество файлов
	const maxCountFile = parseInt(window.MAX_COUNT_FILE),
		// Reset формы модификации
		modeFormReset = function() {
			let form = document.form_mode,
				input_mode = $('input[name=mode]', form)[0],
				input_file = $('input[name=file]', form)[0],
				input_new_file = $('input[name=new_file]', form)[0];
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
	window.uploadFiles = function(el) {
		let p = jq("#p_uploads"),
			files = [...el.files],
			out = [], str = "";
		if(files.length > maxCountFile) {
			alert(`Нельзя загрузить больше ${maxCountFile} файлов`);
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
					alert(`Нельзя загрузить данный тип файла!\n${a.name} - ${a.type}`);
					document.upload.reset();
					return !1;
				}
			}
		}
		p.html(out.join(""));
		return !1;
	}

	window.modeFile = function(el) {
		let mode = $(el).data('mode'),
			file = $(el).data('file'),
			form = document.form_mode,
			input_mode = $('input[name=mode]', form)[0],
			input_file = $('input[name=file]', form)[0],
			input_new_file = $('input[name=new_file]', form)[0];
		switch(mode) {
			case 'rename':
				// На переименование вывести только имя файла
				const [...segments] = file.split('.');
				const fileExtension = segments.pop();
				let fileName = segments.join('.');
				let nwfile = prompt(sprintf(window.com_food_lang.COM_FOOD_RENAME_QUAERE, file), fileName);
				if(!nwfile) {
					modeFormReset();
					return !1
				}
				const regex = /[^A-z0-9-._]+/;
				if(regex.test(nwfile)){
					alert(window.com_food_lang.COM_FOOD_RENAME_ERROR + `!`);
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
				if(confirm(sprintf(window.com_food_lang.COM_FOOD_DELETE_QUAERE, file))){
					input_mode.value = mode;
					input_file.value = file;
					form.submit();
				}
				break;
		}
	}
	if(searchAPI.dir) {
		let table = new DataTable('.food-table .table', {
			//select: true,
			columns: [
				{ name: 'file' },
				{ name: 'permission' },
				{ name: 'date' },
				{ name: 'size' },
				{ name: 'actions' }
			],
			columnDefs : [
				{ 
				   'searchable'    : false, 
				   'targets'       : [1,2,3,4] 
				},
			],
			ordering: false,
			stateSave: true,
			stateSaveCallback: function (settings, data) {
				localStorage.setItem(
					'DataTables_' + settings.sInstance + '_' + searchAPI.dir,
					JSON.stringify(data)
				);
			},
			stateLoadCallback: function (settings) {
				return JSON.parse(localStorage.getItem('DataTables_' + settings.sInstance + '_' + searchAPI.dir));
			},
			lengthMenu: [
				[10, 25, 50, 100, -1],
				['по 10', 'по 25', 'по 50', 'по 100', 'Все']
			],
			layout: {
				topStart: [
					'pageLength',
					'search'
				],
				topEnd: {
					buttons: [
						{
							extend: 'excel',
							text: 'Экспорт в XLSX',
							className: '',
							customize: function (...args) {
								console.log(args);
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
						{
							extend: 'pdf',
							text: 'Экспорт в PDF',
							className: '',
							download: '', //'open',
							customize: function (doc) {
								console.log(doc);
								let title = [
									`Меню ежедневного питания.`,
									`Директория ${location.origin}/${searchAPI.dir}/`
								];
								doc.language = 'ru-RU';
								doc.info = {
									title: title.join(' '),
									author: location.origin,
									subject: title.join(' '),
									keywords: title.join(' '),
									creator: 'Компонент питания для Joomla CMS',
								};
								doc.header = {
	   								columns: [
	   									{
	   										text: `${location.origin}/${searchAPI.dir}/`,
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

								doc.footer = function(currentPage, pageCount) {
									return [
										{
	    									text: currentPage.toString() + ' из ' + pageCount,
	    									margin: [15, 15, 15, 15],
	    									alignment: 'center'
	   									}
									];
								};

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
			language: {
				url: '/administrator/components/com_food/assets/js/ru_RU.json',
			}
		});
	}
}(jQuery));

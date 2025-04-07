/* global window, exports, define */

!function() {
    'use strict'

    var re = {
        not_string: /[^s]/,
        not_bool: /[^t]/,
        not_type: /[^T]/,
        not_primitive: /[^v]/,
        number: /[diefg]/,
        numeric_arg: /[bcdiefguxX]/,
        json: /[j]/,
        not_json: /[^j]/,
        text: /^[^\x25]+/,
        modulo: /^\x25{2}/,
        placeholder: /^\x25(?:([1-9]\d*)\$|\(([^)]+)\))?(\+)?(0|'[^$])?(-)?(\d+)?(?:\.(\d+))?([b-gijostTuvxX])/,
        key: /^([a-z_][a-z_\d]*)/i,
        key_access: /^\.([a-z_][a-z_\d]*)/i,
        index_access: /^\[(\d+)\]/,
        sign: /^[+-]/
    }

    function sprintf(key) {
        // `arguments` is not an array, but should be fine for this call
        return sprintf_format(sprintf_parse(key), arguments)
    }

    function vsprintf(fmt, argv) {
        return sprintf.apply(null, [fmt].concat(argv || []))
    }

    function sprintf_format(parse_tree, argv) {
        var cursor = 1, tree_length = parse_tree.length, arg, output = '', i, k, ph, pad, pad_character, pad_length, is_positive, sign
        for (i = 0; i < tree_length; i++) {
            if (typeof parse_tree[i] === 'string') {
                output += parse_tree[i]
            }
            else if (typeof parse_tree[i] === 'object') {
                ph = parse_tree[i] // convenience purposes only
                if (ph.keys) { // keyword argument
                    arg = argv[cursor]
                    for (k = 0; k < ph.keys.length; k++) {
                        if (arg == undefined) {
                            throw new Error(sprintf('[sprintf] Cannot access property "%s" of undefined value "%s"', ph.keys[k], ph.keys[k-1]))
                        }
                        arg = arg[ph.keys[k]]
                    }
                }
                else if (ph.param_no) { // positional argument (explicit)
                    arg = argv[ph.param_no]
                }
                else { // positional argument (implicit)
                    arg = argv[cursor++]
                }

                if (re.not_type.test(ph.type) && re.not_primitive.test(ph.type) && arg instanceof Function) {
                    arg = arg()
                }

                if (re.numeric_arg.test(ph.type) && (typeof arg !== 'number' && isNaN(arg))) {
                    throw new TypeError(sprintf('[sprintf] expecting number but found %T', arg))
                }

                if (re.number.test(ph.type)) {
                    is_positive = arg >= 0
                }

                switch (ph.type) {
                    case 'b':
                        arg = parseInt(arg, 10).toString(2)
                        break
                    case 'c':
                        arg = String.fromCharCode(parseInt(arg, 10))
                        break
                    case 'd':
                    case 'i':
                        arg = parseInt(arg, 10)
                        break
                    case 'j':
                        arg = JSON.stringify(arg, null, ph.width ? parseInt(ph.width) : 0)
                        break
                    case 'e':
                        arg = ph.precision ? parseFloat(arg).toExponential(ph.precision) : parseFloat(arg).toExponential()
                        break
                    case 'f':
                        arg = ph.precision ? parseFloat(arg).toFixed(ph.precision) : parseFloat(arg)
                        break
                    case 'g':
                        arg = ph.precision ? String(Number(arg.toPrecision(ph.precision))) : parseFloat(arg)
                        break
                    case 'o':
                        arg = (parseInt(arg, 10) >>> 0).toString(8)
                        break
                    case 's':
                        arg = String(arg)
                        arg = (ph.precision ? arg.substring(0, ph.precision) : arg)
                        break
                    case 't':
                        arg = String(!!arg)
                        arg = (ph.precision ? arg.substring(0, ph.precision) : arg)
                        break
                    case 'T':
                        arg = Object.prototype.toString.call(arg).slice(8, -1).toLowerCase()
                        arg = (ph.precision ? arg.substring(0, ph.precision) : arg)
                        break
                    case 'u':
                        arg = parseInt(arg, 10) >>> 0
                        break
                    case 'v':
                        arg = arg.valueOf()
                        arg = (ph.precision ? arg.substring(0, ph.precision) : arg)
                        break
                    case 'x':
                        arg = (parseInt(arg, 10) >>> 0).toString(16)
                        break
                    case 'X':
                        arg = (parseInt(arg, 10) >>> 0).toString(16).toUpperCase()
                        break
                }
                if (re.json.test(ph.type)) {
                    output += arg
                }
                else {
                    if (re.number.test(ph.type) && (!is_positive || ph.sign)) {
                        sign = is_positive ? '+' : '-'
                        arg = arg.toString().replace(re.sign, '')
                    }
                    else {
                        sign = ''
                    }
                    pad_character = ph.pad_char ? ph.pad_char === '0' ? '0' : ph.pad_char.charAt(1) : ' '
                    pad_length = ph.width - (sign + arg).length
                    pad = ph.width ? (pad_length > 0 ? pad_character.repeat(pad_length) : '') : ''
                    output += ph.align ? sign + arg + pad : (pad_character === '0' ? sign + pad + arg : pad + sign + arg)
                }
            }
        }
        return output
    }

    var sprintf_cache = Object.create(null)

    function sprintf_parse(fmt) {
        if (sprintf_cache[fmt]) {
            return sprintf_cache[fmt]
        }

        var _fmt = fmt, match, parse_tree = [], arg_names = 0
        while (_fmt) {
            if ((match = re.text.exec(_fmt)) !== null) {
                parse_tree.push(match[0])
            }
            else if ((match = re.modulo.exec(_fmt)) !== null) {
                parse_tree.push('%')
            }
            else if ((match = re.placeholder.exec(_fmt)) !== null) {
                if (match[2]) {
                    arg_names |= 1
                    var field_list = [], replacement_field = match[2], field_match = []
                    if ((field_match = re.key.exec(replacement_field)) !== null) {
                        field_list.push(field_match[1])
                        while ((replacement_field = replacement_field.substring(field_match[0].length)) !== '') {
                            if ((field_match = re.key_access.exec(replacement_field)) !== null) {
                                field_list.push(field_match[1])
                            }
                            else if ((field_match = re.index_access.exec(replacement_field)) !== null) {
                                field_list.push(field_match[1])
                            }
                            else {
                                throw new SyntaxError('[sprintf] failed to parse named argument key')
                            }
                        }
                    }
                    else {
                        throw new SyntaxError('[sprintf] failed to parse named argument key')
                    }
                    match[2] = field_list
                }
                else {
                    arg_names |= 2
                }
                if (arg_names === 3) {
                    throw new Error('[sprintf] mixing positional and named placeholders is not (yet) supported')
                }

                parse_tree.push(
                    {
                        placeholder: match[0],
                        param_no:    match[1],
                        keys:        match[2],
                        sign:        match[3],
                        pad_char:    match[4],
                        align:       match[5],
                        width:       match[6],
                        precision:   match[7],
                        type:        match[8]
                    }
                )
            }
            else {
                throw new SyntaxError('[sprintf] unexpected placeholder')
            }
            _fmt = _fmt.substring(match[0].length)
        }
        return sprintf_cache[fmt] = parse_tree
    }

    /**
     * export to either browser or node.js
     */
    /* eslint-disable quote-props */
    if (typeof exports !== 'undefined') {
        exports['sprintf'] = sprintf
        exports['vsprintf'] = vsprintf
    }
    if (typeof window !== 'undefined') {
        window['sprintf'] = sprintf
        window['vsprintf'] = vsprintf

        if (typeof define === 'function' && define['amd']) {
            define(function() {
                return {
                    'sprintf': sprintf,
                    'vsprintf': vsprintf
                }
            })
        }
    }
    /* eslint-enable quote-props */
}(); // eslint-disable-line

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
					out.push(a.name);
				}else{
					p.html("");
					alert(`Нельзя загрузить данный тип файла!\n${a.name} - ${a.type}`);
					document.upload.reset();
					return !1;
				}
			}
		}
		p.html(out.join("<br>"));
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
		})
	}


}(jQuery));

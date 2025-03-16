!(function($){
	const jq = $.noConflict(true);
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


}(jQuery));

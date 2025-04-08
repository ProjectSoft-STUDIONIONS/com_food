module.exports = function(grunt) {
	var fs = require('fs'),
		PACK = grunt.file.readJSON('package.json'),
		path = require('path'),
		chalk = require('chalk'),
		hash = function (...args) {
			var md5 = require('md5');
			let result = "",
				arr = [];
			if(!args.length){
				let time = (new Date()).getTime();
				arr.push("Not arguments");
				result = md5(time).toString();
			}else{
				let text = "";
				for(let index in args){
					let file = args[index];
					file = path.normalize(path.join(__dirname, file));
					try{
						let buff = fs.readFileSync(file, {
							encoding: "utf8"
						}).toString();
						text += buff;
						arr.push(file);
					}catch(e){
						// Ничего не делаем
						arr.push("Not found");
					}
				}
				result = md5(text).toString();
			}
			arr.push(result);
			grunt.log.oklns([chalk.cyan("Generate hash:") + "\n" + chalk.yellow(arr.join("\n"))]);
			return result;
		};

	require('load-grunt-tasks')(grunt);
	require('time-grunt')(grunt);

	var gc = {
		version: `${PACK.version}`,
		default: [
			"copy",
			"less",
			"autoprefixer",
			"cssmin",
			"concat",
			"uglify",
			"pug",
			"compress"
		]
	};

	require('load-grunt-tasks')(grunt);
	require('time-grunt')(grunt);

	let arr = `${PACK.homepage}`.split('/'),
		author = PACK.author.replace(/^(.*)(\s+<.*>)/, "$1"),
		authorEmail = PACK.author.replace(/^(?:.*)\s+<(.*)>/, "$1"),
		authorUrl = "";

	arr.pop();
	authorUrl = arr.join("/");
	
	grunt.initConfig({
		globalConfig : gc,
		pkg : PACK,
		less: {
			css: {
				options : {
					compress: false,
					ieCompat: false,
					plugins: []
				},
				files : {
					'component-5x/com_food/admin/assets/css/main.css' : [
						'src-4-5/less/main.less'
					]
				}
			},
		},
		autoprefixer:{
			options: {
				browsers: [
					"last 4 version"
				],
				cascade: true
			},
			css: {
				files: {
					'component-5x/com_food/admin/assets/css/main.css' : [
						'component-5x/com_food/admin/assets/css/main.css'
					]
				}
			},
		},
		cssmin: {
			options: {
				mergeIntoShorthands: false,
				roundingPrecision: -1
			},
			minify: {
				files: {
					'component-5x/com_food/admin/assets/css/main.min.css' : ['component-5x/com_food/admin/assets/css/main.css'],
				}
			},
		},
		concat: {
			options: {
				separator: "\n",
			},
			app: {
				src: [
					'bower_components/jquery/dist/jquery.js',
					'bower_components/pdfmake/build/pdfmake.js',
					'bower_components/jszip/dist/jszip.js',
					'bower_components/pdfmake/build/vfs_fonts.js',
					'bower_components/datatables.net/js/dataTables.js',
					'bower_components/datatables.net-buttons/js/dataTables.buttons.js',
					'bower_components/datatables.net-buttons/js/buttons.html5.js',
					//'bower_components/datatables.net-select/js/dataTables.select.js',
					'bower_components/datatables.net-bs/js/dataTables.bootstrap.js',
					//'bower_components/datatables.net-buttons-bs/js/buttons.bootstrap.js',
					//'bower_components/datatables.net-select-bs/js/select.bootstrap.js'
				],
				dest: 'component-5x/com_food/admin/assets/js/jquery.js'
			},
			emoji: {
				src: [
					'bower_components/sprintf/src/sprintf.js',
					'src-4-5/js/main.js'
				],
				dest: 'component-5x/com_food/admin/assets/js/main.js'
			},
		},
		uglify: {
			options: {
				sourceMap: false,
				compress: {
					drop_console: false
				},
				output: {
					ascii_only: true
				}
			},
			app: {
				files: [
					{
						expand: true,
						flatten : true,
						src: [
							'component-5x/com_food/admin/assets/js/main.js',
							'component-5x/com_food/admin/assets/js/jquery.js'
						],
						dest: 'component-5x/com_food/admin/assets/js',
						filter: 'isFile',
						rename: function (dst, src) {
							return dst + '/' + src.replace('.js', '.min.js');
						}
					}
				]
			},
			viewer: {
				files: [
					{
						expand: true,
						flatten : true,
						src: [
							'component-5x/com_food/viewer/app.js'
						],
						dest: 'component-5x/com_food/viewer',
						filter: 'isFile',
						rename: function (dst, src) {
							return dst + '/' + src.replace('.js', '.min.js');
						}
					}
				]
			}
		},
		pug: {
			serv: {
				options: {
					doctype: 'html',
					client: false,
					pretty: '\t',
					separator:  '\n',
					data: function(dest, src) {
						return {
							"version": PACK.version,
							"create": grunt.template.date((new Date()).getTime(), "yyyy-mm-dd"),
							"description": PACK.description.replace(/(com_food|food)/g, "<code>$1</code>"),
							"license": PACK.license,
							"author": author,
							"authorEmail": authorEmail,
							"authorUrl": authorUrl
						}
					}
				},
				files: [
					{
						expand: true,
						cwd: __dirname + '/src-4-5/pug/',
						src: [ 'food.pug' ],
						dest: __dirname + '/component-5x/com_food/',
						ext: '.xml'
					},
					{
						expand: true,
						cwd: __dirname + '/src-4-5/pug/',
						src: [ 'config.pug' ],
						dest: __dirname + '/component-5x/com_food/admin/',
						ext: '.xml'
					},
				]
			},
		},
		copy: {
			main: {
				expand: true,
				cwd: 'bower_components/bootstrap/dist/fonts',
				src: '**',
				dest: 'component-5x/com_food/admin/assets/fonts/',
			},
			food: {
				expand: true,
				cwd: 'bower_components/food/icons-full',
				src: '**',
				dest: 'component-5x/com_food/icons-full/',
			},
			viewer: {
				expand: true,
				cwd: 'bower_components/food/viewer',
				src: '**',
				dest: 'component-5x/com_food/viewer/',
			},
			htacces: {
				expand: true,
				cwd: 'htacces',
				src: '.*',
				dest: 'component-5x/com_food/admin/htaccess/',
			}
		},
		compress: {
			main: {
				options: {
					archive: 'com_food-4.x-5.x.zip'
				},
				files: [
					{
						expand: true,
						cwd: './component-5x/',
						src: [
							'com_food/**',
							'com_food/**/.*',
						],
						dest: '/'
					},
				],
			},
		},
	});
	grunt.registerTask('default',	gc.default);
};

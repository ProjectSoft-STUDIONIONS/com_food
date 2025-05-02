'use strict';

const fs = require('fs');
const crypto = require('crypto');
const stream = require('stream/promises');

module.exports = function(grunt) {
	grunt.registerMultiTask('sha', 'SHA files.', function() {
		console.log(this.files);
		let val;
		for(val of this.files) {
			let buff, sum,
				obj = {
					"sha256": "",
					"sha384": "",
					"sha512": "",
				},
				file = val.src[0],
				out = val.dest;
			buff = fs.readFileSync(file);
			sum = crypto.createHash('sha256');
			sum.update(buff);
			obj.sha256 = sum.digest('hex');
			sum = crypto.createHash('sha384');
			sum.update(buff);
			obj.sha384 = sum.digest('hex');
			sum = crypto.createHash('sha512');
			sum.update(buff);
			obj.sha512 = sum.digest('hex');
			console.log(obj);
			fs.writeFileSync(out, JSON.stringify(obj, null, '\t'));
		}
	});
};
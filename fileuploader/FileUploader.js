(function( $ ) {

var UploadBase = {
	lastid: 0,
	files: {},
	uploadqueue: [],
	activeuploads: 0,
	defaultsettings: {
		action: '',
		restrictions: {},
		maxconnections: 2,
		onStart: function(id, file) {},
		onProgress: function(e, id, file) {},
		onComplete: function(id, file, response, jqXHR) {},
		onError: function(id, file, textStatus, errorThrown, jqXHR) {},
	},
	settings: {},
	boundedresize: function(origx, origy, maxx, maxy) {
		var newx = 0;
		var newy = 0;
		origx = parseInt(origx);
		origy = parseInt(origy);
		maxx = parseInt(maxx);
		maxy = parseInt(maxy);
		// Calculate thumbnail dimensions.
		if (maxx === 0 && maxy === 0) {
			// If both maxx and maxy are 0 then we're just converting to jpeg.
			newy = origy;
			newx = origx;
		} else {
			var aspect = origx / origy;
			if (maxx === 0) {
				// Unlimited x.
				newy = maxy;
				newx = maxy * aspect;
			} else if (maxy === 0) {
				// Unlimited y.
				newx = maxx;
				newy = maxx / aspect;
			} else {
				if (aspect < 1) {
					// Vertical image.
					newy = maxy;
					newx = newy * aspect;
					if (newx > maxx) {
						newx = maxx;
						newy = maxx / aspect;
					}
				} else if (aspect > 1) {
					// Horizontal image.
					newx = maxx;
					newy = maxx / aspect;
					if (newy > maxy) {
						newy = maxy;
						newx = maxy * aspect;
				    }
				} else {
					// Square image.
					var dim = Math.min(maxx, maxy);
					newx = dim;
					newy = dim;
				}
			}
		}
		return {'maxx':Math.round(newx), 'maxy':Math.round(newy)};
	},
	resizedataurl: function(dataurl, maxx, maxy) {
        var imageObj = new Image();
        imageObj.src = dataurl;
        delete dataurl;
        var imgwidth = imageObj.width;
        var imgheight = imageObj.height;
		var newdims = this.boundedresize(imgwidth, imgheight, maxx, maxy);

        var canvas = document.createElement("canvas");
        ctx = canvas.getContext("2d");
        canvas.width = newdims.maxx;
        canvas.height = newdims.maxy;

        ctx.drawImage(imageObj, 0, 0, newdims.maxx, newdims.maxy);
        delete imageObj;
        var resizeddataurl = canvas.toDataURL('image/jpeg', 0.1);
        delete canvas;
        return resizeddataurl;
    },
    queueforupload: function(file) {
		this.lastid++;
		var curid = this.lastid;
		this.settings.onStart(curid, file, this);

		this.files[curid] = file;
		this.uploadqueue.push(curid);
		this.processuploadqueue();
	},
	removefromqueue: function(id) {
		delete this.files[id];
		var index = this.uploadqueue.indexOf(id);
		if (index > -1) {
		    this.uploadqueue.splice(index, 1);
		}
		this.activeuploads -= 1;
		this.processuploadqueue();
	},
	processuploadqueue: function() {
		while(this.uploadqueue.length > 0 && this.activeuploads < this.settings.maxconnections) {
		    var fileid = this.uploadqueue[0];
			this.uploadqueue.splice(0, 1);
			this.activeuploads += 1;
			this.uploadfile(fileid);
		}
	},
	uploadfile: function(fileid) {
		var file = this.files[fileid];
		var main = this;
		var fd = new FormData();
		fd.append('pdynfileuploader', file);

		$.ajax({
			method: 'POST',
			url: this.settings.action,
			data: fd,
			cache: false,
			processData: false,
  			contentType: false,
  			dataType: 'json',
  			uploadProgress: function(e) {
  				main.settings.onProgress(e, fileid, file);
			},
			success: function(data, textStatus, jqXHR) {
				main.removefromqueue(fileid);
				main.settings.onComplete(fileid, file, data, jqXHR);
			},
			error: function(jqXHR, textStatus, errorThrown) {
				console.log('error while uploading: '+textStatus+' : '+errorThrown);
				main.removefromqueue(fileid);
				main.settings.onError(fileid, file, textStatus, errorThrown, jqXHR);
			}
		});
	},
	getdircontents: function(entry, callback) {
		var main = this;
		if (entry.isDirectory === true) {
			var dirreader = entry.createReader();
			dirreader.readEntries(function(results) {
				for (var i in results) {
					main.getdircontents(results[i], callback);
				}
		    });
		} else if (entry.isFile === true) {
			callback(entry);
		}
	}
};

$.fn.UploadDragDrop = function(options) {
	$.extend(this, UploadBase);
	this.settings = $.extend({}, this.defaultsettings, options);
	var main = this;

	this.addClass('uploaddragdrop');

	this.bind('dragover', function(e) {
		main.addClass('active');
		e.stopPropagation();
		e.preventDefault();
	});

	this.bind('dragenter', function(e) {
		main.addClass('active');
	});

	this.bind('dragleave', function(e) {
		main.removeClass('active');
	});

	this.queueforupload_callback = function(file) {
		main.queueforupload(file);
	}

	this.bind('drop', function(e) {
		e = e.originalEvent;
		var length = e.dataTransfer.items.length;

		var callback = function(entry) {
			entry.file(main.queueforupload_callback);
		}
		for (var i = 0; i < length; i++) {
			var entry = e.dataTransfer.items[i].webkitGetAsEntry();
			var entries = main.getdircontents(entry, callback);
		}
		main.removeClass('active');
		e.preventDefault();
	});

	return this;
}

$.fn.UploadButton = function(options) {
	$.extend(this, UploadBase);
	this.settings = $.extend({}, this.defaultsettings, options);
	var main = this;
	var id = Date.now().toString(16)+(Math.floor(Math.random()*10000000000000000));

	this.createuploadbuttoninput = function() {
		var uploadbutton = $('<input type="file"/>');
		uploadbutton.addClass('UploadButton');
		uploadbutton.attr('id', 'UploadButton_input_'+id);
		uploadbutton.attr('multiple', 'multiple');
		uploadbutton.on('change', function(e) {
			var files = this.files;
			for (var i=0; i<files.length; i++){
				main.queueforupload(files[i]);
			}
			main.resetuploadbuttoninput();
			e.preventDefault();
			e.stopPropagation();
		});
		return uploadbutton;
	}

	this.resetuploadbuttoninput = function() {
		$('#UploadButton_input_'+id).remove();
		$('#UploadButton_label_'+id).remove();

		main.append('<label id="UploadButton_label_'+id+'" for="UploadButton_input_'+id+'"></label>');
		$('body').append(main.createuploadbuttoninput());
	}

	this.init = function() {
		main.addClass('UploadButton');
		main.resetuploadbuttoninput();
	}

	this.init();
}

}( jQuery ));
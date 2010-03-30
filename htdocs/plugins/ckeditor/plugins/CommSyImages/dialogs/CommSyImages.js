﻿/*
Copyright (c) 2003-2010, CKSource - Frederico Knabben. All rights reserved.
For licensing, see LICENSE.html or http://ckeditor.com/license
*/

CKEDITOR.dialog.add( 'CommSyImages', function( editor )
{
	var config = editor.config,
		lang = editor.lang.smiley,
		images = config.smiley_images,
		columns = 8,
		i;

	/**
	 * Simulate "this" of a dialog for non-dialog events.
	 * @type {CKEDITOR.dialog}
	 */
	var dialog;
	var onClick = function( evt )
	{
		var target = evt.data.getTarget(),
			targetID = target.getId();

		if(targetID != null){
			var image = CKEDITOR.dom.element.createFromHtml('<span>(:image '+targetID+':)</span>');
			editor.insertElement(image);
	
			dialog.hide();
			evt.data.preventDefault();
		}
	};

	var types = new Array('jpg', 'gif', 'png');
	
	var html = '';
	if(typeof(ckeditor_commsy_images) !== 'undefined'){
		if(ckeditor_commsy_images.length > 0){
			var image_counter = 0;
			html += '<ul style="list-style-type: none">';
			for ( var int = 0; int < ckeditor_commsy_images.length; int++) {
				var temp_file = ckeditor_commsy_images[int];
				var file_extension_array = temp_file.split('.');
				var file_extension = file_extension_array[file_extension_array.length-1];
				file_extension = file_extension.toLowerCase();
				var is_image = false;
				for ( var int2 = 0; int2 < types.length; int2++) {
					if(file_extension == types[int2]){
						is_image = true;
					}
				}
				if(is_image){
					html += '<li><img src="plugins/ckeditor/plugins/CommSyImages/images/CommSyImages.png" /><span id="'+temp_file+'" onmouseover="this.style.cursor=\'pointer\';"> '+temp_file+'</span></li>';
					image_counter++;
				}
			}
			html += '</ul>';
			if(image_counter == 0){
				html = 'Keine Bilder vorhanden';
				onClick = function( evt ){};
			}
		} else {
			html = 'Keine Bilder vorhanden';
			onClick = function( evt ){};
		}
	}
	
	var commsyImageSelector =
	{
		type : 'html',
		html : html,
		onLoad : function( event )
		{
			dialog = event.sender;
		},
		onClick : onClick,
		style : 'width: 100%; height: 100%; border-collapse: separate;'
	};
	
	return {
		title : 'Bild auswählen',
		minWidth : 270,
		minHeight : 120,
		contents : [
			{
				id : 'tab1',
				label : '',
				title : '',
				expand : false,
				padding : 0,
				elements : [
			            commsyImageSelector
					]
			}
		],
		buttons : [ CKEDITOR.dialog.okButton, CKEDITOR.dialog.cancelButton ]
	};
} );

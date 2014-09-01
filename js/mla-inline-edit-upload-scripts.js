// These functions are adapted from wp-admin/js/inline-edit-post.js

var ajaxurl, jQuery, inlineEditUpload, mla_inline_edit_upload_vars;

(function($) {
inlineEditUpload = {

	init : function(){
		var t = this, qeRow = $('#inline-edit'), bulkRow = $('#bulk-edit');

		t.type = 'upload';
		t.what = '#upload-';

		// prepare the edit rows
		qeRow.keyup(function(e){
			if (e.which == 27)
				return inlineEditUpload.revert();
		});
		bulkRow.keyup(function(e){
			if (e.which == 27)
				return inlineEditUpload.revert();
		});

		$('a.cancel', qeRow).click(function(){
			return inlineEditUpload.revert();
		});
		$('a.save', qeRow).click(function(){
			return inlineEditUpload.save(this);
		});
		$('td', qeRow).keydown(function(e){
			if ( e.which == 13 )
				return inlineEditUpload.save(this);
		});

		$('a.cancel', bulkRow).click(function(){
			return inlineEditUpload.revert();
		});

		// add events
		$('a.editinline').live('click', function(){
			inlineEditUpload.edit(this);
			return false;
		});

		$('#doaction, #doaction2').click(function(e){
			var n = $(this).attr('id').substr(2);

			if ( $('select[name="'+n+'"]').val() == 'edit' ) {
				e.preventDefault();
				t.setBulk();
			} else if ( $('form#posts-filter tr.inline-editor').length > 0 ) {
				t.revert();
			}
		});
	},

	toggle : function(el){
		var t = this;

		if ( 'none' == $( t.what + t.getId( el ) ).css('display') ) {
			t.revert();
		} else {
			t.edit( el );
		}
	},

	setBulk : function(){
		var te = '', c = true;
		this.revert();

		$('#bulk-edit td').attr('colspan', $('.widefat:first thead th:visible').length);
		$('table.widefat tbody').prepend( $('#bulk-edit') );
		$('#bulk-edit').addClass('inline-editor').show();

		$('tbody th.check-column input[type="checkbox"]').each(function(){
			if ( $(this).prop('checked') ) {
				c = false;
				var id = $(this).val(), theTitle;
				theTitle = $('#inline_'+id+' .slug').text() || mla_inline_edit_upload_vars.notitle;
				te += '<div id="ttle'+id+'"><a id="_'+id+'" class="ntdelbutton" title="'+mla_inline_edit_upload_vars.ntdeltitle+'">X</a>'+theTitle+'</div>';
			}
		});

		if ( c )
			return this.revert();

		$('#bulk-titles').html(te);
		$('#bulk-titles a').click(function(){
			var id = $(this).attr('id').substr(1);

			$('table.widefat input[value="' + id + '"]').prop('checked', false);
			$('#ttle'+id).remove();
		});

		$('html, body').animate( { scrollTop: 0 }, 'fast' );
	},

	edit : function(id) {
		var t = this, fields, checkboxes, editRow, rowData, fIndex;
		t.revert();

		if ( typeof(id) == 'object' )
			id = t.getId(id);

		fields = mla_inline_edit_upload_vars.fields;
		checkboxes = mla_inline_edit_upload_vars.checkboxes;

		// add the new blank row
		editRow = $('#inline-edit').clone(true);
		$('td', editRow).attr('colspan', $('.widefat:first thead th:visible').length);

		if ( $(t.what+id).hasClass('alternate') )
			$(editRow).addClass('alternate');

		$(t.what+id).hide().after(editRow);

		// populate the data
		rowData = $('#inline_'+id);

		for ( fIndex = 0; fIndex < fields.length; fIndex++ ) {
			$(':input[name="' + fields[fIndex] + '"]', editRow).val( $('.'+fields[fIndex], rowData).text() );
		}

		for ( fIndex = 0; fIndex < fields.length; fIndex++ ) {
			if ( '1' == $('.'+checkboxes[fIndex], rowData).text() )
				$(':input[name="' + checkboxes[fIndex] + '"]', editRow).attr( 'checked', 'checked' );
			else
				$(':input[name="' + checkboxes[fIndex] + '"]', editRow).removeAttr('checked');
		}

		$(editRow).attr('id', 'edit-'+id).addClass('inline-editor').show();
		$('.ptitle', editRow).focus(); // $('.ptitle', editRow).eq(0).focus();

		return false;
	},

	save : function(id) {
		var params, fields;

		if ( typeof(id) == 'object' )
			id = this.getId(id);

		$('table.widefat .spinner').show();

		params = {
			action: mla_inline_edit_upload_vars.ajax_action,
			nonce: mla_inline_edit_upload_vars.ajax_nonce,
			post_ID: id
		};

		fields = $('#edit-'+id+' :input').serialize();
		params = fields + '&' + $.param(params);

		// make ajax request
		$.post( ajaxurl, params,
			function(r) {
				$('table.widefat .spinner').hide();

				if (r) {
					if ( -1 != r.indexOf('<tr') ) {
						$(inlineEditUpload.what+id).remove();
						$('#edit-'+id).before(r).remove();
						$(inlineEditUpload.what+id).hide().fadeIn();
					} else {
						r = r.replace( /<.[^<>]*?>/g, '' );
						$('#edit-'+id+' .inline-edit-save .error').html(r).show();
					}
				} else {
					$('#edit-'+id+' .inline-edit-save .error').html(mla_inline_edit_upload_vars.error).show();
				}
			}, 'html');
		return false;
	},

	revert : function(){
		var id = $('table.widefat tr.inline-editor').attr('id');

		if ( id ) {
			$('table.widefat .inline-edit-save .waiting').hide();

			if ( 'bulk-edit' == id ) {
				$('table.widefat #bulk-edit').removeClass('inline-editor').hide();
				$('#bulk-titles').html('');
				$('#inlineedit').append( $('#bulk-edit') );
			} else {
				$('table.widefat .spinner').hide();
				$('#'+id).remove();
				id = id.substr( id.lastIndexOf('-') + 1 );
				$(this.what+id).show();
			}
		}

		return false;
	},

	getId : function(o) {
		var id = $(o).closest('tr').attr('id'),
			parts = id.split('-');
		return parts[parts.length - 1];
	}
};

$(document).ready(function(){inlineEditUpload.init();});
})(jQuery);

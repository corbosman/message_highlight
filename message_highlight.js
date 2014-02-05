var mh_cur_row;

$(document).ready(function() {
  if(window.rcmail) {
	
    rcmail.addEventListener('plugin.mh_receive_row', mh_receive_row);
	
    rcmail.addEventListener('insertrow', function(evt) {
      if(!rcmail.env.messages) return; 
      
      var message = rcmail.env.messages[evt.row.uid];


  
      // check if our color info is present
      if(message.flags && message.flags.plugin_mh_color) {
        $(evt.row.obj).addClass('rcmfd_mh_row');
        evt.row.obj.style.backgroundColor = message.flags.plugin_mh_color;
      }
    });  

  
    $('#preferences-details').on('click', '.mh_delete', function() {
      mh_delete(this);
    });

    $('#preferences-details').on('click', '.mh_add', function() {
      mh_add(this);
    });
  }
});


function mh_delete(button) {
  if(confirm(rcmail.get_label('message_highlight.deleteconfirm'))) {
    $(button).closest('tr', '#prefs-details').remove();
  }
}

// do an ajax call to get a new row
function mh_add(button) {
  mh_cur_row = $(button).closest('tr', '#prefs-details');
  lock = rcmail.set_busy(true, 'loading');
  rcmail.http_request('plugin.mh_add_row', '', lock);
}

// ajax return call
function mh_receive_row(data) {
  var row = data.row;
  $(mh_cur_row).after('<tr><td>'+row+'</td></tr>');
  //$('.mh_color_input:last').mColorPicker();
  
  $('input[data-mcolorpicker!="true"]').filter(function() {
    return ($.fn.mColorPicker.init.replace == '[type=color]')? this.getAttribute("type") == 'color': $(this).is($.fn.mColorPicker.init.replace);
  }).mColorPicker({
    imageFolder: 'plugins/message_highlight/colorpicker/images/',
    allowTransparency: false,
    showLogo: false,
    liveEvents: false,
    checkRedraw: 'ajaxSuccess'
  });
}

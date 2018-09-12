var mh_cur_row;

$(document).ready(function() {
  if(window.rcmail) {

    /* listen to roundcube insertrow event */
    rcmail.addEventListener('insertrow', mh_insert_row);

    /* listen to receive row event after asking for another row */
    rcmail.addEventListener('plugin.mh_receive_row', mh_receive_row);

    $('.mh_delete').click(function(e) {
        e.preventDefault();
        mh_delete(this);
    });

    $('.mh_add').click(function(e) {
        e.preventDefault();
        mh_add(this);
    });

    // $('.mh_preferences').on('click', '.mh_delete', function() {
    //   console.log('received click event for ', this)
    //   mh_delete(this);
    // });

    // $('.mh_preferences').on('click', '.mh_add', function() {
    //   mh_add(this);
    // });
  }
});

function mh_insert_row(evt) {

    if(!rcmail.env.messages) return;

    var message = rcmail.env.messages[evt.row.uid];

    // check if our color info is present
    if(message.flags && message.flags.plugin_mh_color) {
        $(evt.row.obj).addClass('rcmfd_mh_row');
        evt.row.obj.style.backgroundColor = message.flags.plugin_mh_color;
    }
}

function mh_delete(button) {
  if (confirm(rcmail.get_label('message_highlight.deleteconfirm'))) {
    console.log('received confirm');
    $(button).closest('tr').remove();
  }
}

// do an ajax call to get a new row
function mh_add(button) {
  mh_cur_row = $(button).closest('tr', '.mh_preferences');
  lock = rcmail.set_busy(true, 'loading');
  rcmail.http_request('plugin.mh_add_row', '', lock);
}

// ajax return call
function mh_receive_row(data) {
  console.log(data);
  var row = data.row;
  $(mh_cur_row).after('<tr class="form-group row"><td colspan="2" style="width: 100%;">'+row+'</td></tr>');
  //$('.mh_color_input:last').mColorPicker();

  $('.mh_delete').unbind('click').click(function(e) {
      e.preventDefault();
      mh_delete(this);
  });

  $('.mh_add').unbind('click').click(function(e) {
      e.preventDefault();
      mh_add(this);
  });


  // $(row).find('.mh_delete').click(function(e) {
  //   e.preventDefault();
  //   mh_delete(this);
  // });
  //
  // $(row).on('click', '.mh_add', function(e) {
  //     e.preventDefault();
  //     mh_add(this);
  // });
  
  $('input[data-mcolorpicker!="true"]').filter(function() {
    return ($.fn.mColorPicker.init.replace == '[type=color]') ? this.getAttribute("type") == 'color': $(this).is($.fn.mColorPicker.init.replace);
  }).mColorPicker({
    imageFolder: 'plugins/message_highlight/colorpicker/images/',
    allowTransparency: false,
    showLogo: false,
    liveEvents: false,
    checkRedraw: 'ajaxSuccess'
  });
}

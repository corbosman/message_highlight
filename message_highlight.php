<?php

/**
* @version 2.1
* @author Cor Bosman (cor@roundcu.be)
*/

class message_highlight extends rcube_plugin
{
  public $task = 'mail|settings';
  private $rcmail;
  private $prefs;

  public function init()
  {
    $this->add_texts('localization/', array('deleteconfirm'));
    $this->add_hook('messages_list', array($this, 'mh_highlight'));
    $this->add_hook('preferences_list', array($this, 'mh_preferences'));
    $this->add_hook('preferences_save', array($this, 'mh_save'));
    $this->add_hook('preferences_sections_list',array($this, 'mh_preferences_section'));
    $this->add_hook('storage_init', array($this, 'storage_init'));

    $this->register_action('plugin.mh_add_row', array($this, 'mh_add_row'));

    $this->include_script('message_highlight.js');
    $this->include_script('colorpicker/mColorPicker.js');
    $this->include_stylesheet('message_highlight.css');
  }

  function storage_init($p)
  {
    $p['fetch_headers'] .= trim($p['fetch_headers']. ' ' . 'CC');
    return($p);
  }


  // add color information for all messages
  function mh_highlight($p)
  {
    $rcmail = rcmail::get_instance();
    $this->prefs = $rcmail->config->get('message_highlight', array());

    // dont loop over all messages if we dont have any highlights or no msgs
    if(!count($this->prefs) or !isset($p['messages']) or !is_array($p['messages'])) return $p;

    // loop over all messages and add highlight color to each message
    foreach($p['messages'] as $message) {
      if(($color = $this->mh_find_match($message)) !== false ) {
        $message->list_flags['extra_flags']['plugin_mh_color'] = $color;
      }
    }
    return($p);
  }

  // find a match for this message
  function mh_find_match($message) {
    foreach($this->prefs as $p) {
      $header = iconv_mime_decode($message->$p['header'], 2, 'UTF-8');
      if(stristr($header, $p['input'])) {
        return($p['color']);
      }
    }
    return false;
  }

  // user preferences
  function mh_preferences($args) {
    if($args['section'] == 'mh_preferences') {
      $this->add_texts('localization/', false);
      $rcmail = rcmail::get_instance();

      $args['blocks']['mh_preferences'] =  array(
        'options' => array(),
        'name'    => Q($this->gettext('mh_title'))
        );

      $i = 1;
      $prefs = $rcmail->config->get('message_highlight', array());

      foreach($prefs as $p) {
        $args['blocks']['mh_preferences']['options'][$i++] = array(
          'content' => $this->mh_get_form_row($p['header'], $p['input'], $p['color'], true)
          );
      }

      // no rows yet, add 1 empty row
      if($i == 1) {
        $args['blocks']['mh_preferences']['options'][$i] = array(
          'content' => 	$this->mh_get_form_row()
          );
      }
    }

    return($args);
  }

  function mh_add_row() {
    $rcmail = rcmail::get_instance();
    $rcmail->output->command('plugin.mh_receive_row', array('row' => $this->mh_get_form_row()));
  }

  // create a form row
  function mh_get_form_row($header = 'from', $input = '', $color = '#ffffff', $delete = false) {

    // header select box
    $header_select = new html_select(array('name' => '_mh_header[]', 'class' => 'rcmfd_mh_header'));
    $header_select->add(Q($this->gettext('subject')), 'subject');
    $header_select->add(Q($this->gettext('from')), 'from');
    $header_select->add(Q($this->gettext('to')), 'to');
    $header_select->add(Q($this->gettext('cc')), 'cc');

    // input field
    $input = new html_inputfield(array('name' => '_mh_input[]', 'class' => 'rcmfd_mh_input', 'type' => 'text', 'autocomplete' => 'off', 'value' => $input));

    // color box
    $color = html::tag('input', array('id' => uniqid() ,'name' => '_mh_color[]' ,'type' => 'color' ,'text' => 'hidden', 'class' => 'mh_color_input', 'value' => $color, 'data-hex' => 'true'));

    // delete button
    $button = html::tag('input', array('class' => 'button mh_delete mh_button', 'type' => 'button', 'value' => $this->gettext('mh_delete'), 'title' => $this->gettext('mh_delete_description')));

    // add button
    $add_button = html::tag('input', array('class' => 'button mh_add mh_button', 'type' => 'button', 'value' => $this->gettext('mh_add'), 'title' => $this->gettext('mh_add_description')));

    $content =  $header_select->show($header) .
      html::span('mh_matches', Q($this->gettext('mh_matches'))) .
      $input->show() .
      html::span('mh_color', Q($this->gettext('mh_color'))) .
      $color . $button . $add_button;

    if(rcmail::get_instance()->config->get('request_saver_compress_html', false)){
      $content = request_saver::html_compress($content);
    }

    return($content);
  }

  // add a section to the preferences tab
  function mh_preferences_section($args) {
    $this->add_texts('localization/', false);
    $args['list']['mh_preferences'] = array(
      'id'      => 'mh_preferences',
      'section' => Q($this->gettext('mh_title'))
      );
    return($args);
  }

  // save preferences
  function mh_save($args) {
    if($args['section'] != 'mh_preferences') return;

    $rcmail = rcmail::get_instance();

    $header  = get_input_value('_mh_header', RCUBE_INPUT_POST);
    $input   = get_input_value('_mh_input', RCUBE_INPUT_POST);
    $color   = get_input_value('_mh_color', RCUBE_INPUT_POST);


    for($i=0; $i < count($header); $i++) {
      if(!in_array($header[$i], array('subject', 'from', 'to', 'cc'))) {
        $rcmail->output->show_message('message_highlight.headererror', 'error');
        return;
      }
      if(!preg_match('/^#[0-9a-fA-F]{2,6}$/', $color[$i])) {
        $rcmail->output->show_message('message_highlight.invalidcolor', 'error');
        return;
      }
      if($input[$i] == '') {
        continue;
      }
      $prefs[] = array('header' => $header[$i], 'input' => $input[$i], 'color' => $color[$i]);
    }

    $args['prefs']['message_highlight'] = $prefs;
    return($args);
  }
}
?>

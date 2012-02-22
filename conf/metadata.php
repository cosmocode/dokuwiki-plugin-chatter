<?php
/**
 * Options for the chatter plugin
 *
 * @author Andreas Gohr <gohr@cosmocode.de>
 */

$meta['loginurl']        = array('multichoice','_choices' => array('live','sandbox'));
$meta['instancename']    = array('string','_pattern' => '^https:\/\/.*?\.salesforce.com');
$meta['consumer_key']    = array('string');
$meta['consumer_secret'] = array('password');


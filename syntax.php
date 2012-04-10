<?php
/**
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Andreas Gohr <gohr@cosmocode.de>
 */

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_chatter extends DokuWiki_Syntax_Plugin {

   /**
    * Get the type of syntax this plugin defines.
    *
    * @param none
    * @return String <tt>'substition'</tt> (i.e. 'substitution').
    * @public
    * @static
    */
    function getType(){
        return 'substition';
    }

    function getSort(){
        return 150;
    }


    function connectTo($mode) {
      $this->Lexer->addSpecialPattern('~~NOCHATTER~~',$mode,'plugin_chatter');
    }

    function handle($match, $state, $pos, &$handler){
        return array();
    }

    function render($mode, &$renderer, $data) {
        if($mode == 'metadata'){
            $renderer->meta['plugin']['nochatter'] = true;
        }
        return true;
    }
}


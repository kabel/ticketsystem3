<?php

class TicketSystem_View_Helper_Wiki extends Zend_View_Helper_Abstract
{
    protected $_rules = array(
        'Prefilter',
        'Delimiter',
        'Code',
        'Raw',
        'Anchor',
        'Heading',
        'Toc',
        'Horiz',
        'Break',
        'Blockquote',
        'List',
        'Deflist',
        'Table',
        'Image',
        'Center',
        //'Newline',
        'Paragraph',
        'Url',
        'Ticketlink',
        'Colortext',
        'Strong',
        'Bold',
        'Emphasis',
        'Italic',
        'Underline',
        'Strikethrough',
        'Tt',
        'Superscript',
        'Subscript',
        'Revise',
        'Smiley',
        'Tighten'
    );
    
    protected $_parseConf = array(
        'Smiley' => array('smileys' => array(
            ':)'        => array('happy', 'Happy'),
            ':('        => array('sad', 'Sad'),
    		';)'        => array('wink', 'Wink'),
            ':D'        => array('grin', 'Big Grin', ':grin:'),
    		';;)'       => array('batting_eyelashes', 'Batting Eyelashes'),
            '>:D<'      => array('hug', 'Big Hug'),
    		':/'        => array('confused', 'Confused', 'O.o', ':???:'),
            ':x'        => array('love', 'Love Struck', '<3'),
    		':">'       => array('blush', 'Blushing', ':-">', ':oops:'),
            ':P'        => array('tongue', 'Razz'),
    		':*'        => array('kiss', 'Kiss'),
            '=(('       => array('broken_heart', 'Broken Heart'),
    		':o'        => array('surprise', 'Surprised', ':eek:'),
            'X('        => array('angry', 'Angry'),
    		':>'        => array('smug', 'Smug'),
            'B)'        => array('cool', 'Cool'),
    		':S'        => array('worried', 'Worried'),
            '#:S'       => array('whew', 'Whew!', '#:-S'),
            '>:)'       => array('evil', 'Evil or Very Mad', '>:-)', ':evil:'),
            ':\'('      => array('cry', 'Crying or Very sad', ':-\'(', ':((', ':cry:'),
            ':))'       => array('lol', 'Laughing', ':lol:'),
            ':|'        => array('neutral', 'Neutral'),
            '/:)'       => array('raised_eyebrows', 'Raised Eyebrows'),
            '=))'       => array('rolling', 'Rolling on the floor', ':rotfl:'),
            'O:)'       => array('angel', 'Angel', 'O:-)'),
            ':B'        => array('nerd', 'Nerd'),
            '=;'        => array('hand', 'Talk to the hand'),
    		':C'        => array('call_me', 'Call me'),
            ':)]'       => array('on_the_phone', 'On the phone', ':-)]'),
            '~X('       => array('wits_end', 'At wits\' end', '~X-('),
            ':H'        => array('wave', 'Wave'),
    		':T'        => array('time_out', 'Time out'),
            '8>'        => array('day_dream', 'Day Dreaming'),
            'I)'        => array('sleepy', 'Sleepy', ':zzz:'),
            'L)'        => array('loser', 'Loser'),
            '8|'        => array('rolling_eyes', 'Rolling Eyes', ':roll:'),
            ':&'        => array('sick', 'Sick'),
            ':$'        => array('shhh', 'Don\'t tell anyone'),
            '[('        => array('no_talk', 'No talking'),
            ':O)'       => array('clown', 'Clown'),
            '8}'        => array('silly', 'Silly'),
            '<:P'       => array('party', 'Party', '<:-P'),
            '|O'        => array('yawn', 'Yawn', '(:|'),
            '=P~'       => array('drool', 'Drooling'),
            ':?'        => array('think', 'Thinking'),
            '#O'        => array('doh', 'D\'oh'),
            '=D>'       => array('applause', 'Applause'),
            ':SS'       => array('nail_bite', 'Nail Biting', ':-SS'),
            '@)'        => array('hypnotized', 'Hypnotized'),
            ':^o'       => array('liar', 'Liar'),
            ':w'        => array('wait', 'Waiting'),
            ':<'        => array('sigh', 'Sigh'),
            '>:P'       => array('phbbbbt', 'Phbbbbt'),
            '<):)'      => array('cowboy', 'Cowboy', '<):-)'),
            'X_X'       => array('no_see', 'I don\'t want to see'),
            ':!'        => array('hurry_up', 'Hurry Up!'),
            '\\m/'      => array('rock_on', 'Rock on!'),
            ':q'        => array('thumbs_down', 'Thumbs down'),
            ':bd'       => array('thumbs_up', 'Thumbs up', ':-bd'),
            '^#(^'      => array('not_me', 'It wasn\'t me'),
        	':ar!'      => array('pirate_2', 'Pirate'),
            ':o3'       => array('puppy', 'Puppy'),
            ':??'       => array('idk', 'I don\'t know', ':-??'),
            '%('        => array('not_listening', 'Not listening'),
            ':@)'       => array('pig', 'Pig'),
            '3:O'       => array('cow', 'Cow', '3:-O'),
            ':(|)'      => array('monkey', 'Monkey'),
            '~:>'       => array('chicken', 'Chicken'),
            '@};-'      => array('rose', 'Rose'),
    		'%%-'       => array('clover', 'Clover'),
            '**--'      => array('flag', 'Flag'),
            '(~~)'      => array('pumpkin', 'Pumpkin'),
            '~O)'       => array('coffee', 'Coffee'),
            '*-:)'      => array('idea', 'Idea', '*-:-)', ':idea:'),
            '8X'        => array('skull', 'Skull'),
            '=:)'       => array('bug', 'Bug', '=:-)'),
            '>)'        => array('alien', 'Alien'),
            ':L'        => array('frustrated', 'Frustrated'),
            '[O<'       => array('pray', 'Praying', '[-O<'),
            '$)'        => array('money_eyes', 'Money Eyes'),
            ':"'        => array('whistle', 'Whistling'),
            'b('        => array('beat_up', 'Feeling beat up'),
            ':)>-'      => array('peace', 'Peace Sign', ':-)>-'),
            '[X'        => array('shame', 'Shame on you'),
            '\\:D/'     => array('dance', 'Dancing'),
            '>:/'       => array('bring_it', 'Bring it on'),
            ';))'       => array('chuckle', 'Hee Hee'),
            ':@'        => array('chatterbox', 'Chatterbox'),
            '^:)^'      => array('not_worthy', 'Not Worthy'),
            ':j'        => array('oh_go_on', 'Oh go on'),
            '(*)'       => array('star', 'Star'),
            'o->'       => array('hiro', 'Hiro'),
            'o=>'       => array('billy', 'Billy'),
            'o-+'       => array('april', 'April'),
            '(%)'       => array('yin_yang', 'Yin Yang'),
            ':bz'       => array('bzzz', 'Bee'),
            '[..]'      => array('transformer', 'Transformer'),
            ':unl:'     => array('unl', 'UNL')
        ))
    );
    
    protected $_renderConf = array();
    
    protected $_cssClasses = array();
    
    public function wiki($text, $ticketId = null)
    {
        try {
            $wiki = Text_Wiki::factory('Default', $this->_rules);
            $wiki->setParseConf('Smiley', $this->_parseConf['Smiley']);
            $wiki->setRenderConf('Xhtml', 'Smiley', 'prefix', $this->view->designUrl('images', 'smilies/'));
            $wiki->setRenderConf('Xhtml', 'Ticketlink', 'view', $this->view);
            if ($ticketId) {
                $wiki->setRenderConf('Xhtml', 'Image', 'upload_ticket_id', $ticketId);
                $wiki->setRenderConf('Xhtml', 'Image', 'upload_model', 'Default_Model_Upload');
                $wiki->setRenderConf('Xhtml', 'Image', 'view', $this->view);
            }
            
            $wiki->parse($text);
            $output = $wiki->render();
            
            $codes = $wiki->getTokens(array('Code'));
            foreach ($codes as $code) {
                $options = $code[1];
                if (isset($options['geshi'])) {
                    /* @var $geshi GeSHi */
                    $geshi = $options['geshi'];
                    if (!$geshi->error && !in_array($geshi->language, $this->_cssClasses)) {
                        $this->view->headStyle($geshi->get_stylesheet());
                        $this->_cssClasses[] = $geshi->language;
                    }
                }
            }
        } catch (Exception $e) {
            $output = 'ERROR: ' . $e->getMessage();
        }
        
        return $output;
    }
}
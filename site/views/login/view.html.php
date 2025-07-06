<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView;

class SmsloginViewLogin extends HtmlView
{
    public function display($tpl = null)
    {
        $document = Factory::getDocument();
        $document->addStyleSheet('components/com_smslogin/assets/css/style.css');
        $document->addScript('components/com_smslogin/assets/js/smslogin.js');
        
        parent::display($tpl);
    }
}

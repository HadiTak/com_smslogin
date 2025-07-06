<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

class PlgSystemSmslogin extends CMSPlugin
{
    protected $autoloadLanguage = true;
    
    public function onAfterInitialise()
    {
        $app = Factory::getApplication();
        
        // فقط در سایت اصلی اجرا شود
        if ($app->isClient('administrator')) {
            return;
        }
        
        // بررسی درخواست‌های ورود
        $this->checkLoginRequests();
    }
    
    private function checkLoginRequests()
    {
        $app = Factory::getApplication();
        $input = $app->input;
        
        // بررسی فرم‌های ورود جوملا
        if ($input->get('task') == 'user.login' || 
            $input->get('option') == 'com_users' && $input->get('view') == 'login') {
            
            $this->redirectToSmsLogin();
        }
        
        // بررسی فرم‌های ثبت نام جوملا
        if ($input->get('task') == 'user.register' || 
            $input->get('option') == 'com_users' && $input->get('view') == 'registration') {
            
            $this->redirectToSmsLogin();
        }
        
        // بررسی فرم‌های ثبت نام هیکاشاپ
        if ($input->get('option') == 'com_hikashop' && 
            ($input->get('view') == 'user' || $input->get('ctrl') == 'user')) {
            
            $task = $input->get('task', '');
            if (in_array($task, ['register', 'login', 'form'])) {
                $this->redirectToSmsLogin();
            }
        }
        
        // بررسی سایر کامپوننت‌ها
        $this->checkOtherComponents();
    }
    
    private function checkOtherComponents()
    {
        $app = Factory::getApplication();
        $input = $app->input;
        
        // لیست کامپوننت‌هایی که باید به SMS Login هدایت شوند
        $components = [
            'com_community',
            'com_kunena',
            'com_virtuemart',
            'com_jshopping',
            'com_acymailing'
        ];
        
        $option = $input->get('option', '');
        
        if (in_array($option, $components)) {
            $view = $input->get('view', '');
            $task = $input->get('task', '');
            
            // بررسی view ها و task های مربوط به ورود و ثبت نام
            $loginViews = ['login', 'register', 'registration', 'user'];
            $loginTasks = ['login', 'register', 'registration', 'user.login', 'user.register'];
            
            if (in_array($view, $loginViews) || in_array($task, $loginTasks)) {
                $this->redirectToSmsLogin();
            }
        }
    }
    
    private function redirectToSmsLogin()
    {
        $app = Factory::getApplication();
        $currentUri = Uri::getInstance();
        
        // ساخت URL برگشت
        $returnUrl = base64_encode($currentUri->toString());
        
        // URL کامپوننت SMS Login
        $smsLoginUrl = Route::_('index.php?option=com_smslogin&return=' . $returnUrl);
        
        // هدایت به صفحه SMS Login
        $app->redirect($smsLoginUrl);
    }
    
    public function onUserAfterLogin($options)
    {
        // اگر کاربر از طریق SMS Login وارد شده باشد

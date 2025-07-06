<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\User;
use Joomla\CMS\Application\CMSApplication;

class PlgSystemSmslogin extends CMSPlugin
{
    protected $autoloadLanguage = true;
    
    /**
     * Application object
     *
     * @var CMSApplication
     */
    protected $app;
    
    /**
     * Database object
     *
     * @var \Joomla\Database\DatabaseInterface
     */
    protected $db;
    
    public function onAfterInitialise()
    {
        // فقط در سایت اصلی اجرا شود
        if ($this->app->isClient('administrator')) {
            return;
        }
        
        // بررسی فعال بودن پلاگین
        if (!$this->params->get('enabled', 1)) {
            return;
        }
        
        // بررسی درخواست‌های ورود
        $this->checkLoginRequests();
    }
    
    private function checkLoginRequests()
    {
        $input = $this->app->input;
        
        // بررسی فرم‌های ورود جوملا
        if ($input->get('task') == 'user.login' || 
            ($input->get('option') == 'com_users' && $input->get('view') == 'login') ||
            ($input->get('option') == 'com_users' && $input->get('task') == 'user.login')) {
            
            $this->redirectToSmsLogin();
        }
        
        // بررسی فرم‌های ثبت نام جوملا
        if ($input->get('task') == 'user.register' || 
            ($input->get('option') == 'com_users' && $input->get('view') == 'registration') ||
            ($input->get('option') == 'com_users' && $input->get('task') == 'user.register')) {
            
            $this->redirectToSmsLogin();
        }
        
        // بررسی سایر کامپوننت‌ها
        $this->checkOtherComponents();
    }
    
    private function checkOtherComponents()
    {
        $input = $this->app->input;
        
        // دریافت لیست کامپوننت‌ها از تنظیمات
        $redirectComponents = $this->params->get('redirect_components', 'com_users,com_hikashop,com_community,com_kunena,com_virtuemart,com_jshopping');
        $components = array_map('trim', explode(',', $redirectComponents));
        
        $option = $input->get('option', '');
        
        if (in_array($option, $components)) {
            $view = $input->get('view', '');
            $task = $input->get('task', '');
            $ctrl = $input->get('ctrl', '');
            
            // بررسی view ها و task های مربوط به ورود و ثبت نام
            $loginViews = ['login', 'register', 'registration', 'user', 'form'];
            $loginTasks = ['login', 'register', 'registration', 'user.login', 'user.register', 'form'];
            
            if (in_array($view, $loginViews) || 
                in_array($task, $loginTasks) || 
                in_array($ctrl, ['user'])) {
                
                $this->redirectToSmsLogin();
            }
            
            // بررسی خاص هیکاشاپ
            if ($option == 'com_hikashop') {
                $layout = $input->get('layout', '');
                if (in_array($layout, ['login', 'register', 'registration'])) {
                    $this->redirectToSmsLogin();
                }
            }
        }
    }
    
    private function redirectToSmsLogin()
    {
        $currentUri = Uri::getInstance();
        $currentUrl = $currentUri->toString();
        
        // جلوگیری از redirect loop
        if (strpos($currentUrl, 'com_smslogin') !== false) {
            return;
        }
        
        // ساخت URL برگشت
        $returnUrl = base64_encode($currentUrl);
        
        // URL کامپوننت SMS Login
        $smsLoginUrl = Route::_('index.php?option=com_smslogin&return=' . $returnUrl);
        
        // هدایت به صفحه SMS Login
        $this->app->redirect($smsLoginUrl);
    }
    
    public function onUserAfterLogin($options)
    {
        // اگر کاربر از طریق SMS Login وارد شده باشد
        $user = $options['user'];
        
        // بررسی اینکه آیا کاربر با موبایل وارد شده
        if (preg_match('/^09\d{9}$/', $user->username)) {
            // ثبت لاگ یا عملیات خاص
            $this->logSmsLogin($user);
        }
        
        return true;
    }
    
    public function onUserAfterSave($user, $isNew, $success, $msg)
    {
        if (!$success) {
            return;
        }
        
        // اگر کاربر جدید با موبایل ثبت شده
        if ($isNew && preg_match('/^09\d{9}$/', $user['username'])) {
            $this->updateSmsUserRecord($user['username'], $user['id']);
        }
    }
    
    private function logSmsLogin($user)
    {
        try {
            $log = [
                'user_id' => $user->id,
                'username' => $user->username,
                'login_time' => Factory::getDate()->toSql(),
                'ip_address' => $this->app->input->server->get('REMOTE_ADDR', '', 'string')
            ];
            
            // می‌توانید لاگ را در جدول مخصوص ذخیره کنید
            // یا در فایل لاگ
            
        } catch (Exception $e) {
            // خطا را نادیده بگیرید تا فرآیند ورود مختل نشود
        }
    }
    
    private function updateSmsUserRecord($mobile, $userId)
    {
        try {
            $query = $this->db->getQuery(true)
                ->update('#__smslogin_users')
                ->set('user_id = ' . (int)$userId)
                ->where('mobile = ' . $this->db->quote($mobile));
            
            $this->db->setQuery($query);
            $this->db->execute();
            
        } catch (Exception $e) {
            // خطا را نادیده بگیرید
        }
    }
}

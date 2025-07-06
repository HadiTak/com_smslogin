<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\User\UserHelper;
use Joomla\CMS\Language\Text;

class SmsloginController extends BaseController
{
    protected $default_view = 'login';
    
    public function display($cachable = false, $urlparams = array())
    {
        parent::display($cachable, $urlparams);
    }
    
    public function ajax()
    {
        $app = Factory::getApplication();
        $input = $app->input;
        $action = $input->get('action', '', 'string');
        $response = ['success' => false, 'message' => ''];
        
        try {
            switch ($action) {
                case 'send_code':
                    $response = $this->sendVerificationCode();
                    break;
                case 'verify_code':
                    $response = $this->verifyCode();
                    break;
                case 'login_password':
                    $response = $this->loginWithPassword();
                    break;
                case 'forgot_password':
                    $response = $this->forgotPassword();
                    break;
                case 'reset_password':
                    $response = $this->resetPassword();
                    break;
                case 'set_password':
                    $response = $this->setPassword();
                    break;
                default:
                    $response['message'] = 'عملیات نامعتبر';
            }
        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
        }
        
        echo json_encode($response);
        $app->close();
    }
    
    private function sendVerificationCode()
    {
        $app = Factory::getApplication();
        $mobile = $app->input->get('mobile', '', 'string');
        
        // اعتبارسنجی موبایل
        if (!preg_match('/^09\d{9}$/', $mobile)) {
            return ['success' => false, 'message' => 'شماره موبایل باید 11 رقم و با 09 شروع شود'];
        }
        
        $db = Factory::getDbo();
        $config = include JPATH_COMPONENT_ADMINISTRATOR . '/components/com_smslogin/config/config.php';
        
        // بررسی محدودیت‌ها
        $query = $db->getQuery(true)
            ->select('*')
            ->from('#__smslogin_users')
            ->where('mobile = ' . $db->quote($mobile));
        $db->setQuery($query);
        $smsUser = $db->loadObject();
        
        if ($smsUser) {
            // بررسی قفل
            if ($smsUser->locked_until && strtotime($smsUser->locked_until) > time()) {
                return ['success' => false, 'message' => 'حساب شما قفل شده است. با مدیریت تماس بگیرید'];
            }
            
            // بررسی فاصله زمانی
            if ($smsUser->last_sms && (time() - strtotime($smsUser->last_sms)) < $config['sms_interval']) {
                return ['success' => false, 'message' => 'لطفاً ' . $config['sms_interval'] . ' ثانیه صبر کنید'];
            }
            
            // بررسی تعداد ارسال
            if ($smsUser->sms_count >= $config['max_attempts'] && 
                $smsUser->last_sms && (time() - strtotime($smsUser->last_sms)) < 300) {
                return ['success' => false, 'message' => 'حد ارسال پیامک به پایان رسیده است'];
            }
        }
        
        // تولید کد تصادفی
        $verificationCode = str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
        
        // ارسال پیامک
        $smsResult = $this->sendSMS($mobile, $verificationCode);
        
        if (!$smsResult) {
            return ['success' => false, 'message' => 'خطا در ارسال پیامک'];
        }
        
        // ذخیره در دیتابیس
        if ($smsUser) {
            $query = $db->getQuery(true)
                ->update('#__smslogin_users')
                ->set('verification_code = ' . $db->quote($verificationCode))
                ->set('code_expire = ' . $db->quote(date('Y-m-d H:i:s', time() + 300)))
                ->set('sms_count = sms_count + 1')
                ->set('last_sms = NOW()')
                ->where('mobile = ' . $db->quote($mobile));
        } else {
            $query = $db->getQuery(true)
                ->insert('#__smslogin_users')
                ->columns('mobile, verification_code, code_expire, sms_count, last_sms')
                ->values($db->quote($mobile) . ', ' . $db->quote($verificationCode) . ', ' . 
                        $db->quote(date('Y-m-d H:i:s', time() + 300)) . ', 1, NOW()');
        }
        
        $db->setQuery($query);
        $db->execute();
        
        // بررسی وجود کاربر
        $query = $db->getQuery(true)
            ->select('id')
            ->from('#__users')
            ->where('username = ' . $db->quote($mobile));
        $db->setQuery($query);
        $userId = $db->loadResult();
        
        return [
            'success' => true, 
            'message' => 'کد تایید ارسال شد',
            'user_exists' => (bool)$userId
        ];
    }
    
    private function verifyCode()
    {
        $app = Factory::getApplication();
        $mobile = $app->input->get('mobile', '', 'string');
        $code = $app->input->get('code', '', 'string');
        
        $db = Factory::getDbo();
        $query = $db->getQuery(true)
            ->select('*')
            ->from('#__smslogin_users')
            ->where('mobile = ' . $db->quote($mobile));
        $db->setQuery($query);
        $smsUser = $db->loadObject();
        
        if (!$smsUser || $smsUser->verification_code !== $code) {
            // افزایش تعداد تلاش
            if ($smsUser) {
                $attempts = $smsUser->attempts + 1;
                $updateQuery = $db->getQuery(true)
                    ->update('#__smslogin_users')
                    ->set('attempts = ' . $attempts)
                    ->where('mobile = ' . $db->quote($mobile));
                
                $config = include JPATH_COMPONENT_ADMINISTRATOR . '/components/com_smslogin/config/config.php';
                if ($attempts >= $config['max_attempts']) {
                    $updateQuery->set('locked_until = ' . $db->quote(date('Y-m-d H:i:s', time() + $config['lock_duration'])));
                }
                
                $db->setQuery($updateQuery);
                $db->execute();
            }
            
            return ['success' => false, 'message' => 'کد تایید اشتباه است'];
        }
        
        // بررسی انقضای کد
        if (strtotime($smsUser->code_expire) < time()) {
            return ['success' => false, 'message' => 'کد تایید منقضی شده است'];
        }
        
        // پاک کردن کد و ریست کردن تلاش‌ها
        $query = $db->getQuery(true)
            ->update('#__smslogin_users')
            ->set('verification_code = NULL')
            ->set('code_expire = NULL')
            ->set('attempts = 0')
            ->where('mobile = ' . $db->quote($mobile));
        $db->setQuery($query);
        $db->execute();
        
        return ['success' => true, 'message' => 'کد تایید صحیح است'];
    }
    
    private function loginWithPassword()
    {
        $app = Factory::getApplication();
        $mobile = $app->input->get('mobile', '', 'string');
        $password = $app->input->get('password', '', 'string');
        
        $db = Factory::getDbo();
        $query = $db->getQuery(true)
            ->select('id, password')
            ->from('#__users')
            ->where('username = ' . $db->quote($mobile));
        $db->setQuery($query);
        $user = $db->loadObject();
        
        if (!$user || !UserHelper::verifyPassword($password, $user->password)) {
            return ['success' => false, 'message' => 'رمز عبور اشتباه است'];
        }
        
        // ورود کاربر
        $this->loginUser($user->id);
        
        return ['success' => true, 'message' => 'ورود موفق'];
    }
    
    private function sendSMS($mobile, $code)
    {
        $config = include JPATH_COMPONENT_ADMINISTRATOR . '/components/com_smslogin/config/config.php';
        
        try {
            $post_data = json_encode([
                'pattern_code' => $config['sms_pattern_code'],
                'originator' => $config['sms_originator'],
                'recipient' => $mobile,
                'values' => (object)[$config['sms_variable'] => $code]
            ]);
            
            $url = 'https://rest.ippanel.com/v1/messages/patterns/send';
            
            $handler = curl_init($url);
            curl_setopt($handler, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($handler, CURLOPT_POSTFIELDS, $post_data);
            curl_setopt($handler, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($handler, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: AccessKey ' . $config['sms_api_key']
            ]);
            
            $response = curl_exec($handler);
            $http_code = curl_getinfo($handler, CURLINFO_HTTP_CODE);
            
            // ثبت لاگ
            $log_file = JPATH_ROOT . $config['log_path'];
            $log_message = date('Y-m-d H:i:s') . ' - SMS to: ' . $mobile . ' - Code: ' . $code . ' - HTTP: ' . $http_code;
            
            if ($response !== false) {
                $response_data = json_decode($response, true);
                if ($http_code == 200 && isset($response_data['data']['bulk_id'])) {
                    $log_message .= ' - SUCCESS - bulk_id: ' . $response_data['data']['bulk_id'];
                    file_put_contents($log_file, $log_message . PHP_EOL, FILE_APPEND);
                    curl_close($handler);
                    return true;
                } else {
                    $log_message .= ' - FAILED - Response: ' . $response;
                }
            } else {
                $log_message .= ' - cURL Error: ' . curl_error($handler);
            }
            
            file_put_contents($log_file, $log_message . PHP_EOL, FILE_APPEND);
            curl_close($handler);
            
        } catch (Exception $e) {
            $log_file = JPATH_ROOT . $config['log_path'];
            file_put_contents($log_file, date('Y-m-d H:i:s') . ' - Exception: ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
        }
        
        return false;
    }
    
    private function loginUser($userId)
    {
        $app = Factory::getApplication();
        $user = Factory::getUser($userId);
        
        $app->login(['username' => $user->username, 'password' => ''], ['remember' => false]);
    }
    
    private function createUser($mobile, $password = null)
    {
        $config = include JPATH_COMPONENT_ADMINISTRATOR . '/components/com_smslogin/config/config.php';
        
        $userData = [
            'username' => $mobile,
            'name' => $mobile,
            'email' => $mobile . '@' . $config['site_domain'],
            'password' => $password ?: UserHelper::genRandomPassword(),
            'groups' => [2] // Registered
        ];
        
        $user = new JUser();
        if ($user->bind($userData) && $user->save()) {
            return $user->id;
        }
        
        return false;
    }
}

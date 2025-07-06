<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Uri\Uri;

$app = Factory::getApplication();
$return = $app->input->get('return', '', 'base64');
$returnUrl = $return ? base64_decode($return) : Uri::root();

echo HTMLHelper::_('form.token');
?>

<div class="smslogin-container">
    <div class="smslogin-box">
        <div class="smslogin-header">
            <h2>ورود/ثبت نام</h2>
        </div>
        
        <div class="smslogin-content">
            <!-- مرحله 1: ورود شماره موبایل -->
            <div id="step-mobile" class="smslogin-step active">
                <div class="form-group">
                    <input type="text" id="mobile" placeholder="موبایل خود را وارد کنید" maxlength="11" class="form-control">
                </div>
                <button type="button" onclick="sendCode()" class="btn btn-primary btn-block">ادامه</button>
            </div>
            
            <!-- مرحله 2: انتخاب نوع ورود (کاربر موجود) -->
            <div id="step-login-type" class="smslogin-step">
                <div class="login-options">
                    <button type="button" onclick="showPasswordLogin()" class="btn btn-outline-primary btn-block">ورود با رمز</button>
                    <button type="button" onclick="showSmsLogin()" class="btn btn-outline-primary btn-block">ورود با کد پیامکی</button>
                </div>
            </div>
            
            <!-- مرحله 3: ورود با رمز -->
            <div id="step-password-login" class="smslogin-step">
                <div class="form-group">
                    <input type="password" id="password" placeholder="رمز عبور" class="form-control">
                </div>
                <div class="form-actions">
                    <button type="button" onclick="loginWithPassword()" class="btn btn-primary">ورود</button>
                    <button type="button" onclick="forgotPassword()" class="btn btn-link">فراموشی رمز</button>
                    <button type="button" onclick="goBack('step-login-type')" class="btn btn-secondary">بازگشت</button>
                </div>
            </div>
            
            <!-- مرحله 4: ورود کد تایید -->
            <div id="step-verification" class="smslogin-step">
                <div class="form-group">
                    <input type="text" id="verification-code" placeholder="کد 4 رقمی" maxlength="4" class="form-control">
                </div>
                <div class="form-actions">
                    <button type="button" onclick="verifyCode()" class="btn btn-primary">تایید</button>
                    <button type="button" onclick="resendCode()" class="btn btn-link" id="resend-btn">ارسال مجدد</button>
                    <button type="button" onclick="goBackToMobile()" class="btn btn-secondary">بازگشت</button>
                </div>
            </div>
            
            <!-- مرحله 5: انتخاب عملیات (کاربر جدید) -->
            <div id="step-new-user-options" class="smslogin-step">
                <div class="new-user-options">
                    <button type="button" onclick="viewSite()" class="btn btn-success btn-block">مشاهده سایت</button>
                    <button type="button" onclick="showSetPassword()" class="btn btn-primary btn-block">تعیین رمز</button>
                </div>
            </div>
            
            <!-- مرحله 6: تعیین رمز -->
            <div id="step-set-password" class="smslogin-step">
                <div class="form-group">
                    <input type="password" id="new-password" placeholder="رمز جدید" class="form-control">
                </div>
                <div class="form-group">
                    <input type="password" id="confirm-password" placeholder="تکرار رمز" class="form-control">
                </div>
                <div class="form-actions">
                    <button type="button" onclick="setPassword()" class="btn btn-primary">ذخیره رمز</button>
                    <button type="button" onclick="goBack('step-new-user-options')" class="btn btn-secondary">بازگشت</button>
                </div>
            </div>
            
            <!-- مرحله 7: بازیابی رمز -->
            <div id="step-forgot-password" class="smslogin-step">
                <div class="form-group">
                    <input type="text" id="forgot-code" placeholder="کد 4 رقمی" maxlength="4" class="form-control">
                </div>
                <div class="form-actions">
                    <button type="button" onclick="verifyForgotCode()" class="btn btn-primary">تایید</button>
                    <button type="button" onclick="resendForgotCode()" class="btn btn-link">ارسال مجدد</button>
                    <button type="button" onclick="goBack('step-password-login')" class="btn btn-secondary">بازگشت</button>
                </div>
            </div>
            
            <!-- مرحله 8: تغییر رمز -->
            <div id="step-reset-password" class="smslogin-step">
                <div class="form-group">
                    <input type="password" id="reset-password" placeholder="رمز جدید" class="form-control">
                </div>
                <div class="form-group">
                    <input type="password" id="confirm-reset-password" placeholder="تکرار رمز" class="form-control">
                </div>
                <div class="form-actions">
                    <button type="button" onclick="resetPassword()" class="btn btn-primary">تغییر رمز</button>
                </div>
            </div>
        </div>
        
        <div class="smslogin-messages">
            <div id="message-container"></div>
        </div>
    </div>
</div>

<script>
var returnUrl = '<?php echo $returnUrl; ?>';
var token = '<?php echo Factory::getSession()->getFormToken(); ?>';
var currentMobile = '';
var userExists = false;
var attempts = 0;
var maxAttempts = 3;
</script>

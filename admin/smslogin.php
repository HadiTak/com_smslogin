<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$app = Factory::getApplication();
$document = Factory::getDocument();
$document->addStyleSheet('components/com_smslogin/assets/css/style.css');

// بارگذاری تنظیمات
$config = include JPATH_COMPONENT . '/config/config.php';

// پردازش فرم
if ($app->input->getMethod() == 'POST') {
    $task = $app->input->get('task', '');
    
    if ($task == 'save_config') {
        $newConfig = [
            'sms_api_key' => $app->input->get('sms_api_key', '', 'string'),
            'sms_pattern_code' => $app->input->get('sms_pattern_code', '', 'string'),
            'sms_originator' => $app->input->get('sms_originator', '', 'string'),
            'sms_variable' => $app->input->get('sms_variable', '', 'string'),
            'sms_interval' => (int)$app->input->get('sms_interval', 10, 'int'),
            'max_attempts' => (int)$app->input->get('max_attempts', 3, 'int'),
            'lock_duration' => (int)$app->input->get('lock_duration', 600, 'int'),
            'log_path' => $app->input->get('log_path', '', 'string'),
            'site_domain' => $app->input->get('site_domain', '', 'string')
        ];
        
        $configContent = "<?php\ndefined('_JEXEC') or die;\n\nreturn " . var_export($newConfig, true) . ";";
        file_put_contents(JPATH_COMPONENT . '/config/config.php', $configContent);
        
        $app->enqueueMessage('تنظیمات ذخیره شد.', 'success');
        $config = $newConfig;
    }
}

// نمایش وضعیت کاربران
$db = Factory::getDbo();
$query = $db->getQuery(true)
    ->select('s.*, u.username, u.email')
    ->from('#__smslogin_users s')
    ->leftJoin('#__users u ON u.id = s.user_id')
    ->order('s.modified DESC');
$db->setQuery($query);
$users = $db->loadObjectList();

?>
<div class="smslogin-admin">
    <h1>مدیریت ثبت نام و ورود پیامکی</h1>
    
    <!-- تنظیمات -->
    <div class="card">
        <h2>تنظیمات</h2>
        <form method="post" action="">
            <input type="hidden" name="task" value="save_config">
            <?php echo HTMLHelper::_('form.token'); ?>
            
            <div class="form-group">
                <label>کلید API:</label>
                <input type="text" name="sms_api_key" value="<?php echo htmlspecialchars($config['sms_api_key']); ?>" class="form-control">
            </div>
            
            <div class="form-group">
                <label>کد پترن:</label>
                <input type="text" name="sms_pattern_code" value="<?php echo htmlspecialchars($config['sms_pattern_code']); ?>" class="form-control">
            </div>
            
            <div class="form-group">
                <label>شماره ارسال کننده:</label>
                <input type="text" name="sms_originator" value="<?php echo htmlspecialchars($config['sms_originator']); ?>" class="form-control">
            </div>
            
            <div class="form-group">
                <label>متغیر کد:</label>
                <input type="text" name="sms_variable" value="<?php echo htmlspecialchars($config['sms_variable']); ?>" class="form-control">
            </div>
            
            <div class="form-group">
                <label>فاصله ارسال پیامک (ثانیه):</label>
                <input type="number" name="sms_interval" value="<?php echo $config['sms_interval']; ?>" class="form-control">
            </div>
            
            <div class="form-group">
                <label>تعداد دفعات ارسال:</label>
                <input type="number" name="max_attempts" value="<?php echo $config['max_attempts']; ?>" class="form-control">
            </div>
            
            <div class="form-group">
                <label>مدت زمان قفل (ثانیه):</label>
                <input type="number" name="lock_duration" value="<?php echo $config['lock_duration']; ?>" class="form-control">
            </div>
            
            <div class="form-group">
                <label>مسیر لاگ:</label>
                <input type="text" name="log_path" value="<?php echo htmlspecialchars($config['log_path']); ?>" class="form-control">
            </div>
            
            <div class="form-group">
                <label>دامنه سایت:</label>
                <input type="text" name="site_domain" value="<?php echo htmlspecialchars($config['site_domain']); ?>" class="form-control">
            </div>
            
            <button type="submit" class="btn btn-primary">ذخیره تنظیمات</button>
        </form>
    </div>
    
    <!-- وضعیت کاربران -->
    <div class="card">
        <h2>وضعیت کاربران</h2>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>شماره موبایل</th>
                        <th>نام کاربری</th>
                        <th>تعداد تلاش</th>
                        <th>قفل تا</th>
                        <th>آخرین پیامک</th>
                        <th>وضعیت</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo $user->mobile; ?></td>
                        <td><?php echo $user->username ?: 'ثبت نام نشده'; ?></td>
                        <td><?php echo $user->attempts; ?></td>
                        <td><?php echo $user->locked_until ? date('Y-m-d H:i:s', strtotime($user->locked_until)) : '-'; ?></td>
                        <td><?php echo $user->last_sms ? date('Y-m-d H:i:s', strtotime($user->last_sms)) : '-'; ?></td>
                        <td>
                            <?php if ($user->locked_until && strtotime($user->locked_until) > time()): ?>
                                <span class="badge badge-danger">قفل شده</span>
                            <?php elseif ($user->attempts >= $config['max_attempts']): ?>
                                <span class="badge badge-warning">حد تلاش</span>
                            <?php else: ?>
                                <span class="badge badge-success">عادی</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
